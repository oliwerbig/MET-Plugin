<?php
/**
 * Fő plugin osztály
 */
class Met_Plugin {
	public function __construct() {
		add_action( 'init', [ $this, 'init_plugin' ] );
	}

	public function init_plugin() {
		// Plugin inicializálás
	}
}
