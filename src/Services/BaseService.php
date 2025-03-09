public function handle()
{
    $model = $this->argument('model');
    $serviceName = "{$model}Service";
    $modelClass = "App\\Models\\{$model}";
    $servicePath = app_path("Services/{$serviceName}.php");

    if (File::exists($servicePath)) {
        $this->error("Service {$serviceName} already exists!");
        return 1;
    }

    // Determine which base service to use
    $baseServiceClass = class_exists(\App\Services\BaseService::class)
        ? 'App\Services\BaseService'
        : 'Mahfouz\Helpers\Services\BaseService';

    $serviceContent = <<<PHP

<?php


namespace Mahfouz\Helpers\Services;


use Illuminate\Database\Eloquent\Model;
use Spatie\QueryBuilder\QueryBuilder;

abstract class BaseService
{
    protected string $modelClass;

    public function get()
    {
        return QueryBuilder::for($this->modelClass::latest())
            ->allowedFilters($this->defaultFilters())
            ->get();
    }

    public function paginate(array $with = [], $per_page = null, ?callable $callback = null)
    {
        $query = QueryBuilder::for($this->modelClass::latest())
            ->with($with)
            ->allowedFilters($this->defaultFilters());

        if ($callback) {
            $callback($query);
        }

        return $query->paginate($per_page ?? request()->query('perPage', 15));
    }
    public function store(object $data)
    {
        return $this->modelClass::create($data->toArray());
    }

    public function update(Model $model, object $data)
    {
        $model->update($data->toArray());
        return $model->refresh();
    }

    public function destroy(Model $model)
    {
        return $model->delete();
    }

    protected function defaultFilters(): array
    {
        return [];
    }
}
PHP;

    File::ensureDirectoryExists(app_path('Services'));
    File::put($servicePath, $serviceContent);

    $this->info("Service created successfully: {$serviceName}");
    return 0;
}