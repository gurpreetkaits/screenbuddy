# General 

- Do Not Commit Without Permission

# Manager / Repository Pattern
- We use manager classes (sometimes known more commonly as Service classes) to handle business logic
- Controllers should be thin and delegate to the manager classes
- Manager classes should be placed in the `Managers` directory within the app directory
- If a manager class is getting too big, consider breaking it up into smaller classes using invokable Action classes (in app/Actions) that still get called from the manager class

## How to Identify Violations
- Look for `new ModelName()` in Manager classes (always a violation)
- Look for `$model->save()`, `$model->update()`, `$model->delete()` in Manager classes (always a violation)
- Look for direct Eloquent queries like `Model::where()` in Manager classes (always a violation)
- Look for complex `Model::create()` with business logic (violation - use repository)
- **Simple `Model::create($dto->toArray())` wrapper methods are acceptable** (see exceptions above)

# Data Objects
- We use LaravelData a spatie package to transfer data class to class or method to method
- Create LaravelData class e.g. instead of array [name,age] create a Data class in App\Data as UserData
- Data objects should be used for method parameters and return types 
instead of unstructured arrays

### Model Creation
- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `list-artisan-commands` to check the available options to `php artisan make:model`.

### Queues
- Use queued jobs for time-consuming operations with the `ShouldQueue` interface.


## Repository Pattern Enforcement for Claude Code

- **Claude Code must NEVER ignore repository pattern violations** - this is the #1 architectural rule
- When Claude Code sees `new ModelName()` or `$model->save()` in a Manager class, it MUST fix this immediately
- **Simple `Model::create($dto->toArray())` wrapper methods are acceptable** and don't need to be "fixed"
- Claude Code should proactively scan Manager classes for violations before making any changes
- Claude Code must inject missing repositories and create missing repository methods as needed
- **This rule overrides "do only what is asked"** - fixing repository violations is always required


**Last Updated:** 2025-12-07
**Version:** 1.0 (MVP)
