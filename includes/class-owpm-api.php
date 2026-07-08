<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OWPM_API {

	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
		add_action( 'wp_ajax_owpm_generate_token', array( $this, 'ajax_generate_token' ) );
		add_action( 'wp_ajax_owpm_pull_site', array( $this, 'ajax_pull_site' ) );
	}

	public function register_routes() {
		register_rest_route( 'owpm/v1', '/download/(?P<backup_id>[a-zA-Z0-9_-]+)', array(
			'methods'  => 'GET',
			'callback' => array( $this, 'api_download_backup' ),
			'permission_callback' => '__return_true' // We handle auth manually via secret
		) );
	}

	public function ajax_generate_token() {
		check_ajax_referer( 'owpm_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error();

		$backup_id = isset( $_POST['backup_id'] ) ? sanitize_text_field( wp_unslash( $_POST['backup_id'] ) ) : '';
		if ( empty( $backup_id ) ) wp_send_json_error( 'No backup ID provided.' );

		$secret = wp_generate_password( 32, false );
		
		// Store secret temporarily (expires in 24h)
		set_transient( 'owpm_secret_' . $backup_id, $secret, DAY_IN_SECONDS );

		$token_data = array(
			'url'       => site_url(),
			'backup_id' => $backup_id,
			'secret'    => $secret
		);

		$token_string = base64_encode( wp_json_encode( $token_data ) );

		wp_send_json_success( array( 'token' => $token_string ) );
	}

	public function api_download_backup( WP_REST_Request $request ) {
		$backup_id = $request->get_param( 'backup_id' );
		$secret    = $request->get_param( 'secret' );

		$stored_secret = get_transient( 'owpm_secret_' . $backup_id );

		if ( ! $stored_secret || ! hash_equals( $stored_secret, $secret ) ) {
			return new WP_Error( 'unauthorized', 'Invalid or expired token.', array( 'status' => 401 ) );
		}

		$upload_dir = wp_upload_dir();
		$file_path  = trailingslashit( $upload_dir['basedir'] ) . 'owpm-backups/' . $backup_id . '.zip';

		if ( ! file_exists( $file_path ) ) {
			return new WP_Error( 'not_found', 'Backup file not found.', array( 'status' => 404 ) );
		}

		// Simple streaming response
		header( 'Content-Type: application/octet-stream' );
		header( 'Content-Disposition: attachment; filename="' . basename( $file_path ) . '"' );
		header( 'Content-Length: ' . filesize( $file_path ) );

		// Output file directly to output buffer safely
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
		readfile( $file_path );
		exit;
	}

	public function ajax_pull_site() {
		check_ajax_referer( 'owpm_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error();

		$token = isset( $_POST['token'] ) ? sanitize_text_field( wp_unslash( $_POST['token'] ) ) : '';
		if ( empty( $token ) ) wp_send_json_error( 'Token is required.' );

		$decoded = json_decode( base64_decode( $token ), true );
		if ( ! $decoded || empty( $decoded['url'] ) || empty( $decoded['backup_id'] ) || empty( $decoded['secret'] ) ) {
			wp_send_json_error( 'Invalid token format.' );
		}

		$download_url = trailingslashit( $decoded['url'] ) . 'wp-json/owpm/v1/download/' . $decoded['backup_id'] . '?secret=' . $decoded['secret'];

		// We need to download this file into our temp directory
		$upload_dir = wp_upload_dir();
		$temp_dir   = trailingslashit( $upload_dir['basedir'] ) . 'owpm-backups/';
		if ( ! file_exists( $temp_dir ) ) wp_mkdir_p( $temp_dir );

		$local_file = $temp_dir . $decoded['backup_id'] . '.zip';

		// Download file
		$response = wp_remote_get( $download_url, array( 'timeout' => 300, 'stream' => true, 'filename' => $local_file ) );

		if ( is_wp_error( $response ) ) {
			wp_send_json_error( 'Failed to pull site: ' . $response->get_error_message() );
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		if ( $status_code !== 200 ) {
			wp_send_json_error( 'Failed to pull site. Server responded with: ' . $status_code );
		}

		// Download successful, pass file_id back so the JS can trigger import
		wp_send_json_success( array( 'file_id' => $decoded['backup_id'] ) );
	}
}
new OWPM_API();
