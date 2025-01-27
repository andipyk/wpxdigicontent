# Contributing to DigiContent

First off, thank you for considering contributing to DigiContent! It's people like you that make DigiContent such a great tool.

## Code of Conduct

This project and everyone participating in it is governed by our [Code of Conduct](CODE_OF_CONDUCT.md). By participating, you are expected to uphold this code.

## How Can I Contribute?

### Reporting Bugs

Before creating bug reports, please check the issue list as you might find out that you don't need to create one. When you are creating a bug report, please include as many details as possible:

* Use a clear and descriptive title
* Describe the exact steps to reproduce the problem
* Provide specific examples to demonstrate the steps
* Describe the behavior you observed and what behavior you expected to see
* Include screenshots if possible
* Include your WordPress version and PHP version
* Note any recent changes that might have caused the issue

### Suggesting Enhancements

Enhancement suggestions are tracked as GitHub issues. When creating an enhancement suggestion, please include:

* A clear and descriptive title
* A detailed description of the proposed functionality
* Explain why this enhancement would be useful
* List any similar features in other plugins
* Include mockups or examples if applicable

### Pull Requests

* Fill in the required template
* Follow the WordPress Coding Standards
* Include appropriate test cases
* Link PR to issue if you are solving one
* Document new code based on the Documentation Styleguide
* End files with a newline

## Development Process

1. Fork the repo and create your branch from `main`
2. Run `composer install` and `npm install`
3. Make your changes
4. Run tests and ensure they pass
5. Update documentation if needed
6. Create Pull Request

### Local Development Setup

```bash
# Clone your fork
git clone git@github.com:your-username/digicontent.git

# Add upstream remote
git remote add upstream git@github.com:original-username/digicontent.git

# Install dependencies
composer install
npm install

# Build assets
npm run build

# Run tests
composer run test
npm test
```

### Coding Standards

* Follow [WordPress PHP Coding Standards](https://make.wordpress.org/core/handbook/best-practices/coding-standards/php/)
* Use [PSR-12](https://www.php-fig.org/psr/psr-12/) for PHP code style
* Follow [WordPress JavaScript Coding Standards](https://make.wordpress.org/core/handbook/best-practices/coding-standards/javascript/)
* Use ESLint for JavaScript linting
* Write clear, readable, and maintainable code
* Comment your code when necessary
* Keep functions small and focused
* Use meaningful variable and function names

### Testing

* Write unit tests for new functionality
* Update existing tests when modifying code
* Ensure all tests pass before submitting PR
* Include both positive and negative test cases
* Test edge cases and error conditions

### Documentation

* Update README.md if needed
* Add PHPDoc blocks for new classes and methods
* Include inline documentation for complex code
* Update changelog for significant changes
* Document any new hooks or filters

### Git Commit Messages

* Use the present tense ("Add feature" not "Added feature")
* Use the imperative mood ("Move cursor to..." not "Moves cursor to...")
* Limit the first line to 72 characters or less
* Reference issues and pull requests liberally after the first line
* Consider starting the commit message with an applicable emoji:
    * 🎨 `:art:` when improving the format/structure of the code
    * 🐛 `:bug:` when fixing a bug
    * ✨ `:sparkles:` when adding a new feature
    * 📝 `:memo:` when writing docs
    * 🔒 `:lock:` when dealing with security
    * ⚡️ `:zap:` when improving performance
    * 🔧 `:wrench:` when updating configs

## Additional Notes

### Issue and Pull Request Labels

* `bug` - Something isn't working
* `enhancement` - New feature or request
* `documentation` - Improvements or additions to documentation
* `good first issue` - Good for newcomers
* `help wanted` - Extra attention is needed
* `security` - Security related issues
* `performance` - Performance improvements
* `refactor` - Code improvements
* `testing` - Test related changes

## Questions?

* Create a GitHub issue
* Email the maintainers
* Join our community chat

Thank you for contributing to DigiContent! 🎉 