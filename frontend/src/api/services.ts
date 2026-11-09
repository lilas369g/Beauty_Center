import { apiClient } from './client';
import type { PaginatedResponse, ServiceFilters, ServiceItem, ServicePayload } from '../features/services/types';

export const servicesApi = {
  async list(filters: ServiceFilters): Promise<PaginatedResponse<ServiceItem>> {
    const response = await apiClient.get<PaginatedResponse<ServiceItem>>('/v1/services', { params: filters });
    return response.data;
  },

  async create(payload: ServicePayload): Promise<ServiceItem> {
    const response = await apiClient.post<{ data: ServiceItem }>('/v1/services', payload);
    return response.data.data;
  },

  async update(uuid: string, payload: ServicePayload): Promise<ServiceItem> {
    const response = await apiClient.patch<{ data: ServiceItem }>(`/v1/services/${uuid}`, payload);
    return response.data.data;
  },

  async updateStatus(uuid: string, isActive: boolean): Promise<ServiceItem> {
    const response = await apiClient.patch<{ data: ServiceItem }>(`/v1/services/${uuid}/status`, { is_active: isActive });
    return response.data.data;
  },
};
