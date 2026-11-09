import { apiClient } from './client';
import type { PaginatedResponse, ServiceCategory, ServiceCategoryOption } from '../features/services/types';

export interface ServiceCategoryFilters {
  search?: string;
  is_active?: boolean;
  per_page?: number;
  page?: number;
}

export interface ServiceCategoryPayload {
  name: string;
  description?: string | null;
  display_order: number;
  is_active: boolean;
}

export const serviceCategoriesApi = {
  async listOptions(active = true): Promise<ServiceCategoryOption[]> {
    const response = await apiClient.get<{ data: ServiceCategoryOption[] }>('/v1/service-categories/options', { params: { active } });
    return response.data.data;
  },

  async list(filters: ServiceCategoryFilters): Promise<PaginatedResponse<ServiceCategory>> {
    const response = await apiClient.get<PaginatedResponse<ServiceCategory>>('/v1/service-categories', { params: filters });
    return response.data;
  },

  async create(payload: ServiceCategoryPayload): Promise<ServiceCategory> {
    const response = await apiClient.post<{ data: ServiceCategory }>('/v1/service-categories', payload);
    return response.data.data;
  },

  async update(uuid: string, payload: ServiceCategoryPayload): Promise<ServiceCategory> {
    const response = await apiClient.patch<{ data: ServiceCategory }>(`/v1/service-categories/${uuid}`, payload);
    return response.data.data;
  },

  async updateStatus(uuid: string, isActive: boolean): Promise<ServiceCategory> {
    const response = await apiClient.patch<{ data: ServiceCategory }>(`/v1/service-categories/${uuid}/status`, { is_active: isActive });
    return response.data.data;
  },

  async remove(uuid: string): Promise<void> {
    await apiClient.delete(`/v1/service-categories/${uuid}`);
  },
};
