# Troubleshooting Guide

This guide covers common issues and their solutions when using LangChain Laravel.

## Table of Contents

1. [Installation Issues](#installation-issues)
2. [Configuration Problems](#configuration-problems)
3. [API Key Issues](#api-key-issues)
4. [Provider-Specific Issues](#provider-specific-issues)
5. [Performance Issues](#performance-issues)
6. [Error Messages](#error-messages)
7. [Testing Issues](#testing-issues)
8. [Getting Help](#getting-help)

## Installation Issues

### Composer Installation Fails

**Problem**: `composer require langchain-laravel/langchain` fails

**Solutions**:
1. Check PHP version: `php --version` (requires PHP 8.1+)
2. Update Composer: `composer self-update`
3. Clear Composer cache: `composer clear-cache`
4. Check Laravel version compatibility (requires Laravel 10.0+)

### Service Provider Not Registered

**Problem**: LangChain facade not found

**Solutions**:
1. Ensure auto-discovery is enabled in `composer.json`
2. Manually register in `config/app.php`:
   ```php
   'providers' => [
       // ...
       LangChainLaravel\LangChainServiceProvider::class,
   ],
   ```
3. Clear Laravel cache: `php artisan config:clear`

## Configuration Problems

### Config File Not Published

**Problem**: Configuration file missing

**Solution**:
```bash
php artisan vendor:publish --tag=langchain-config
```

### Invalid Configuration

**Problem**: Configuration validation errors

**Solutions**:
1. Check `config/langchain.php` syntax
2. Validate environment variables in `.env`
3. Clear config cache: `php artisan config:clear`

## API Key Issues

### Missing API Key Error

**Problem**: "API key is required" error

**Solutions**:
1. Add API key to `.env` file:
   ```env
   OPENAI_API_KEY=your_key_here
   CLAUDE_API_KEY=your_key_here
   LLAMA_API_KEY=your_key_here
   DEEPSEEK_API_KEY=your_key_here
   ```
2. Clear config cache: `php artisan config:clear`
3. Restart web server

### Invalid API Key

**Problem**: "Invalid API key" or authentication errors

**Solutions**:
1. Verify API key is correct and active
2. Check API key permissions and quotas
3. Ensure no extra spaces or characters in `.env`
4. Test API key directly with provider's API

### API Key Not Loading

**Problem**: API key appears empty in application

**Solutions**:
1. Check `.env` file syntax (no spaces around `=`)
2. Restart development server
3. Clear all caches:
   ```bash
   php artisan config:clear
   php artisan cache:clear
   php artisan route:clear
   php artisan view:clear
   ```

## Provider-Specific Issues

### OpenAI Issues

**Rate Limiting**:
- Implement exponential backoff
- Check your usage limits
- Consider upgrading your plan

**Model Not Found**:
- Verify model name spelling
- Check model availability in your region
- Use supported models: `gpt-3.5-turbo`, `gpt-4`, etc.

### Claude (Anthropic) Issues

**API Version Mismatch**:
- Ensure correct API version in config:
  ```env
  CLAUDE_API_VERSION=2023-06-01
  ```

**Region Restrictions**:
- Claude API may not be available in all regions
- Use VPN or proxy if necessary

### Llama Issues

**Endpoint Configuration**:
- Verify base URL is correct
- Check if self-hosted endpoint is running
- Ensure proper authentication method

### DeepSeek Issues

**Model Availability**:
- Check which models are available
- Verify model names in documentation

## Performance Issues

### Slow Response Times

**Solutions**:
1. Enable caching:
   ```env
   LANGCHAIN_CACHE_ENABLED=true
   ```
2. Optimize prompts (shorter, more specific)
3. Use appropriate models (faster models for simple tasks)
4. Implement request timeouts

### Memory Issues

**Solutions**:
1. Increase PHP memory limit
2. Process large texts in chunks
3. Use streaming responses when available
4. Clear unnecessary variables

### Timeout Errors

**Solutions**:
1. Increase timeout in config:
   ```php
   'request_timeout' => 120, // seconds
   ```
2. Use asynchronous processing for long tasks
3. Implement retry logic

## Error Messages

### "Provider not found"

**Cause**: Invalid provider name

**Solution**: Use valid provider names: `openai`, `claude`, `llama`, `deepseek`

### "Model not supported"

**Cause**: Model not available for the provider

**Solution**: Check provider documentation for supported models

### "Request failed"

**Causes & Solutions**:
1. Network connectivity issues - check internet connection
2. API endpoint down - check provider status pages
3. Invalid request format - validate request parameters
4. Rate limiting - implement backoff strategy

### "Invalid response format"

**Cause**: Unexpected API response

**Solutions**:
1. Check API version compatibility
2. Validate request parameters
3. Enable debug logging to inspect responses

## Testing Issues

### Tests Failing

**Solutions**:
1. Set test API keys in `phpunit.xml`:
   ```xml
   <env name="OPENAI_API_KEY" value="test-key"/>
   ```
2. Use mocking for external API calls
3. Check test database configuration

### Mock Setup Problems

**Solution**: Use proper mocking in tests:
```php
use LangChainLaravel\Facades\LangChain;

LangChain::shouldReceive('generateText')
    ->once()
    ->andReturn(['success' => true, 'text' => 'mocked response']);
```

## Debugging Tips

### Enable Debug Logging

1. Set log level in `.env`:
   ```env
   LOG_LEVEL=debug
   ```

2. Add logging to your code:
   ```php
   use Illuminate\Support\Facades\Log;
   
   Log::debug('LangChain request', ['prompt' => $prompt]);
   ```

### Inspect Raw Responses

```php
try {
    $response = LangChain::generateText($prompt);
    Log::info('LangChain response', $response);
} catch (\Exception $e) {
    Log::error('LangChain error', [
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
```

### Check Configuration

```php
// In a controller or command
dd(config('langchain'));
```

## Getting Help

### Before Asking for Help

1. **Search existing issues** on GitHub
2. **Check the documentation** in the `docs/` folder
3. **Review examples** in the `examples/` folder
4. **Enable debug logging** and check logs
5. **Test with minimal code** to isolate the issue

### When Reporting Issues

Include:
- PHP and Laravel versions
- LangChain Laravel version
- Complete error messages
- Minimal code example
- Configuration (remove sensitive data)
- Steps to reproduce

### Community Resources

- **GitHub Issues**: Report bugs and request features
- **GitHub Discussions**: Ask questions and share ideas
- **Documentation**: Comprehensive guides and examples
- **Examples**: Real-world usage patterns

### Professional Support

For enterprise support and custom implementations, contact the maintainers through the GitHub repository.

---

*This troubleshooting guide is regularly updated. If you encounter an issue not covered here, please open an issue on GitHub to help improve this guide.*