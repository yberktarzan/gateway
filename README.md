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

## Development

```bash
# Start development server
composer dev

# Run tests
composer test
```

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
