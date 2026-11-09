export interface ServiceCategoryOption {
  uuid: string;
  name: string;
  display_order: number;
  is_active: boolean;
}

export interface ServiceCategory extends ServiceCategoryOption {
  description: string | null;
  services_count: number;
  created_at: string;
  updated_at: string;
}

export interface ServiceItem {
  uuid: string;
  name: string;
  description: string | null;
  price: string;
  duration_minutes: number;
  is_active: boolean;
  is_laser_enabled: boolean;
  category: Pick<ServiceCategoryOption, 'uuid' | 'name'>;
  created_at: string;
  updated_at: string;
}

export interface PaginationMeta {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

export interface PaginatedResponse<T> {
  data: T[];
  meta: PaginationMeta;
}

export interface ServiceFilters {
  search?: string;
  category_uuid?: string;
  is_active?: boolean;
  per_page?: number;
  page?: number;
}

export interface ServicePayload {
  category_uuid: string;
  name: string;
  description?: string | null;
  price: number;
  duration_minutes: number;
  is_active: boolean;
}
