(function($){
    $(document).ready(function(){
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

            $.post(MetGemini.ajax_url, { action: 'agent_get_response', security: MetGemini.nonce, question: question })
            .done(function(response){
                if (response.success) {
                    displayMessage('bot', response.data.answer, response.data.context);
                } else {
                    displayMessage('bot', 'Hiba: ' + (response.data && response.data.message ? response.data.message : 'Ismeretlen hiba'));
                }
            })
            .fail(function(){
                displayMessage('bot', 'Hiba: Ismeretlen szerveroldali hiba.');
            })
            .always(function(){
                chatSend.prop('disabled', false).text('Küldés');
            });
        }

        chatSend.on('click', sendMessage);
        chatInput.on('keypress', function(e) { if (e.which === 13 && !e.shiftKey) { e.preventDefault(); sendMessage(); } });
    });
})(jQuery);