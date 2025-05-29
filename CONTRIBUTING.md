# Contributing to LangChain Laravel

Thank you for your interest in contributing to the LangChain Laravel package! This document provides guidelines and information for contributors.

## Table of Contents

- [Code of Conduct](#code-of-conduct)
- [Getting Started](#getting-started)
- [Development Setup](#development-setup)
- [Contributing Guidelines](#contributing-guidelines)
- [Pull Request Process](#pull-request-process)
- [Coding Standards](#coding-standards)
- [Testing](#testing)
- [Documentation](#documentation)
- [Issue Reporting](#issue-reporting)
- [Feature Requests](#feature-requests)
- [Security Issues](#security-issues)

## Code of Conduct

This project adheres to a code of conduct that we expect all contributors to follow. Please be respectful, inclusive, and constructive in all interactions.

### Our Standards

- **Be respectful**: Treat everyone with respect and kindness
- **Be inclusive**: Welcome newcomers and help them get started
- **Be constructive**: Provide helpful feedback and suggestions
- **Be patient**: Remember that everyone has different experience levels
- **Be collaborative**: Work together towards common goals

## Getting Started

### Prerequisites

- PHP 8.1 or higher
- Composer
- Laravel 10.0+ knowledge
- Git
- OpenAI API key (for testing)

### Fork and Clone

1. Fork the repository on GitHub
2. Clone your fork locally:
   ```bash
   git clone https://github.com/YOUR_USERNAME/langchain-laravel.git
   cd langchain-laravel
   ```
3. Add the upstream repository:
   ```bash
   git remote add upstream https://github.com/YourWisemaker/langchain-laravel.git
   ```

## Development Setup

### 1. Install Dependencies

```bash
composer install
```

### 2. Environment Configuration

```bash
# Copy the example environment file
cp .env.example .env

# Add your OpenAI API key for testing
echo "OPENAI_API_KEY=your_api_key_here" >> .env
```

### 3. Verify Setup

```bash
# Run tests to ensure everything is working
composer test

# Run static analysis
composer analyse

# Check code formatting
composer format-check
```

## Contributing Guidelines

### Types of Contributions

We welcome various types of contributions:

- **Bug fixes**: Fix issues and improve stability
- **Feature additions**: Add new functionality
- **Documentation**: Improve or add documentation
- **Tests**: Add or improve test coverage
- **Performance**: Optimize existing code
- **Examples**: Add usage examples
- **Refactoring**: Improve code structure

### Before You Start

1. **Check existing issues**: Look for existing issues or discussions
2. **Create an issue**: For significant changes, create an issue first
3. **Discuss your approach**: Get feedback before implementing
4. **Keep it focused**: One feature/fix per pull request

### Branch Naming

Use descriptive branch names:

- `feature/add-streaming-support`
- `fix/memory-leak-in-batch-processing`
- `docs/improve-installation-guide`
- `test/add-integration-tests`
- `refactor/simplify-error-handling`

## Pull Request Process

### 1. Create a Feature Branch

```bash
# Update your main branch
git checkout main
git pull upstream main

# Create a new feature branch
git checkout -b feature/your-feature-name
```

### 2. Make Your Changes

- Write clean, readable code
- Follow coding standards
- Add tests for new functionality
- Update documentation as needed
- Commit with clear messages

### 3. Test Your Changes

```bash
# Run the full test suite
composer test

# Run static analysis
composer analyse

# Check code formatting
composer format

# Run performance tests if applicable
vendor/bin/phpunit --group=performance
```

### 4. Commit Your Changes

```bash
# Stage your changes
git add .

# Commit with a descriptive message
git commit -m "Add streaming support for real-time responses

- Implement StreamingResponse class
- Add streaming configuration options
- Update documentation with streaming examples
- Add tests for streaming functionality"
```

### 5. Push and Create Pull Request

```bash
# Push your branch
git push origin feature/your-feature-name

# Create a pull request on GitHub
```

### Pull Request Template

When creating a pull request, please include:

```markdown
## Description
Brief description of the changes made.

## Type of Change
- [ ] Bug fix
- [ ] New feature
- [ ] Documentation update
- [ ] Performance improvement
- [ ] Refactoring
- [ ] Test improvement

## Testing
- [ ] All existing tests pass
- [ ] New tests added for new functionality
- [ ] Manual testing completed
- [ ] Performance impact assessed

## Documentation
- [ ] Documentation updated
- [ ] Examples added/updated
- [ ] Changelog updated

## Checklist
- [ ] Code follows project coding standards
- [ ] Self-review completed
- [ ] No breaking changes (or clearly documented)
- [ ] Security implications considered
```

## Coding Standards

### PHP Standards

We follow PSR-12 coding standards with some additional rules:

```php
<?php

namespace LangChainLaravel\Services;

use Exception;
use Illuminate\Support\Facades\Cache;

/**
 * Service class for handling AI operations.
 */
class AIService
{
    /**
     * Generate text using AI.
     *
     * @param string $prompt The input prompt
     * @param array<string, mixed> $options Additional options
     * @return array{success: bool, text?: string, error?: string}
     */
    public function generateText(string $prompt, array $options = []): array
    {
        try {
            // Implementation here
            return ['success' => true, 'text' => $result];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
```

### Key Rules

1. **Type Hints**: Always use type hints for parameters and return types
2. **PHPDoc**: Document all public methods with proper PHPDoc blocks
3. **Array Shapes**: Use array shape notation for complex arrays
4. **Exceptions**: Handle exceptions appropriately
5. **Naming**: Use descriptive names for variables and methods
6. **Constants**: Use class constants for configuration values

### Code Formatting

```bash
# Format code automatically
composer format

# Check formatting without making changes
composer format-check
```

## Testing

### Test Structure

We maintain comprehensive test coverage:

```
tests/
├── Unit/                    # Unit tests for individual classes
│   ├── LangChainManagerTest.php
│   └── ConfigurationTest.php
├── Feature/                 # Feature tests for Laravel integration
│   ├── LangChainFacadeTest.php
│   └── AdvancedFeaturesTest.php
├── Integration/             # Integration tests with external APIs
│   └── OpenAIIntegrationTest.php
└── Performance/             # Performance and benchmark tests
    └── BenchmarkTest.php
```

### Writing Tests

#### Unit Tests

```php
<?php

namespace Tests\Unit;

use LangChainLaravel\LangChainManager;
use PHPUnit\Framework\TestCase;

class LangChainManagerTest extends TestCase
{
    public function test_can_generate_text_successfully()
    {
        $manager = new LangChainManager([
            'openai' => ['api_key' => 'test-key']
        ]);
        
        // Mock the OpenAI client
        // Test the functionality
        
        $this->assertTrue($result['success']);
    }
}
```

#### Feature Tests

```php
<?php

namespace Tests\Feature;

use LangChainLaravel\Facades\LangChain;
use Tests\TestCase;

class LangChainFacadeTest extends TestCase
{
    public function test_facade_can_generate_text()
    {
        config(['langchain.openai.api_key' => 'test-key']);
        
        LangChain::shouldReceive('generateText')
            ->once()
            ->andReturn(['success' => true, 'text' => 'Generated text']);
        
        $response = LangChain::generateText('Test prompt');
        
        $this->assertTrue($response['success']);
    }
}
```

### Running Tests

```bash
# Run all tests
composer test

# Run specific test suites
vendor/bin/phpunit --testsuite=Unit
vendor/bin/phpunit --testsuite=Feature
vendor/bin/phpunit --testsuite=Integration

# Run tests with coverage
composer test-coverage

# Run performance tests
vendor/bin/phpunit --group=performance
```

### Test Guidelines

1. **Coverage**: Aim for 90%+ test coverage
2. **Isolation**: Tests should be independent
3. **Mocking**: Mock external dependencies
4. **Assertions**: Use descriptive assertion messages
5. **Data Providers**: Use data providers for multiple test cases
6. **Performance**: Include performance tests for critical paths

## Documentation

### Documentation Structure

```
docs/
├── installation.md     # Installation and setup
├── api.md             # API reference
├── usage-guide.md     # Usage examples and best practices
└── testing-guide.md   # Testing strategies

examples/
├── BasicUsage.php           # Simple examples
├── AdvancedUsage.php        # Complex use cases
├── WebApplicationExamples.php # Web integration
└── MiddlewareExamples.php   # Middleware patterns
```

### Writing Documentation

1. **Clear and Concise**: Write clear, easy-to-understand documentation
2. **Examples**: Include practical examples
3. **Code Blocks**: Use proper syntax highlighting
4. **Links**: Link to related sections
5. **Updates**: Keep documentation up-to-date with code changes

### Documentation Standards

```markdown
# Section Title

Brief description of what this section covers.

## Subsection

Detailed explanation with examples.

```php
// Code example with proper syntax highlighting
$response = LangChain::generateText('Hello, world!');
```

### Key Points

- Important information
- Best practices
- Common pitfalls to avoid
```

## Issue Reporting

### Before Reporting

1. **Search existing issues**: Check if the issue already exists
2. **Reproduce the issue**: Ensure you can consistently reproduce it
3. **Gather information**: Collect relevant details

### Issue Template

```markdown
## Bug Report

**Description**
A clear description of the bug.

**Steps to Reproduce**
1. Step one
2. Step two
3. Step three

**Expected Behavior**
What you expected to happen.

**Actual Behavior**
What actually happened.

**Environment**
- PHP version:
- Laravel version:
- Package version:
- OpenAI PHP client version:

**Additional Context**
Any additional information, logs, or screenshots.
```

## Feature Requests

### Before Requesting

1. **Check existing requests**: Look for similar feature requests
2. **Consider the scope**: Ensure it fits the package's purpose
3. **Think about implementation**: Consider how it might be implemented

### Feature Request Template

```markdown
## Feature Request

**Problem Statement**
Describe the problem this feature would solve.

**Proposed Solution**
Describe your proposed solution.

**Alternative Solutions**
Describe any alternative solutions you've considered.

**Use Cases**
Provide specific use cases for this feature.

**Implementation Ideas**
Any ideas about how this could be implemented.
```

## Security Issues

### Reporting Security Issues

**DO NOT** report security issues in public GitHub issues.

Instead:
1. Email security issues to: [security@example.com]
2. Include detailed information about the vulnerability
3. Allow time for the issue to be addressed before public disclosure

### Security Best Practices

1. **API Keys**: Never commit API keys to the repository
2. **Input Validation**: Always validate user input
3. **Error Messages**: Don't expose sensitive information in errors
4. **Dependencies**: Keep dependencies up-to-date
5. **HTTPS**: Always use HTTPS for API communications

## Development Workflow

### Daily Development

```bash
# Start your day
git checkout main
git pull upstream main

# Create feature branch
git checkout -b feature/new-feature

# Make changes, test, commit
git add .
git commit -m "Descriptive commit message"

# Push and create PR
git push origin feature/new-feature
```

### Code Review Process

1. **Self Review**: Review your own code first
2. **Automated Checks**: Ensure all CI checks pass
3. **Peer Review**: Wait for maintainer review
4. **Address Feedback**: Make requested changes
5. **Merge**: Maintainer will merge when ready

### Release Process

1. **Version Bump**: Update version in `composer.json`
2. **Changelog**: Update `CHANGELOG.md`
3. **Testing**: Run full test suite
4. **Tag**: Create git tag
5. **Release**: Create GitHub release

## Getting Help

### Resources

- **Documentation**: Check the `docs/` directory
- **Examples**: Look at the `examples/` directory
- **Tests**: Review test files for usage patterns
- **Issues**: Search existing GitHub issues
- **Discussions**: Use GitHub Discussions for questions

### Communication Channels

- **GitHub Issues**: Bug reports and feature requests
- **GitHub Discussions**: Questions and general discussion
- **Pull Requests**: Code contributions and reviews

### Maintainer Response Times

- **Issues**: We aim to respond within 48 hours
- **Pull Requests**: Initial review within 72 hours
- **Security Issues**: Response within 24 hours

## Recognition

### Contributors

All contributors will be recognized in:
- `CONTRIBUTORS.md` file
- GitHub contributors list
- Release notes for significant contributions

### Types of Recognition

- **Code Contributors**: Those who submit code changes
- **Documentation Contributors**: Those who improve documentation
- **Community Contributors**: Those who help with issues and discussions
- **Testing Contributors**: Those who improve test coverage

## License

By contributing to this project, you agree that your contributions will be licensed under the same license as the project (MIT License).

---

Thank you for contributing to LangChain Laravel! Your contributions help make this package better for everyone.