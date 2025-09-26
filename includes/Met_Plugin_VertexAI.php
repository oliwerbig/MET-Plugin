<?php
/**
 * Vertex AI kommunikációs osztály Google service account JSON-nal
 */

use Google\Auth\Credentials\ServiceAccountCredentials;
use GuzzleHttp\Client;

class Met_Plugin_VertexAI {
	private $credentials;
	private $project_id;

	public function __construct($json_path) {
		if (!file_exists($json_path)) {
			throw new Exception('Service account JSON nem található: ' . $json_path);
		}
	       $this->credentials = new ServiceAccountCredentials(
		       $json_path,
		       ['scope' => 'https://www.googleapis.com/auth/cloud-platform']
	       );
	       $json = json_decode(file_get_contents($json_path), true);
	       $this->project_id = $json['project_id'] ?? '';
	}


       public function get_access_token() {
	       $tokenArr = $this->credentials->fetchAuthToken();
	       return $tokenArr['access_token'] ?? null;
       }

	// Példa Vertex AI REST API hívásra
       public function call_vertex_api($endpoint, $body = []) {
	       $access_token = $this->get_access_token();
	       if (!$access_token) {
		       throw new \Exception('Nem sikerült access tokent szerezni.');
	       }
	       $url = 'https://us-central1-aiplatform.googleapis.com/v1/' . ltrim($endpoint, '/');
	       $client = new Client();
	       $response = $client->post($url, [
		       'headers' => [
			       'Authorization' => 'Bearer ' . $access_token,
			       'Content-Type' => 'application/json',
		       ],
		       'json' => $body,
	       ]);
	       return json_decode($response->getBody(), true);
       }

	public function get_project_id() {
		return $this->project_id;
	}
}
