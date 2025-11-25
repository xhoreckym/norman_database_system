# Laravel Project

## Tech Stack
- Laravel 11
- PHP 8.2+
- PostgreSQL 16+
- Tailwind CSS 3.x
- Livewire 3.x
- Spatie/Permission

## Commands
- `php artisan migrate` - User runs migration manually
- `php artisan migrate:fresh --seed` - User runs seeders manually
- `composer pint` - Format code (PSR-12)

## Database (PostgreSQL)
- Use migrations for ALL schema changes
- Use `jsonb` for JSON columns (not `json`)
- Add indexes on foreign keys and frequently queried columns
- Use database transactions for multi-step operations
- Prefer Eloquent relationships over raw queries
- Use UUID for primary keys if requested by user: `$table->uuid('id')->primary()`

## Code Standards
- Follow PSR-12
- Use FormRequests for validation
- Service classes in `app/Services/`
- Action classes in `app/Actions/` for complex logic
- Always use strict types: `declare(strict_types=1);`
- Type hint everything (params, returns, properties)
- Use readonly properties (PHP 8.1+)

## Tailwind CSS
- Use Tailwind utilities, avoid custom CSS
- Component classes in `resources/css/app.css` only for repeated patterns
- Always keep in mind the mobile view, but desktop is primary and preferred. `md:`, `lg:` prefixes
- Use Tailwind's color palette, don't define custom colors unless necessary
- Prefer Tailwind forms plugin for form styling
- Never use traditional BLUE and INDIGO colors

## Laravel Best Practices
- Use resource controllers: `php artisan make:controller PostController --resource`
- Use make:migration: `php artisan make:migration`
- Route model binding over manual `findOrFail()`
- Eager load relationships to avoid N+1: `Post::with('author')->get()`
- Use query scopes for reusable queries
- Jobs for long-running tasks
- Events for decoupled actions

## File Organization
```
app/
├── Actions/          # Single-purpose action classes
├── Services/         # Business logic
├── Http/
│   ├── Controllers/  # Thin controllers
│   └── Requests/     # Form validation
├── Models/           # Eloquent models
└── Policies/         # Authorization

resources/
├── views/            # Blade templates
└── css/
    └── app.css       # Tailwind + custom components
```

## Testing
- Feature tests for user-facing functionality
- Unit tests for complex business logic
- Use factories for test data
- Database transactions in tests: `use RefreshDatabase;`

## Do NOT
- Never commit `.env` file
- Never modify `vendor/` or `node_modules/`
- Don't use `DB::raw()` with user input (SQL injection)
- Don't store sensitive data in session/localStorage
- Don't skip validation on API endpoints
- Don't use `SELECT *` in production queries
- Don't forget CSRF protection on forms

## Git
- Branch naming: `feature/`, `fix/`, `refactor/`
- Commit after each working feature
- Run tests before committing