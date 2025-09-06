# API Gateway

A powerful API Gateway built with Laravel framework for managing and routing API requests to microservices.

## Features

- **API Routing**: Intelligent request routing to backend services
- **Rate Limiting**: Built-in rate limiting and throttling
- **Authentication**: Centralized authentication and authorization
- **Request/Response Transformation**: Modify requests and responses
- **Monitoring**: Comprehensive logging and monitoring
- **Load Balancing**: Distribute requests across multiple service instances

## Code Quality

This project uses automated code quality tools:

- **Laravel Pint**: Automatic code formatting following Laravel standards
- **PHPStan (Larastan)**: Static analysis for better code quality
- **GitHub Actions**: Automated quality checks on every push

### Available Commands

```bash
# Code formatting
composer pint              # Fix code style issues
composer pint-test         # Check code style without fixing

# Static analysis
composer phpstan           # Run PHPStan analysis

# Combined quality checks
composer quality           # Run both pint-test and phpstan
composer quality-fix       # Run pint (with fixes) and phpstan
```

### Pre-commit Hooks

The project includes pre-commit hooks that automatically run Pint and PHPStan on staged files before each commit, ensuring code quality is maintained.

## Semantic Commits

This project enforces [Conventional Commits](https://www.conventionalcommits.org/) specification:

### Format
```
<type>[optional scope]: <description>
```

### Available Types
- `feat`: A new feature
- `fix`: A bug fix  
- `docs`: Documentation only changes
- `style`: Code style changes (formatting, etc)
- `refactor`: Code refactoring
- `test`: Adding or updating tests
- `chore`: Maintenance tasks
- `perf`: Performance improvements
- `ci`: CI/CD changes
- `build`: Build system changes
- `revert`: Revert previous commit

### Examples
```bash
feat(auth): add JWT authentication
fix: resolve memory leak in parser
docs(api): update endpoint documentation
style: format code with Pint
refactor(user): simplify validation logic
```

### Rules
- Description must be 1-50 characters
- Use lowercase for description
- Use imperative mood (add, not added)
- No period at the end

```bash
# Get commit examples
composer commit-help
```

## Development

### Initial Setup
```bash
# Clone the repository
git clone https://github.com/yberktarzan/gateway.git
cd gateway

# Install dependencies
composer install

# Setup Git hooks and project
composer setup
```

### Available Commands
```bash
# Start development server
composer dev

# Run tests
composer test

# Install/reinstall Git hooks
composer install-hooks
```

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
