jQuery(document).ready(function($) {
    const $prompt = $('#digicontent-prompt');
    const $model = $('#digicontent-model');
    const $generateBtn = $('#digicontent-generate');
    const editor = wp.data.dispatch('core/editor');

    $generateBtn.on('click', function() {
        const prompt = $prompt.val().trim();
        const model = $model.val();

        if (!prompt) {
            alert('Please enter a prompt');
            return;
        }

        const $btn = $(this);
        const originalText = $btn.text();
        $btn.prop('disabled', true).text(digiContentEditor.generating);

        $.ajax({
            url: digiContentEditor.ajaxUrl,
            type: 'POST',
            data: {
                action: 'generate_ai_content',
                prompt: prompt,
                model: model,
                _ajax_nonce: digiContentEditor.nonce
            },
            success: function(response) {
                if (response.success && response.data.content) {
                    editor.insertBlocks(
                        wp.blocks.createBlock('core/paragraph', {
                            content: response.data.content
                        })
                    );
                    $prompt.val('');
                } else {
                    alert(response.data || digiContentEditor.error);
                }
            },
            error: function() {
                alert(digiContentEditor.error);
            },
            complete: function() {
                $btn.prop('disabled', false).text(originalText);
            }
        });
    });
});