<?php
/**
 * Pinecone API kommunikációs osztály
 */
class Met_Plugin_Pinecone {
	private $api_key;
	private $environment;

	public function __construct() {
		$this->api_key = get_option('met_plugin_pinecone_api_key', '');
		$this->environment = get_option('met_plugin_pinecone_env', '');
	}

	public function is_configured() {
		return !empty($this->api_key) && !empty($this->environment);
	}

	public function query($endpoint, $data = [], $method = 'POST') {
		if (!$this->is_configured()) {
			return new WP_Error('pinecone_not_configured', __('Pinecone API nincs konfigurálva', 'met-plugin'));
		}
		$url = 'https://' . $this->environment . '.pinecone.io/' . ltrim($endpoint, '/');
		$args = [
			'headers' => [
				'Api-Key' => $this->api_key,
				'Content-Type' => 'application/json',
			],
			'body' => !empty($data) ? json_encode($data) : null,
			'timeout' => 20,
			'blocking' => true,
			'headers' => [
				'Api-Key' => $this->api_key,
				'Content-Type' => 'application/json',
			],
			'method' => $method,
		];
		$response = wp_remote_request($url, $args);
		if (is_wp_error($response)) {
			return $response;
		}
		return json_decode(wp_remote_retrieve_body($response), true);
	}
}
