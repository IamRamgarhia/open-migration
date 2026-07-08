<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OWPM_Admin {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	public function add_admin_menu() {
		add_menu_page(
			__( 'Open Migration', 'open-migration' ),
			__( 'Open Migration', 'open-migration' ),
			'manage_options',
			'open-migration',
			array( $this, 'render_admin_page' ),
			'dashicons-migrate',
			30
		);
	}

	public function enqueue_scripts( $hook ) {
		if ( 'toplevel_page_open-migration' !== $hook ) {
			return;
		}

		wp_enqueue_style( 'owpm-admin-css', OWPM_PLUGIN_URL . 'assets/css/admin.css', array(), OWPM_VERSION );
		wp_enqueue_script( 'owpm-admin-js', OWPM_PLUGIN_URL . 'assets/js/admin.js', array( 'jquery' ), OWPM_VERSION, true );
        
        // Pass variables to JS
        wp_localize_script( 'owpm-admin-js', 'owpm_ajax', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'owpm_nonce' )
        ) );
	}

	public function render_admin_page() {
		?>
		<div class="wrap owpm-wrap">
			<!-- H1 removed to fix duplicate heading -->
			<div id="owpm-app-root">
				<!-- JS app will mount here -->
                <p>Loading UI...</p>
			</div>
		</div>
		<?php
	}
}

new OWPM_Admin();
