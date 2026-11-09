import { render, screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { afterEach, describe, expect, it, vi } from 'vitest';
import { ApiError } from '../../api/client';
import { serviceCategoriesApi } from '../../api/serviceCategories';
import { ServiceCategoryListPage } from './ServiceCategoryListPage';

vi.mock('../../api/serviceCategories', () => ({
  serviceCategoriesApi: { list: vi.fn(), create: vi.fn(), update: vi.fn(), updateStatus: vi.fn(), remove: vi.fn(), listOptions: vi.fn() },
}));

const category = { uuid: 'category-uuid', name: 'ليزر', description: 'إزالة الشعر بالليزر', display_order: 1, is_active: true, services_count: 0, created_at: '2026-09-18T00:00:00Z', updated_at: '2026-09-18T00:00:00Z' };
const usedCategory = { ...category, uuid: 'used-category-uuid', name: 'أظافر', services_count: 2 };
const page = { data: [category], meta: { current_page: 1, last_page: 1, per_page: 20, total: 1 } };

function mockLoad(data = page) { vi.mocked(serviceCategoriesApi.list).mockResolvedValue(data); }

describe('ServiceCategoryListPage', () => {
  afterEach(() => vi.clearAllMocks());

  it('loads the category list and applies search and status filters', async () => {
    mockLoad();
    const user = userEvent.setup();
    render(<ServiceCategoryListPage />);
    expect((await screen.findAllByText('ليزر'))[0]).toBeInTheDocument();
    await user.type(screen.getByLabelText('البحث'), 'ليزر');
    await user.selectOptions(screen.getByLabelText('الحالة'), 'false');
    await waitFor(() => expect(serviceCategoriesApi.list).toHaveBeenLastCalledWith(expect.objectContaining({ search: 'ليزر', is_active: false })));
  });

  it('opens the add form and displays backend validation errors', async () => {
    mockLoad();
    vi.mocked(serviceCategoriesApi.create).mockRejectedValue(new ApiError('البيانات غير صالحة.', 422, { name: ['اسم التصنيف مستخدم مسبقاً.'] }));
    const user = userEvent.setup();
    render(<ServiceCategoryListPage />);
    await screen.findAllByText('ليزر');
    await user.click(screen.getByRole('button', { name: /إضافة تصنيف/ }));
    const dialog = screen.getByRole('dialog', { name: 'إضافة تصنيف' });
    await user.type(within(dialog).getByLabelText('اسم التصنيف'), 'ليزر');
    await user.click(within(dialog).getByRole('button', { name: 'إضافة التصنيف' }));
    expect(await screen.findByText('اسم التصنيف مستخدم مسبقاً.')).toBeInTheDocument();
  });

  it('creates a category and refreshes the list', async () => {
    mockLoad();
    vi.mocked(serviceCategoriesApi.create).mockResolvedValue(category);
    const user = userEvent.setup();
    render(<ServiceCategoryListPage />);
    await screen.findAllByText('ليزر');
    await user.click(screen.getByRole('button', { name: /إضافة تصنيف/ }));
    const dialog = screen.getByRole('dialog', { name: 'إضافة تصنيف' });
    await user.type(within(dialog).getByLabelText('اسم التصنيف'), 'عناية بالبشرة');
    await user.click(within(dialog).getByRole('button', { name: 'إضافة التصنيف' }));
    await waitFor(() => expect(serviceCategoriesApi.create).toHaveBeenCalledWith(expect.objectContaining({ name: 'عناية بالبشرة', display_order: 1 })));
    expect(await screen.findByText('تمت إضافة التصنيف بنجاح.')).toBeInTheDocument();
  });

  it('updates a category by UUID', async () => {
    mockLoad();
    vi.mocked(serviceCategoriesApi.update).mockResolvedValue(category);
    const user = userEvent.setup();
    render(<ServiceCategoryListPage />);
    await screen.findAllByText('ليزر');
    await user.click(screen.getAllByRole('button', { name: 'تعديل' })[0]);
    const dialog = screen.getByRole('dialog', { name: 'تعديل التصنيف' });
    await user.clear(within(dialog).getByLabelText('اسم التصنيف'));
    await user.type(within(dialog).getByLabelText('اسم التصنيف'), 'ليزر طبي');
    await user.click(within(dialog).getByRole('button', { name: 'حفظ التعديلات' }));
    await waitFor(() => expect(serviceCategoriesApi.update).toHaveBeenCalledWith(category.uuid, expect.objectContaining({ name: 'ليزر طبي' })));
  });

  it('changes status only after confirmation', async () => {
    mockLoad();
    vi.mocked(serviceCategoriesApi.updateStatus).mockResolvedValue({ ...category, is_active: false });
    const user = userEvent.setup();
    render(<ServiceCategoryListPage />);
    await screen.findAllByText('ليزر');
    await user.click(screen.getAllByRole('button', { name: 'تعطيل' })[0]);
    expect(screen.getByRole('dialog', { name: 'تعطيل التصنيف؟' })).toBeInTheDocument();
    expect(serviceCategoriesApi.updateStatus).not.toHaveBeenCalled();
    await user.click(screen.getByRole('button', { name: 'تعطيل التصنيف' }));
    await waitFor(() => expect(serviceCategoriesApi.updateStatus).toHaveBeenCalledWith(category.uuid, false));
  });

  it('disables deleting a category that still has services', async () => {
    mockLoad({ data: [usedCategory], meta: { current_page: 1, last_page: 1, per_page: 20, total: 1 } });
    render(<ServiceCategoryListPage />);
    await screen.findAllByText('أظافر');
    expect(screen.getAllByRole('button', { name: 'حذف' })[0]).toBeDisabled();
  });
});
