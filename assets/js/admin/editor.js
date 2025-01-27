/**
 * DigiContent Editor Integration
 * Handles the integration with WordPress post editor
 */
class DigiContentEditor {
  constructor() {
    this.initializeElements();
    if (!this.editorWrapper) return;
    this.init();
  }

  initializeElements() {
    this.editorWrapper = document.querySelector(".digicontent-editor-wrapper");
    this.templateSelect = document.getElementById("digicontent-template");
    this.promptInput = document.getElementById("digicontent-prompt");
    this.directPromptInput = document.getElementById("digicontent-direct-prompt");
    this.modelSelect = document.getElementById("digicontent-model");
    this.generateButton = document.getElementById("digicontent-generate");
    this.notificationContainer = document.getElementById(
      "digicontent-notifications"
    );
    this.variablesContainer = document.getElementById("template-variables");
    this.variableFields =
      this.variablesContainer?.querySelector(".variable-fields");
    this.templateSection = document.getElementById("template-section");
    this.directPromptSection = document.getElementById("direct-prompt-section");
    this.useTemplateToggle = document.getElementById("use-template-toggle");
    this.currentTemplate = null;
  }

  init() {
    this.bindEventListeners();
  }

  bindEventListeners() {
    this.templateSelect?.addEventListener(
      "change",
      this.handleTemplateChange.bind(this)
    );
    this.generateButton?.addEventListener(
      "click",
      this.handleGenerate.bind(this)
    );
    this.useTemplateToggle?.addEventListener(
      "change",
      this.handleTemplateToggle.bind(this)
    );
  }

  handleTemplateToggle(event) {
    const useTemplate = event.target.checked;
    this.templateSection.style.display = useTemplate ? "block" : "none";
    this.directPromptSection.style.display = useTemplate ? "none" : "block";
    this.generateButton.disabled = false;
  }

  async handleTemplateChange(event) {
    const templateId = event.target.value;
    if (!templateId) {
      this.resetForm();
      return;
    }

    try {
      this.showNotification("Loading template...", "info");
      const response = await this.fetchTemplateContent(templateId);
      const data = await this.handleTemplateResponse(response);
      await this.processTemplateData(data);
    } catch (error) {
      this.handleError(error, "Template load error");
    }
  }

  async handleTemplateResponse(response) {
    if (!response.ok) {
      const data = await response.json();
      throw new Error(
        data.data?.message || digiContentEditor.i18n.templateLoadError
      );
    }
    return response.json();
  }

  async processTemplateData(data) {
    if (!data.success || !data.data?.prompt) {
      throw new Error(digiContentEditor.i18n.templateLoadError);
    }

    this.currentTemplate = data.data;
    this.promptInput.value = this.currentTemplate.prompt;

    const variables = this.extractVariables(this.currentTemplate.prompt);

    if (variables.length > 0) {
      this.createVariableFields(variables);
      this.variablesContainer.style.display = "block";
    } else {
      this.variablesContainer.style.display = "none";
      this.variableFields.innerHTML = "";
    }

    this.generateButton.disabled = false;
    this.showNotification("Template loaded successfully", "success");
  }

  async handleGenerate(event) {
    event.preventDefault();

    let prompt;
    if (this.useTemplateToggle.checked) {
      if (!this.validateTemplate()) return;
      prompt = this.buildPrompt();
    } else {
      prompt = this.directPromptInput.value.trim();
      if (!prompt) {
        this.showNotification(digiContentEditor.i18n.emptyPrompt, "error");
        return;
      }
    }

    try {
      this.setGeneratingState(true);
      const response = this.generateContent(
        prompt,
        this.modelSelect.value
      );
      this.handleGenerateResponse(response);
    } catch (error) {
      this.handleError(error, "Generation error");
    } finally {
      this.setGeneratingState(false);
    }
  }

  validateTemplate() {
    if (!this.currentTemplate) {
      this.showNotification(digiContentEditor.i18n.selectTemplate, "error");
      return false;
    }
    return true;
  }

  buildPrompt() {
    const variables = this.collectVariables();
    return this.replaceVariablesInPrompt(variables);
  }

  collectVariables() {
    const variables = {};
    this.variableFields.querySelectorAll("input").forEach((input) => {
      variables[input.dataset.variable] = input.value.trim();
    });
    return variables;
  }

