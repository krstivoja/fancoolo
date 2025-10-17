<?php

namespace FanCoolo\Services;

use CoreFramework\Helper;

/**
 * Core Framework Integration Service
 *
 * Loads Core Framework preset data from the database and exposes it to the
 * admin editor as browser globals so the Monaco autocomplete can consume it.
 */
class CoreFrameworkIntegrationService
{
    private const TARGET_HOOK = 'toplevel_page_fancoolo-app';

    private ?Helper $helper = null;

    public function __construct()
    {
        add_action('admin_enqueue_scripts', [$this, 'exposeCoreFrameworkData'], 6);
    }

    public function exposeCoreFrameworkData(string $hook): void
    {
        if ($hook !== self::TARGET_HOOK) {
            return;
        }

        if (!$this->isCoreFrameworkActive()) {
            return;
        }

        $helper = $this->getHelper();

        if (!$helper) {
            return;
        }

        $helper->loadPreset();
        $classes = $helper->getClassNames(['group_by_category' => false]);

        $classPayload = array_values(array_unique(array_filter($classes)));

        if (empty($classPayload)) {
            return;
        }

        $classJson = \wp_json_encode($classPayload);

        add_action(
            'admin_footer',
            function () use ($classJson) {
                ?>
                <script>
                (function () {
                    const coreClasses = <?php echo $classJson; ?>;
                    const existing = Array.isArray(window.winden_autocomplete) ? window.winden_autocomplete : [];
                    const merged = Array.from(new Set([...existing, ...coreClasses]));
                    window.winden_autocomplete = merged;
                    window.winden_autocomplete_screens = window.winden_autocomplete_screens || [];
                    console.log('[FanCoolo] Core Framework classes merged into winden_autocomplete:', {
                        total: merged.length,
                        addedFromCoreFramework: coreClasses.length
                    });
                })();
                </script>
                <?php
            },
            999
        );
    }

    private function isCoreFrameworkActive(): bool
    {
        return function_exists('CoreFramework') && class_exists(Helper::class);
    }

    private function getHelper(): ?Helper
    {
        if (!$this->helper && $this->isCoreFrameworkActive()) {
            $this->helper = new Helper();
        }

        return $this->helper;
    }
}
