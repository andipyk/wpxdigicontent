/**
 * Template Manager
 * Handles template management functionality
 */
class TemplateManager {
    constructor() {
        this.form = document.getElementById('digicontent-new-template-form');
        this.noticeContainer = document.getElementById('template-notice');
        this.templateList = document.querySelector('.digicontent-templates-list');
        this.promptEditor = document.querySelector('.prompt-editor');
        
        this.init();
    }
    
    init() {
        if (this.form) {
            this.form.addEventListener('submit', this.handleSubmit.bind(this));
        }
        
        // Bind delete buttons
        document.querySelectorAll('.delete-template').forEach(button => {
            button.addEventListener('click', this.handleDelete.bind(this));
        });
        
        // Bind edit buttons
        document.querySelectorAll('.edit-template').forEach(button => {
            button.addEventListener('click', this.handleEdit.bind(this));
        });
        
        // Initialize variable insertion
        if (this.promptEditor) {
            this.initVariableInsertion();
        }
    }
    
    initVariableInsertion() {
        const insertButton = this.promptEditor.querySelector('.insert-variable');
        const variableList = this.promptEditor.querySelector('.variable-list');
        const promptTextarea = this.promptEditor.querySelector('#template-prompt');
        
        // Toggle variable list
        insertButton.addEventListener('click', () => {
            const isVisible = variableList.style.display === 'block';
            variableList.style.display = isVisible ? 'none' : 'block';
        });
        
        // Handle variable selection
        variableList.addEventListener('click', (event) => {
            const button = event.target.closest('.variable-item');
            if (!button) return;
            
            const variable = button.dataset.variable;
            const cursorPos = promptTextarea.selectionStart;
            const textBefore = promptTextarea.value.substring(0, cursorPos);
            const textAfter = promptTextarea.value.substring(promptTextarea.selectionEnd);
            
            promptTextarea.value = textBefore + `((${variable}))` + textAfter;
            promptTextarea.focus();
            
            // Hide variable list
            variableList.style.display = 'none';
            
            // Trigger input event for any listeners
            promptTextarea.dispatchEvent(new Event('input'));
        });
        
        // Close variable list when clicking outside
        document.addEventListener('click', (event) => {
            if (!event.target.closest('.variable-tools')) {
                variableList.style.display = 'none';
            }
        });
        
        // Handle keyboard shortcuts
        promptTextarea.addEventListener('keydown', (event) => {
            if (event.key === '(' && event.shiftKey) {
                const cursorPos = promptTextarea.selectionStart;
                if (promptTextarea.value[cursorPos - 1] === '(') {
                    variableList.style.display = 'block';
                }
            }
        });
    }
    
    async handleSubmit(event) {
        event.preventDefault();
        
        const formData = {
            name: this.form.querySelector('#template-name').value,
            category: this.form.querySelector('#template-category').value,
            prompt: this.form.querySelector('#template-prompt').value,
            variables: this.extractVariables(this.form.querySelector('#template-prompt').value)
        };
        
        try {
            const submitButton = this.form.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            submitButton.textContent = 'Saving...';
            
            const response = await fetch(`${wpApiSettings.root}digicontent/v1/templates`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': wpApiSettings.nonce
                },
                body: JSON.stringify(formData)
            });
            
            const data = await response.json();
            
            if (!response.ok) {
                throw new Error(data.message || 'Failed to save template');
            }
            
            this.showNotice('Template saved successfully!', 'success');
            this.form.reset();
            
            // Reload the page after a short delay
            setTimeout(() => {
                window.location.reload();
            }, 1500);
            
        } catch (error) {
            console.error('Template save error:', error);
            this.showNotice(error.message || 'Failed to save template', 'error');
        } finally {
            const submitButton = this.form.querySelector('button[type="submit"]');
            submitButton.disabled = false;
            submitButton.textContent = 'Save Template';
        }
    }
    
    async handleDelete(event) {
        const button = event.target;
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
            
            if (!response.ok) {
                const data = await response.json();
                throw new Error(data.message || 'Failed to delete template');
            }
            
            this.showNotice('Template deleted successfully!', 'success');
            
            // Remove the template row from the table
            const row = button.closest('tr');
            if (row) {
                row.remove();
            }
            
        } catch (error) {
            console.error('Template delete error:', error);
            this.showNotice(error.message || 'Failed to delete template', 'error');
        } finally {
            button.disabled = false;
        }
    }
    
    async handleEdit(event) {
        const button = event.target;
        const templateId = button.dataset.id;
        
        try {
            button.disabled = true;
            
            const response = await fetch(`${wpApiSettings.root}digicontent/v1/templates/${templateId}`, {
                headers: {
                    'X-WP-Nonce': wpApiSettings.nonce
                }
            });
            
            const data = await response.json();
            
            if (!response.ok) {
                throw new Error(data.message || 'Failed to load template');
            }
            
            // Populate the form with template data
            this.form.querySelector('#template-name').value = data.name;
            this.form.querySelector('#template-category').value = data.category;
            this.form.querySelector('#template-prompt').value = data.prompt;
            
            // Update form for editing mode
            this.form.dataset.mode = 'edit';
            this.form.dataset.templateId = templateId;
            
            // Update submit button text
            const submitButton = this.form.querySelector('button[type="submit"]');
            submitButton.textContent = 'Update Template';
            
            // Scroll to the form
            this.form.scrollIntoView({ behavior: 'smooth' });
            
        } catch (error) {
            console.error('Template edit error:', error);
            this.showNotice(error.message || 'Failed to load template', 'error');
        } finally {
            button.disabled = false;
        }
    }
    
    extractVariables(prompt) {
        const matches = prompt.match(/\(\(([^)]+)\)\)/g) || [];
        return matches.map(match => match.replace(/[()]/g, ''));
    }
    
    showNotice(message, type = 'success') {
        if (!this.noticeContainer) return;
        
        const notice = document.createElement('div');
        notice.className = `notice notice-${type} is-dismissible`;
        notice.innerHTML = `<p>${message}</p>`;
        
        this.noticeContainer.innerHTML = '';
        this.noticeContainer.appendChild(notice);
        this.noticeContainer.style.display = 'block';
        
        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            if (notice.parentNode === this.noticeContainer) {
                this.noticeContainer.style.display = 'none';
                this.noticeContainer.innerHTML = '';
            }
        }, 5000);
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    new TemplateManager();
}); 