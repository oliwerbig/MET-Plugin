<?php
/**
 * Helper functions for the Gemini RAG module
 */
if ( ! defined( 'ABSPATH' ) ) exit;

use Google\Auth\ApplicationDefaultCredentials;

if (!defined('AGENT_CHAT_MODEL')) define('AGENT_CHAT_MODEL', 'gemini-2.5-flash-lite');
if (!defined('AGENT_EMBED_MODEL')) define('AGENT_EMBED_MODEL', 'gemini-embedding-001');

function agent_get_company_profile() {
    return "=== CÉGPROFIL: MET Industry Kft. ===\n" .
           "A MET Industry Kft. egy magyarországi, professzionális műhelyberendezésekre és ipari megoldásokra szakosodott vállalkozás. Fő célközönségük az autószerelő műhelyek, haszongépjármű-szervizek, ipari üzemek és a vasúti karbantartás.\n\n" .
           "FŐ TEVÉKENYSÉGEK:\n- Komponens vizsgáló próbapadok értékesítése.\n- Professzionális ipari szerszámok, szerszámgépek és diagnosztikai eszközök forgalmazása.\n- Ipari és járműmérlegek értékesítése, telepítése, szervizelése és hatósági hitelesítése.\n";
}

function agent_generate_sub_queries($original_question) {
    $company_profile = agent_get_company_profile();
    $prompt = "Te egy stratégiai tervező asszisztens vagy. A feladatod hogy a megadott cégprofil ismeretében egy általános felhasználói kérést lebonts 3-5 konkrét, kutatható al-témára. A kimenet KIZÁRÓLAG egy JSON tömb legyen stringekkel. Kérés: '{$original_question}'";

    $contents = [['role' => 'user', 'parts' => [['text' => $prompt]]]];
    $response_text = agent_generate_response($contents, '');
    if (!$response_text) return [];
    preg_match('/\[.*\]/s', $response_text, $matches);
    if (isset($matches[0])) {
        $json_array = json_decode($matches[0], true);
        return is_array($json_array) ? array_filter($json_array, 'is_string') : [];
    }
    return [];
}

function agent_get_gcloud_auth_token() {
    $cached_token = get_transient('agent_gcloud_auth_token');
    if ($cached_token) return $cached_token;

    $key_file_path = get_option('gemrag_service_account_path');
    if (empty($key_file_path) || !file_exists($key_file_path) || !class_exists('Google\\Auth\\ApplicationDefaultCredentials')) {
        return false;
    }
    try {
        putenv('GOOGLE_APPLICATION_CREDENTIALS=' . $key_file_path);
        $creds = ApplicationDefaultCredentials::getCredentials(['https://www.googleapis.com/auth/cloud-platform']);
        $token_data = $creds->fetchAuthToken();
        $access_token = $token_data['access_token'] ?? false;
        if ($access_token) {
            set_transient('agent_gcloud_auth_token', $access_token, 55 * MINUTE_IN_SECONDS);
            return $access_token;
        }
    } catch (Exception $e) {
        error_log('Tartalom Ügynök Auth Hiba: ' . $e->getMessage());
        return false;
    }
    return false;
}

function agent_get_embedding($text) {
    $project_id = get_option('gemrag_google_project_id');
    $access_token = agent_get_gcloud_auth_token();
    if (empty($project_id) || empty($access_token)) return false;

    $url = "https://us-central1-aiplatform.googleapis.com/v1/projects/{$project_id}/locations/us-central1/publishers/google/models/" . AGENT_EMBED_MODEL . ":predict";
    $body = ['instances' => [['content' => $text]]];
    $res = wp_remote_post($url, ['headers' => ['Authorization' => 'Bearer ' . $access_token, 'Content-Type' => 'application/json; charset=utf-8'], 'body' => wp_json_encode($body), 'timeout' => 20]);
    if (is_wp_error($res) || wp_remote_retrieve_response_code($res) !== 200) return false;
    $data = json_decode(wp_remote_retrieve_body($res), true);
    return $data['predictions'][0]['embeddings']['values'] ?? false;
}

function agent_query_pinecone($vector, $topK = 5) {
    $api_key = get_option('gemrag_pinecone_api_key');
    $host = get_option('gemrag_pinecone_host');
    if (empty($api_key) || empty($host)) return [];

    $api_url = "https://{$host}/query";
    $response = wp_remote_post($api_url, ['headers' => ['Api-Key' => $api_key, 'Content-Type' => 'application/json'], 'body' => json_encode(['vector' => $vector, 'topK' => $topK, 'includeMetadata' => true]), 'timeout' => 20]);
    if (is_wp_error($response) || wp_remote_retrieve_response_code($response) != 200) return [];
    $result = json_decode(wp_remote_retrieve_body($response), true);
    return $result['matches'] ?? [];
}

function agent_generate_response($contents, $system_instruction) {
    $project_id = get_option('gemrag_google_project_id');
    $access_token = agent_get_gcloud_auth_token();
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