  replaceVariablesInPrompt(variables) {
    let prompt = this.currentTemplate.prompt;
    Object.entries(variables).forEach(([key, value]) => {
      if (value.trim()) {
        const safeKey = key.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
        prompt = prompt.replace(
          new RegExp(`\\(\\(${safeKey}\\)\\)`, "g"),
          `, ${key}: ${value}`
        );
      }
    });
    return prompt;
  }

  setGeneratingState(isGenerating) {
    this.generateButton.disabled = isGenerating;
    if (isGenerating) {
      this.showNotification(digiContentEditor.i18n.generating, "info");
    }
  }

  async handleGenerateResponse(response) {
    const data = await this.validateGenerateResponse(response);
    await this.insertGeneratedContent(data.data.content);
    this.showNotification("Content generated successfully", "success");
  }

  async validateGenerateResponse(response) {
    if (!response.ok) {
      const data = await response.json();
      throw new Error(data.data?.message || digiContentEditor.i18n.error);
    }

    const data = await response.json();
    if (!data.success || !data.data?.content) {
      throw new Error(digiContentEditor.i18n.error);
    }

    return data;
  }

  async insertGeneratedContent(content) {
    try {
      const { createBlock } = wp.blocks;
      const { dispatch } = wp.data;

      const block = createBlock("core/paragraph", { content });
      dispatch("core/editor").insertBlock(block);
    } catch (error) {
      console.error("Block insertion failed:", error);
      this.showNotification(digiContentEditor.i18n.insertError, "error");
    }
  }

  extractVariables(prompt) {
    const matches = prompt.match(/\(\(([^)]+)\)\)/g) || [];
    return [...new Set(matches.map((match) => match.replace(/[()]/g, "")))];
  }

  createVariableFields(variables) {
    if (!this.variableFields) return;
    this.variableFields.innerHTML = "";

    variables.forEach((variable) => {
      const field = this.createVariableField(variable);
      this.variableFields.appendChild(field);
    });
  }

  createVariableField(variable) {
    const field = document.createElement("div");
    field.className = "variable-field";

    const label = document.createElement("label");
    label.textContent = this.formatVariableName(variable);

    const input = document.createElement("input");
    input.type = "text";
    input.className = "widefat";
    input.dataset.variable = variable;
    input.required = true;
    input.addEventListener("input", () => this.updatePromptPreview());

    field.append(label, input);
    return field;
  }

  formatVariableName(variable) {
    return variable.replace(/_/g, " ").replace(/\b\w/g, (l) => l.toUpperCase());
  }

  updatePromptPreview() {
    if (!this.currentTemplate) return;

    const variables = this.collectVariables();
    this.promptInput.value = this.replaceVariablesInPrompt(variables);
  }

  resetForm() {
    this.currentTemplate = null;
    this.promptInput.value = "";
    this.variablesContainer.style.display = "none";
    this.variableFields.innerHTML = "";
    this.generateButton.disabled = true;
  }

  async fetchTemplateContent(templateId) {
    const formData = new FormData();
    formData.append("action", "get_template_content");
    formData.append("nonce", digiContentEditor.nonce);
    formData.append("template_id", templateId);

    return fetch(digiContentEditor.ajaxUrl, {
      method: "POST",
      body: formData,
      credentials: "same-origin",
    });
  }

  async generateContent(prompt, model) {
    const formData = new FormData();
    formData.append("action", "generate_ai_content");
    formData.append("nonce", digiContentEditor.nonce);
    formData.append("prompt", prompt);
    formData.append("model", model);

    return fetch(digiContentEditor.ajaxUrl, {
      method: "POST",
      body: formData,
      credentials: "same-origin",
    });
  }

  handleError(error, context) {
    console.error(`${context}:`, error);
    this.showNotification(
      error.message || digiContentEditor.i18n.error,
      "error"
    );
    if (context === "Template load error") {
      this.resetForm();
    }
  }

  showNotification(message, type = "info") {
    if (!this.notificationContainer) return;

    const notification = document.createElement("div");
    notification.className = `notice notice-${type} is-dismissible`;
    notification.innerHTML = `<p>${message}</p>`;

    this.notificationContainer.innerHTML = "";
    this.notificationContainer.appendChild(notification);

    setTimeout(() => {
      if (notification.parentNode === this.notificationContainer) {
        this.notificationContainer.removeChild(notification);
      }
    }, 5000);
  }
}

document.addEventListener("DOMContentLoaded", () => {
  new DigiContentEditor();
});
