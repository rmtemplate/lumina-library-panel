<?php

namespace App\Services\Admin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MasterDataService
{
    public function create(string $modelClass, array $data, ?string $actorId = null): Model
    {
        return DB::transaction(function () use ($modelClass, $data, $actorId) {
            $data['created_by'] = $actorId;
            return $modelClass::create($data);
        });
    }

    public function update(Model $model, array $data, ?string $actorId = null): Model
    {
        return DB::transaction(function () use ($model, $data, $actorId) {
            $data['updated_by'] = $actorId;
            $model->update($data);
            return $model->fresh();
        });
    }

    public function delete(Model $model, ?string $actorId = null, ?string $relationGuard = null, string $message = 'Data tidak dapat dihapus karena masih digunakan.'): void
    {
        if ($relationGuard && method_exists($model, $relationGuard) && $model->{$relationGuard}()->exists()) {
            throw ValidationException::withMessages(['delete' => $message]);
        }

        DB::transaction(function () use ($model, $actorId) {
            if (in_array('updated_by', $model->getFillable(), true)) {
                $model->update(['updated_by' => $actorId]);
            }
            $model->delete();
        });
    }

    public function only(array $data, array $keys): array
    {
        return Arr::only($data, $keys);
    }
}
