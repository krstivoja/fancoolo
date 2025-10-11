<?php

namespace FanCoolo\FilesManager\Files;

use FanCoolo\FilesManager\Interfaces\FileGeneratorInterface;
use FanCoolo\Content\FunculoTypeTaxonomy;
use FanCoolo\Admin\Api\Services\MetaKeysConstants;
use FanCoolo\FilesManager\Services\AttributeMapper;
use FanCoolo\Database\BlockSettingsRepository;
use WP_Post;

class BlockJson implements FileGeneratorInterface
{
    public function canGenerate(string $contentType): bool
    {
        return $contentType === FunculoTypeTaxonomy::getTermBlocks();
    }

    public function generate(int $postId, WP_Post $post, string $outputPath): bool
    {
        $dbSettings = BlockSettingsRepository::get($postId) ?? [];

        // Verify and create output path if needed
        if (!is_dir($outputPath)) {
            if (!wp_mkdir_p($outputPath)) {
                error_log('BlockJson: Failed to create output directory: ' . $outputPath);
                return false;
            }
        }

        $attributes = get_post_meta($postId, MetaKeysConstants::BLOCK_ATTRIBUTES, true);

        $blockJson = $this->buildBlockJson($post, $attributes, $dbSettings, $outputPath);
        $filepath = $outputPath . '/' . $this->getGeneratedFileName($post);

        $result = file_put_contents($filepath, wp_json_encode($blockJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        if ($result === false) {
            error_log('BlockJson: Failed to write block.json file: ' . $filepath);
            return false;
        }

        return true;
    }

    public function getRequiredMetaKeys(): array
    {
        return [MetaKeysConstants::BLOCK_ATTRIBUTES];
    }

    public function getGeneratedFileName(WP_Post $post): string
    {
        return 'block.json';
    }

    public function getFileExtension(): string
    {
        return 'json';
    }

    public function validate(int $postId): bool
    {
        return true; // block.json is always generated for blocks
    }

    private function buildBlockJson(WP_Post $post, $attributes, array $dbSettings, string $outputPath): array
    {
        $category = !empty($dbSettings['category'])
            ? sanitize_text_field($dbSettings['category'])
            : 'theme';

        $icon = !empty($dbSettings['icon'])
            ? sanitize_text_field($dbSettings['icon'])
            : 'smiley';

        $description = !empty($dbSettings['description'])
            ? sanitize_text_field($dbSettings['description'])
            : '';

        $innerBlocksEnabled = !empty($dbSettings['supports_inner_blocks']);

        $renderContainsInnerBlocks = false;
        $renderFile = $outputPath . '/render.php';
        if (file_exists($renderFile)) {
            $renderContents = file_get_contents($renderFile);
            if ($renderContents !== false) {
                $renderContainsInnerBlocks = stripos($renderContents, '<innerblocks') !== false;
            }
        }

        $usesInnerBlocks = $innerBlocksEnabled || $renderContainsInnerBlocks;

        $blockJson = [
            '$schema' => 'https://schemas.wp.org/trunk/block.json',
            'apiVersion' => 3,
            'name' => 'fancoolo/' . $post->post_name,
            'version' => '1.0.0',
            'title' => $post->post_title,
            'category' => $category,
            'icon' => $icon,
            'description' => $description,
        ];

        $defaultSupports = [
            'html' => $usesInnerBlocks,
            'align' => ['left', 'center', 'right', 'wide', 'full'],
            'anchor' => true
        ];

        $jsContent = get_post_meta($post->ID, MetaKeysConstants::BLOCK_JS, true);
        if (!empty($jsContent)) {
            $defaultSupports['interactivity'] = true;
        }

        if (isset($dbSettings['supports']) && is_array($dbSettings['supports'])) {
            $blockJson['supports'] = wp_parse_args($dbSettings['supports'], $defaultSupports);
        } else {
            $blockJson['supports'] = $defaultSupports;
        }

        if ($usesInnerBlocks) {
            $blockJson['supports']['html'] = true;

            if (!isset($blockJson['supports']['innerBlocks'])) {
                $blockJson['supports']['innerBlocks'] = true;
            }
        }

        $blockJson['textdomain'] = 'fancoolo';

        // Conditionally add asset files only if they exist
        $this->addConditionalAssets($blockJson, $outputPath, $post->ID, $dbSettings);

        // Add attributes using AttributeMapper for consistent schema generation
        $attributeSchema = AttributeMapper::generateAttributeSchema($post->ID);
        if (!empty($attributeSchema)) {
            $blockJson['attributes'] = $attributeSchema;
        }

        return $blockJson;
    }

    /**
     * Add asset files to block.json only if they exist
     * @param array &$blockJson Block configuration array
     * @param string $outputPath Output directory path
     * @param int $postId Post ID for checking meta content
     * @param array $dbSettings Database settings for the block
     */
    private function addConditionalAssets(array &$blockJson, string $outputPath, int $postId, array $dbSettings): void
    {
        // Define potential asset files (excluding view.js which is handled separately)
        $assetFiles = [
            'editorScript' => 'index.js',
            'style' => 'style.css',
            'editorStyle' => 'editor.css',
            'render' => 'render.php',
        ];

        foreach ($assetFiles as $property => $filename) {
            $filePath = $outputPath . '/' . $filename;

            // Only add the asset if file exists or will be generated
            if (file_exists($filePath) || $this->willFileBeGenerated($filename, $postId)) {
                $blockJson[$property] = 'file:./' . $filename;
            }
        }

        // Handle view.js separately based on view_script_module setting
        $viewJsPath = $outputPath . '/view.js';
        if (file_exists($viewJsPath) || $this->willFileBeGenerated('view.js', $postId)) {
            // Check if module mode is enabled (default to false if not set)
            $useModule = $dbSettings['view_script_module'] ?? false;

            if ($useModule) {
                $blockJson['viewScriptModule'] = 'file:./view.js';
            } else {
                $blockJson['viewScript'] = 'file:./view.js';
            }
        }
    }

    /**
     * Check if a file will be generated by other generators
     * @param string $filename The filename to check
     * @param int $postId The post ID to check meta content for
     * @return bool Whether the file will be generated
     */
    private function willFileBeGenerated(string $filename, int $postId): bool
    {
        switch ($filename) {
            case 'index.js':
            case 'render.php':
                // These are always generated for blocks
                return true;

            case 'style.css':
                // Check if we have CSS content to generate
                $cssContent = get_post_meta($postId, MetaKeysConstants::CSS_CONTENT, true);
                $scssContent = get_post_meta($postId, MetaKeysConstants::BLOCK_SCSS, true);
                return !empty($cssContent) || !empty($scssContent);

            case 'editor.css':
                // Check if we have editor CSS content to generate
                $editorCssContent = get_post_meta($postId, MetaKeysConstants::BLOCK_EDITOR_CSS_CONTENT, true);
                $editorScssContent = get_post_meta($postId, MetaKeysConstants::BLOCK_EDITOR_SCSS, true);
                return !empty($editorCssContent) || !empty($editorScssContent);

            case 'view.js':
                // Check if we have JS content to generate
                $jsContent = get_post_meta($postId, MetaKeysConstants::BLOCK_JS, true);
                return !empty($jsContent);

            default:
                return false;
        }
    }
}
