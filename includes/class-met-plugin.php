<?php
/**
 * Fő plugin osztály
 */
require_once plugin_dir_path( __FILE__ ) . 'Met_Plugin_Pinecone.php';

class Met_Plugin {
       public $pinecone;

       public function __construct() {
	       $this->pinecone = new Met_Plugin_Pinecone();
	       add_action( 'init', [ $this, 'init_plugin' ] );
       }

       public function init_plugin() {
	       // Plugin inicializálás
       }
}
