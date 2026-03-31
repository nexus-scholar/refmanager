# Contributing to Nexus RefManager

Thank you for your interest in contributing to Nexus RefManager!

## Development Setup

1. Clone the repository:
   ```bash
   git clone https://github.com/nexus-scholar/refmanager.git
   cd refmanager
   ```

2. Install dependencies:
   ```bash
   composer install
   ```

3. Run the test suite:
   ```bash
   composer test
   ```

## Coding Standards

- Follow PSR-12 coding standards
- Use PHP 8.2+ features (constructor property promotion, readonly, match expressions)
- Add return types to all public methods
- Write tests for new features
- Keep methods small and focused

## Working on Code

1. Create a feature branch from `main`:
   ```bash
   git checkout -b feature/your-feature-name
   ```

2. Make your changes, following the [AGENTS.md](AGENTS.md) guidelines

3. Write or update tests

4. Run tests to ensure everything passes:
   ```bash
   ./vendor/bin/phpunit
   ```

5. Commit your changes with a clear message:
   ```bash
   git commit -m "Add feature: brief description"
   ```

6. Push to your fork and create a Pull Request

## Pull Request Guidelines

- **One feature per PR** - Keep changes focused and atomic
- **Describe your changes** - Explain what the PR does and why
- **Link issues** - Reference any related issues
- **Keep it small** - Smaller PRs get reviewed faster
- **Update documentation** - Update docs if needed

## Reporting Issues

- Use the [GitHub Issues](https://github.com/nexus-scholar/refmanager/issues)
- Search before creating a duplicate
- Include PHP version, Laravel version, and relevant code samples
- Provide minimal reproduction steps for bugs

## Questions?

Feel free to open a discussion or reach out!
