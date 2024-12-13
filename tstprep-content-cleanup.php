<?php
/**
 * Plugin Name: TSTPrep Content Cleanup
 * Description: Cleans up content for lessons and topics based on selected cleanup type.
 * Version: 2.0.8-alpha
 * Author: Vlad Tudorie
 */

if (!defined('ABSPATH')) exit;

define('TSTPREP_CC_VERSION', '2.0.2-alpha');
define('TSTPREP_CC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('TSTPREP_CC_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once TSTPREP_CC_PLUGIN_DIR . 'admin/class-admin-page.php';
require_once TSTPREP_CC_PLUGIN_DIR . 'admin/class-bulk-cleanup.php';
require_once TSTPREP_CC_PLUGIN_DIR . 'admin/class-ajax-handlers.php'; // Add this line
require_once TSTPREP_CC_PLUGIN_DIR . 'includes/class-cleanup-types.php';
require_once TSTPREP_CC_PLUGIN_DIR . 'includes/class-lesson-topic-handler.php';
require_once TSTPREP_CC_PLUGIN_DIR . 'includes/class-content-processor.php';

function tstprep_cc_init() {
    new TSTPrep_CC_Admin_Page();
    new TSTPrep_CC_Bulk_Cleanup();
    new TSTPrep_CC_Ajax_Handlers(); // Add this line
}


add_action('plugins_loaded', 'tstprep_cc_init');
