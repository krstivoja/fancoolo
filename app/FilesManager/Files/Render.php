<?php

namespace FanCoolo\FilesManager\Files;

use RuntimeException;
use WP_Post;

use FanCoolo\Admin\Api\Services\MetaKeysConstants;
use FanCoolo\Content\FunculoTypeTaxonomy;
use FanCoolo\FilesManager\Interfaces\FileGeneratorInterface;
use FanCoolo\Services\PhpSyntaxValidator;

use function copy;
use function file_exists;
use function file_put_contents;
use function preg_match;
use function preg_replace;
use function rename;
use function sprintf;
use function substr;
use function tempnam;
use function trim;
use function unlink;

class Render implements FileGeneratorInterface
{
    private PhpSyntaxValidator $validator;

    public function __construct()
    {
        $this->validator = new PhpSyntaxValidator();
    }
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
            $this->validator->validate($temporaryFile, $processedContent, sprintf('block "%s"', $blockLabel));
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
