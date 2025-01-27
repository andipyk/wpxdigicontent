# DigiContent - AI Content Generator for WordPress

A powerful WordPress plugin that leverages AI to generate high-quality content using OpenAI's GPT-4 and Anthropic's Claude models.

## Features

- 🤖 Integration with OpenAI GPT-4 and Anthropic Claude
- 📝 Customizable content templates
- 🔄 Variable support in templates
- 🔒 Secure API key management
- 📊 Debug logging for troubleshooting
- 🎨 Modern, user-friendly interface
- 🔍 Preview generated content
- ⚡ Fast content generation
- 🛡️ Input validation and sanitization
- 🌐 WordPress block editor integration

## Requirements

- WordPress 6.0 or higher
- PHP 8.2 or higher
- OpenAI or Anthropic API key
- SSL certificate (recommended)

## Installation

1. Download the plugin ZIP file
2. Go to WordPress admin > Plugins > Add New
3. Click "Upload Plugin" and select the ZIP file
4. Click "Install Now"
5. After installation, click "Activate"
6. Go to Settings > DigiContent to configure API keys

## Configuration

### API Keys

1. Obtain API keys from:
   - [OpenAI API Keys](https://platform.openai.com/account/api-keys)
   - [Anthropic API Keys](https://console.anthropic.com/account/keys)
2. Go to Settings > DigiContent > API Settings
3. Enter your API keys in the respective fields
4. Click "Save Changes"

### Debug Mode

1. Go to Settings > DigiContent > Debug Settings
2. Enable debug logging if needed
3. Logs are stored in `wp-content/digicontent-logs/`

## Usage

### Creating Templates

1. Go to Settings > DigiContent > Templates
2. Click "Add New Template"
3. Fill in:
   - Template Name
   - Category
   - Prompt Template
4. Use variables in the format: `((variable_name))`
5. Click "Save Template"

### Generating Content

1. Create a new post/page
2. In the editor, locate the DigiContent meta box
3. Select a template
4. Fill in the variable values
5. Choose an AI model
6. Click "Generate"
7. The generated content will be inserted into the editor

## Development

### Requirements

- Node.js 18+
- Composer
- WordPress coding standards

### Setup

1. Clone the repository
```bash
git clone https://github.com/yourusername/digicontent.git
cd digicontent
```

2. Install dependencies
```bash
composer install
npm install
```

3. Build assets
```bash
npm run build
```

### Directory Structure

```
digicontent/
├── assets/
│   ├── css/
│   └── js/
├── includes/
│   ├── Admin/
│   ├── Core/
│   └── REST/
├── languages/
├── vendor/
├── digicontent.php
├── uninstall.php
└── readme.txt
```

### Coding Standards

This plugin follows:
- WordPress PHP Coding Standards
- WordPress JavaScript Coding Standards
- PSR-12 coding style

Run code checks:
```bash
composer run check
```

### Testing

Run PHP tests:
```bash
composer run test
```

Run JavaScript tests:
```bash
npm test
```

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Run tests
5. Submit a pull request

Please read [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## Security

- API keys are encrypted before storage
- Input is sanitized and validated
- Outputs are properly escaped
- Capabilities are checked for all actions
- CSRF protection via nonces
- Rate limiting on API requests

Report security issues via [security.md](SECURITY.md)

## Support

- [Documentation](https://docs.example.com/digicontent)
- [GitHub Issues](https://github.com/yourusername/digicontent/issues)
- [Support Forum](https://wordpress.org/support/plugin/digicontent)

## License

This project is licensed under the GPL v2 or later - see the [LICENSE.txt](LICENSE.txt) file for details.

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for version history.