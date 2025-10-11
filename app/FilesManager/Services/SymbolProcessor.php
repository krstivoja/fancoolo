<?php

namespace FanCoolo\FilesManager\Services;

/**
 * Symbol processor service
 * Handles the replacement of React-like symbol tags in block templates
 * Similar to InnerBlocksProcessor but for custom symbols
 */
class SymbolProcessor
{
    /**
     * WordPress native components that should NOT be processed as symbols
     */
    private const WORDPRESS_COMPONENTS = [
        'InnerBlocks',
        'RichText',
        'MediaUpload',
        'BlockControls',
        'InspectorControls',
        'ColorPalette',
        'PlainText'
    ];

    /**
     * Regex pattern for detecting React-like symbol tags
     * Matches: <Button />, <Card attr="value" />, etc.
     */
    private const SYMBOL_PATTERN = '/<([A-Z][a-zA-Z0-9]*)\s*([^>]*?)\s*\/\s*>/';

    /**
     * HTML/SVG tags that are valid as self-closing void elements.
     */
    private const SELF_CLOSING_ALLOWED_TAGS = [
        'area',
        'base',
        'br',
        'col',
        'embed',
        'hr',
        'img',
        'input',
        'link',
        'meta',
        'param',
        'source',
        'track',
        'wbr',
        // Common SVG elements that are typically self-closing
        'path',
        'circle',
        'ellipse',
        'line',
        'polyline',
        'polygon',
        'rect',
        'stop',
        'use',
    ];

    /**
     * Check if content contains symbol tags
     *
     * @param string $content Content to check
     * @return bool True if symbols found
     */
    public static function hasSymbols(string $content): bool
    {
        return preg_match(self::SYMBOL_PATTERN, $content) === 1;
    }

    /**
     * Check if a specific file contains symbol tags
     *
     * @param string $filePath Path to the file to check
     * @return bool True if file exists and contains symbols
     */
    public static function fileHasSymbols(string $filePath): bool
    {
        if (!file_exists($filePath)) {
            return false;
        }

        $content = file_get_contents($filePath);
        return $content !== false && self::hasSymbols($content);
    }

    /**
     * Process a block template and replace symbol tags with symbol content
     *
     * @param string $templatePath Path to the clean template file
     * @param array $attributes Block attributes
     * @param string $content Inner blocks content
     * @param \WP_Block|null $block Block instance
     * @return string Processed HTML
     */
    public static function processTemplate(string $templatePath, array $attributes = [], string $content = '', $block = null): string
    {
        if (!file_exists($templatePath)) {
            return '';
        }

        // Make variables available to the template
        $GLOBALS['attributes'] = $attributes;
        $GLOBALS['content'] = $content;
        $GLOBALS['block'] = $block;

        // Start output buffering to capture the template output
        ob_start();

        // Make variables available to the render file
        $block_attributes = $attributes;
        $block_content = $content;
        $block_instance = $block;

        // Include the clean template
        include $templatePath;

        // Get the rendered content
        $renderedContent = ob_get_clean();

        // Process symbols in the rendered content
        return self::processSymbols($renderedContent, dirname($templatePath));
    }

    /**
     * Process symbol tags in content
     *
     * @param string $content Content to process
     * @param string $templateDir Directory containing the template (for symbol path resolution)
     * @return string Processed content
     */
    public static function processSymbols(string $content, string $templateDir): string
    {
        return preg_replace_callback(self::SYMBOL_PATTERN, function($matches) use ($templateDir) {
            $componentName = $matches[1];
            $attributes = trim($matches[2]);

            // Skip WordPress native components
            if (in_array($componentName, self::WORDPRESS_COMPONENTS)) {
                return $matches[0]; // Return original unchanged
            }

            // Convert PascalCase to kebab-case for file lookup
            $fileName = self::convertToKebabCase($componentName);
            $symbolFile = $templateDir . '/../symbols/' . $fileName . '.php';

            if (file_exists($symbolFile)) {
                // Parse attributes
                $symbol_attrs = [];
                if (!empty($attributes)) {
                    $symbol_attrs = self::parseAttributes($attributes);
                }

                // Capture symbol output
                ob_start();
                include $symbolFile;
                $output = ob_get_clean();
                return self::normalizeSelfClosingTags($output);
            } else {
                return '<!-- Symbol not found: ' . $fileName . '.php -->';
            }
        }, $content);
    }

