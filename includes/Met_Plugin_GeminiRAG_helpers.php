<?php
/**
 * Helper functions for the Gemini RAG module
 */
if ( ! defined( 'ABSPATH' ) ) exit;

use Google\Auth\ApplicationDefaultCredentials;

if (!defined('AGENT_CHAT_MODEL')) define('AGENT_CHAT_MODEL', 'gemini-2.5-flash-lite');
if (!defined('AGENT_EMBED_MODEL')) define('AGENT_EMBED_MODEL', 'gemini-embedding-001');

/**
 * Wrapper for backward compatibility — returns the company profile string.
 */
function agent_get_company_profile() {
    return "=== CÉGPROFIL: MET Industry Kft. ===\n" .
           "A MET Industry Kft. egy magyarországi, professzionális műhelyberendezésekre és ipari megoldásokra szakosodott vállalkozás. Fő célközönségük az autószerelő műhelyek, haszongépjármű-szervizek, ipari üzemek és a vasúti karbantartás.\n\n" .
           "FŐ TEVÉKENYSÉGEK:\n- Komponens vizsgáló próbapadok értékesítése.\n- Professzionális ipari szerszámok, szerszámgépek és diagnosztikai eszközök forgalmazása.\n- Ipari és járműmérlegek értékesítése, telepítése, szervizelése és hatósági hitelesítése.\n";
}

/**
 * Wrapper that generates sub-queries — delegates to the procedural implementation for now.
 * Keeps signature identical for compatibility.
 */
function agent_generate_sub_queries($original_question) {
    // Delegate to class-level implementation if available
    if (class_exists('Met_Plugin_GeminiRAG_Helper')) {
        return Met_Plugin_GeminiRAG_Helper::generate_sub_queries($original_question);
    }
    // Fallback: simple naive split (best effort)
    $parts = preg_split('/[\.|\?|\n]/', $original_question);
    $results = [];
    foreach ($parts as $p) {
        $t = trim($p);
        if ($t !== '') $results[] = mb_substr($t, 0, 200);
        if (count($results) >= 5) break;
    }
    return $results;
}

/**
 * Retrieves an access token for Google Cloud using Application Default Credentials.
 * Returns the access token string or false on failure.
 */
function agent_get_gcloud_auth_token() {
    if (class_exists('Met_Plugin_GeminiRAG_Helper')) {
        return Met_Plugin_GeminiRAG_Helper::get_gcloud_auth_token();
    }
    return false;
}

function agent_get_embedding($text) {
    if (class_exists('Met_Plugin_GeminiRAG_Helper')) {
        return Met_Plugin_GeminiRAG_Helper::get_embedding($text);
    }
    return false;
}

function agent_query_pinecone($vector, $topK = 5) {
    if (class_exists('Met_Plugin_GeminiRAG_Helper')) {
        return Met_Plugin_GeminiRAG_Helper::query_pinecone($vector, $topK);
    }
    return [];
}

function agent_generate_response($contents, $system_instruction) {
    if (class_exists('Met_Plugin_GeminiRAG_Helper')) {
        return Met_Plugin_GeminiRAG_Helper::generate_response($contents, $system_instruction);
    }
    return false;
}
