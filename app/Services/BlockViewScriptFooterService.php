<?php

namespace FanCoolo\Services;

class BlockViewScriptFooterService
{
    /**
     * @var array<string>
     */
    private array $delayedHandles = [];

    public function __construct()
    {
        add_action('wp_enqueue_scripts', [$this, 'moveHandlesToFooter'], 999);
        add_action('wp_print_footer_scripts', [$this, 'captureHandles'], 9);
        add_action('wp_footer', [$this, 'printHandles'], 9999);
        add_filter('script_loader_tag', [$this, 'removeDefer'], 10, 3);
    }

    public function moveHandlesToFooter(): void
    {
        global $wp_scripts;

        if (!isset($wp_scripts) || empty($wp_scripts->registered)) {
            return;
        }

        foreach ($wp_scripts->registered as $handle => $script) {
            if (strpos($handle, 'fancoolo-') === false || strpos($handle, '-view-script') === false) {
                continue;
            }

            $wp_scripts->add_data($handle, 'group', 1);

            if (!empty($script->deps)) {
                foreach ($script->deps as $dependencyHandle) {
                    if (isset($wp_scripts->registered[$dependencyHandle])) {
                        $wp_scripts->add_data($dependencyHandle, 'group', 1);
                    }
                }
            }
        }
    }

    public function captureHandles(): void
    {
        global $wp_scripts;

        if (!isset($wp_scripts) || empty($wp_scripts->queue)) {
            return;
        }

        foreach ($wp_scripts->queue as $index => $handle) {
            if (strpos($handle, 'fancoolo-') === false || strpos($handle, '-view-script') === false) {
                continue;
            }

            if (!in_array($handle, $this->delayedHandles, true)) {
                $this->delayedHandles[] = $handle;
            }

            unset($wp_scripts->queue[$index]);
        }

        $wp_scripts->queue = array_values($wp_scripts->queue);
    }

    public function printHandles(): void
    {
        global $wp_scripts;

        if (!isset($wp_scripts) || empty($this->delayedHandles)) {
            return;
        }

        foreach ($this->delayedHandles as $handle) {
            if (!in_array($handle, $wp_scripts->done, true)) {
                $wp_scripts->do_item($handle, 1);
            }
        }

        $this->delayedHandles = [];
    }

    public function removeDefer(string $tag, string $handle, string $src): string
    {
        // Only process our fancoolo view scripts
        if (strpos($handle, 'fancoolo-') === false || strpos($handle, '-view-script') === false) {
            return $tag;
        }

        // Remove defer attribute
        $tag = str_replace(' defer', '', $tag);
        $tag = str_replace(' defer=""', '', $tag);

        // Remove data-wp-strategy attribute
        $tag = preg_replace('/ data-wp-strategy="[^"]*"/', '', $tag);

        return $tag;
    }
}
