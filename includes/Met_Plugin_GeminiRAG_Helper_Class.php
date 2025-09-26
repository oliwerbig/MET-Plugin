<?php
/**
 * Object-oriented helper for Gemini RAG functionality.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

use Google\Auth\ApplicationDefaultCredentials;

class Met_Plugin_GeminiRAG_Helper {
    /**
     * Return the company profile string used as system prompt.
     */
    public static function get_company_profile() {
        return agent_get_company_profile(); // keep single source in wrapper for now
    }

    public static function generate_sub_queries($original_question) {
        // Use the model to create sub-queries. Fallback to naive split.
        $prompt = "Te egy stratégiai tervező asszisztens vagy. A feladatod hogy a megadott cégprofil ismeretében egy általános felhasználói kérést lebonts 3-5 konkrét, kutatható al-témára. A kimenet KIZÁRÓLAG egy JSON tömb legyen stringekkel. Kérés: '{$original_question}'";
        $contents = [['role' => 'user', 'parts' => [['text' => $prompt]]]];
        $response_text = self::generate_response($contents, '');
        if ($response_text) {
            if (preg_match('/\[.*\]/s', $response_text, $matches)) {
                $json_array = json_decode($matches[0], true);
                if (is_array($json_array)) return array_filter($json_array, 'is_string');
            }
        }
        // Fallback
        $parts = preg_split('/[\.\?\n]/', $original_question);
        $results = [];
        foreach ($parts as $p) {
            $t = trim($p);
            if ($t !== '') $results[] = mb_substr($t, 0, 200);
            if (count($results) >= 5) break;
        }
        return $results;
    }

    public static function get_gcloud_auth_token() {
        $cached_token = get_transient('agent_gcloud_auth_token');
        if ($cached_token) return $cached_token;

        $key_file_path = get_option('gemrag_service_account_path');
        if (empty($key_file_path) || !file_exists($key_file_path) || !class_exists('Google\\Auth\\ApplicationDefaultCredentials')) {
            set_transient('agent_gcloud_auth_error', 'missing_credentials', 5 * MINUTE_IN_SECONDS);
            return false;
        }
        try {
            putenv('GOOGLE_APPLICATION_CREDENTIALS=' . $key_file_path);
            $creds = ApplicationDefaultCredentials::getCredentials(['https://www.googleapis.com/auth/cloud-platform']);
            $token_data = $creds->fetchAuthToken();
            $access_token = $token_data['access_token'] ?? false;
            if ($access_token) {
                set_transient('agent_gcloud_auth_token', $access_token, 55 * MINUTE_IN_SECONDS);
                delete_transient('agent_gcloud_auth_error');
                return $access_token;
            }
        } catch (Exception $e) {
            $msg = sprintf('MetPlugin Auth error: %s (keyfile: %s)', $e->getMessage(), $key_file_path);
            if (function_exists('error_log')) error_log($msg);
            set_transient('agent_gcloud_auth_error', 'exception', 5 * MINUTE_IN_SECONDS);
            return false;
        }
        return false;
    }

    public static function get_embedding($text) {
        $project_id = get_option('gemrag_google_project_id');
        $access_token = self::get_gcloud_auth_token();
        if (empty($project_id) || empty($access_token)) return false;

        $url = "https://us-central1-aiplatform.googleapis.com/v1/projects/{$project_id}/locations/us-central1/publishers/google/models/" . AGENT_EMBED_MODEL . ":predict";
        $body = ['instances' => [['content' => $text]]];
        $res = wp_remote_post($url, ['headers' => ['Authorization' => 'Bearer ' . $access_token, 'Content-Type' => 'application/json; charset=utf-8'], 'body' => wp_json_encode($body), 'timeout' => 20]);
        if (is_wp_error($res) || wp_remote_retrieve_response_code($res) !== 200) return false;
        $data = json_decode(wp_remote_retrieve_body($res), true);
        return $data['predictions'][0]['embeddings']['values'] ?? false;
    }

    public static function query_pinecone($vector, $topK = 5) {
        $api_key = get_option('gemrag_pinecone_api_key');
        $host = get_option('gemrag_pinecone_host');
        if (empty($api_key) || empty($host)) return [];

        $api_url = "https://{$host}/query";
        $response = wp_remote_post($api_url, ['headers' => ['Api-Key' => $api_key, 'Content-Type' => 'application/json'], 'body' => json_encode(['vector' => $vector, 'topK' => $topK, 'includeMetadata' => true]), 'timeout' => 20]);
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) != 200) return [];
        $result = json_decode(wp_remote_retrieve_body($response), true);
        return $result['matches'] ?? [];
    }

    public static function generate_response($contents, $system_instruction) {
        $project_id = get_option('gemrag_google_project_id');
        $access_token = self::get_gcloud_auth_token();
        if (empty($project_id) || empty($access_token)) return false;

        $url = "https://us-central1-aiplatform.googleapis.com/v1/projects/{$project_id}/locations/us-central1/publishers/google/models/" . AGENT_CHAT_MODEL . ":generateContent";
        $body = [
            'contents' => $contents,
            'generationConfig' => ['maxOutputTokens' => 8192, 'temperature' => 0.4]
        ];
        if (!empty($system_instruction)) {
            $body['systemInstruction'] = ['parts' => [['text' => $system_instruction]]];
        }

        $res = wp_remote_post($url, ['headers' => ['Authorization' => 'Bearer ' . $access_token, 'Content-Type' => 'application/json'], 'body' => wp_json_encode($body), 'timeout' => 60]);
        if (is_wp_error($res) || wp_remote_retrieve_response_code($res) !== 200) return false;
        $data = json_decode(wp_remote_retrieve_body($res), true);
        return $data['candidates'][0]['content']['parts'][0]['text'] ?? false;
    }
}

return true;
