<?php

namespace App\Services;

use App\Models\ServiceCategory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ServiceCategoryManagementService
{
    private const DELETE_BLOCKED_MESSAGE = 'لا يمكن حذف هذا التصنيف لأنه مرتبط بخدمات. عطّليه بدلاً من ذلك.';

    public function paginate(array $filters): LengthAwarePaginator
    {
        return ServiceCategory::query()
            ->withCount('services')
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($searchQuery) use ($search) {
                    $searchQuery->where('name', 'ilike', "%{$search}%")
                        ->orWhere('description', 'ilike', "%{$search}%");
                });
            })
            ->when(array_key_exists('is_active', $filters), fn ($query) => $query->where('is_active', $filters['is_active']))
            ->orderBy('display_order')
            ->orderBy('name')
            ->paginate($filters['per_page'] ?? 20)
            ->withQueryString();
    }

    public function options(bool $active = true): Collection
    {
        return ServiceCategory::query()
            ->where('is_active', $active)
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();
    }

    public function create(array $attributes): ServiceCategory
    {
        return $this->forResponse(ServiceCategory::query()->create($attributes));
    }

    public function update(ServiceCategory $category, array $attributes): ServiceCategory
    {
        $category->update($attributes);

        return $this->forResponse($category);
    }

    public function updateStatus(ServiceCategory $category, bool $isActive): ServiceCategory
    {
        $category->update(['is_active' => $isActive]);

        return $this->forResponse($category);
    }

    public function forResponse(ServiceCategory $category): ServiceCategory
    {
        return $category->fresh()->loadCount('services');
    }

    public function delete(ServiceCategory $category): void
    {
        try {
            DB::transaction(function () use ($category): void {
                $lockedCategory = ServiceCategory::query()->lockForUpdate()->findOrFail($category->id);

                if ($lockedCategory->services()->exists()) {
                    $this->throwDeleteBlocked();
                }

                $lockedCategory->delete();
            });
        } catch (QueryException $exception) {
            if ($exception->getCode() === '23503') {
                $this->throwDeleteBlocked();
            }

            throw $exception;
        }
    }

    private function throwDeleteBlocked(): never
    {
        throw ValidationException::withMessages(['category' => [self::DELETE_BLOCKED_MESSAGE]]);
    }
}
