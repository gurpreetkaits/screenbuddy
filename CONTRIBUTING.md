# Contributing to ScreenSense

First off, thank you for considering contributing to ScreenSense! It's people like you that make ScreenSense such a great tool.

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
- **Explain why this enhancement would be useful** to most ScreenSense users
- **List any similar features** in other tools if applicable

### Pull Requests

1. **Fork the repository** and create your branch from `master` (see [Branch Management](#branch-management))
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

### Setup Git Hooks

We use Git hooks to auto-format code with Pint on every commit:

```bash
git config core.hooksPath .githooks
```

This will automatically run Laravel Pint on staged PHP files before each commit.

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

## Branch Management

### Branch Strategy (GitHub Flow)

We use a simplified GitHub Flow:

```
master (protected, production-ready)
  │
  ├── feature/video-thumbnails    ← New features
  ├── fix/upload-bug              ← Bug fixes
  ├── refactor/video-controller   ← Code improvements
  └── docs/api-endpoints          ← Documentation
```

### Branch Rules

| Branch | Protection | Who Can Merge |
|--------|------------|---------------|
| `master` | Protected | Maintainers only (via PR) |
| `feature/*` | None | Anyone (your fork) |
| `fix/*` | None | Anyone (your fork) |

### Branch Naming Convention

Use descriptive names with prefixes:

| Prefix | Purpose | Example |
|--------|---------|---------|
| `feature/` | New features | `feature/video-thumbnails` |
| `fix/` | Bug fixes | `fix/upload-validation` |
| `refactor/` | Code refactoring | `refactor/video-controller` |
| `docs/` | Documentation | `docs/api-endpoints` |
| `test/` | Test additions | `test/video-upload` |
| `chore/` | Maintenance tasks | `chore/update-dependencies` |

### Creating a Branch

```bash
# Always start from latest master
git checkout master
git pull origin master

# Create your branch
git checkout -b feature/my-awesome-feature

# Make your changes, then push
git add .
git commit -m "Add awesome feature"
git push origin feature/my-awesome-feature
```

---

## Pull Request Workflow

### Before Submitting a PR

1. **Sync with master** - Rebase or merge latest master into your branch
2. **Run tests** - `composer test`
3. **Check formatting** - `composer pint`
4. **Self-review** - Review your own diff before submitting

```bash
# Sync with master
git fetch origin
git rebase origin/master

# Run checks
composer pint
composer test
```

### PR Requirements

All PRs must meet these requirements before merging:

- [ ] **Passing CI** - All automated tests pass
- [ ] **Code Review** - At least 1 approval from maintainer
- [ ] **No Conflicts** - Branch is up-to-date with master
- [ ] **Linked Issue** - Reference related issue (if applicable)
- [ ] **Clear Description** - Explain what and why

### PR Title Format

Use conventional commit style for PR titles:

```
<type>: <short description>

Examples:
feat: Add video thumbnail generation
fix: Resolve upload validation error
refactor: Simplify VideoController logic
docs: Update API documentation
test: Add VideoController tests
chore: Update dependencies
```

### PR Description Template

```markdown
## Summary
Brief description of the changes.

## Changes
- Added X
- Fixed Y
- Updated Z

## Testing
Describe how you tested these changes.

## Screenshots (if applicable)
Add screenshots for UI changes.

## Related Issues
Closes #123
```

### Review Process

1. **Submit PR** → Automated CI runs
2. **CI Passes** → Maintainer reviews code
3. **Feedback** → Make requested changes (if any)
4. **Approval** → Maintainer approves
5. **Merge** → Maintainer merges to master

### After Your PR is Merged

```bash
# Switch to master and pull latest
git checkout master
git pull origin master

# Delete your local branch
git branch -d feature/my-awesome-feature

# Delete remote branch (if needed)
git push origin --delete feature/my-awesome-feature
```

---

## Releases

We use **tag-based releases** with [Semantic Versioning](https://semver.org/):

```
v1.0.0  →  MAJOR.MINOR.PATCH
  │ │ │
  │ │ └── Patch: Bug fixes (backwards compatible)
  │ └──── Minor: New features (backwards compatible)
  └────── Major: Breaking changes
```

### Release Process (Maintainers Only)

```bash
# Ensure master is up to date
git checkout master
git pull origin master

# Create and push tag
git tag -a v1.2.0 -m "Release v1.2.0"
git push origin v1.2.0
```

Releases are created automatically via GitHub Actions when a tag is pushed.

---

## Issue Labels

| Label | Description |
|-------|-------------|
| `bug` | Something isn't working |
| `enhancement` | New feature request |
| `documentation` | Documentation improvements |
| `good first issue` | Good for newcomers |
| `help wanted` | Extra attention needed |
| `wontfix` | This will not be worked on |
| `duplicate` | This issue already exists |
| `priority: high` | High priority issue |
| `priority: low` | Low priority issue |

## Project Structure

```
screenbuddy/
├── app/
│   ├── Http/
│   │   └── Controllers/   # Handle HTTP request/response only
│   ├── Managers/          # Business logic layer
│   ├── Repositories/      # Database interaction layer
│   ├── Models/            # Eloquent models
│   └── Jobs/              # Background jobs (video conversion)
├── database/
│   └── migrations/        # Database migrations
├── frontend/              # Vue.js frontend
│   └── src/
│       ├── components/    # Vue components
│       ├── views/         # Page views
│       └── lib/           # Utilities and helpers
├── routes/
│   └── api.php           # API routes
└── tests/                # PHP tests
```

### Architecture Pattern

We follow the **Controller → Manager → Repository** pattern:

```
Controller (request/response handling)
    ↓
Manager (business logic)
    ↓
Repository (database operations)
```

**Example:**
```php
// Controller - only handles HTTP
class VideoController extends Controller
{
    public function __construct(
        protected VideoManager $videoManager
    ) {}
}

// Manager - business logic, uses short names for repos
class VideoManager
{
    public function __construct(
        protected VideoRepository $videos
    ) {}
}

// Repository - database operations only
class VideoRepository extends BaseRepository
{
    public function findByUserId(int $userId): Collection
    {
        return Video::where('user_id', $userId)->get();
    }
}
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

Thank you for contributing to ScreenSense!
