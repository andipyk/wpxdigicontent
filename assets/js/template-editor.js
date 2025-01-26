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

            // Update preview
            TemplateEditor.updatePreview(newContent);
        },

        handleTemplateInput: function() {
            TemplateEditor.updatePreview($(this).val());
        },

        updatePreview: function(content) {
            const preview = $('.variable-preview');
            const previewContent = $('.preview-content');
            const variables = {};

            // Extract variables from content
            const matches = content.match(/\(\(([^)]+)\)\)/g) || [];
            if (matches.length > 0) {
                preview.show();
                let previewText = content;

                // Replace variables with sample values
                matches.forEach(match => {
                    const variable = match.replace(/[()]/g, '');
                    if (!variables[variable]) {
                        variables[variable] = this.getSampleValue(variable);
                    }
                    previewText = previewText.replace(match, variables[variable]);
                });

                previewContent.html(previewText);
            } else {
                preview.hide();
            }
        },

        getSampleValue: function(variable) {
            const samples = {
                topic: 'Artificial Intelligence',
                tone: 'professional',
                length: '1000 words',
                style: 'informative',
                keywords: 'AI, machine learning, future'
            };
            return samples[variable] || `[${variable}]`;
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

        // Handle template form submission
        $('#digicontent-new-template-form').on('submit', function(e) {
            e.preventDefault();
            const formData = {
                name: $('#template-name').val(),
                category: $('#template-category').val(),
                prompt: $('#template-prompt').val(),
                variables: $('#template-prompt').val().match(/\(\(([^)]+)\)\)/g)?.map(v => v.replace(/[()]/g, '')) || []
            };

            // Save template via REST API
            $.ajax({
                url: '/wp-json/digicontent/v1/templates',
                method: 'POST',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', wpApiSettings.nonce);
                },
                data: JSON.stringify(formData),
                contentType: 'application/json',
                success: function(response) {
                    // Reload the page to show the new template
                    window.location.reload();
                },
                error: function(xhr) {
                    alert('Error saving template: ' + (xhr.responseJSON?.message || 'Unknown error'));
                }
            });
        });

        // Handle template deletion
        $('.delete-template').on('click', function() {
            if (!confirm('Are you sure you want to delete this template?')) {
                return;
            }

            const templateId = $(this).data('id');
            $.ajax({
                url: `/wp-json/digicontent/v1/templates/${templateId}`,
                method: 'DELETE',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', wpApiSettings.nonce);
                },
                success: function() {
                    window.location.reload();
                },
                error: function(xhr) {
                    alert('Error deleting template: ' + (xhr.responseJSON?.message || 'Unknown error'));
                }
            });
        });
    });

})(jQuery);