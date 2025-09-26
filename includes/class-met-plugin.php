<?php
/**
 * Fő plugin osztály
 */

require_once plugin_dir_path( __FILE__ ) . 'Met_Plugin_Pinecone.php';
require_once plugin_dir_path( __FILE__ ) . 'Met_Plugin_Admin.php';
require_once plugin_dir_path( __FILE__ ) . 'Met_Plugin_GeminiRAG.php';
require_once plugin_dir_path( __FILE__ ) . 'Met_Plugin_GeminiRAG_helpers.php';


class Met_Plugin {
       public $pinecone;
       public $admin;
       public $gemini;

       public function __construct() {
              $this->pinecone = new Met_Plugin_Pinecone();
              $this->admin = new Met_Plugin_Admin();
              // Gemini RAG admin/chat
              if ( class_exists('Met_Plugin_GeminiRAG') ) {
                  $this->gemini = new Met_Plugin_GeminiRAG();
              }
              add_action( 'init', [ $this, 'init_plugin' ] );
       }

       public function init_plugin() {
              // Plugin inicializálás
       }
}
