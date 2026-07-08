<?php
/**
 * Plugin Name: Open WP Migration – Unlimited Site Transfer & Backup
 * Plugin URI:  https://wordpress.org/plugins/open-migration/
 * Description: Migrate, backup, and restore your WordPress website for free. Bypasses upload limits for massive websites.
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
