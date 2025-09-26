<?php
/**
 * Fő plugin osztály
 */

require_once plugin_dir_path( __FILE__ ) . 'Met_Plugin_Pinecone.php';
require_once plugin_dir_path( __FILE__ ) . 'Met_Plugin_Admin.php';


class Met_Plugin {
       public $pinecone;
       public $admin;

       public function __construct() {
              $this->pinecone = new Met_Plugin_Pinecone();
              $this->admin = new Met_Plugin_Admin();
              add_action( 'init', [ $this, 'init_plugin' ] );
       }

       public function init_plugin() {
              // Plugin inicializálás
       }
}
