# Mahfouz Helpers

A Laravel package that provides helpful tools and utilities for API development. This package includes service classes, API controller generators, and other utilities to streamline your Laravel API development process.

## Compatibility

This package supports Laravel 11 and 12.

## Installation

You can install the package via composer:

```bash
composer require mahfouz/helpers
```

The package will automatically register its service provider.

## Features

### Base Service Class

The package provides a `BaseService` class that implements common CRUD operations for your models. It integrates with Spatie's Query Builder for advanced filtering and pagination.

```php
<?php

namespace App\Services;

use App\Models\User;
use Mahfouz\Helpers\Services\BaseService;

class UserService extends BaseService
{
    protected string $modelClass = User::class;
    
    protected function defaultFilters(): array
    {
        return array_merge(parent::defaultFilters(), [
            'name',
            'email'
        ]);
    }
}
```

### API Controller Generator

Generate API controllers with a single command:

```bash
php artisan make:api-controller UserController --methods=index,store,show,update,destroy --resource=id,name,email --store-request=name,email,password --update-request=name,email
```

Options:
- `--methods`: Comma-separated list of methods to generate (index, store, show, update, destroy)
- `--resource`: Fields to include in the resource
- `--store-request`: Fields for the store request validation
- `--update-request`: Fields for the update request validation

You can also use `--methods=*` to generate all standard CRUD methods.

### Service Generator

Generate service classes for your models:

```bash
php artisan make:service User
```

This will create a service class that extends the BaseService class and is configured for your model.

## Usage

### Services

Once you've created a service, you can use it in your controllers:

```php
class UserController extends Controller
{
    protected UserService $userService;
    
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }
    
    public function index()
    {
        return UserResource::collection(
            $this->userService->paginate(['role'])
        );
    }
    
    public function store(StoreUserRequest $request)
    {
        $user = $this->userService->store(UserData::from($request));
        return new UserResource($user);
    }
    
    // Other methods...
}
```

### Available Service Methods

The `BaseService` class provides the following methods:

- `get(?callable $callback = null)`: Get all records with optional callback for query customization
- `paginate(array $with = [], $per_page = null, ?callable $callback = null)`: Paginate records with eager loading
- `store(object $data)`: Create a new record
- `update(Model $model, object $data)`: Update an existing record
- `destroy(Model $model)`: Delete a record

## Customization

You can publish the stubs to customize them:

```bash
php artisan vendor:publish --tag=mahfouz-stubs
```

This will publish the stubs to `stubs/mahfouz` in your application.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
