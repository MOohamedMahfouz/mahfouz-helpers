<?php

namespace Mahfouz\Helpers\Console\Commands;


use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeApiCommand extends Command
{
    protected $signature = 'make:api-controller
    {name : Controller name}
    {--methods=* : Methods to generate}
    {--resource=* : Resource fields}
    {--store-request=* : Store request fields}
    {--update-request=* : Update request fields}';
    protected $description = 'Create a new API controller with specified methods';
    protected $filesystem;

    public function __construct(Filesystem $filesystem)
    {
        parent::__construct();
        $this->filesystem = $filesystem;
    }

    public function handle()
    {
        $name = $this->argument('name');
        $methods = explode(',', $this->option('methods')[0] ?? '');
        $modelName = $this->getModelName($name);
        $variableName = lcfirst($modelName);
        $path = $this->getPath($name);
        $namespace = $this->getNamespace($name);

        if ($this->filesystem->exists($path)) {
            $this->error('Controller already exists!');
            return;
        }

        $modelName = $this->getModelName($name);
        $serviceClass = "{$modelName}Service";
        $storeRequest = "";
        $updateRequest = "";
        $resource = "{$modelName}Resource";
        $dataImport = "";

        if ($resourceFields = $this->option('resource')) {
            $this->generateResource($modelName, explode(',', $resourceFields[0] ?? ''));
        }

        if ($storeFields = $this->option('store-request')) {
            $this->generateRequest('Store', $modelName, explode(',', $storeFields[0] ?? ''));
            $storeRequest = "Store{$modelName}Request";
            $dataImport = "App\\Data\\{$modelName}Data";
        }

        if ($updateFields = $this->option('update-request')) {
            $this->generateRequest('Update', $modelName, explode(',', $updateFields[0] ?? ''));
            $updateRequest = "Update{$modelName}Request";
            $dataImport = "App\\Data\\{$modelName}Data";
        }

        $content = $this->buildClass(
            $name,
            $namespace,
            $modelName,
            $variableName,
            $serviceClass,
            $storeRequest,
            $updateRequest,
            $dataImport,
            $resource,
            $methods
        );

        $this->filesystem->put($path, $content);

        $this->info("Controller created successfully: {$name}");

        $this->printRoutes(
            $modelName,
            $methods,
            $this->argument('name'),
            $variableName
        );
    }

    protected function getPath($name)
    {
        $name = Str::replaceFirst('Controllers\\', '', $name);
        return app_path("Http/Controllers/{$name}.php");
    }

    protected function getNamespace($name)
    {
        $namespace = 'App\\Http\\Controllers';
        if (strpos($name, '/') !== false) {
            $segments = explode('/', $name);
            array_pop($segments);
            $namespace .= '\\' . implode('\\', $segments);
        }
        return $namespace;
    }

    protected function getModelName($name)
    {
        return Str::singular(str_replace('Controller', '', class_basename($name)));
    }



    protected function buildClass(
        $name,
        $namespace,
        $modelName,
        $variableName,
        $serviceClass,
        $storeRequest,
        $updateRequest,
        $dataImport,
        $resource,
        $methods
    ) {
        $stub = file_get_contents(__DIR__ . '/stubs/api-controller.stub');

        $controllerSegments = explode('/', $this->argument('name'));
        array_pop($controllerSegments);
        $directory = implode('/', $controllerSegments);
        $requestNamespace = "App\\Http\\Requests\\" . str_replace('/', '\\', $directory);

        $requestImports = '';
        if ($storeRequest) {
            $requestImports .= "use {$requestNamespace}\\Store{$modelName}Request;\n";
        }
        if ($updateRequest) {
            $requestImports .= "use {$requestNamespace}\\Update{$modelName}Request;";
        }
        if ($dataImport) {
            $requestImports .= "use {$dataImport};";
        }


        return str_replace(
            [
                '{{namespace}}',
                '{{class}}',
                '{{modelName}}',
                '{{variableName}}',
                '{{serviceClass}}',
                '{{storeRequest}}',
                '{{updateRequest}}',
                '{{resource}}',
                '{{methods}}',
                '{{requestImports}}',
            ],
            [
                $namespace,
                class_basename($name),
                $modelName,
                $variableName,
                $serviceClass,
                $storeRequest,
                $updateRequest,
                $resource,
                $this->buildMethods($modelName, $variableName, $serviceClass, $storeRequest, $updateRequest, $resource, $methods),
                $requestImports,
            ],
            $stub
        );
    }

    protected function buildMethods(
        $modelName,
        $variableName,
        $serviceClass,
        $storeRequest,
        $updateRequest,
        $resource,
        $methods
    ) {
        $output = '';
        $stubPath = __DIR__ . '/stubs/methods/';

        if (in_array('*', $methods)) {
            $methods = [
                'index',
                'store',
                'update',
                'show',
                'destroy'
            ];
        }

        foreach ($methods as $method) {

            $methodStub = file_get_contents(__DIR__ . "/stubs/methods/{$method}.stub");

            $output .= str_replace(
                [
                    '{{modelName}}',
                    '{{variableName}}',
                    '{{serviceClass}}',
                    '{{storeRequest}}',
                    '{{updateRequest}}',
                    '{{resource}}'
                ],
                [
                    $modelName,
                    $variableName,
                    $serviceClass,
                    $storeRequest,
                    $updateRequest,
                    $resource
                ],
                $methodStub
            );
        }

        return $output;
    }

    protected function generateResource($modelName, $fields)
    {
        $resourceName = "{$modelName}Resource";
        $path = app_path("Http/Resources/{$resourceName}.php");

        if ($this->filesystem->exists($path)) {
            $this->warn("Resource {$resourceName} already exists.");
            return;
        }

        $fieldsCode = '';
        foreach ($fields as $field) {
            $field = trim($field);
            $fieldsCode .= "'{$field}' => \$this->{$field},\n            ";
        }

        $stub = file_get_contents(__DIR__ . '/stubs/resource.stub');
        $content = str_replace(
            ['{{modelName}}', '{{fields}}'],
            [$modelName, $fieldsCode],
            $stub
        );

        $this->filesystem->put($path, $content);
        $this->info("Resource created: {$resourceName}");
    }

    protected function generateRequest($type, $modelName, $fields)
    {
        $controllerPath = $this->argument('name');
        $segments = explode('/', $controllerPath);
        array_pop($segments);
        $directory = implode('/', $segments);
        $namespace = "App\\Http\\Requests\\" . str_replace('/', '\\', $directory);

        $requestName = "{$type}{$modelName}Request";
        $path = app_path("Http/Requests/{$directory}/{$requestName}.php");

        if (!file_exists(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        if ($this->filesystem->exists($path)) {
            $this->warn("{$requestName} already exists.");
            return;
        }

        $rules = $this->buildValidationRules($fields);
        $stub = file_get_contents(__DIR__ . '/stubs/request.stub');

        $content = str_replace(
            ['{{namespace}}', '{{modelName}}', '{{type}}', '{{rules}}'],
            [$namespace, $modelName, $type, $rules],
            $stub
        );

        $this->filesystem->put($path, $content);
        $this->info("Created: {$namespace}\\{$requestName}");
    }

    protected function buildValidationRules($fields)
    {
        $rules = [];
        foreach ($fields as $field) {
            $field = trim($field);
            $fieldRules = $this->getFieldRules($field);
            $rules[] = "'{$field}' => [" . implode(', ', array_map(fn($r) => "'{$r}'", $fieldRules)) . "]";
        }
        return implode(",\n            ", $rules);
    }

    protected function getFieldRules($field)
    {
        if (str_ends_with($field, '_id') || str_ends_with($field, 'Id')) {
            if (str_ends_with($field, 'Id')) {
                $table = Str::plural(str_replace('Id', '', $field));
            } else {
                $table = Str::plural(str_replace('_id', '', $field));
            }

            return ['required', 'integer', "exists:{$table},id"];
        }

        if (str_ends_with($field, '_price') || str_ends_with($field, 'Price')) {
            return ['required', 'numeric', 'decimal:0,2'];
        }

        if (in_array($field, ['name', 'title', 'subject'])) {
            return ['required', 'string', 'max:255'];
        }

        if (in_array($field, ['description', 'content'])) {
            return ['nullable', 'string'];
        }

        if (str_ends_with($field, '_at')) {
            return ['nullable', 'date_format:Y-m-d H:i:s'];
        }

        return ['required', 'string'];
    }

    protected function printRoutes($modelName, $methods, $controllerPath,$variableName)
    {
        $routes = [];
        $kebabModelName = Str::plural(Str::kebab($modelName));
        $controllerClass = $modelName . 'Controller';

        if (in_array('*', $methods)) {
            $routes[] = "Route::apiResource('{$kebabModelName}', {$controllerClass}::class);";
        } else {
            foreach ($methods as $method) {
                $uri = $kebabModelName;
                $httpMethod = 'get';

                switch ($method) {
                    case 'index':
                        $uri = $kebabModelName;
                        $httpMethod = 'get';
                        break;
                    case 'store':
                        $uri = $kebabModelName;
                        $httpMethod = 'post';
                        break;
                    case 'show':
                        $uri = "{$kebabModelName}/{$variableName}";
                        $httpMethod = 'get';
                        break;
                    case 'update':
                        $uri = "{$kebabModelName}/{$variableName}";
                        $httpMethod = 'put';
                        break;
                    case 'destroy':
                        $uri = "{$kebabModelName}/{$variableName}";
                        $httpMethod = 'delete';
                        break;
                }

                $routes[] = "Route::{$httpMethod}('{$uri}', [{$controllerClass}::class, '{$method}']);";
            }
        }

        $this->newLine();
        $this->info('Add these routes to routes/api.php:');
        $this->newLine();
        $this->line('//' . str_repeat('-', 60));
        $this->line('// ' . ucfirst($modelName) . ' Routes');
        $this->line('//' . str_repeat('-', 60));

        foreach ($routes as $route) {
            $this->line($route);
        }

        $this->newLine();
    }
}
