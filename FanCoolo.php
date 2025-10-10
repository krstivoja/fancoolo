<?php
/**
 * Plugin Name: FanCoolo WP
 * Plugin URI: https://github.com/marko-krstic/fancoolo
 * Description: Build gutenberg blocks without scraming in the screen.
 * Version: 0.0.3
 * Author: Marko Krstić
 * Author URI: https://dplugins.com/
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: fancoolo
 * Requires at least: 5.0
 * Tested up to: 6.8.2
 * Requires PHP: 8.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('FANCOOLO_PLUGIN_FILE', __FILE__);
define('FANCOOLO_URL', plugin_dir_url(__FILE__));
define('FANCOOLO_PATH', plugin_dir_path(__FILE__));
define('FANCOOLO_VERSION', '0.0.1');

// Define blocks directory - save to wp-content/plugins/fancoolo-blocks
$blocks_dir_name = 'fancoolo-blocks';
$blocks_path = WP_PLUGIN_DIR . '/' . $blocks_dir_name;
define('FANCOOLO_BLOCKS_DIR', $blocks_path);
define('FANCOOLO_BLOCKS_URL', plugins_url($blocks_dir_name));

// Check for Composer autoloader
$autoloader = __DIR__ . '/vendor/autoload.php';
if (!file_exists($autoloader)) {
    add_action('admin_notices', function() {
        echo '<div class="notice notice-error"><p>';
        echo __('FanCoolo Plugin Error: Composer dependencies are missing. Please run "composer install" in the plugin directory.', 'fancoolo');
        echo '</p></div>';
    });
    return; // Stop plugin initialization
}

// Load Composer autoloader
require_once $autoloader;

// Bootstrap the plugin
add_action('plugins_loaded', function() {
    \FanCoolo\App::boot(FANCOOLO_PLUGIN_FILE);
});


add_action( 'wp_enqueue_scripts', 'move_fancoolo_blocks_to_footer', 999 );
function move_fancoolo_blocks_to_footer() {
    global $wp_scripts;
    
    foreach ( $wp_scripts->registered as $handle => $script ) {
        if ( strpos( $handle, 'fancoolo-' ) !== false && strpos( $handle, '-view-script' ) !== false ) {
            // Move to footer with dependencies
            $wp_scripts->add_data( $handle, 'group', 1 );
            
            // Move dependencies too
            if ( ! empty( $script->deps ) ) {
                foreach ( $script->deps as $dep ) {
                    if ( isset( $wp_scripts->registered[ $dep ] ) ) {
                        $wp_scripts->add_data( $dep, 'group', 1 );
                    }
                }
            }
        }
    }
}




// Remove fancoolo view scripts from the default print queue
add_action( 'wp_print_footer_scripts', 'remove_fancoolo_from_queue', 9 );
function remove_fancoolo_from_queue() {
    global $wp_scripts;
    
    // Store handles to print later
    if ( ! isset( $GLOBALS['fancoolo_delayed_scripts'] ) ) {
        $GLOBALS['fancoolo_delayed_scripts'] = [];
    }
    
    // Remove from queue and save for later
    foreach ( $wp_scripts->queue as $key => $handle ) {
        if ( strpos( $handle, 'fancoolo-' ) !== false && strpos( $handle, '-view-script' ) !== false ) {
            $GLOBALS['fancoolo_delayed_scripts'][] = $handle;
            unset( $wp_scripts->queue[ $key ] );
        }
    }
    
    // Re-index array after unsetting
    $wp_scripts->queue = array_values( $wp_scripts->queue );
}

// Print fancoolo scripts at priority 9999 (after everything else)
add_action( 'wp_footer', 'print_fancoolo_scripts_late', 9999 );
function print_fancoolo_scripts_late() {
    global $wp_scripts;
    
    if ( ! isset( $GLOBALS['fancoolo_delayed_scripts'] ) || empty( $GLOBALS['fancoolo_delayed_scripts'] ) ) {
        return;
    }
    
    // Print the delayed scripts with their dependencies
    foreach ( $GLOBALS['fancoolo_delayed_scripts'] as $handle ) {
        if ( ! in_array( $handle, $wp_scripts->done ) ) {
            $wp_scripts->do_item( $handle, 1 ); // 1 = footer group
        }
    }
}




// Debut script Loading

// add_action( 'wp_footer', 'debug_enqueued_scripts_with_priorities', 99999 );
// function debug_enqueued_scripts_with_priorities() {
//     global $wp_scripts, $wp_filter;
    
//     echo '<div style="background: #000; color: #0f0; padding: 20px; margin: 20px; font-family: monospace; font-size: 12px; overflow: auto; max-height: 80vh;">';
//     echo '<h2 style="color: #0ff;">ENQUEUED SCRIPTS WITH HOOK PRIORITIES</h2>';
    
//     // Show wp_footer hooks with priorities
//     echo '<h3 style="color: #ff0;">WP_FOOTER HOOKS (showing script printing):</h3>';
//     echo '<pre>';
//     if ( isset( $wp_filter['wp_footer'] ) ) {
//         foreach ( $wp_filter['wp_footer']->callbacks as $priority => $callbacks ) {
//             foreach ( $callbacks as $callback ) {
//                 $function_name = '';
//                 if ( is_string( $callback['function'] ) ) {
//                     $function_name = $callback['function'];
//                 } elseif ( is_array( $callback['function'] ) ) {
//                     if ( is_object( $callback['function'][0] ) ) {
//                         $function_name = get_class( $callback['function'][0] ) . '->' . $callback['function'][1];
//                     } else {
//                         $function_name = $callback['function'][0] . '::' . $callback['function'][1];
//                     }
//                 } elseif ( $callback['function'] instanceof Closure ) {
//                     $function_name = 'Closure';
//                 }
                
//                 // Highlight script-related functions
//                 $is_script_function = in_array( $function_name, [
//                     'wp_print_footer_scripts',
//                     'wp_print_scripts',
//                     '_wp_footer_scripts',
//                     'print_fancoolo_scripts_late'
//                 ]) || strpos( $function_name, 'script' ) !== false;
                
//                 if ( $is_script_function ) {
//                     echo "<span style='color: #f0f;'>Priority {$priority}: {$function_name} ★</span>\n";
//                 }
//             }
//         }
//     }
//     echo '</pre>';
    
//     // Show enqueued scripts in footer with group info
//     echo '<h3 style="color: #ff0;">FOOTER SCRIPTS (order in queue):</h3>';
//     echo '<pre>';
//     $footer_count = 0;
//     foreach ( $wp_scripts->queue as $handle ) {
//         if ( isset( $wp_scripts->registered[ $handle ] ) ) {
//             $script = $wp_scripts->registered[ $handle ];
//             $group = isset( $wp_scripts->groups[ $handle ] ) ? $wp_scripts->groups[ $handle ] : 0;
            
//             if ( $group === 1 ) {
//                 $footer_count++;
//                 $highlight = strpos( $handle, 'fancoolo-' ) !== false ? ' <span style="color: #f0f;">★ FANCOOLO</span>' : '';
//                 echo "{$footer_count}. {$handle}{$highlight}\n";
//                 echo "   Src: {$script->src}\n";
//                 echo "   Group: {$group} (1=footer, 0=header)\n";
//                 if ( ! empty( $script->deps ) ) {
//                     echo "   Deps: " . implode( ', ', $script->deps ) . "\n";
//                 }
//                 echo "\n";
//             }
//         }
//     }
//     echo '</pre>';
    
//     echo '<p style="color: #ff0;">NOTE: Default WordPress prints footer scripts at priority 20 (wp_print_footer_scripts)</p>';
//     echo '<p style="color: #0ff;">Your custom functions should use priority > 20 to print after default scripts</p>';
//     echo '</div>';
// }