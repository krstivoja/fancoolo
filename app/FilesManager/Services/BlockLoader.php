<?php

namespace FanCoolo\FilesManager\Services;

use ErrorException;
use Throwable;

use function basename;
use function current_user_can;
use function error_log;
use function error_reporting;
use function esc_html;
use function file_exists;
use function file_get_contents;
use function function_exists;
use function glob;
use function htmlspecialchars;
use function json_decode;
use function json_last_error;
use function register_block_type;
use function restore_error_handler;
use function set_error_handler;

use FanCoolo\FilesManager\Services\SymbolProcessor;

class BlockLoader
{
    public function loadBlocks(): void
    {
        if (!defined('FANCOOLO_BLOCKS_DIR')) {
            return;
        }

        $folders = glob(FANCOOLO_BLOCKS_DIR . '/*', GLOB_ONLYDIR);

        if (empty($folders)) {
            return;
        }

        foreach ($folders as $folder) {
            $this->loadSingleBlock($folder);
        }
    }

    private function loadSingleBlock(string $folder): void
    {
        // Get the block.json file
        $block_json = $folder . '/block.json';
        if (!file_exists($block_json)) {
            return;
        }

        // Read block.json content
        $block_json_content = file_get_contents($block_json);
        if ($block_json_content === false) {
            return;
        }

        $block_data = json_decode($block_json_content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return;
        }

        // Check if this is a dynamic block
        $is_dynamic = isset($block_data['render']) && strpos($block_data['render'], '.php') !== false;

        // Register the block with its assets
        $args = [];

        if ($is_dynamic) {
            $args['render_callback'] = function($attributes, $content, $block) use ($folder) {
                return $this->renderDynamicBlock($folder, $attributes, $content, $block);
            };
        }

        $result = register_block_type($block_json, $args);

        // Block registration complete
    }

    private function renderDynamicBlock(string $folder, $attributes, $content, $block): string
    {
        $render_file = $folder . '/render.php';

        if (!file_exists($render_file)) {
            return '<!-- Render file not found -->';
        }

        // Make sure we have access to WordPress functions
        if (!function_exists('get_the_ID')) {
            return 'WordPress functions not available';
        }

        // Make essential variables available to the render file
        $GLOBALS['attributes'] = $attributes;
        $GLOBALS['content'] = $content;
        $GLOBALS['block'] = $block;

        // Use the Symbol processor service which handles both InnerBlocks and Symbols
        $renderCallback = SymbolProcessor::createRenderCallback($render_file);

        $errorHandler = static function (int $severity, string $message, string $file, int $line): bool {
            if (!(error_reporting() & $severity)) {
                return false;
            }

            throw new ErrorException($message, 0, $severity, $file, $line);
        };

        set_error_handler($errorHandler);

        $errorMarkup = null;
        $rendered_output = '';

        try {
            $rendered_output = $renderCallback($attributes, $content, $block);
        } catch (Throwable $throwable) {
            $errorMarkup = $this->handleBlockRenderFailure($folder, $throwable);
        } finally {
            restore_error_handler();
        }

        if ($errorMarkup !== null) {
            return $errorMarkup;
        }

        // If output is empty, return a message
        if (empty($rendered_output)) {
            return '<!-- Block rendered as empty -->';
        }

        return $rendered_output;
    }

    private function handleBlockRenderFailure(string $folder, Throwable $throwable): string
    {
        $this->logBlockRenderError($folder, $throwable);

        $blockSlug = basename($folder);

        $message = $this->escapeHtml(
            sprintf('FanCoolo block "%s" failed to render. Please review the error logs.', $blockSlug)
        );

        if (function_exists('current_user_can') && current_user_can('manage_options')) {
            $message .= ' ' . $this->escapeHtml($throwable->getMessage());
        }

        return sprintf('<div class="fancoolo-block-error">%s</div>', $message);
    }

    private function logBlockRenderError(string $folder, Throwable $throwable): void
    {
        $blockSlug = basename($folder);

        $logMessage = sprintf(
            'FanCoolo block "%s" render error: %s in %s on line %d',
            $blockSlug,
            $throwable->getMessage(),
            $throwable->getFile(),
            $throwable->getLine()
        );

        error_log($logMessage);
    }

    private function escapeHtml(string $value): string
    {
        if (function_exists('esc_html')) {
            return esc_html($value);
        }

        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

}