    /**
     * Convert PascalCase to kebab-case
     * Button -> button, ProductCard -> product-card
     *
     * @param string $input PascalCase string
     * @return string kebab-case string
     */
    private static function convertToKebabCase(string $input): string
    {
        // Insert hyphens before capital letters (except the first one)
        $kebab = preg_replace('/(?<!^)[A-Z]/', '-$0', $input);

        // Convert to lowercase
        return strtolower($kebab);
    }

    /**
     * Parse HTML-like attributes into an associative array
     * Parses: type="primary" text="Click me" disabled="true"
     *
     * @param string $attributeString The attribute string
     * @return array Parsed attributes
     */
    private static function parseAttributes(string $attributeString): array
    {
        $attributes = [];

        // Pattern to match key="value" or key='value'
        $pattern = '/(\w+)\s*=\s*(["\'])(.*?)\2/';

        if (preg_match_all($pattern, $attributeString, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $key = $match[1];
                $value = $match[3]; // The content inside quotes

                $attributes[$key] = $value;
            }
        }

        return $attributes;
    }

    /**
     * Convert shorthand self-closing tags (e.g. <button />) into proper closing tags
     * for elements that are not valid void/self-closing HTML tags.
     */
    private static function normalizeSelfClosingTags(string $html): string
    {
        if ($html === '') {
            return $html;
        }

        return (string) preg_replace_callback(
            '/<([a-z][\w:-]*)([^>]*)\/>/i',
            static function (array $matches): string {
                $tagNameOriginal = $matches[1];
                $tagName = strtolower($tagNameOriginal);

                if (in_array($tagName, self::SELF_CLOSING_ALLOWED_TAGS, true)) {
                    return $matches[0];
                }

                $attributes = $matches[2] ?? '';
                $attributes = rtrim($attributes);

                if ($attributes === '') {
                    return sprintf('</%s>', $tagNameOriginal);
                }

                return sprintf(
                    '<%1$s%2$s></%1$s>',
                    $tagNameOriginal,
                    $attributes
                );
            },
            $html
        );
    }

    /**
     * Create a render callback that uses both InnerBlocks and Symbol processors
     *
     * @param string $templatePath Path to the clean template file
     * @return callable Render callback function
     */
    public static function createRenderCallback(string $templatePath): callable
    {
        return function ($attributes, $content, $block) use ($templatePath) {
            // First process with InnerBlocks processor if needed
            if (InnerBlocksProcessor::fileHasInnerBlocks($templatePath)) {
                $processedContent = InnerBlocksProcessor::processTemplate($templatePath, $attributes, $content, $block);

                // Then process symbols in the already processed content
                if (self::hasSymbols($processedContent)) {
                    return self::processSymbols($processedContent, dirname($templatePath));
                }

                return $processedContent;
            }

            // If no InnerBlocks, just process symbols
            return self::processTemplate($templatePath, $attributes, $content, $block);
        };
    }

    /**
     * Check if any block render templates contain symbol tags
     *
     * @return bool True if any templates contain symbols
     */
    public static function hasSymbolsInTemplates(): bool
    {
        if (!defined('FANCOOLO_BLOCKS_DIR')) {
            return false;
        }

        $renderFiles = glob(FANCOOLO_BLOCKS_DIR . '/*/render.php');

        foreach ($renderFiles as $renderFile) {
            if (self::fileHasSymbols($renderFile)) {
                return true;
            }
        }

        return false;
    }
}
