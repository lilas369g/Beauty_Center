<?php

namespace App\Services;

use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ServiceManagementService
{
    public function categories(array $filters): Collection
    {
        return ServiceCategory::query()
            ->when(array_key_exists('active', $filters), fn ($query) => $query->where('is_active', $filters['active']))
            ->orderBy('name')
            ->get();
    }

    public function paginate(array $filters): LengthAwarePaginator
    {
        return Service::query()
            ->with('category')
            ->withExists('laserServiceSetting')
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where('name', 'ilike', "%{$search}%"))
            ->when($filters['category_uuid'] ?? null, function ($query, $uuid) {
                $query->whereHas('category', fn ($category) => $category->where('uuid', $uuid));
            })
            ->when(array_key_exists('is_active', $filters), fn ($query) => $query->where('is_active', $filters['is_active']))
            ->orderBy('name')
            ->paginate($filters['per_page'] ?? 20)
            ->withQueryString();
    }

    public function create(array $attributes): Service
    {
        return DB::transaction(function () use ($attributes): Service {
            $category = $this->categoryByUuid($attributes['category_uuid']);
            unset($attributes['category_uuid']);

            $service = $category->services()->create($attributes);

            return $this->forResponse($service);
        });
    }

    public function update(Service $service, array $attributes): Service
    {
        return DB::transaction(function () use ($service, $attributes): Service {
            if (array_key_exists('category_uuid', $attributes)) {
                $attributes['service_category_id'] = $this->categoryByUuid($attributes['category_uuid'])->id;
                unset($attributes['category_uuid']);
            }

            $service->update($attributes);

            return $this->forResponse($service);
        });
    }

    public function updateStatus(Service $service, bool $isActive): Service
    {
        return DB::transaction(function () use ($service, $isActive): Service {
            $service->update(['is_active' => $isActive]);

            return $this->forResponse($service);
        });
    }

    public function forResponse(Service $service): Service
    {
        return $service->fresh(['category'])->loadExists('laserServiceSetting');
    }

    private function categoryByUuid(string $uuid): ServiceCategory
    {
        return ServiceCategory::query()->where('uuid', $uuid)->firstOrFail();
    }
}
