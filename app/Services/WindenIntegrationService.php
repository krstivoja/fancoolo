<?php

namespace FanCoolo\Services;

/**
 * Winden Integration Service
 *
 * Handles integration with Winden plugin to provide Tailwind CSS autocomplete
 * in Monaco editors for block content and symbols.
 *
 * @since 1.0.0
 */
class WindenIntegrationService
{
    /**
     * Constructor - hooks into WordPress
     */
    public function __construct()
    {
        // Use priority 5 to ensure Winden loads BEFORE FanCoolo's assets (which load at priority 10)
        add_action('admin_enqueue_scripts', [$this, 'requestWindenAutocomplete'], 5);
    }

    /**
     * Request Winden's plain classes autocomplete for FanCoolo admin pages
     *
     * This triggers Winden to enqueue:
     * - window.winden_autocomplete (array of Tailwind classes)
     * - window.winden_autocomplete_screens (array of breakpoint prefixes)
     * - window.WindenAutocompleteWithScreens (React component)
     *
     * @param string $hook Current admin page hook
     * @return void
     */
    public function requestWindenAutocomplete(string $hook): void
    {
        // Only load on FanCoolo's main settings page (where editors are used)
        if ($hook !== 'toplevel_page_fancoolo-app') {
            return;
        }

        // Check if Winden plugin is active
        if (!$this->isWindenActive()) {
            // Add admin notice if Winden is not active (only for admins)
            if (current_user_can('manage_options')) {
                add_action('admin_notices', function () {
                    echo '<div class="notice notice-warning is-dismissible">';
                    echo '<p><strong>FanCoolo:</strong> Winden plugin is not active. Tailwind autocomplete in Monaco editors will not be available.</p>';
                    echo '</div>';
                });
            }
            return;
        }

        // Check if Winden cache has been generated
        if (!$this->isWindenCacheGenerated()) {
            // Add admin notice if Winden cache is not generated
            if (current_user_can('manage_options')) {
                add_action('admin_notices', function () {
                    echo '<div class="notice notice-warning is-dismissible">';
                    echo '<p><strong>FanCoolo:</strong> Winden cache is not generated. Please go to <a href="' . admin_url('admin.php?page=winden') . '">Winden Settings</a> and generate the Tailwind CSS cache for autocomplete to work.</p>';
                    echo '</div>';
                });
            }
            // Still request autocomplete in case the cache gets generated later
        }

        // Request Winden's plain classes autocomplete
        // This will make window.winden_autocomplete and window.winden_autocomplete_screens available
        do_action('winden_request_plain_classes_autocomplete');

        // Add debug script to verify Winden data is loaded - ALWAYS show, not just in debug mode
        add_action('admin_footer', function () {
            ?>
            <script>
            console.log('[FanCoolo Debug] ========================================');
            console.log('[FanCoolo Debug] Winden Autocomplete Status');
            console.log('[FanCoolo Debug] ========================================');
            console.log('[FanCoolo Debug] window.winden_autocomplete exists:', !!window.winden_autocomplete);
            console.log('[FanCoolo Debug] Total classes:', window.winden_autocomplete?.length || 0);
            console.log('[FanCoolo Debug] window.winden_autocomplete_screens:', window.winden_autocomplete_screens || 'NOT LOADED');
            console.log('[FanCoolo Debug] All winden globals:', Object.keys(window).filter(k => k.toLowerCase().includes('winden')));

            // Show first 50 classes as a sample
            if (window.winden_autocomplete && window.winden_autocomplete.length > 0) {
                console.log('[FanCoolo Debug] ========================================');
                console.log('[FanCoolo Debug] Sample classes (first 50):');
                console.log('[FanCoolo Debug] ========================================');
                console.table(window.winden_autocomplete.slice(0, 50));

                console.log('[FanCoolo Debug] ========================================');
                console.log('[FanCoolo Debug] Search for specific classes:');
                console.log('[FanCoolo Debug] ========================================');
                console.log('[FanCoolo Debug] bg-* classes:', window.winden_autocomplete.filter(c => c.startsWith('bg-')).slice(0, 20));
                console.log('[FanCoolo Debug] text-* classes:', window.winden_autocomplete.filter(c => c.startsWith('text-')).slice(0, 20));
                console.log('[FanCoolo Debug] flex-* classes:', window.winden_autocomplete.filter(c => c.startsWith('flex-')).slice(0, 20));
            } else {
                console.warn('[FanCoolo Debug] ⚠️ NO CLASSES LOADED!');
                console.warn('[FanCoolo Debug] This means Winden cache is not generated or not loaded yet.');
            }
            console.log('[FanCoolo Debug] ========================================');
            </script>
            <?php
        });
    }

    /**
     * Check if Winden plugin is installed and active
     *
     * @return bool
     */
    private function isWindenActive(): bool
    {
        // Check if Winden's MonacoEditorProvider class exists (indicating the plugin is active)
        return class_exists('Winden\App\Assets\MonacoEditorProvider');
    }

    /**
     * Check if Winden cache has been generated
     *
     * @return bool
     */
    private function isWindenCacheGenerated(): bool
    {
        $cache_status = get_option('winden_cache_status');
        return !empty($cache_status) && isset($cache_status['classes']) && is_array($cache_status['classes']) && count($cache_status['classes']) > 0;
    }

    /**
     * Add custom Tailwind classes to Winden's autocomplete
     *
     * This allows FanCoolo to add custom utility classes or project-specific classes
     * to the autocomplete suggestions.
     *
     * @param array $classes Existing Tailwind classes from Winden
     * @return array Modified array with custom classes
     */
    public function addCustomClasses(array $classes): array
    {
        $custom_classes = [
            // Add any FanCoolo-specific custom Tailwind utilities here
            // Example: 'fancoolo-primary', 'fancoolo-secondary', etc.
        ];

        return array_merge($classes, $custom_classes);
    }

    /**
     * Check if Winden autocomplete data is available in JavaScript
     *
     * This can be used for debugging or conditional feature loading
     *
     * @return bool
     */
    public static function isAutocompleteAvailable(): bool
    {
        // This would need to be checked on the frontend via JavaScript
        // Here we just check if the plugin is active
        return has_action('winden_request_plain_classes_autocomplete') !== false;
    }
}
