/**
 * DigiContent Editor
 * Version: 1.1.0
 * Handles template selection and AI content generation in the post editor
 */

'use strict';

class DigiContentEditor {
    /**
     * Initialize the editor
     * @param {Object} config Editor configuration
     */
    constructor(config = {}) {
        // Elements
        this.wrapper = document.querySelector('.digicontent-editor-wrapper');
        this.templateSelect = document.querySelector('#digicontent-template');
        this.promptArea = document.querySelector('#digicontent-prompt');
        this.directPromptArea = document.querySelector('#digicontent-direct-prompt');
        this.modelSelect = document.querySelector('#digicontent-model');
        this.generateButton = document.querySelector('#digicontent-generate');
        this.variablesContainer = document.querySelector('#template-variables');
        this.variableFields = new Map();
        
        // Configuration
        this.config = {
            restUrl: wpApiSettings?.root ?? '/wp-json',
            nonce: wpApiSettings?.nonce ?? '',
            ...config
        };
        
        this.initialize();
    }
    
    /**
     * Initialize event listeners and setup
     */
    initialize() {
        if (!this.wrapper) {
            console.error('DigiContent editor wrapper not found');
            return;
        }
        
        // Bind event listeners
        this.templateSelect?.addEventListener('change', this.handleTemplateChange.bind(this));
        this.generateButton?.addEventListener('click', this.handleGenerate.bind(this));
        
        // Initialize tooltips
        this.initializeTooltips();
        
        // Disable generate button initially
        this.generateButton.disabled = true;
    }
    
    /**
     * Initialize tooltips
     */
    initializeTooltips() {
        const tooltips = this.wrapper.querySelectorAll('.tooltip');
        tooltips.forEach(tooltip => {
            const content = tooltip.querySelector('.tooltip-content');
            if (content) {
                tooltip.setAttribute('aria-label', content.textContent);
                tooltip.setAttribute('role', 'tooltip');
            }
        });
    }
    
    /**
     * Handle template selection change
     * @param {Event} event Change event
     */
    async handleTemplateChange(event) {
        const templateId = event.target.value;
        
        if (!templateId) {
            this.resetForm();
            return;
        }
        
        try {
            const response = await fetch(`${this.config.restUrl}templates/${templateId}`, {
                headers: {
                    'X-WP-Nonce': this.config.nonce,
                    'Content-Type': 'application/json'
                }
            });
            
            if (!response.ok) {
                throw new Error(digiContentEditor.i18n.templateLoadError);
            }
            
            const data = await response.json();
            this.updatePromptPreview(data.prompt);
            this.createVariableFields(data.variables);
            this.generateButton.disabled = false;
            
        } catch (error) {
            this.showNotification(error.message, 'error');
            console.error('Template load error:', error);
            this.resetForm();
        }
    }
    
    /**
     * Update the prompt preview with template content
     * @param {string} prompt Template prompt
     */
    updatePromptPreview(prompt) {
        if (this.promptArea) {
            this.promptArea.value = prompt;
            this.promptArea.readOnly = true;
        }
    }
    
    /**
     * Create input fields for template variables
     * @param {Object} variables Template variables
     */
    createVariableFields(variables) {
        if (!this.variablesContainer || !variables) {
            return;
        }
        
        // Clear existing fields
        this.variableFields.clear();
        this.variablesContainer.innerHTML = '';
        
        if (Object.keys(variables).length === 0) {
            this.variablesContainer.style.display = 'none';
            return;
        }
        
        // Create variable fields
        const fieldsContainer = document.createElement('div');
        fieldsContainer.className = 'variable-fields';
        
        Object.entries(variables).forEach(([name, value]) => {
            const field = this.createVariableField(name, value);
            fieldsContainer.appendChild(field);
        });
        
        this.variablesContainer.appendChild(fieldsContainer);
        this.variablesContainer.style.display = 'block';
    }
    
    /**
     * Create a single variable input field
     * @param {string} name Variable name
     * @param {string} value Default value
     * @returns {HTMLElement} Field element
     */
    createVariableField(name, value) {
        const field = document.createElement('div');
        field.className = 'variable-field';
        
        const label = document.createElement('label');
        label.textContent = this.formatVariableName(name);
        
        const input = document.createElement('input');
        input.type = 'text';
        input.value = value || '';
        input.placeholder = `Enter ${name}`;
        input.addEventListener('input', () => this.updatePromptWithVariables());
        
        this.variableFields.set(name, input);
        
        field.appendChild(label);
        field.appendChild(input);
        
        return field;
    }
    
