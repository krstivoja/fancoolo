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
            return;
        }

        // Request Winden's plain classes autocomplete
        // This will make window.winden_autocomplete and window.winden_autocomplete_screens available
        do_action('winden_request_plain_classes_autocomplete');

        // Add minimal debug logging (only when WP_DEBUG is enabled)
        if (defined('WP_DEBUG') && WP_DEBUG) {
            add_action('admin_footer', function () {
                ?>
                <script>
                if (window.winden_autocomplete) {
                    console.log('[FanCoolo] Winden autocomplete loaded:', window.winden_autocomplete.length, 'classes');
                } else {
                    console.warn('[FanCoolo] Winden autocomplete not loaded');
                }
                </script>
                <?php
            }, 999); // Late priority to ensure Winden has loaded
        }
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

}
