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
        
        document.querySelectorAll('.delete-template').forEach(button => {
            button.addEventListener('click', this.handleDelete.bind(this));
        });
        
        document.querySelectorAll('.edit-template').forEach(button => {
            button.addEventListener('click', this.handleEdit.bind(this));
        });
        
        if (this.promptEditor) {
            this.initVariableInsertion();
        }
    }
    
    initVariableInsertion() {
        const insertButton = this.promptEditor.querySelector('.insert-variable');
        const variableList = this.promptEditor.querySelector('.variable-list');
        const promptTextarea = this.promptEditor.querySelector('#template-prompt');
        
        insertButton?.addEventListener('click', () => {
            const isVisible = variableList.style.display === 'block';
            variableList.style.display = isVisible ? 'none' : 'block';
        });
        
        variableList?.addEventListener('click', (event) => {
            const button = event.target.closest('.variable-item');
            if (!button) return;
            
            this.insertVariable(button.dataset.variable, promptTextarea);
            variableList.style.display = 'none';
        });
        
        document.addEventListener('click', (event) => {
            if (!event.target.closest('.variable-tools')) {
                variableList.style.display = 'none';
            }
        });
        
        promptTextarea?.addEventListener('keydown', (event) => {
            if (event.key === '(' && event.shiftKey && promptTextarea.value[promptTextarea.selectionStart - 1] === '(') {
                variableList.style.display = 'block';
            }
        });
    }
    
    insertVariable(variable, textarea) {
        const cursorPos = textarea.selectionStart;
        const textBefore = textarea.value.substring(0, cursorPos);
        const textAfter = textarea.value.substring(textarea.selectionEnd);
        
        textarea.value = textBefore + `((${variable}))` + textAfter;
        textarea.focus();
        textarea.dispatchEvent(new Event('input'));
    }
    
    async handleSubmit(event) {
        event.preventDefault();
        
        const formData = this.collectFormData();
        if (!this.validateFormData(formData)) return;
        
        const submitButton = this.form.querySelector('button[type="submit"]');
        
        try {
            await this.submitTemplate(formData, submitButton);
        } catch (error) {
            console.error('Template save error:', error);
            this.showNotice(error.message || 'Failed to save template', 'error');
        }
    }
    
    collectFormData() {
        return {
            name: this.form.querySelector('#template-name').value,
            category: this.form.querySelector('#template-category').value,
            prompt: this.form.querySelector('#template-prompt').value,
            variables: this.extractVariables(this.form.querySelector('#template-prompt').value)
        };
    }
    
    validateFormData(formData) {
        if (!formData.name || !formData.prompt) {
            this.showNotice('Please fill in all required fields', 'error');
            return false;
        }
        return true;
    }
    
    async submitTemplate(formData, submitButton) {
        submitButton.disabled = true;
        submitButton.textContent = 'Saving...';
        
        try {
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
            
            setTimeout(() => window.location.reload(), 1500);
            
        } finally {
            submitButton.disabled = false;
            submitButton.textContent = 'Save Template';
        }
    }
    
    async handleDelete(event) {
        const button = event.target;
        const templateId = button.dataset.id;
        
        if (!confirm('Are you sure you want to delete this template?')) return;
        
        try {
            button.disabled = true;
            await this.deleteTemplate(templateId);
            
            this.showNotice('Template deleted successfully!', 'success');
            button.closest('tr')?.remove();
            
        } catch (error) {
            console.error('Template delete error:', error);
            this.showNotice(error.message || 'Failed to delete template', 'error');
        } finally {
            button.disabled = false;
        }
    }
    
    async deleteTemplate(templateId) {
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
    }
    
    async handleEdit(event) {
        const button = event.target;
        const templateId = button.dataset.id;
        
        try {
            button.disabled = true;
            const template = await this.fetchTemplate(templateId);
            this.populateForm(template);
            this.form.scrollIntoView({ behavior: 'smooth' });
            
        } catch (error) {
            console.error('Template edit error:', error);
            this.showNotice(error.message || 'Failed to load template', 'error');
        } finally {
            button.disabled = false;
        }
    }
    
    async fetchTemplate(templateId) {
        const response = await fetch(`${wpApiSettings.root}digicontent/v1/templates/${templateId}`, {
            headers: {
                'X-WP-Nonce': wpApiSettings.nonce
            }
        });
        
        const data = await response.json();
        
        if (!response.ok) {
            throw new Error(data.message || 'Failed to load template');
        }
        
        return data;
    }
    
    populateForm(template) {
        this.form.querySelector('#template-name').value = template.name;
        this.form.querySelector('#template-category').value = template.category;
        this.form.querySelector('#template-prompt').value = template.prompt;
        
        this.form.dataset.mode = 'edit';
        this.form.dataset.templateId = template.id;
        
        const submitButton = this.form.querySelector('button[type="submit"]');
        submitButton.textContent = 'Update Template';
    }
    
    extractVariables(prompt) {
        const matches = prompt.match(/\(\(([^)]+)\)\)/g) || [];
        return [...new Set(matches.map(match => match.replace(/[()]/g, '')))];    
    }
    
    showNotice(message, type = 'success') {
        if (!this.noticeContainer) return;
        
        const notice = document.createElement('div');
        notice.className = `notice notice-${type} is-dismissible`;
        notice.innerHTML = `<p>${message}</p>`;
        
        this.noticeContainer.innerHTML = '';
        this.noticeContainer.appendChild(notice);
        this.noticeContainer.style.display = 'block';
        
        setTimeout(() => {
            if (notice.parentNode === this.noticeContainer) {
                this.noticeContainer.style.display = 'none';
                this.noticeContainer.innerHTML = '';
            }
        }, 5000);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    new TemplateManager();
});