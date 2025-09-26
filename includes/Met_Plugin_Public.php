<?php
/**
 * Publikus oldal logika
 */
class Met_Plugin_Public {
	public function __construct() {
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
	}

	public function enqueue_scripts() {
		// Publikus assetek betöltése
	}
}
