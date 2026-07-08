<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OWPM_Export {

	private $temp_dir;

	public function __construct() {
		$upload_dir = wp_upload_dir();
		$this->temp_dir = trailingslashit( $upload_dir['basedir'] ) . 'owpm-backups/';
		
		add_action( 'wp_ajax_owpm_start_export', array( $this, 'ajax_start_export' ) );
		add_action( 'wp_ajax_owpm_export_db', array( $this, 'ajax_export_db' ) );
		add_action( 'wp_ajax_owpm_export_files', array( $this, 'ajax_export_files' ) );
		add_action( 'wp_ajax_owpm_finish_export', array( $this, 'ajax_finish_export' ) );
		add_action( 'wp_ajax_owpm_get_backups', array( $this, 'ajax_get_backups' ) );
		add_action( 'wp_ajax_owpm_delete_backup', array( $this, 'ajax_delete_backup' ) );
	}

	private function init_dir() {
		if ( ! file_exists( $this->temp_dir ) ) {
			wp_mkdir_p( $this->temp_dir );
			// Protect directory initially
			file_put_contents( $this->temp_dir . '.htaccess', 'deny from all' );
			file_put_contents( $this->temp_dir . 'index.php', '<?php // silence' );
		}
	}

	public function ajax_start_export() {
		check_ajax_referer( 'owpm_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized' );
		}

		$this->init_dir();

		$backup_id = 'backup_' . gmdate( 'Ymd_His' ) . '_' . wp_generate_password( 6, false );
		$export_path = $this->temp_dir . $backup_id;

		if ( ! file_exists( $export_path ) ) {
			wp_mkdir_p( $export_path );
		}

		// Save state
		update_option( 'owpm_current_export', array(
			'id' => $backup_id,
			'path' => $export_path,
			'zip_file' => $this->temp_dir . $backup_id . '.zip',
			'file_offset' => 0
		) );

		wp_send_json_success( array( 'backup_id' => $backup_id ) );
	}

	public function ajax_export_db() {
		check_ajax_referer( 'owpm_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized' );

		$state = get_option( 'owpm_current_export' );
		if ( ! $state ) wp_send_json_error( 'No export in progress.' );

		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$tables = $wpdb->get_col( 'SHOW TABLES' );
		
		$sql_file = $state['path'] . '/database.sql';

		// Dump tables
		foreach ( $tables as $table ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
			$create_table = $wpdb->get_row( $wpdb->prepare( "SHOW CREATE TABLE %i", $table ), ARRAY_N );
			
			$buffer = "DROP TABLE IF EXISTS `$table`;\n";
			$buffer .= $create_table[1] . ";\n\n";

			$offset = 0;
			$limit = 500;
			
			while ( true ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM %i LIMIT %d OFFSET %d", $table, $limit, $offset ), ARRAY_A );
				if ( empty( $rows ) ) break;

				foreach ( $rows as $row ) {
					$vals = array_map( function( $val ) use ( $wpdb ) {
						if ( is_null( $val ) ) return 'NULL';
						return "'" . esc_sql( $val ) . "'";
					}, array_values( $row ) );
					
					$buffer .= "INSERT INTO `$table` VALUES (" . implode( ',', $vals ) . ");\n";
				}
				
				file_put_contents( $sql_file, $buffer, FILE_APPEND );
				$buffer = '';
				
				$offset += $limit;
			}
			$buffer .= "\n";
			file_put_contents( $sql_file, $buffer, FILE_APPEND );
		}

		wp_send_json_success( 'DB Exported' );
	}

	public function ajax_export_files() {
		check_ajax_referer( 'owpm_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized' );

		$state = get_option( 'owpm_current_export' );
		if ( ! $state ) wp_send_json_error( 'No export in progress' );

		$zip = new ZipArchive();
		$zip_opened = $zip->open( $state['zip_file'], ZipArchive::CREATE );
		if ( $zip_opened !== true ) {
			wp_send_json_error( 'Could not create zip.' );
		}

		if ( $state['file_offset'] == 0 ) {
			if ( file_exists( $state['path'] . '/database.sql' ) ) {
				$zip->addFile( $state['path'] . '/database.sql', 'database.sql' );
			}
			
			// Inject metadata for automatic search & replace
			$metadata = array(
				'source_url'  => site_url(),
				'source_path' => ABSPATH
			);
			$zip->addFromString( 'metadata.json', wp_json_encode( $metadata ) );
		}

		$wp_content_dir = WP_CONTENT_DIR;
		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $wp_content_dir, RecursiveDirectoryIterator::SKIP_DOTS )
		);

		$files = array();
		foreach ( $iterator as $file ) {
			if ( ! $file->isDir() ) {
				$files[] = $file->getPathname();
			}
		}

		$batch_size = 500;
		$offset = $state['file_offset'];
		$total_files = count( $files );
		
		$files_to_process = array_slice( $files, $offset, $batch_size );
		
		$last_file = '';
		foreach ( $files_to_process as $file_path ) {
			$local_name = 'wp-content/' . str_replace( trailingslashit( WP_CONTENT_DIR ), '', $file_path );
            $local_name = str_replace( '\\', '/', $local_name );
			$zip->addFile( $file_path, $local_name );
			$last_file = $local_name;
		}

		$zip->close();

		$new_offset = $offset + count( $files_to_process );
		$state['file_offset'] = $new_offset;
		update_option( 'owpm_current_export', $state );

		$progress = min( 100, round( ( $new_offset / $total_files ) * 100 ) );

		if ( $new_offset >= $total_files ) {
			wp_send_json_success( array( 'status' => 'complete', 'progress' => 100, 'current_file' => 'Done' ) );
		} else {
			wp_send_json_success( array( 'status' => 'processing', 'progress' => $progress, 'current_file' => $last_file ) );
		}
	}

	public function ajax_finish_export() {
		check_ajax_referer( 'owpm_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized' );

		$state = get_option( 'owpm_current_export' );
		
		require_once ABSPATH . 'wp-admin/includes/file.php';
		WP_Filesystem();
		global $wp_filesystem;
		
		if ( $wp_filesystem->exists( $state['path'] . '/database.sql' ) ) {
			$wp_filesystem->delete( $state['path'] . '/database.sql' );
		}
		if ( $wp_filesystem->is_dir( $state['path'] ) ) {
			$wp_filesystem->delete( $state['path'], true );
		}

		delete_option( 'owpm_current_export' );
		
		$upload_dir = wp_upload_dir();
		$url = trailingslashit( $upload_dir['baseurl'] ) . 'owpm-backups/' . basename( $state['zip_file'] );

		// Re-write .htaccess to allow .zip downloads
		$htaccess_content = "Order allow,deny\nDeny from all\n<FilesMatch \"\.(zip)$\">\nAllow from all\n</FilesMatch>";
		file_put_contents( $this->temp_dir . '.htaccess', $htaccess_content );

		wp_send_json_success( array( 'download_url' => $url ) );
	}

	public function ajax_get_backups() {
		check_ajax_referer( 'owpm_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized' );

		$this->init_dir(); // Ensure directory exists
		$files = glob( $this->temp_dir . '*.zip' );
		$backups = array();

		$upload_dir = wp_upload_dir();
		$baseurl = trailingslashit( $upload_dir['baseurl'] ) . 'owpm-backups/';

		if ( $files ) {
			foreach ( $files as $file ) {
				$backups[] = array(
					'name' => basename( $file ),
					'size' => size_format( filesize( $file ) ),
					'date' => date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), filemtime( $file ) ),
					'url'  => $baseurl . basename( $file )
				);
			}
			// Sort newest first
			usort( $backups, function($a, $b) {
				return strtotime($b['date']) - strtotime($a['date']);
			} );
		}

		wp_send_json_success( $backups );
	}

	public function ajax_delete_backup() {
		check_ajax_referer( 'owpm_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized' );

		$file = isset( $_POST['file'] ) ? sanitize_text_field( wp_unslash( $_POST['file'] ) ) : '';
		if ( empty( $file ) ) wp_send_json_error( 'No file specified' );

		// Security: prevent directory traversal
		$file = basename( $file );
		$filepath = $this->temp_dir . $file;

		if ( file_exists( $filepath ) && strpos( $file, '.zip' ) !== false ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();
			global $wp_filesystem;
			$wp_filesystem->delete( $filepath );
			wp_send_json_success( 'Deleted' );
		} else {
			wp_send_json_error( 'File not found' );
		}
	}
}
new OWPM_Export();