    /**
     * Format variable name for display
     * @param {string} name Variable name
     * @returns {string} Formatted name
     */
    formatVariableName(name) {
        return name
            .replace(/_/g, ' ')
            .replace(/\b\w/g, l => l.toUpperCase());
    }
    
    /**
     * Update prompt with variable values
     */
    updatePromptWithVariables() {
        if (!this.promptArea) return;
        
        let prompt = this.promptArea.value;
        
        this.variableFields.forEach((input, name) => {
            const value = input.value.trim();
            const regex = new RegExp(`\\(\\(${name}\\)\\)`, 'g');
            prompt = prompt.replace(regex, value || `((${name}))`);
        });
        
        if (this.directPromptArea) {
            this.directPromptArea.value = prompt;
        }
    }
    
    /**
     * Handle generate button click
     * @param {Event} event Click event
     */
    async handleGenerate(event) {
        event.preventDefault();
        
        if (!this.validateForm()) {
            return;
        }
        
        const prompt = this.directPromptArea?.value.trim();
        const model = this.modelSelect?.value;
        
        if (!prompt || !model) {
            this.showNotification(digiContentEditor.i18n.emptyPrompt, 'error');
            return;
        }
        
        try {
            this.generateButton.disabled = true;
            this.generateButton.textContent = digiContentEditor.i18n.generating;
            
            const response = await fetch(`${this.config.restUrl}generate`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': this.config.nonce
                },
                body: JSON.stringify({ prompt, model })
            });
            
            if (!response.ok) {
                throw new Error(digiContentEditor.i18n.error);
            }
            
            const data = await response.json();
            
            if (data.success) {
                // Insert content into editor
                if (typeof wp !== 'undefined' && wp.data && wp.data.dispatch('core/editor')) {
                    const { insertBlocks, createBlock } = wp.blocks;
                    const { dispatch } = wp.data;
                    
                    const block = createBlock('core/paragraph', {
                        content: data.data.content
                    });
                    
                    dispatch('core/block-editor').insertBlocks(block);
                    this.showNotification('Content generated and inserted successfully', 'success');
                } else {
                    console.error('WordPress editor not found');
                    this.showNotification('Editor not found. Please try again.', 'error');
                }
            } else {
                throw new Error(data.message || digiContentEditor.i18n.error);
            }
        } catch (error) {
            this.showNotification(error.message, 'error');
            console.error('Generation error:', error);
        } finally {
            this.generateButton.disabled = false;
            this.generateButton.textContent = digiContentEditor.i18n.insertContent;
        }
    }
    
    /**
     * Validate form before submission
     * @returns {boolean} Validation result
     */
    validateForm() {
        let isValid = true;
        
        // Check if all required variables are filled
        this.variableFields.forEach((input, name) => {
            if (!input.value.trim()) {
                this.showNotification(`Please fill in the ${this.formatVariableName(name)} field`, 'error');
                input.focus();
                isValid = false;
            }
        });
        
        return isValid;
    }
    
    /**
     * Show notification message
     * @param {string} message Notification message
     * @param {string} type Notification type
     */
    showNotification(message, type = 'info') {
        const container = document.querySelector('#digicontent-notifications');
        if (!container) return;
        
        const notice = document.createElement('div');
        notice.className = `notice notice-${type}`;
        notice.innerHTML = `<p>${message}</p>`;
        
        container.innerHTML = '';
        container.appendChild(notice);
        
        // Auto-hide after 5 seconds
        setTimeout(() => {
            notice.remove();
        }, 5000);
    }
    
    /**
     * Reset form to initial state
     */
    resetForm() {
        if (this.promptArea) {
            this.promptArea.value = '';
            this.promptArea.readOnly = false;
        }
        
        if (this.directPromptArea) {
            this.directPromptArea.value = '';
        }
        
        if (this.variablesContainer) {
            this.variablesContainer.style.display = 'none';
            this.variablesContainer.innerHTML = '';
        }
        
        this.variableFields.clear();
        this.generateButton.disabled = true;
    }
}

// Initialize editor when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    new DigiContentEditor();
});
