<?php
/**
 * Plugin Name: TSTPrep Content Cleanup
 * Description: Cleans up content for lessons and topics based on selected cleanup type.
 * Version: 2.0.23-alpha
 * Author: Vlad Tudorie
 */

if (!defined('ABSPATH')) exit;

define('TSTPREP_CC_VERSION', '2.0.23-alpha');
define('TSTPREP_CC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('TSTPREP_CC_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once TSTPREP_CC_PLUGIN_DIR . 'admin/class-admin-page.php';
require_once TSTPREP_CC_PLUGIN_DIR . 'admin/class-bulk-cleanup.php';
require_once TSTPREP_CC_PLUGIN_DIR . 'admin/class-ajax-handlers.php'; // Add this line
require_once TSTPREP_CC_PLUGIN_DIR . 'includes/class-cleanup-types.php';
require_once TSTPREP_CC_PLUGIN_DIR . 'includes/class-lesson-topic-handler.php';
require_once TSTPREP_CC_PLUGIN_DIR . 'includes/class-content-processor.php';

// Global variable to store the Ajax Handlers instance
$tstprep_cc_ajax_handlers = null;

function tstprep_cc_init() {
    global $tstprep_cc_ajax_handlers;
    
    new TSTPrep_CC_Admin_Page();
    new TSTPrep_CC_Bulk_Cleanup();
    $tstprep_cc_ajax_handlers = new TSTPrep_CC_Ajax_Handlers();
}
add_action('plugins_loaded', 'tstprep_cc_init');

// Add the download log action with an instance method, not a static method
function tstprep_cc_add_ajax_actions() {
    global $tstprep_cc_ajax_handlers;
    if ($tstprep_cc_ajax_handlers) {
        add_action('wp_ajax_tstprep_cc_download_log', array($tstprep_cc_ajax_handlers, 'download_log'));
    }
}
add_action('init', 'tstprep_cc_add_ajax_actions');
