<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ListManagedServiceCategoriesRequest;
use App\Http\Requests\Api\V1\ListServiceCategoryOptionsRequest;
use App\Http\Requests\Api\V1\StoreServiceCategoryRequest;
use App\Http\Requests\Api\V1\UpdateServiceCategoryRequest;
use App\Http\Requests\Api\V1\UpdateServiceCategoryStatusRequest;
use App\Http\Resources\ServiceCategoryOptionResource;
use App\Http\Resources\ServiceCategoryResource;
use App\Models\ServiceCategory;
use App\Services\ServiceCategoryManagementService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ServiceCategoryController extends Controller
{
    public function __construct(private readonly ServiceCategoryManagementService $serviceCategories) {}

    public function index(ListManagedServiceCategoriesRequest $request): AnonymousResourceCollection
    {
        if ($request->has('active')) {
            return ServiceCategoryOptionResource::collection($this->serviceCategories->options($request->boolean('active')));
        }

        return ServiceCategoryResource::collection($this->serviceCategories->paginate($request->validated()));
    }

    public function options(ListServiceCategoryOptionsRequest $request): AnonymousResourceCollection
    {
        return ServiceCategoryOptionResource::collection($this->serviceCategories->options($request->boolean('active', true)));
    }

    public function store(StoreServiceCategoryRequest $request): ServiceCategoryResource
    {
        return new ServiceCategoryResource($this->serviceCategories->create($request->validated()));
    }

    public function show(ServiceCategory $serviceCategory): ServiceCategoryResource
    {
        return new ServiceCategoryResource($this->serviceCategories->forResponse($serviceCategory));
    }

    public function update(UpdateServiceCategoryRequest $request, ServiceCategory $serviceCategory): ServiceCategoryResource
    {
        return new ServiceCategoryResource($this->serviceCategories->update($serviceCategory, $request->validated()));
    }

    public function updateStatus(UpdateServiceCategoryStatusRequest $request, ServiceCategory $serviceCategory): ServiceCategoryResource
    {
        return new ServiceCategoryResource($this->serviceCategories->updateStatus($serviceCategory, $request->boolean('is_active')));
    }

    public function destroy(ServiceCategory $serviceCategory): Response
    {
        $this->serviceCategories->delete($serviceCategory);

        return response()->noContent();
    }
}
