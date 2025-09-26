<?php
/**
 * Admin felület logika
 */
class Met_Plugin_Admin {
       public function __construct() {
	       add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
	       add_action( 'admin_init', [ $this, 'register_settings' ] );
       }

       public function add_admin_menu() {
	       add_menu_page( __( 'MET Plugin', 'met-plugin' ), __( 'MET Plugin', 'met-plugin' ), 'manage_options', 'met-plugin', [ $this, 'admin_page' ] );
       }

       public function register_settings() {
	       register_setting( 'met_plugin_settings', 'met_plugin_pinecone_api_key' );
	       register_setting( 'met_plugin_settings', 'met_plugin_pinecone_env' );
       }

       public function admin_page() {
	       echo '<div class="wrap"><h1>' . esc_html__( 'MET Plugin admin', 'met-plugin' ) . '</h1>';
	       echo '<form method="post" action="options.php">';
	       settings_fields( 'met_plugin_settings' );
	       do_settings_sections( 'met_plugin_settings' );
	       echo '<table class="form-table">';
	       echo '<tr><th scope="row">' . esc_html__( 'Pinecone API kulcs', 'met-plugin' ) . '</th>';
	       echo '<td><input type="password" name="met_plugin_pinecone_api_key" value="' . esc_attr( get_option('met_plugin_pinecone_api_key', '') ) . '" size="50" /></td></tr>';
	       echo '<tr><th scope="row">' . esc_html__( 'Pinecone environment', 'met-plugin' ) . '</th>';
	       echo '<td><input type="text" name="met_plugin_pinecone_env" value="' . esc_attr( get_option('met_plugin_pinecone_env', '') ) . '" size="30" /></td></tr>';
	       echo '</table>';
	       submit_button();
	       echo '</form></div>';
       }
}
