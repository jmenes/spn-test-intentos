<?php
/**
 * Plugin Name:       Test Intentos (SeguimientoPN)
 * Plugin URI:        https://menes.studio
 * Description:       Plugin para buscar usuarios y gestionar sus intentos de Tests/Simulacros. Permite borrar intentos para resetear los 10 días.
 * Version:           1.0.0
 * Author:            menes.studio
 * Author URI:        https://menes.studio
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       test-intentos
 */

// Si este archivo es llamado directamente, abortar.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'TEST_INTENTOS_VERSION', '1.0.0' );
define( 'TEST_INTENTOS_PATH', plugin_dir_path( __FILE__ ) );
define( 'TEST_INTENTOS_URL', plugin_dir_url( __FILE__ ) );

/**
 * El código principal de administración
 */
require_once TEST_INTENTOS_PATH . 'admin/class-test-intentos-admin.php';
require_once TEST_INTENTOS_PATH . 'admin/class-test-intentos-ajax.php';

function run_test_intentos() {
	$plugin_admin = new Test_Intentos_Admin();
	$plugin_admin->init();

	$plugin_ajax = new Test_Intentos_Ajax();
	$plugin_ajax->init();
}

run_test_intentos();
