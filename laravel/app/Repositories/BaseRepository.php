<?php

namespace App\Repositories;

use App\Filters\BaseFilter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

abstract class BaseRepository
{
    public function __construct(protected Model $model) {}

    public function get(?BaseFilter $filter = null): Collection
    {
        return $this->model
            ->when($filter, fn($query) => $query->filter($filter))
            ->get();
    }

    public function paginate(?BaseFilter $filter = null): LengthAwarePaginator
    {
        return $this->model
            ->when($filter, fn($query) => $query->filter($filter))
            ->paginate(request()->get('perPage') ?? 10);
    }

    public function findById(int $id): Model
    {
        return $this->model->findOrFail($id);
    }

    public function findByIds(array $ids): Collection
    {
        return $this->model->whereIn('id', $ids)->get();
    }

    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): Model
    {
        $record = $this->findById($id);
        $record->update($data);

        return $record->refresh();
    }
}