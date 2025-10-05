<?php

namespace FanCoolo\Services;

use RuntimeException;

use function array_map;
use function escapeshellarg;
use function explode;
use function fclose;
use function ini_get;
use function in_array;
use function is_int;
use function is_resource;
use function ob_end_clean;
use function ob_start;
use function preg_match;
use function preg_replace;
use function preg_split;
use function proc_close;
use function proc_open;
use function rtrim;
use function sprintf;
use function stream_get_contents;
use function stripos;
use function strpos;
use function strtolower;
use function trim;

use const PHP_BINARY;

/**
 * Validates PHP syntax using php -l (lint) or eval fallback
 *
 * Provides two validation methods:
 * 1. Primary: Uses PHP binary's lint command (php -l)
 * 2. Fallback: Uses eval() if proc_open is disabled
 */
class PhpSyntaxValidator
{
    /**
     * Validate PHP syntax of a file
     *
     * @param string $filePath Path to the PHP file to validate
     * @param string $phpContent Original PHP content (used for fallback validation)
     * @param string $label Human-readable label for error messages
     * @throws RuntimeException If syntax errors are found
     * @return void
     */
    public function validate(string $filePath, string $phpContent, string $label): void
    {
        $lintResult = $this->lintWithPhpBinary($filePath);

        if ($lintResult['available']) {
            if ($lintResult['error'] !== null) {
                throw new RuntimeException(
                    sprintf('PHP syntax error in %s: %s', $label, $lintResult['error'])
                );
            }

            return;
        }

        $fallbackError = $this->lintWithEval($phpContent);

        if ($fallbackError !== null) {
            throw new RuntimeException(
                sprintf('PHP syntax error in %s: %s', $label, $fallbackError)
            );
        }
    }

    /**
     * Lint PHP file using PHP binary (php -l)
     *
     * @param string $filePath Path to the PHP file
     * @return array{available: bool, error: string|null}
     */
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

    /**
     * Lint PHP content using eval() as fallback
     *
     * @param string $phpContent PHP content to validate
     * @return string|null Error message if syntax error found, null if valid
     */
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

    /**
     * Normalize lint error messages for consistency
     *
     * @param string $message Raw error message from lint or eval
     * @return string Normalized error message
     */
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

    /**
     * Check if a PHP function is disabled in php.ini
     *
     * @param string $functionName Function name to check
     * @return bool True if function is disabled or doesn't exist
     */
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

    /**
     * Check if error message indicates non-CLI PHP binary usage
     *
     * @param string $message Error message to check
     * @return bool True if message indicates non-CLI usage
     */
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
}
