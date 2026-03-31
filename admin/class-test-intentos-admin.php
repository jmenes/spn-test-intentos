<?php

class Test_Intentos_Admin {

	private $plugin_name;
	private $version;

	public function __construct() {
		$this->plugin_name = 'test-intentos';
		$this->version = TEST_INTENTOS_VERSION;
	}

	public function init() {
		add_action( 'admin_menu', array( $this, 'add_plugin_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles_and_scripts' ) );
	}

	public function add_plugin_admin_menu() {
		add_menu_page(
			'Test Intentos', 
			'Test Intentos', 
			'manage_options', 
			$this->plugin_name, 
			array( $this, 'display_plugin_setup_page' ), 
			'dashicons-clipboard',
			30
		);
	}

	public function enqueue_styles_and_scripts( $hook ) {
		// Only load on our plugin page
		if ( 'toplevel_page_' . $this->plugin_name !== $hook ) {
			return;
		}

		// Select2
		wp_enqueue_style( 'select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', array(), '4.1.0' );
		wp_enqueue_script( 'select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array( 'jquery' ), '4.1.0', true );

		// SweetAlert2
		wp_enqueue_script( 'sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@11', array(), '11.0.0', true );

		// Our custom assets
		wp_enqueue_style( $this->plugin_name, TEST_INTENTOS_URL . 'assets/admin.css', array(), $this->version, 'all' );
		wp_enqueue_script( $this->plugin_name, TEST_INTENTOS_URL . 'assets/admin.js', array( 'jquery', 'select2', 'sweetalert2' ), $this->version, true );

		// Pass data to JS
		wp_localize_script( $this->plugin_name, 'TestIntentosObj', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'test_intentos_nonce' ),
			'loading'  => 'Cargando datos...',
			'error'    => 'Hubo un error en la solicitud. Intenta de nuevo.'
		) );
	}

	public function display_plugin_setup_page() {
		require_once TEST_INTENTOS_PATH . 'admin/partials/test-intentos-admin-display.php';
	}
}
