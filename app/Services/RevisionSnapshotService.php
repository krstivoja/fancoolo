<?php

namespace FanCoolo\Services;

use FanCoolo\Admin\Api\Services\MetaKeysConstants;
use FanCoolo\Database\BlockSettingsRepository;
use FanCoolo\Database\ScssPartialsSettingsRepository;
use FanCoolo\Database\BlockAttributesRepository;
use FanCoolo\Content\FunculoTypeTaxonomy;

/**
 * Revision Snapshot Service
 *
 * Creates and applies snapshots of post meta data and database settings
 * for revision history functionality.
 */
class RevisionSnapshotService
{
    /**
     * Create a snapshot of current post state
     *
     * @param int $post_id Post ID
     * @return array Snapshot data containing all meta fields and database settings
     */
    public static function createSnapshot(int $post_id): array
    {
        $post = get_post($post_id);
        if (!$post) {
            throw new \RuntimeException("Post {$post_id} not found");
        }

        // Determine post type (block, symbol, scss-partial)
        $terms = wp_get_post_terms($post_id, FunculoTypeTaxonomy::getTaxonomy());
        $post_type_slug = !empty($terms) && !is_wp_error($terms) ? $terms[0]->slug : null;

        $snapshot = [
            'post_type' => $post_type_slug,
            'meta' => [],
            'database' => [],
        ];

        // Capture meta fields based on post type
        switch ($post_type_slug) {
            case FunculoTypeTaxonomy::getTermBlocks():
                $snapshot['meta'] = self::captureBlockMeta($post_id);
                $snapshot['database'] = self::captureBlockDatabaseSettings($post_id);
                break;

            case FunculoTypeTaxonomy::getTermSymbols():
                $snapshot['meta'] = self::captureSymbolMeta($post_id);
                break;

            case FunculoTypeTaxonomy::getTermScssPartials():
                $snapshot['meta'] = self::captureScssPartialMeta($post_id);
                $snapshot['database'] = self::captureScssPartialDatabaseSettings($post_id);
                break;
        }

        return $snapshot;
    }

    /**
     * Apply a snapshot to restore previous state
     *
     * @param int $post_id Post ID
     * @param array $snapshot Snapshot data
     * @return bool Success status
     */
    public static function applySnapshot(int $post_id, array $snapshot): bool
    {
        $post_type = $snapshot['post_type'] ?? null;
        $meta = $snapshot['meta'] ?? [];
        $database = $snapshot['database'] ?? [];

        // Apply meta fields based on post type
        switch ($post_type) {
            case FunculoTypeTaxonomy::getTermBlocks():
                self::applyBlockMeta($post_id, $meta);
                self::applyBlockDatabaseSettings($post_id, $database);
                break;

            case FunculoTypeTaxonomy::getTermSymbols():
                self::applySymbolMeta($post_id, $meta);
                break;

            case FunculoTypeTaxonomy::getTermScssPartials():
                self::applyScssPartialMeta($post_id, $meta);
                self::applyScssPartialDatabaseSettings($post_id, $database);
                break;
        }

        return true;
    }

    /**
     * Get current state for comparison (same as createSnapshot)
     *
     * @param int $post_id Post ID
     * @return array Current state data
     */
    public static function getCurrentState(int $post_id): array
    {
        return self::createSnapshot($post_id);
    }

    // ==================== Block Snapshot Methods ====================

    private static function captureBlockMeta(int $post_id): array
    {
        return [
            'php' => get_post_meta($post_id, MetaKeysConstants::BLOCK_PHP, true) ?: '',
            'scss' => get_post_meta($post_id, MetaKeysConstants::BLOCK_SCSS, true) ?: '',
            'editor_scss' => get_post_meta($post_id, MetaKeysConstants::BLOCK_EDITOR_SCSS, true) ?: '',
            'js' => get_post_meta($post_id, MetaKeysConstants::BLOCK_JS, true) ?: '',
            'attributes' => get_post_meta($post_id, MetaKeysConstants::BLOCK_ATTRIBUTES, true) ?: '[]',
            'css_content' => get_post_meta($post_id, MetaKeysConstants::CSS_CONTENT, true) ?: '',
            'editor_css_content' => get_post_meta($post_id, MetaKeysConstants::BLOCK_EDITOR_CSS_CONTENT, true) ?: '',
        ];
    }

    private static function captureBlockDatabaseSettings(int $post_id): array
    {
        $settings = BlockSettingsRepository::get($post_id);
        $attributes = BlockAttributesRepository::get($post_id);

        return [
            'settings' => $settings ?: [],
            'attributes' => $attributes ?: [],
        ];
    }

    private static function applyBlockMeta(int $post_id, array $meta): void
    {
        update_post_meta($post_id, MetaKeysConstants::BLOCK_PHP, $meta['php'] ?? '');
        update_post_meta($post_id, MetaKeysConstants::BLOCK_SCSS, $meta['scss'] ?? '');
        update_post_meta($post_id, MetaKeysConstants::BLOCK_EDITOR_SCSS, $meta['editor_scss'] ?? '');
        update_post_meta($post_id, MetaKeysConstants::BLOCK_JS, $meta['js'] ?? '');
        update_post_meta($post_id, MetaKeysConstants::BLOCK_ATTRIBUTES, $meta['attributes'] ?? '[]');
        update_post_meta($post_id, MetaKeysConstants::CSS_CONTENT, $meta['css_content'] ?? '');
        update_post_meta($post_id, MetaKeysConstants::BLOCK_EDITOR_CSS_CONTENT, $meta['editor_css_content'] ?? '');
    }

    private static function applyBlockDatabaseSettings(int $post_id, array $database): void
    {
        // Apply block settings
        if (!empty($database['settings'])) {
            BlockSettingsRepository::save($post_id, $database['settings']);
        }

        // Apply block attributes
        if (array_key_exists('attributes', $database)) {
            $attributes = is_array($database['attributes']) ? $database['attributes'] : [];
            BlockAttributesRepository::save($post_id, $attributes);
        }
    }

    // ==================== Symbol Snapshot Methods ====================

    private static function captureSymbolMeta(int $post_id): array
    {
        return [
            'php' => get_post_meta($post_id, MetaKeysConstants::SYMBOL_PHP, true) ?: '',
        ];
    }

    private static function applySymbolMeta(int $post_id, array $meta): void
    {
        update_post_meta($post_id, MetaKeysConstants::SYMBOL_PHP, $meta['php'] ?? '');
    }

    // ==================== SCSS Partial Snapshot Methods ====================

    private static function captureScssPartialMeta(int $post_id): array
    {
        return [
            'scss' => get_post_meta($post_id, MetaKeysConstants::SCSS_PARTIAL_SCSS, true) ?: '',
        ];
    }

    private static function captureScssPartialDatabaseSettings(int $post_id): array
    {
        $settings = ScssPartialsSettingsRepository::get($post_id);

        return [
            'settings' => $settings ?: [],
        ];
    }

    private static function applyScssPartialMeta(int $post_id, array $meta): void
    {
        update_post_meta($post_id, MetaKeysConstants::SCSS_PARTIAL_SCSS, $meta['scss'] ?? '');
    }

    private static function applyScssPartialDatabaseSettings(int $post_id, array $database): void
    {
        // Apply SCSS partial settings
        if (!empty($database['settings'])) {
            ScssPartialsSettingsRepository::save($post_id, $database['settings']);
        }
    }
}
