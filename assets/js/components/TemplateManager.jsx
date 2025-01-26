import React, { useState, useEffect } from 'react';
import { Button, SelectControl, TextControl, TextareaControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

const TemplateManager = ({ onApplyTemplate }) => {
    const [templates, setTemplates] = useState([]);
    const [selectedTemplate, setSelectedTemplate] = useState('');
    const [newTemplate, setNewTemplate] = useState({
        name: '',
        category: 'blog_post',
        prompt: '',
        variables: []
    });
    const [templateVariables, setTemplateVariables] = useState({});

    useEffect(() => {
        loadTemplates();
    }, []);

    const loadTemplates = async () => {
        try {
            const response = await apiFetch({ path: '/digicontent/v1/templates' });
            setTemplates(response);
        } catch (error) {
            console.error('Error loading templates:', error);
        }
    };

    const saveTemplate = async () => {
        try {
            await apiFetch({
                path: '/digicontent/v1/templates',
                method: 'POST',
                data: newTemplate
            });
            loadTemplates();
            setNewTemplate({ name: '', category: 'blog_post', prompt: '', variables: [] });
        } catch (error) {
            console.error('Error saving template:', error);
        }
    };

    const deleteTemplate = async (templateId) => {
        try {
            await apiFetch({
                path: `/digicontent/v1/templates/${templateId}`,
                method: 'DELETE'
            });
            loadTemplates();
        } catch (error) {
            console.error('Error deleting template:', error);
        }
    };

    const handleTemplateSelect = (templateId) => {
        const template = templates.find(t => t.id === templateId);
        setSelectedTemplate(templateId);
        if (template) {
            const initialVariables = {};
            template.variables.forEach(variable => {
                initialVariables[variable] = '';
            });
            setTemplateVariables(initialVariables);
        }
    };

    const applyTemplate = () => {
        const template = templates.find(t => t.id === selectedTemplate);
        if (template) {
            let processedPrompt = template.prompt;
            Object.entries(templateVariables).forEach(([key, value]) => {
                processedPrompt = processedPrompt.replace(`{{${key}}}`, value);
            });
            onApplyTemplate(processedPrompt);
        }
    };

    return (
        <div className="digicontent-template-manager">
            <div className="template-selector">
                <SelectControl
                    label={__('Select Template', 'digicontent')}
                    value={selectedTemplate}
                    options={[
                        { label: __('Choose a template...', 'digicontent'), value: '' },
                        ...templates.map(t => ({ label: t.name, value: t.id }))
                    ]}
                    onChange={handleTemplateSelect}
                />

                {selectedTemplate && (
                    <div className="template-variables">
                        {templates
                            .find(t => t.id === selectedTemplate)
                            ?.variables?.map(variable => (
                                <TextControl
                                    key={variable}
                                    label={__(`Variable: ${variable}`, 'digicontent')}
                                    value={templateVariables[variable] || ''}
                                    onChange={value => setTemplateVariables(prev => ({
                                        ...prev,
                                        [variable]: value
                                    }))}
                                />
                            ))}
                        <Button
                            isPrimary
                            onClick={applyTemplate}
                        >
                            {__('Apply Template', 'digicontent')}
                        </Button>
                    </div>
                )}
            </div>

            <div className="template-creator">
                <h3>{__('Create New Template', 'digicontent')}</h3>
                <TextControl
                    label={__('Template Name', 'digicontent')}
                    value={newTemplate.name}
                    onChange={name => setNewTemplate(prev => ({ ...prev, name }))}
                />
                <SelectControl
                    label={__('Category', 'digicontent')}
                    value={newTemplate.category}
                    options={[
                        { label: __('Blog Post', 'digicontent'), value: 'blog_post' },
                        { label: __('Product Description', 'digicontent'), value: 'product_description' },
                        { label: __('News Article', 'digicontent'), value: 'news_article' }
                    ]}
                    onChange={category => setNewTemplate(prev => ({ ...prev, category }))}
                />
                <TextareaControl
                    label={__('Prompt Template', 'digicontent')}
                    value={newTemplate.prompt}
                    onChange={prompt => setNewTemplate(prev => ({ ...prev, prompt }))}
                    help={__('Use {{variable}} syntax for custom variables', 'digicontent')}
                />
                <TextControl
                    label={__('Variables (comma-separated)', 'digicontent')}
                    value={newTemplate.variables.join(', ')}
                    onChange={value => setNewTemplate(prev => ({
                        ...prev,
                        variables: value.split(',').map(v => v.trim()).filter(Boolean)
                    }))}
                />
                <Button
                    isPrimary
                    onClick={saveTemplate}
                    disabled={!newTemplate.name || !newTemplate.prompt}
                >
                    {__('Save Template', 'digicontent')}
                </Button>
            </div>
        </div>
    );
};

export default TemplateManager;