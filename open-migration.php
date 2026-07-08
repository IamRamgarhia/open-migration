<?php
/**
 * Plugin Name: Open Migration
 * Plugin URI:  https://wordpress.org/plugins/open-migration/
 * Description: A 100% free, limitless site migration and backup plugin. Export, import, and do direct site-to-site transfers without file size limits.
 * Version:     1.1.4
 * Author:      Open Migration Team
 * Author URI:  https://wordpress.org/plugins/open-migration/
 * License:     GPLv2 or later
 * Text Domain: open-migration
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'OWPM_VERSION', '1.1.4' );
define( 'OWPM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'OWPM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Load includes
require_once OWPM_PLUGIN_DIR . 'includes/class-owpm-admin.php';
require_once OWPM_PLUGIN_DIR . 'includes/class-owpm-export.php';
require_once OWPM_PLUGIN_DIR . 'includes/class-owpm-import.php';
require_once OWPM_PLUGIN_DIR . 'includes/class-owpm-api.php';
require_once OWPM_PLUGIN_DIR . 'includes/class-owpm-search-replace.php';
