/**
 * DigiContent Editor Integration
 * Handles the integration with WordPress post editor
 */
class DigiContentEditor {
    constructor() {
        this.editorWrapper = document.querySelector('.digicontent-editor-wrapper');
        this.templateSelect = document.getElementById('digicontent-template');
        this.promptInput = document.getElementById('digicontent-prompt');
        this.modelSelect = document.getElementById('digicontent-model');
        this.generateButton = document.getElementById('digicontent-generate');
        this.notificationContainer = document.getElementById('digicontent-notifications');
        this.variablesContainer = document.getElementById('template-variables');
        this.variableFields = this.variablesContainer.querySelector('.variable-fields');
        
        this.currentTemplate = null;
        if (!this.editorWrapper) return;
        
        this.init();
    }

    init() {
        // Bind event listeners
        if (this.templateSelect) {
            this.templateSelect.addEventListener('change', this.handleTemplateChange.bind(this));
        }
        
        if (this.generateButton) {
            this.generateButton.addEventListener('click', this.handleGenerate.bind(this));
        }
    }

    async handleTemplateChange(event) {
        const templateId = event.target.value;
        if (!templateId) {
            this.resetForm();
            return;
        }
        
        try {
            this.showNotification('Loading template...', 'info');
            const response = await this.fetchTemplateContent(templateId);
            
            if (!response.ok) {
                const data = await response.json();
                throw new Error(data.data?.message || digiContentEditor.i18n.templateLoadError);
            }
            
            const data = await response.json();
            if (!data.success || !data.data?.prompt) {
                throw new Error(digiContentEditor.i18n.templateLoadError);
            }
            
            this.currentTemplate = data.data;
            this.promptInput.value = this.currentTemplate.prompt;
            
            // Extract and create variable fields
            const variables = this.extractVariables(this.currentTemplate.prompt);
            this.createVariableFields(variables);
            
            // Show variables section and enable generate button
            this.variablesContainer.style.display = 'block';
            this.generateButton.disabled = false;
            
            this.showNotification('Template loaded successfully', 'success');
            
        } catch (error) {
            console.error('Template load error:', error);
            this.showNotification(error.message || digiContentEditor.i18n.templateLoadError, 'error');
            this.resetForm();
        }
    }

    async handleGenerate(event) {
        event.preventDefault();
        
        if (!this.currentTemplate) {
            this.showNotification(digiContentEditor.i18n.selectTemplate, 'error');
            return;
        }
        
        // Get all variable values
        const variables = {};
        this.variableFields.querySelectorAll('input').forEach(input => {
            variables[input.dataset.variable] = input.value;
        });
        
        // Replace variables in prompt
        let prompt = this.currentTemplate.prompt;
        Object.entries(variables).forEach(([key, value]) => {
            prompt = prompt.replace(new RegExp(`\\(\\(${key}\\)\\)`, 'g'), value);
        });
        
        try {
            this.generateButton.disabled = true;
            this.showNotification(digiContentEditor.i18n.generating, 'info');
            
            const response = await this.generateContent(prompt, this.modelSelect.value);
            
            if (!response.ok) {
                const data = await response.json();
                throw new Error(data.data?.message || digiContentEditor.i18n.error);
            }
            
            const data = await response.json();
            if (!data.success || !data.data?.content) {
                throw new Error(digiContentEditor.i18n.error);
            }
            
            // Insert content into editor
            const { createBlock } = wp.blocks;
            const { dispatch } = wp.data;
            
            const block = createBlock('core/paragraph', {
                content: data.data.content
            });
            
            dispatch('core/editor').insertBlock(block);
            this.showNotification('Content generated successfully', 'success');
            
        } catch (error) {
            console.error('Generation error:', error);
            this.showNotification(error.message || digiContentEditor.i18n.error, 'error');
        } finally {
            this.generateButton.disabled = false;
        }
    }

    extractVariables(prompt) {
        const matches = prompt.match(/\(\(([^)]+)\)\)/g) || [];
        return [...new Set(matches.map(match => match.replace(/[()]/g, '')))];
    }

    createVariableFields(variables) {
        this.variableFields.innerHTML = '';
        
        variables.forEach(variable => {
            const field = document.createElement('div');
            field.className = 'variable-field';
            
            const label = document.createElement('label');
            label.textContent = this.formatVariableName(variable);
            
            const input = document.createElement('input');
            input.type = 'text';
            input.className = 'widefat';
            input.dataset.variable = variable;
            input.required = true;
            
            // Add event listener to update prompt preview
            input.addEventListener('input', () => this.updatePromptPreview());
            
            field.appendChild(label);
            field.appendChild(input);
            this.variableFields.appendChild(field);
        });
    }

    formatVariableName(variable) {
        return variable
            .replace(/_/g, ' ')
            .replace(/\b\w/g, l => l.toUpperCase());
    }

    updatePromptPreview() {
        if (!this.currentTemplate) return;
        
        let prompt = this.currentTemplate.prompt;
        this.variableFields.querySelectorAll('input').forEach(input => {
            const value = input.value.trim();
            if (value) {
                prompt = prompt.replace(
                    new RegExp(`\\(\\(${input.dataset.variable}\\)\\)`, 'g'),
                    value
                );
            }
        });
        
        this.promptInput.value = prompt;
    }

    resetForm() {
        this.currentTemplate = null;
        this.promptInput.value = '';
        this.variablesContainer.style.display = 'none';
        this.variableFields.innerHTML = '';
        this.generateButton.disabled = true;
    }

    async fetchTemplateContent(templateId) {
        const formData = new FormData();
        formData.append('action', 'get_template_content');
        formData.append('nonce', digiContentEditor.nonce);
        formData.append('template_id', templateId);
        
        return fetch(digiContentEditor.ajaxUrl, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        });
    }

    async generateContent(prompt, model) {
        const formData = new FormData();
        formData.append('action', 'generate_ai_content');
        formData.append('nonce', digiContentEditor.nonce);
        formData.append('prompt', prompt);
        formData.append('model', model);
        
        return fetch(digiContentEditor.ajaxUrl, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        });
    }

    showNotification(message, type = 'info') {
        if (!this.notificationContainer) return;
        
        const notification = document.createElement('div');
        notification.className = `notice notice-${type} is-dismissible`;
        notification.innerHTML = `<p>${message}</p>`;
        
        // Clear previous notifications
        this.notificationContainer.innerHTML = '';
        this.notificationContainer.appendChild(notification);
        
        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            if (notification.parentNode === this.notificationContainer) {
                this.notificationContainer.removeChild(notification);
            }
        }, 5000);
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    new DigiContentEditor();
}); 