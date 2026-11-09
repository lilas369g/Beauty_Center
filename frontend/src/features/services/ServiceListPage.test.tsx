import { render, screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { afterEach, describe, expect, it, vi } from 'vitest';
import { ApiError } from '../../api/client';
import { serviceCategoriesApi } from '../../api/serviceCategories';
import { servicesApi } from '../../api/services';
import { ServiceListPage } from './ServiceListPage';

vi.mock('../../api/services', () => ({
  servicesApi: { list: vi.fn(), create: vi.fn(), update: vi.fn(), updateStatus: vi.fn() },
}));
vi.mock('../../api/serviceCategories', () => ({ serviceCategoriesApi: { listOptions: vi.fn() } }));

const category = { uuid: 'category-uuid', name: 'ليزر', display_order: 1, is_active: true };
const service = {
  uuid: 'service-uuid', name: 'ليزر الإبطين', description: null, price: '120.00', duration_minutes: 30,
  is_active: true, is_laser_enabled: true, category: { uuid: category.uuid, name: category.name },
  created_at: '2026-09-17T10:00:00.000000Z', updated_at: '2026-09-17T10:00:00.000000Z',
};
const page = { data: [service], meta: { current_page: 1, last_page: 1, per_page: 20, total: 1 } };

function mockInitialLoad() {
  vi.mocked(serviceCategoriesApi.listOptions).mockResolvedValue([category]);
  vi.mocked(servicesApi.list).mockResolvedValue(page);
}

describe('ServiceListPage', () => {
  afterEach(() => vi.clearAllMocks());

  it('loads and displays the services list', async () => {
    mockInitialLoad();
    render(<ServiceListPage />);

    expect((await screen.findAllByText('ليزر الإبطين'))[0]).toBeInTheDocument();
    expect(screen.getAllByText('خدمة ليزر')[0]).toBeInTheDocument();
    expect(servicesApi.list).toHaveBeenCalledWith(expect.objectContaining({ per_page: 20, page: 1 }));
  });

  it('sends search and status filters to the API', async () => {
    mockInitialLoad();
    const user = userEvent.setup();
    render(<ServiceListPage />);
    await screen.findAllByText('ليزر الإبطين');

    await user.type(screen.getByLabelText('البحث بالاسم'), 'إبطين');
    await user.selectOptions(screen.getByLabelText('الحالة'), 'false');

    await waitFor(() => expect(servicesApi.list).toHaveBeenLastCalledWith(expect.objectContaining({ search: 'إبطين', is_active: false })));
  });

  it('opens the add-service form and displays backend validation errors', async () => {
    mockInitialLoad();
    vi.mocked(servicesApi.create).mockRejectedValue(new ApiError('البيانات غير صالحة.', 422, { name: ['اسم الخدمة مستخدم مسبقاً.'] }));
    const user = userEvent.setup();
    render(<ServiceListPage />);
    await screen.findAllByText('ليزر الإبطين');

    await user.click(screen.getByRole('button', { name: /إضافة خدمة/ }));
    expect(screen.getByRole('dialog', { name: 'إضافة خدمة جديدة' })).toBeInTheDocument();
    await user.selectOptions(within(screen.getByRole('dialog')).getByLabelText('التصنيف'), category.uuid);
    await user.type(screen.getByLabelText('اسم الخدمة'), 'خدمة مكررة');
    await user.type(screen.getByLabelText('السعر'), '50');
    await user.type(screen.getByLabelText('المدة بالدقائق'), '30');
    await user.click(screen.getByRole('button', { name: 'إضافة الخدمة' }));

    expect(await screen.findByText('اسم الخدمة مستخدم مسبقاً.')).toBeInTheDocument();
  });

  it('submits a new service and refreshes the list', async () => {
    mockInitialLoad();
    vi.mocked(servicesApi.create).mockResolvedValue(service);
    const user = userEvent.setup();
    render(<ServiceListPage />);
    await screen.findAllByText('ليزر الإبطين');

    await user.click(screen.getByRole('button', { name: /إضافة خدمة/ }));
    await user.selectOptions(within(screen.getByRole('dialog')).getByLabelText('التصنيف'), category.uuid);
    await user.type(screen.getByLabelText('اسم الخدمة'), 'خدمة جديدة');
    await user.type(screen.getByLabelText('السعر'), '50');
    await user.type(screen.getByLabelText('المدة بالدقائق'), '30');
    await user.click(screen.getByRole('button', { name: 'إضافة الخدمة' }));

    await waitFor(() => expect(servicesApi.create).toHaveBeenCalledWith(expect.objectContaining({ name: 'خدمة جديدة', category_uuid: category.uuid, price: 50, duration_minutes: 30 })));
    expect(await screen.findByText('تمت إضافة الخدمة بنجاح.')).toBeInTheDocument();
  });

  it('changes a service status only after confirmation', async () => {
    mockInitialLoad();
    vi.mocked(servicesApi.updateStatus).mockResolvedValue({ ...service, is_active: false });
    const user = userEvent.setup();
    render(<ServiceListPage />);
    await screen.findAllByText('ليزر الإبطين');

    await user.click(screen.getAllByRole('button', { name: 'تعطيل' })[0]);
    expect(screen.getByRole('dialog', { name: 'تعطيل الخدمة؟' })).toBeInTheDocument();
    expect(servicesApi.updateStatus).not.toHaveBeenCalled();
    await user.click(screen.getByRole('button', { name: 'تعطيل الخدمة' }));

    await waitFor(() => expect(servicesApi.updateStatus).toHaveBeenCalledWith(service.uuid, false));
    expect(await screen.findByText('تم تعطيل الخدمة.')).toBeInTheDocument();
  });
});
