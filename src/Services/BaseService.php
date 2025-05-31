<?php


namespace Mahfouz\Helpers\Services;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;
use Spatie\QueryBuilder\QueryBuilder;

abstract class BaseService
{
    protected string $modelClass;

    public function get(?callable $callback = null)
    {
        $query = QueryBuilder::for($this->modelClass::query()->latest())
            ->allowedFilters($this->defaultFilters());

        if ($callback) {
            $callback($query);
        }

        return $query->get();
    }

    public function paginate(array $with = [], $per_page = null, ?callable $callback = null)
    {
        $query = QueryBuilder::for($this->modelClass::query()->latest())
            ->with($with)
            ->allowedFilters($this->defaultFilters());

        if ($callback) {
            $callback($query);
        }

        return $query->paginate($per_page ?? Request::integer('perPage', 15));
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
