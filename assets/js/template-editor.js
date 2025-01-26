'use strict';

class TemplateEditor {
    constructor() {
        this.init();
    }

    init() {
        this.bindEvents();
        this.initVariableAutocomplete();
    }

    bindEvents() {
        document.querySelectorAll('.insert-variable-button').forEach(btn => 
            btn.addEventListener('click', this.handleVariableButtonClick.bind(this)));
        
        document.querySelectorAll('.variable-chip').forEach(chip => 
            chip.addEventListener('click', this.handleVariableChipClick.bind(this)));
        
        const templatePrompt = document.getElementById('template-prompt');
        if (templatePrompt) {
            templatePrompt.addEventListener('input', this.handleTemplateInput.bind(this));
        }
        
        const templateForm = document.getElementById('digicontent-new-template-form');
        if (templateForm) {
            templateForm.addEventListener('submit', this.handleTemplateSubmit.bind(this));
        }
        
        document.querySelectorAll('.delete-template').forEach(btn => 
            btn.addEventListener('click', this.handleTemplateDelete.bind(this)));
    }

    showNotification(message, type = 'success') {
        const notice = document.getElementById('template-notice');
        if (!notice) return;

        notice.className = `notice notice-${type}`;
        const p = notice.querySelector('p') || document.createElement('p');
        p.textContent = message;
        if (!p.parentNode) notice.appendChild(p);

        notice.style.display = 'block';
        
        setTimeout(() => {
            notice.style.display = 'none';
        }, 3000);
    }

    async handleTemplateSubmit(e) {
        e.preventDefault();
        const form = e.target;
        const submitButton = form.querySelector('button[type="submit"]');
        const formData = {
            name: document.getElementById('template-name').value.trim(),
            category: document.getElementById('template-category').value,
            prompt: document.getElementById('template-prompt').value.trim(),
            variables: this.extractVariables(document.getElementById('template-prompt').value)
        };

        if (!formData.name || !formData.prompt) {
            this.showNotification('Please fill in all required fields', 'error');
            return;
        }

        try {
            submitButton.disabled = true;
            submitButton.textContent = 'Saving...';

            const response = await fetch(wpApiSettings.root + 'digicontent/v1/templates', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': wpApiSettings.nonce
                },
                body: JSON.stringify(formData)
            });

