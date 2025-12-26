# Contributing to ScreenBuddy

First off, thank you for considering contributing to ScreenBuddy! It's people like you that make ScreenBuddy such a great tool.

## Code of Conduct

This project and everyone participating in it is governed by our [Code of Conduct](CODE_OF_CONDUCT.md). By participating, you are expected to uphold this code.

## How Can I Contribute?

### Reporting Bugs

Before creating bug reports, please check the existing issues to avoid duplicates. When you create a bug report, include as many details as possible:

- **Use a clear and descriptive title**
- **Describe the exact steps to reproduce the problem**
- **Provide specific examples** - Include links, screenshots, or code snippets
- **Describe the behavior you observed** and what you expected to see
- **Include your environment details** - OS, browser, PHP version, etc.

### Suggesting Enhancements

Enhancement suggestions are tracked as GitHub issues. When creating an enhancement suggestion:

- **Use a clear and descriptive title**
- **Provide a detailed description** of the suggested enhancement
- **Explain why this enhancement would be useful** to most ScreenBuddy users
- **List any similar features** in other tools if applicable

### Pull Requests

1. **Fork the repository** and create your branch from `master`
2. **Make your changes** following our coding standards
3. **Add tests** if you've added code that should be tested
4. **Ensure the test suite passes** - Run `composer test`
5. **Make sure your code lints** - Run `composer pint` for PHP formatting
6. **Write a clear commit message** following our commit guidelines
7. **Submit your pull request**

## Development Setup

### Prerequisites

- PHP 8.2+
- Composer
- Node.js 18+
- MariaDB/MySQL
- FFmpeg

### Initial Setup

```bash
# Clone your fork
git clone https://github.com/yourusername/screenbuddy.git
cd screenbuddy

# Install dependencies
composer install
cd frontend && npm install && cd ..

# Setup environment
cp .env.example .env
php artisan key:generate

# Configure database in .env, then:
php artisan migrate
php artisan storage:link
chmod -R 775 storage/ bootstrap/cache/
```

### Running Development Servers

You need three terminals:

```bash
# Terminal 1: Backend
php artisan serve

# Terminal 2: Frontend
cd frontend && npm run dev

# Terminal 3: Queue worker
php artisan queue:work --queue=default --tries=3 --timeout=1800
```

### Running Tests

```bash
# Run all tests
composer test

# Run specific test
php artisan test --filter=VideoControllerTest

# Run with coverage
php artisan test --coverage
```

### Code Formatting

We use Laravel Pint for PHP code formatting:

```bash
# Check formatting
composer pint -- --test

# Auto-fix formatting
composer pint
```

## Coding Standards

### PHP (Backend)

- Follow [PSR-12](https://www.php-fig.org/psr/psr-12/) coding standards
- Use type hints for parameters and return types
- Write PHPDoc comments for public methods
- Keep methods focused and under 50 lines when possible
- Use Laravel's built-in helpers and facades

**Example:**

```php
/**
 * Upload a new video recording.
 *
 * @param  \Illuminate\Http\Request  $request
 * @return \Illuminate\Http\JsonResponse
 */
public function store(Request $request): JsonResponse
{
    $validated = $request->validate([
        'video' => 'required|file|mimes:webm,mp4,mov|max:512000',
        'title' => 'required|string|max:255',
    ]);

    // Implementation...
}
```

### TypeScript/React (Frontend)

- Use TypeScript for type safety
- Follow React best practices and hooks guidelines
- Use functional components over class components
- Keep components small and focused
- Use meaningful component and variable names

**Example:**

```typescript
interface VideoCardProps {
  video: Video;
  onDelete: (id: number) => void;
}

export function VideoCard({ video, onDelete }: VideoCardProps) {
  // Implementation...
}
```

### Database

- Use migrations for all schema changes
- Never modify existing migrations that have been released
- Use descriptive names for tables and columns
- Add indexes for foreign keys and frequently queried columns

### Git Commit Messages

- Use present tense ("Add feature" not "Added feature")
- Use imperative mood ("Move cursor to..." not "Moves cursor to...")
- Limit first line to 72 characters
- Reference issues and pull requests when relevant

**Good examples:**
```
Add video thumbnail generation
Fix video streaming range request bug
Update README with installation steps
Refactor VideoController for better readability
```

**Bad examples:**
```
fixed bug
updated stuff
WIP
asdfasdf
```

## Branch Naming

Use descriptive branch names with prefixes:

- `feature/` - New features (e.g., `feature/video-thumbnails`)
- `fix/` - Bug fixes (e.g., `fix/upload-validation`)
- `refactor/` - Code refactoring (e.g., `refactor/video-controller`)
- `docs/` - Documentation updates (e.g., `docs/api-endpoints`)
- `test/` - Test additions/changes (e.g., `test/video-upload`)

## Project Structure

```
screenbuddy/
├── app/                    # Laravel application code
│   ├── Http/Controllers/   # API controllers
│   ├── Models/            # Eloquent models
│   └── Services/          # Business logic services
├── database/
│   └── migrations/        # Database migrations
├── frontend/              # React frontend
│   └── src/
│       ├── components/    # React components
│       ├── pages/         # Page components
│       └── lib/           # Utilities and helpers
├── routes/
│   └── api.php           # API routes
└── tests/                # PHP tests
```

## Testing Guidelines

- Write tests for new features
- Update tests when modifying existing features
- Aim for high test coverage on critical paths
- Use descriptive test names

```php
/** @test */
public function it_uploads_video_successfully(): void
{
    Storage::fake('public');

    $response = $this->postJson('/api/videos', [
        'video' => UploadedFile::fake()->create('test.webm', 1024),
        'title' => 'Test Video',
        'duration' => 60,
    ]);

    $response->assertStatus(201);
    $this->assertDatabaseHas('videos', ['title' => 'Test Video']);
}
```

## Documentation

- Update README.md if you change functionality
- Add or update inline code comments for complex logic
- Update API documentation for endpoint changes
- Add JSDoc comments for TypeScript functions

## Need Help?

- Check existing issues and discussions
- Read the [CLAUDE.md](CLAUDE.md) development guide
- Join our discussions on GitHub
- Ask questions in pull request comments

## Recognition

Contributors will be recognized in our:
- GitHub contributors page
- Release notes for significant contributions
- Special mentions in our community

## License

By contributing, you agree that your contributions will be licensed under the MIT License.

---

Thank you for contributing to ScreenBuddy!
