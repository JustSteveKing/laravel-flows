# GEMINI.md

## Project Overview

This is a PHP library for Laravel that provides a fluent, modular workflow builder. It leverages Laravel's Pipeline pattern to simplify and organize complex business logic into a series of manageable steps. The library is designed to be lightweight, with no runtime dependencies beyond PHP 8.3+ and Laravel.

The core of the library is the `Flow` class, which allows you to chain together different actions, including conditional branches, exception handling, and logging.

## Building and Running

The project uses `composer` to manage dependencies and scripts.

### Dependencies

To install the project dependencies, run:

```bash
composer install
```

### Testing

The project uses PHPUnit for unit tests. To run the test suite:

```bash
composer test
```

The tests are located in the `tests/` directory and follow the same namespace structure as the `src/` directory.

### Code Style and Linting

This project uses `laravel/pint` for code style.

To check for any style violations, run:
```bash
composer lint
```

To automatically fix any style violations, run:
```bash
composer pint
```

### Static Analysis

The project uses `phpstan` for static analysis.

To run the static analyzer:
```bash
composer stan
```

## Development Conventions

### File Structure

- Source code is located in the `src/` directory.
- Tests are in the `tests/` directory.
- The main entry point for the library is `src/Flow.php`.

### Autoloading

The project uses PSR-4 autoloading, configured in `composer.json`:
- The `JustSteveKing\Flows\` namespace maps to the `src/` directory.
- The `JustSteveKing\Flows\Tests\` namespace maps to the `tests/` directory.

### Core Concepts

The library is built around a few key contracts:

- **`FlowStep`:** An interface for a single step in a workflow. Each step must implement a `handle` method.
- **`FlowCondition`:** An interface for a condition that can be used in a `branch` step. Each condition must be invokable and return a boolean.

### Laravel Integration

The package registers a service provider (`JustSteveKing\Flows\Providers\PackageServiceProvider`) to integrate with Laravel applications. The `Flow` class uses Laravel's `resolve()` helper to instantiate step and condition classes, allowing for dependency injection within your workflow steps.
