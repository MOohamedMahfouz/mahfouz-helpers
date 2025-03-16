<?php


namespace Mahfouz\Helpers\Services;


use Illuminate\Database\Eloquent\Model;
use Spatie\QueryBuilder\QueryBuilder;

abstract class BaseService
{
    protected string $modelClass;

    public function get(?callable $callback = null)
    {
        $query =  QueryBuilder::for($this->modelClass::latest())
            ->allowedFilters($this->defaultFilters());

        if ($callback) {
            $callback($query);
        }

        return $query->get();
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