            const data = await response.json();
            if (data && data.id) {
                this.showNotification('Template saved successfully!');
                form.reset();
                document.querySelector('.variable-preview').style.display = 'none';
                setTimeout(() => window.location.reload(), 1500);
            } else {
                throw new Error('Invalid response from server');
            }
        } catch (error) {
            const errorMessage = error.message || 'An error occurred while saving the template';
            this.showNotification(errorMessage, 'error');
        } finally {
            submitButton.disabled = false;
            submitButton.textContent = 'Save Template';
        }
    }

    async handleTemplateDelete(e) {
        const button = e.target;
        const templateId = button.dataset.id;

        if (!confirm('Are you sure you want to delete this template?')) {
            return;
        }

        try {
            button.disabled = true;

            const response = await fetch(`${wpApiSettings.root}digicontent/v1/templates/${templateId}`, {
                method: 'DELETE',
                headers: {
                    'X-WP-Nonce': wpApiSettings.nonce
                }
            });

            if (!response.ok) throw new Error('Error deleting template');
            
            this.showNotification('Template deleted successfully!');
            setTimeout(() => window.location.reload(), 1500);
        } catch (error) {
            const errorMessage = error.message || 'Error deleting template';
            this.showNotification(errorMessage, 'error');
        } finally {
            button.disabled = false;
        }
    }

    handleVariableButtonClick(e) {
        e.preventDefault();
        const variables = ['topic', 'tone', 'length', 'style', 'keywords'];
        const textarea = document.getElementById('template-prompt');
        const pos = textarea.selectionStart;
        const content = textarea.value;
    
        const dialog = this.createVariableDialog(variables, textarea, pos, content);
        document.body.appendChild(dialog);
    }

    createVariableDialog(variables, textarea, pos, content) {
        const dialog = document.createElement('div');
        dialog.className = 'variable-dialog';
        Object.assign(dialog.style, {
            position: 'fixed',
            top: '50%',
            left: '50%',
            transform: 'translate(-50%, -50%)',
            background: '#fff',
            padding: '20px',
            boxShadow: '0 2px 10px rgba(0,0,0,0.1)',
            zIndex: '100000'
        });
    
        const title = document.createElement('h3');
        title.textContent = 'Insert Variable';
        dialog.appendChild(title);
    
        variables.forEach(variable => {
            const button = document.createElement('button');
            button.className = 'button';
            button.textContent = variable;
            button.addEventListener('click', () => {
                const insertion = ` ((${variable})) `;
                textarea.value = content.slice(0, pos) + insertion + content.slice(pos);
                dialog.remove();
                textarea.focus();
            });
            dialog.appendChild(button);
        });
    
        const closeBtn = document.createElement('button');
        closeBtn.className = 'button';
        closeBtn.textContent = '×';
        Object.assign(closeBtn.style, {
            position: 'absolute',
            top: '10px',
            right: '10px'
        });
        closeBtn.addEventListener('click', () => dialog.remove());
        dialog.appendChild(closeBtn);
    
        return dialog;
    }

    handleVariableChipClick(e) {
        const variable = e.target.dataset.variable;
        const textarea = document.getElementById('template-prompt');
        const pos = textarea.selectionStart;
        const content = textarea.value;
        const insertion = `((${variable}))`;
        textarea.value = content.slice(0, pos) + insertion + content.slice(pos);
        textarea.focus();
    }

    handleTemplateInput(e) {
        const content = e.target.value;
        const preview = document.querySelector('.variable-preview');
        const previewContent = document.querySelector('.preview-content');
        
        if (content.includes('((') && content.includes('))')) {
            preview.style.display = 'block';
            previewContent.innerHTML = content.replace(/\(\(([^)]+)\)\)/g, '<span class="variable-highlight">$1</span>');
        } else {
            preview.style.display = 'none';
        }
    }

    initVariableAutocomplete() {
        const textarea = document.getElementById('template-prompt');
        if (!textarea) return;

        textarea.addEventListener('keyup', (e) => {
            const pos = e.target.selectionStart;
            const content = e.target.value;

            if (content.slice(pos - 2, pos) === '((') {
                this.showVariableSuggestions(textarea);
            }
        });
    }

    showVariableSuggestions(textarea) {
        const variables = ['topic', 'tone', 'length', 'style', 'keywords'];
        const pos = textarea.selectionStart;
        const content = textarea.value;

        const dropdown = this.createVariableSuggestionsDropdown(variables, textarea, pos, content);
        textarea.parentNode.appendChild(dropdown);

        document.addEventListener('click', function closeDropdown(e) {
            if (!dropdown.contains(e.target)) {
                dropdown.remove();
                document.removeEventListener('click', closeDropdown);
            }
        });
    }

    createVariableSuggestionsDropdown(variables, textarea, pos, content) {
        const dropdown = document.createElement('div');
        dropdown.className = 'variable-suggestions';

        variables.forEach(variable => {
            const item = document.createElement('div');
            item.className = 'suggestion-item';
            item.textContent = variable;
            item.addEventListener('click', () => {
                textarea.value = content.slice(0, pos) + variable + '))' + content.slice(pos);
                dropdown.remove();
                textarea.focus();
            });
            dropdown.appendChild(item);
        });

        const coords = this.getCaretCoordinates(textarea, pos);
        dropdown.style.top = (coords.top + 20) + 'px';
        dropdown.style.left = coords.left + 'px';

        return dropdown;
    }

    extractVariables(prompt) {
        const matches = prompt.match(/\(\(([^)]+)\)\)/g) || [];
        return [...new Set(matches.map(match => match.replace(/[()]/g, '')))];    
    }

    getCaretCoordinates(element, position) {
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
        div.remove();

        return coordinates;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    new TemplateEditor();
});