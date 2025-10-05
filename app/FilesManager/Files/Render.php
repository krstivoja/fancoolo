<?php

namespace FanCoolo\FilesManager\Files;

use RuntimeException;
use WP_Post;

use FanCoolo\Admin\Api\Services\MetaKeysConstants;
use FanCoolo\Content\FunculoTypeTaxonomy;
use FanCoolo\FilesManager\Interfaces\FileGeneratorInterface;

use function array_map;
use function copy;
use function escapeshellarg;
use function explode;
use function file_exists;
use function file_put_contents;
use function fclose;
use function ini_get;
use function in_array;
use function is_resource;
use function ob_end_clean;
use function ob_start;
use function preg_match;
use function preg_replace;
use function preg_split;
use function proc_close;
use function proc_open;
use function rename;
use function sprintf;
use function stream_get_contents;
use function stripos;
use function tempnam;
use function trim;
use function unlink;

use const PHP_BINARY;

class Render implements FileGeneratorInterface
{
    public function canGenerate(string $contentType): bool
    {
        return $contentType === FunculoTypeTaxonomy::getTermBlocks();
    }

    public function generate(int $postId, WP_Post $post, string $outputPath): bool
    {
        $phpContent = get_post_meta($postId, MetaKeysConstants::BLOCK_PHP, true);

        if (empty($phpContent)) {
            return false;
        }

        $processedContent = $this->processBlockPropsPlaceholder($phpContent);

        $targetPath = $outputPath . '/' . $this->getGeneratedFileName($post);
        $blockLabel = $post->post_title ?: $post->post_name;

        $temporaryFile = tempnam($outputPath, 'render_');

        if ($temporaryFile === false) {
            throw new RuntimeException(
                sprintf('Unable to create a temporary render file for block "%s".', $blockLabel)
            );
        }

        if (file_put_contents($temporaryFile, $processedContent) === false) {
            @unlink($temporaryFile);

            throw new RuntimeException(
                sprintf('Unable to write render template for block "%s".', $blockLabel)
            );
        }

        try {
            $this->assertValidPhpSyntax($temporaryFile, $processedContent, $blockLabel);
            $this->persistGeneratedFile($temporaryFile, $targetPath, $blockLabel);
        } catch (RuntimeException $exception) {
            @unlink($temporaryFile);

            throw $exception;
        }

        return true;
    }

    public function getRequiredMetaKeys(): array
    {
        return [MetaKeysConstants::BLOCK_PHP];
    }

    public function getGeneratedFileName(WP_Post $post): string
    {
        return 'render.php';
    }

    public function getFileExtension(): string
    {
        return 'php';
    }

    public function validate(int $postId): bool
    {
        $phpContent = get_post_meta($postId, MetaKeysConstants::BLOCK_PHP, true);
        return !empty($phpContent);
    }

    private function processBlockPropsPlaceholder(string $phpContent): string
    {
        $processed = preg_replace_callback(
            '/(<[^>]+?)(\s+)blockProps(\s*[^>]*?>)/i',
            function($matches) {
                $beforeBlockProps = $matches[1];
                $afterBlockProps = $matches[3];

                $trimmedAfter = trim($afterBlockProps);
                if ($trimmedAfter === '>') {
                    return $beforeBlockProps . ' <?php echo get_block_wrapper_attributes(); ?>' . $afterBlockProps;
                }

                $existingAttrs = trim(substr($afterBlockProps, 0, -1));

                if (!empty($existingAttrs)) {
                    return $beforeBlockProps . ' <?php echo get_block_wrapper_attributes(array( \'class\' => \'' .
                        $this->extractClassFromAttributes($existingAttrs) . '\' )); ?>' .
                        $this->extractNonClassAttributes($existingAttrs) . '>';
                }

                return $beforeBlockProps . ' <?php echo get_block_wrapper_attributes(); ?>' . $afterBlockProps;
            },
            $phpContent
        );

        return $processed;
    }

    private function assertValidPhpSyntax(string $filePath, string $phpContent, string $blockLabel): void
    {
        $lintResult = $this->lintWithPhpBinary($filePath);

        if ($lintResult['available']) {
            if ($lintResult['error'] !== null) {
                throw new RuntimeException(
                    sprintf('PHP syntax error in block "%s": %s', $blockLabel, $lintResult['error'])
                );
            }

            return;
        }

        $fallbackError = $this->lintWithEval($phpContent);

        if ($fallbackError !== null) {
            throw new RuntimeException(
                sprintf('PHP syntax error in block "%s": %s', $blockLabel, $fallbackError)
            );
        }
    }

