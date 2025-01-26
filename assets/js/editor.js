jQuery(document).ready(function($) {
    const $promptField = $('#digicontent-prompt');
    const $modelSelect = $('#digicontent-model');
    const $generateButton = $('#digicontent-generate');
    const $templateSelect = $('#digicontent-template');

    // Handle template selection
    $templateSelect.on('change', function() {
        const templateId = $(this).val();
        if (!templateId) {
            $promptField.val('');
            return;
        }

        $promptField.prop('disabled', true);
        $generateButton.prop('disabled', true);

        // Fetch template data
        $.ajax({
            url: digiContentEditor.ajaxUrl,
            type: 'POST',
            data: {
                action: 'get_template_content',
                nonce: digiContentEditor.nonce,
                template_id: templateId
            },
            success: function(response) {
                if (response.success && response.data.prompt) {
                    $promptField.val(response.data.prompt);
                } else {
                    alert(digiContentEditor.error);
                }
            },
            error: function() {
                alert(digiContentEditor.error);
            },
            complete: function() {
                $promptField.prop('disabled', false);
                $generateButton.prop('disabled', false);
            }
        });
    });

    // Handle content generation
    $generateButton.on('click', function() {
        const prompt = $promptField.val();
        const model = $modelSelect.val();

        if (!prompt) {
            alert(digiContentEditor.emptyPrompt || 'Please enter a prompt');
            return;
        }

        const originalText = $(this).text();
        $(this).prop('disabled', true).text(digiContentEditor.generating);
        $promptField.prop('disabled', true);
        $templateSelect.prop('disabled', true);
        $modelSelect.prop('disabled', true);

        $.ajax({
            url: digiContentEditor.ajaxUrl,
            type: 'POST',
            data: {
                action: 'generate_ai_content',
                nonce: digiContentEditor.nonce,
                prompt: prompt,
                model: model
            },
            success: function(response) {
                if (response.success && response.data.content) {
                    // Insert content into the editor
                    if (typeof wp !== 'undefined' && wp.data && wp.data.dispatch('core/editor')) {
                        wp.data.dispatch('core/editor').insertBlocks(
                            wp.blocks.parse(response.data.content)
                        );
                    } else {
                        // Fallback for classic editor
                        const editor = tinyMCE.get('content');
                        if (editor) {
                            editor.setContent(response.data.content);
                        }
                    }
                } else {
                    alert(digiContentEditor.error);
                }
            },
            error: function() {
                alert(digiContentEditor.error);
            },
            complete: function() {
                $generateButton.prop('disabled', false).text(originalText);
                $promptField.prop('disabled', false);
                $templateSelect.prop('disabled', false);
                $modelSelect.prop('disabled', false);
            }
        });
    });
});