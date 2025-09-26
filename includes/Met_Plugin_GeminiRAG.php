<?php
/**
 * Gemini RAG integration (admin UI + AJAX)
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class Met_Plugin_GeminiRAG {
    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_pages']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('wp_ajax_agent_get_response', [$this, 'agent_handle_ajax_request']);
    }

    public function add_admin_pages() {
        add_menu_page('Tartalom Ügynök', 'Tartalom Ügynök', 'manage_options', 'content-agent-main', [$this, 'render_chat_page'], 'dashicons-edit-page', 26);
        add_submenu_page('content-agent-main', 'Ügynök Beállítások', 'Beállítások', 'manage_options', 'content-agent-settings', [$this, 'render_settings_page']);
    }

    public function register_settings() {
        register_setting('agent_settings_group', 'gemrag_google_project_id');
        register_setting('agent_settings_group', 'gemrag_service_account_path');
        register_setting('agent_settings_group', 'gemrag_pinecone_api_key');
        register_setting('agent_settings_group', 'gemrag_pinecone_host');
    }

    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Tartalom Ügynök - Beállítások', 'met-plugin'); ?></h1>
            <form action="options.php" method="post">
                <?php settings_fields('agent_settings_group'); ?>
                <table class="form-table">
                    <tr valign="top"><th scope="row"><?php _e('Google Cloud Project ID', 'met-plugin'); ?></th><td><input type="text" name="gemrag_google_project_id" size="60" value="<?php echo esc_attr(get_option('gemrag_google_project_id')); ?>" /></td></tr>
                    <tr valign="top"><th scope="row"><?php _e('Service Account JSON Fájl Elérési Útja', 'met-plugin'); ?></th><td><input type="text" name="gemrag_service_account_path" size="60" value="<?php echo esc_attr(get_option('gemrag_service_account_path')); ?>" placeholder="/var/www/private/service-account.json" /></td></tr>
                    <tr valign="top"><th scope="row"><?php _e('Pinecone API Key', 'met-plugin'); ?></th><td><input type="password" name="gemrag_pinecone_api_key" size="60" value="<?php echo esc_attr(get_option('gemrag_pinecone_api_key')); ?>" /></td></tr>
                    <tr valign="top"><th scope="row"><?php _e('Pinecone Host', 'met-plugin'); ?></th><td><input type="text" name="gemrag_pinecone_host" size="60" value="<?php echo esc_attr(get_option('gemrag_pinecone_host')); ?>" placeholder="index-name-12345.svc.host.pinecone.io" /></td></tr>
                </table>
                <?php submit_button(__('Beállítások Mentése', 'met-plugin')); ?>
            </form>
        </div>
        <?php
    }

    public function render_chat_page() {
        // Use the provided HTML/JS from the user's snippet but keep it minimal here and enqueue scripts properly in real plugin
        ?>
        <div class="wrap">
            <h1>Intelligens Tartalom Ügynök</h1>
            <p>Tegyél fel egy tág, stratégiai kérdést. Az ügynök először kutatási tervet készít, majd végrehajtja a kutatást, és végül szintetizálja a választ.</p>
            <div id="agent-chat-container">
                <div id="chat-history">
                    <div class="chat-message bot">
                        <div class="message-content">Üdvözöllek! Én a MET Industry tartalomstratégiai ügynöke vagyok. Hogyan segíthetek ma a tartalomfejlesztésben?</div>
                    </div>
                </div>
                <div id="chat-input-area">
                    <textarea id="chat-input" placeholder="Például: 'Készíts egy tartalomstratégiai javaslatot a következő negyedévre...'" rows="3"></textarea>
                    <button id="chat-send" class="button button-primary">Küldés</button>
                </div>
            </div>
        </div>
        <style>
            #agent-chat-container { max-width: 900px; margin: 20px 0; border: 1px solid #ccd0d4; border-radius: 4px; display: flex; flex-direction: column; height: 70vh; background-color: #fff; }
            #chat-history { flex-grow: 1; overflow-y: auto; padding: 15px; background-color: #f7f7f7; }
            .chat-message { padding: 10px 15px; border-radius: 18px; margin-bottom: 15px; max-width: 90%; word-wrap: break-word; line-height: 1.5; }
            .user { background-color: #f0f0f0; color: #333; margin-left: auto; border-bottom-right-radius: 5px; }
            .bot { background-color: #e5f5ff; color: #005a87; margin-right: auto; border-bottom-left-radius: 5px; }
        </style>
        <script>
        jQuery(document).ready(function($) {
            const chatHistory = $('#chat-history');
            const chatInput = $('#chat-input');
            const chatSend = $('#chat-send');

            function displayMessage(role, content, context = null) {
                let messageHtml = `<div class="chat-message ${role}"><div class="message-content">${content}</div>`;
                if (role === 'bot' && context) {
                    messageHtml += `<div class="agent-context-details"><details><summary>Az ügynök gondolatmenete</summary><pre>${$('<div>').text(context).html()}</pre></details></div>`;
                }
                messageHtml += `</div>`;
                chatHistory.append(messageHtml);
                chatHistory.scrollTop(chatHistory.prop("scrollHeight"));
            }

            function sendMessage() {
                const question = chatInput.val().trim();
                if (question === "") return;
                displayMessage('user', question.replace(/\n/g, '<br>'));
                chatInput.val('');
                chatSend.prop('disabled', true).html('<span class="spinner"></span>');

                $.post(ajaxurl, { action: 'agent_get_response', security: '<?php echo wp_create_nonce('agent_nonce'); ?>', question: question }, function(response) {
                    if (response.success) {
                        displayMessage('bot', response.data.answer, response.data.context);
                    } else {
                        displayMessage('bot', 'Hiba: ' + response.data.message);
                    }
                }).fail(function(){
                    displayMessage('bot', 'Hiba: Ismeretlen szerveroldali hiba.');
                }).always(function(){
                    chatSend.prop('disabled', false).text('Küldés');
                });
            }

            chatSend.on('click', sendMessage);
            chatInput.on('keypress', function(e) { if (e.which === 13 && !e.shiftKey) { e.preventDefault(); sendMessage(); } });
        });
        </script>
        <?php
    }

    /* --------- AJAX handler (calls to the helper functions are delegated to global functions which we expect to exist in plugin) ---------- */
    public function agent_handle_ajax_request() {
        check_ajax_referer('agent_nonce', 'security');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Nincs jogosultságod!']);
        }

        $original_question = sanitize_text_field($_POST['question'] ?? '');
        if (empty($original_question)) wp_send_json_error(['message' => 'A kérdés nem lehet üres.']);

        $sub_queries = agent_generate_sub_queries($original_question);
        if (empty($sub_queries)) $sub_queries = [$original_question];

        $full_context_text = "=== GONDOLATMENET ÉS KUTATÁSI TERV ===\n";
        $full_context_text .= "Az eredeti kérdés megválaszolásához a cégprofil alapján a következő témákat fogom vizsgálni a tudásbázisban:\n- " . implode("\n- ", $sub_queries) . "\n\n";

        foreach ($sub_queries as $sub_query) {
            $full_context_text .= "--- KUTATÁS: \"{$sub_query}\" ---\n";
            $vector = agent_get_embedding($sub_query);
            if ($vector) {
                $context_results = agent_query_pinecone($vector, 3);
                if (!empty($context_results)) {
                    foreach ($context_results as $result) {
                        $full_context_text .= "Találat: " . ($result['metadata']['title'] ?? 'Ismeretlen') . " (Relevancia: " . round($result['score'], 2) . ")\n> " . ($result['metadata']['text'] ?? 'N/A') . "\n\n";
                    }
                } else {
                    $full_context_text .= "> Ehhez a témához nem találtam konkrét információt a tudásbázisban.\n\n";
                }
            }
        }

        $system_prompt = agent_get_company_profile() . "\n\n--- FELADATLEÍRÁS ---\nTe egy proaktív, vezető tartalomstratéga vagy. A személyazonosságod a fenti cégprofil. A feladatod, hogy egy átfogó kutatás eredményeit szintetizáld, és ez alapján adj egy magas szintű, stratégiai választ az adminisztrátornak. A válaszod legyen strukturált, könnyen áttekinthető és azonnal használható.";
        $final_prompt = "Elvégeztem egy többlépcsős kutatást a tudásbázisban. Az alábbiakban a teljes gondolatmenetem és a kutatásom eredményei láthatók.\n\n" . $full_context_text . "=== KUTATÁS VÉGE ===\n\nEREDETI KÉRÉS: \"{$original_question}\"\n\nFELADAT: A fenti, részletes kutatási eredmények és a cégprofilod alapján szintetizálj egy teljes körű, stratégiai választ. Ne csak listázd a tényeket, hanem alakíts ki belőlük egy koherens tervet, javaslatot vagy vázlatot!";

        $contents = [['role' => 'user', 'parts' => [['text' => $final_prompt]]]];
        $raw_answer = agent_generate_response($contents, $system_prompt);
        if (!$raw_answer) wp_send_json_error(['message' => 'Hiba a válasz generálása során.']);

        $formatted_answer = do_shortcode("[markdown]{$raw_answer}[/markdown]");

        wp_send_json_success([
            'answer'  => $formatted_answer,
            'context' => $full_context_text
        ]);
    }
}

new Met_Plugin_GeminiRAG();