    private function persistGeneratedFile(string $source, string $destination, string $blockLabel): void
    {
        if (file_exists($destination) && !@unlink($destination)) {
            throw new RuntimeException(
                sprintf('Unable to overwrite render template for block "%s".', $blockLabel)
            );
        }

        if (@rename($source, $destination)) {
            return;
        }

        if (@copy($source, $destination)) {
            @unlink($source);
            return;
        }

        throw new RuntimeException(
            sprintf('Unable to persist render template for block "%s".', $blockLabel)
        );
    }

    private function lintWithPhpBinary(string $filePath): array
    {
        if (!defined('PHP_BINARY') || PHP_BINARY === '' || $this->isFunctionDisabled('proc_open')) {
            return ['available' => false, 'error' => null];
        }

        $binary = PHP_BINARY;

        if (stripos($binary, 'php-fpm') !== false || stripos($binary, 'php-cgi') !== false) {
            return ['available' => false, 'error' => null];
        }

        $command = escapeshellarg($binary) . ' -l ' . escapeshellarg($filePath);

        $descriptors = [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = @proc_open($command, $descriptors, $pipes, null, null);

        if (!is_resource($process)) {
            return ['available' => false, 'error' => null];
        }

        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);

        foreach ($pipes as $pipe) {
            fclose($pipe);
        }

        $exitCode = proc_close($process);

        if ($exitCode === 0) {
            return ['available' => true, 'error' => null];
        }

        $message = trim($stderr !== '' ? $stderr : $stdout);

        if ($this->isNonCliUsageMessage($message)) {
            return ['available' => false, 'error' => null];
        }

        return [
            'available' => true,
            'error' => $this->normalizeLintMessage($message),
        ];
    }

    private function lintWithEval(string $phpContent): ?string
    {
        try {
            ob_start();
            eval('?>' . $phpContent);
            ob_end_clean();
        } catch (\ParseError $exception) {
            ob_end_clean();

            $message = $this->normalizeLintMessage($exception->getMessage());
            $line = $exception->getLine();

            if (is_int($line) && $line > 0 && stripos($message, ' on line ') === false) {
                return sprintf('%s on line %d.', rtrim($message, '.'), $line);
            }

            return $message;
        } catch (\Throwable $throwable) {
            ob_end_clean();
        }

        return null;
    }

    private function normalizeLintMessage(string $message): string
    {
        $message = trim($message);

        if ($message === '') {
            return 'Unknown syntax error.';
        }

        $lines = preg_split('/\r?\n/', $message);
        $firstLine = $lines[0] ?? $message;

        $firstLine = preg_replace('/^PHP Parse error:\s*/i', '', $firstLine);

        if (preg_match('/ in .* on line (\d+)/i', $firstLine, $matches)) {
            $line = (int) $matches[1];
            $firstLine = preg_replace('/ in .* on line (\d+)/i', ' on line ' . $line, $firstLine);
        }

        if (preg_match('/ in .*:(\d+)/', $firstLine, $matches)) {
            $line = (int) $matches[1];
            $firstLine = preg_replace('/ in .*:(\d+)/', ' on line ' . $line, $firstLine);
        }

        return rtrim($firstLine, '.') . '.';
    }

    private function isFunctionDisabled(string $functionName): bool
    {
        if (!function_exists($functionName)) {
            return true;
        }

        $disabled = ini_get('disable_functions');

        if (empty($disabled)) {
            return false;
        }

        $disabledFunctions = array_map('trim', explode(',', $disabled));

        return in_array($functionName, $disabledFunctions, true);
    }

    private function isNonCliUsageMessage(string $message): bool
    {
        $lower = strtolower($message);

        if ($lower === '') {
            return false;
        }

        if (strpos($lower, 'usage: php-fpm') !== false) {
            return true;
        }

        if (strpos($lower, 'command not found') !== false) {
            return true;
        }

        if (strpos($lower, 'no such file or directory') !== false) {
            return true;
        }

        return false;
    }

    private function extractClassFromAttributes(string $attributes): string
    {
        if (preg_match('/class=["\']([^"\']*)["\']/', $attributes, $matches)) {
            return $matches[1];
        }
        return '';
    }

    private function extractNonClassAttributes(string $attributes): string
    {
        $withoutClass = preg_replace('/\s*class=["\'][^"\']*["\']/', '', $attributes);
        return trim($withoutClass) ? ' ' . trim($withoutClass) : '';
    }
}
