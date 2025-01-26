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
            this.promptInput.value = '';
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
            
            this.promptInput.value = data.data.prompt;
            this.showNotification('Template loaded successfully', 'success');
            
        } catch (error) {
            console.error('Template load error:', error);
            this.showNotification(error.message || digiContentEditor.i18n.templateLoadError, 'error');
            this.promptInput.value = '';
        }
    }

    async handleGenerate(event) {
        event.preventDefault();
        
        const prompt = this.promptInput.value.trim();
        const model = this.modelSelect.value;
        
        if (!prompt) {
            this.showNotification(digiContentEditor.i18n.emptyPrompt, 'error');
            return;
        }
        
        try {
            this.generateButton.disabled = true;
            this.showNotification(digiContentEditor.i18n.generating, 'info');
            
            const response = await this.generateContent(prompt, model);
            
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
            const { dispatch, select } = wp.data;
            const editor = select('core/editor');
            
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