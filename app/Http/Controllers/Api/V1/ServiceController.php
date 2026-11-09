<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ListServiceCategoriesRequest;
use App\Http\Requests\Api\V1\ListServicesRequest;
use App\Http\Requests\Api\V1\StoreServiceRequest;
use App\Http\Requests\Api\V1\UpdateServiceRequest;
use App\Http\Requests\Api\V1\UpdateServiceStatusRequest;
use App\Http\Resources\ServiceCategoryResource;
use App\Http\Resources\ServiceResource;
use App\Models\Service;
use App\Services\ServiceManagementService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ServiceController extends Controller
{
    public function __construct(private readonly ServiceManagementService $serviceManagement) {}

    public function categories(ListServiceCategoriesRequest $request): AnonymousResourceCollection
    {
        return ServiceCategoryResource::collection($this->serviceManagement->categories($request->validated()));
    }

    public function index(ListServicesRequest $request): AnonymousResourceCollection
    {
        return ServiceResource::collection($this->serviceManagement->paginate($request->validated()));
    }

    public function store(StoreServiceRequest $request): ServiceResource
    {
        return new ServiceResource($this->serviceManagement->create($request->validated()));
    }

    public function show(Service $service): ServiceResource
    {
        return new ServiceResource($this->serviceManagement->forResponse($service));
    }

    public function update(UpdateServiceRequest $request, Service $service): ServiceResource
    {
        return new ServiceResource($this->serviceManagement->update($service, $request->validated()));
    }

    public function updateStatus(UpdateServiceStatusRequest $request, Service $service): ServiceResource
    {
        return new ServiceResource($this->serviceManagement->updateStatus($service, $request->boolean('is_active')));
    }
}
