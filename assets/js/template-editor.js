(function($) {
    'use strict';

    const TemplateEditor = {
        init: function() {
            this.bindEvents();
            this.initVariableAutocomplete();
        },

        bindEvents: function() {
            $('.insert-variable-button').on('click', this.handleVariableButtonClick);
            $('.variable-chip').on('click', this.handleVariableChipClick);
            $('#template-prompt').on('input', this.handleTemplateInput);
            $('#digicontent-new-template-form').on('submit', this.handleTemplateSubmit);
            $('.delete-template').on('click', this.handleTemplateDelete);
        },

        showNotification: function(message, type = 'success') {
            const notice = $('#template-notice');
            notice.removeClass().addClass(`notice notice-${type}`);
            notice.find('p').text(message);
            notice.slideDown();
            
            // Auto hide after 3 seconds
            setTimeout(() => {
                notice.slideUp();
            }, 3000);
        },

        handleTemplateSubmit: function(e) {
            e.preventDefault();
            const form = $(this);
            const submitButton = form.find('button[type="submit"]');
            const formData = {
                name: $('#template-name').val().trim(),
                category: $('#template-category').val(),
                prompt: $('#template-prompt').val().trim(),
                variables: $('#template-prompt').val().match(/\(\(([^)]+)\)\)/g)?.map(v => v.replace(/[()]/g, '')) || []
            };

            // Validate form data
            if (!formData.name || !formData.prompt) {
                TemplateEditor.showNotification('Please fill in all required fields', 'error');
                return;
            }

            // Disable submit button and show loading state
            submitButton.prop('disabled', true).text('Saving...');

            // Save template via REST API
            $.ajax({
                url: wpApiSettings.root + 'digicontent/v1/templates',
                method: 'POST',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', wpApiSettings.nonce);
                },
                data: JSON.stringify(formData),
                contentType: 'application/json',
                success: function(response) {
                    if (response && response.id) {
                        // Show success message
                        TemplateEditor.showNotification('Template saved successfully!');
                        
                        // Reset form
                        form[0].reset();
                        $('.variable-preview').hide();

                        // Reload after a short delay to show the success message
                        setTimeout(function() {
                            window.location.reload();
                        }, 1500);
                    } else {
                        throw new Error('Invalid response from server');
                    }
                },
                error: function(xhr) {
                    const errorMessage = xhr.responseJSON?.message || 'An error occurred while saving the template';
                    TemplateEditor.showNotification(errorMessage, 'error');
                },
                complete: function() {
                    submitButton.prop('disabled', false).text('Save Template');
                }
            });
        },

        handleTemplateDelete: function() {
            if (!confirm('Are you sure you want to delete this template?')) {
                return;
            }

            const button = $(this);
            const templateId = button.data('id');
            button.prop('disabled', true);

            $.ajax({
                url: wpApiSettings.root + `digicontent/v1/templates/${templateId}`,
                method: 'DELETE',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', wpApiSettings.nonce);
                },
                success: function() {
                    TemplateEditor.showNotification('Template deleted successfully!');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                },
                error: function(xhr) {
                    const errorMessage = xhr.responseJSON?.message || 'Error deleting template';
                    TemplateEditor.showNotification(errorMessage, 'error');
                    button.prop('disabled', false);
                }
            });
        },

        handleVariableButtonClick: function(e) {
            e.preventDefault();
            const variables = ['topic', 'tone', 'length', 'style', 'keywords'];
            const textarea = $('#template-prompt');
            const pos = textarea[0].selectionStart;
            const content = textarea.val();

            // Show variable selection dialog
            const dialog = $('<div class="variable-dialog"></div>');
            variables.forEach(variable => {
                dialog.append(
                    $('<button class="button"></button>')
                        .text(variable)
                        .on('click', function() {
                            const insertion = '((' + variable + '))';
                            const newContent = content.slice(0, pos) + insertion + content.slice(pos);
                            textarea.val(newContent);
                            dialog.dialog('close');
                            textarea.focus();
                        })
                );
            });

            dialog.dialog({
                title: 'Insert Variable',
                modal: true,
                width: 300,
                close: function() {
                    $(this).dialog('destroy').remove();
                }
            });
        },

        handleVariableChipClick: function() {
            const variable = $(this).data('variable');
            const textarea = $('#template-prompt');
            const pos = textarea[0].selectionStart;
            const content = textarea.val();
            const insertion = '((' + variable + '))';
            const newContent = content.slice(0, pos) + insertion + content.slice(pos);
            textarea.val(newContent).focus();
        },

        handleTemplateInput: function() {
            const content = $(this).val();
            const preview = $('.variable-preview');
            const previewContent = $('.preview-content');
            
            if (content.includes('((') && content.includes('))')) {
                preview.show();
                previewContent.html(content.replace(/\(\(([^)]+)\)\)/g, '<span class="variable-highlight">$1</span>'));
            } else {
                preview.hide();
            }
        },

        initVariableAutocomplete: function() {
            const textarea = $('#template-prompt');
            let lastPos = 0;

            textarea.on('keyup', function(e) {
                const pos = this.selectionStart;
                const content = $(this).val();

                if (content.slice(pos - 2, pos) === '((') {
                    TemplateEditor.showVariableSuggestions(textarea);
                }

                lastPos = pos;
            });
        },

        showVariableSuggestions: function(textarea) {
            const variables = ['topic', 'tone', 'length', 'style', 'keywords'];
            const pos = textarea[0].selectionStart;
            const content = textarea.val();

            // Create suggestion dropdown
            const dropdown = $('<div class="variable-suggestions"></div>');
            variables.forEach(variable => {
                dropdown.append(
                    $('<div class="suggestion-item"></div>')
                        .text(variable)
                        .on('click', function() {
                            const newContent = content.slice(0, pos) + variable + '))' + content.slice(pos);
                            textarea.val(newContent);
                            dropdown.remove();
                            textarea.focus();
                        })
                );
            });

            // Position and show dropdown
            const coords = this.getCaretCoordinates(textarea[0], pos);
            dropdown.css({
                top: coords.top + 20 + 'px',
                left: coords.left + 'px'
            }).appendTo(textarea.parent());

            // Close dropdown when clicking outside
            $(document).one('click', function() {
                dropdown.remove();
            });
        },

        getCaretCoordinates: function(element, position) {
            const div = document.createElement('div');
            const styles = getComputedStyle(element);
            const properties = [
                'direction', 'boxSizing', 'width', 'height', 'overflowX', 'overflowY',
                'borderTopWidth', 'borderRightWidth', 'borderBottomWidth', 'borderLeftWidth',
                'paddingTop', 'paddingRight', 'paddingBottom', 'paddingLeft',
                'fontStyle', 'fontVariant', 'fontWeight', 'fontStretch', 'fontSize',
                'fontSizeAdjust', 'lineHeight', 'fontFamily', 'textAlign', 'textTransform',
                'textIndent', 'textDecoration', 'letterSpacing', 'wordSpacing'
            ];

            div.style.position = 'absolute';
            div.style.visibility = 'hidden';
            properties.forEach(prop => div.style[prop] = styles[prop]);
            div.textContent = element.value.substring(0, position);

            const span = document.createElement('span');
            span.textContent = element.value.substring(position) || '.';
            div.appendChild(span);

            document.body.appendChild(div);
            const coordinates = {
                top: span.offsetTop + parseInt(styles.borderTopWidth),
                left: span.offsetLeft + parseInt(styles.borderLeftWidth)
            };
            document.body.removeChild(div);

            return coordinates;
        }
    };

    $(document).ready(function() {
        TemplateEditor.init();
    });

})(jQuery);