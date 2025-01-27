/**
 * DigiContent Template Editor
 * Handles template management functionality in the admin area
 */
'use strict';

class TemplateEditor {
    constructor() {
        // Initialize properties
        this.form = document.getElementById('digicontent-new-template-form');
        this.prompt = document.getElementById('template-prompt');
        this.preview = document.querySelector('.variable-preview');
        this.previewContent = document.querySelector('.preview-content');
        this.noticeContainer = document.getElementById('template-notice');
        
        this.init();
    }

    init() {
        if (!this.form || !this.prompt) {
            console.error('Required elements not found');
            return;
        }
        
        this.bindEvents();
        this.initVariableAutocomplete();
    }

    bindEvents() {
        // Variable buttons
        document.querySelectorAll('.insert-variable-button').forEach(btn => 
            btn.addEventListener('click', this.handleVariableButtonClick.bind(this)));
        
        // Variable chips
        document.querySelectorAll('.variable-chip').forEach(chip => 
            chip.addEventListener('click', this.handleVariableChipClick.bind(this)));
        
        // Template prompt input
        this.prompt?.addEventListener('input', this.handleTemplateInput.bind(this));
        
        // Form submission
        this.form?.addEventListener('submit', this.handleTemplateSubmit.bind(this));
        
        // Template deletion
        document.querySelectorAll('.delete-template').forEach(btn => 
            btn.addEventListener('click', this.handleTemplateDelete.bind(this)));
    }

    showNotification(message, type = 'success') {
        if (!this.noticeContainer) return;

        this.noticeContainer.className = `notice notice-${type}`;
        const p = this.noticeContainer.querySelector('p') || document.createElement('p');
        p.textContent = message;
        
        if (!p.parentNode) {
            this.noticeContainer.appendChild(p);
        }

        this.noticeContainer.style.display = 'block';
        
        setTimeout(() => {
            this.noticeContainer.style.display = 'none';
        }, 3000);
    }

    async handleTemplateSubmit(e) {
        e.preventDefault();
        
        const form = e.target;
        const submitButton = form.querySelector('[type="submit"]');
        
        try {
            submitButton.disabled = true;
            
            const formData = new FormData(form);
            const response = await fetch(`${wpApiSettings.root}digicontent/v1/templates`, {
                method: 'POST',
                headers: {
                    'X-WP-Nonce': wpApiSettings.nonce
                },
                body: formData
            });

            if (!response.ok) {
                const data = await response.json();
                throw new Error(data.message || 'Error saving template');
            }
            
            this.showNotification('Template saved successfully!');
            form.reset();
            setTimeout(() => window.location.reload(), 1500);
            
        } catch (error) {
            console.error('Template save error:', error);
            this.showNotification(error.message || 'Error saving template', 'error');
        } finally {
            submitButton.disabled = false;
        }
    }

    async handleTemplateDelete(e) {
        const button = e.target;
        const templateId = button.dataset.id;

        if (!templateId || !confirm('Are you sure you want to delete this template?')) {
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

            if (!response.ok) {
                const data = await response.json();
                throw new Error(data.message || 'Error deleting template');
            }
            
            this.showNotification('Template deleted successfully!');
            setTimeout(() => window.location.reload(), 1500);
            
        } catch (error) {
            console.error('Template delete error:', error);
            this.showNotification(error.message || 'Error deleting template', 'error');
        } finally {
            button.disabled = false;
        }
    }

    handleVariableButtonClick(e) {
        e.preventDefault();
        
        const variables = ['topic', 'tone', 'length', 'style', 'keywords'];
        const pos = this.prompt.selectionStart;
        const content = this.prompt.value;
    
        const dialog = this.createVariableDialog(variables, pos, content);
        document.body.appendChild(dialog);
    }

    handleVariableChipClick(e) {
        if (!this.prompt) return;
        
        const variable = e.target.dataset.variable;
        const pos = this.prompt.selectionStart;
        const content = this.prompt.value;
        const insertion = `((${variable}))`;
        
        this.prompt.value = content.slice(0, pos) + insertion + content.slice(pos);
        this.prompt.focus();
    }

    handleTemplateInput(e) {
        if (!this.preview || !this.previewContent) return;
        
        const content = e.target.value;
        
        if (content.includes('((') && content.includes('))')) {
            this.preview.style.display = 'block';
            this.previewContent.innerHTML = content.replace(
                /\(\(([^)]+)\)\)/g, 
                '<span class="variable-highlight">$1</span>'
            );
        } else {
            this.preview.style.display = 'none';
        }
    }

    initVariableAutocomplete() {
        if (!this.prompt) return;

        this.prompt.addEventListener('keyup', (e) => {
            const pos = e.target.selectionStart;
            const content = e.target.value;

            if (content.slice(pos - 2, pos) === '((') {
                this.showVariableSuggestions();
            }
        });
    }

    showVariableSuggestions() {
        if (!this.prompt) return;
        
        const variables = ['topic', 'tone', 'length', 'style', 'keywords'];
        const pos = this.prompt.selectionStart;
        const content = this.prompt.value;

        const dropdown = this.createVariableSuggestionsDropdown(variables, pos, content);
        this.prompt.parentNode?.appendChild(dropdown);

        const closeDropdown = (e) => {
            if (!dropdown.contains(e.target)) {
                dropdown.remove();
                document.removeEventListener('click', closeDropdown);
            }
        };

        document.addEventListener('click', closeDropdown);
    }

    createVariableSuggestionsDropdown(variables, pos, content) {
        const dropdown = document.createElement('div');
        dropdown.className = 'variable-suggestions';

        variables.forEach(variable => {
            const item = document.createElement('div');
            item.className = 'suggestion-item';
            item.textContent = variable;
            
            item.addEventListener('click', () => {
                if (!this.prompt) return;
                
                this.prompt.value = content.slice(0, pos) + variable + '))' + content.slice(pos);
                dropdown.remove();
                this.prompt.focus();
            });
            
            dropdown.appendChild(item);
        });

        const coords = this.getCaretCoordinates(pos);
        dropdown.style.top = (coords.top + 20) + 'px';
        dropdown.style.left = coords.left + 'px';

        return dropdown;
    }

    extractVariables(prompt) {
        const matches = prompt.match(/\(\(([^)]+)\)\)/g) || [];
        return [...new Set(matches.map(match => match.replace(/[()]/g, '')))];    
    }

    getCaretCoordinates(position) {
        if (!this.prompt) return { top: 0, left: 0 };
        
        const div = document.createElement('div');
        const styles = getComputedStyle(this.prompt);
        
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
        div.textContent = this.prompt.value.substring(0, position);

        const span = document.createElement('span');
        span.textContent = this.prompt.value.substring(position) || '.';
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

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    new TemplateEditor();
});