<?php
/**
 * Admin felület logika
 */
class Met_Plugin_Admin {
	public function __construct() {
		add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
	}

	public function add_admin_menu() {
		add_menu_page( __( 'MET Plugin', 'met-plugin' ), __( 'MET Plugin', 'met-plugin' ), 'manage_options', 'met-plugin', [ $this, 'admin_page' ] );
	}

	public function admin_page() {
		echo '<div class="wrap"><h1>' . esc_html__( 'MET Plugin admin', 'met-plugin' ) . '</h1></div>';
	}
}
