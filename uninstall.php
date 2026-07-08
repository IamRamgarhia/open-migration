<?php
// If uninstall not called from WordPress, exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Clean up our temporary backups directory when the plugin is deleted
$owpm_upload_dir = wp_upload_dir();
$owpm_backup_dir = trailingslashit( $owpm_upload_dir['basedir'] ) . 'owpm-backups/';

if ( is_dir( $owpm_backup_dir ) ) {
	require_once ABSPATH . 'wp-admin/includes/file.php';
	WP_Filesystem();
	global $wp_filesystem;
	
	if ( $wp_filesystem ) {
		$wp_filesystem->delete( $owpm_backup_dir, true );
	}
}
