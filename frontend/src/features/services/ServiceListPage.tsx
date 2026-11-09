import { useCallback, useEffect, useState } from 'react';
import { ApiError } from '../../api/client';
import { serviceCategoriesApi } from '../../api/serviceCategories';
import { servicesApi } from '../../api/services';
import { ServiceFormModal } from './components/ServiceFormModal';
import { StatusConfirmationDialog } from './components/StatusConfirmationDialog';
import type { PaginatedResponse, ServiceCategoryOption, ServiceFilters, ServiceItem } from './types';

const defaultResult: PaginatedResponse<ServiceItem> = {
  data: [],
  meta: { current_page: 1, last_page: 1, per_page: 20, total: 0 },
};

type StatusFilter = '' | 'true' | 'false';

export function ServiceListPage() {
  const [categories, setCategories] = useState<ServiceCategoryOption[]>([]);
  const [filters, setFilters] = useState({ search: '', category_uuid: '', is_active: '' as StatusFilter });
  const [page, setPage] = useState(1);
  const [result, setResult] = useState(defaultResult);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [formService, setFormService] = useState<ServiceItem | null | undefined>(undefined);
  const [statusService, setStatusService] = useState<ServiceItem | null>(null);
  const [statusLoading, setStatusLoading] = useState(false);
  const [notice, setNotice] = useState<string | null>(null);

  const loadServices = useCallback(async (requestedPage = page) => {
    setLoading(true);
    setError(null);
    const requestFilters: ServiceFilters = { per_page: 20, page: requestedPage };
    if (filters.search.trim()) requestFilters.search = filters.search.trim();
    if (filters.category_uuid) requestFilters.category_uuid = filters.category_uuid;
    if (filters.is_active) requestFilters.is_active = filters.is_active === 'true';

    try {
      setResult(await servicesApi.list(requestFilters));
    } catch (requestError) {
      setError(requestError instanceof Error ? requestError.message : 'تعذر تحميل الخدمات.');
    } finally {
      setLoading(false);
    }
  }, [filters, page]);

  useEffect(() => { void loadServices(); }, [loadServices]);
  useEffect(() => {
    void serviceCategoriesApi.listOptions().then(setCategories).catch(() => setCategories([]));
  }, []);

  const changeFilter = <K extends keyof typeof filters>(key: K, value: (typeof filters)[K]) => {
    setPage(1);
    setFilters((current) => ({ ...current, [key]: value }));
  };

  const clearFilters = () => {
    setPage(1);
    setFilters({ search: '', category_uuid: '', is_active: '' });
  };

  const saveCompleted = async (message: string) => {
    setFormService(undefined);
    setNotice(message);
    setPage(1);
    await loadServices(1);
  };

  const confirmStatus = async () => {
    if (!statusService) return;
    setStatusLoading(true);
    try {
      await servicesApi.updateStatus(statusService.uuid, !statusService.is_active);
      setResult((current) => ({
        ...current,
        data: current.data.map((service) => service.uuid === statusService.uuid ? { ...service, is_active: !service.is_active } : service),
      }));
      setNotice(statusService.is_active ? 'تم تعطيل الخدمة.' : 'تم تفعيل الخدمة.');
      setStatusService(null);
    } catch (requestError) {
      setNotice(requestError instanceof ApiError ? requestError.message : 'تعذر تحديث حالة الخدمة.');
    } finally {
      setStatusLoading(false);
    }
  };

  return (
    <main className="app-shell">
      <aside className="sidebar" aria-label="التنقل الرئيسي">
        <a href="/services" className="brand">
          <span className="brand-mark"><PetalIcon /></span>
          <span><span className="brand-name">Beauty Center</span><span className="brand-subtitle">لوحة الإدارة</span></span>
        </a>
        <nav className="sidebar-nav">
          <a href="/services" className="sidebar-link" aria-current="page"><ServicesIcon /><span>الخدمات</span></a>
          <a href="/service-categories" className="sidebar-link"><CategoryIcon /><span>تصنيفات الخدمات</span></a>
        </nav>
        <p className="sidebar-footnote">إدارة مركز التجميل</p>
      </aside>

      <div className="workspace">
        <header className="topbar">
          <span className="topbar-eyebrow">الخدمات</span>
          <div className="topbar-profile"><span>لوحة الإدارة</span><span className="profile-avatar" aria-hidden="true">BC</span></div>
        </header>

        <div className="page-grid">
          <section className="page-heading">
            <div>
              <p className="page-kicker">مركز التجميل</p>
              <h1 className="page-title">إدارة الخدمات</h1>
              <p className="page-description">أدر الخدمات والأسعار وحالة الإتاحة من مكان واحد.</p>
            </div>
            <button type="button" className="primary-button page-add-button" onClick={() => setFormService(null)}>
              <PlusIcon /> إضافة خدمة
            </button>
          </section>

          {notice && <div role="status" className="notice"><span>{notice}</span><button type="button" onClick={() => setNotice(null)} className="notice-close" aria-label="إخفاء الرسالة">×</button></div>}

          <section aria-label="فلاتر الخدمات" className="filter-panel">
            <div className="filter-grid">
              <div>
                <label htmlFor="service-search" className="field-label">البحث بالاسم</label>
                <input id="service-search" value={filters.search} onChange={(event) => changeFilter('search', event.target.value)} placeholder="ابحث عن خدمة" className="field-control" />
              </div>
              <div>
                <label htmlFor="category-filter" className="field-label">التصنيف</label>
                <select id="category-filter" value={filters.category_uuid} onChange={(event) => changeFilter('category_uuid', event.target.value)} className="field-control">
                  <option value="">كل التصنيفات</option>
                  {categories.map((category) => <option value={category.uuid} key={category.uuid}>{category.name}</option>)}
                </select>
              </div>
              <div>
                <label htmlFor="status-filter" className="field-label">الحالة</label>
                <select id="status-filter" value={filters.is_active} onChange={(event) => changeFilter('is_active', event.target.value as StatusFilter)} className="field-control">
                  <option value="">الكل</option><option value="true">مفعّلة</option><option value="false">غير مفعّلة</option>
                </select>
              </div>
              <button type="button" onClick={clearFilters} className="secondary-button">مسح الفلاتر</button>
            </div>
          </section>

          <section className="service-panel" aria-live="polite">
            <div className="panel-heading"><h2 className="panel-title">قائمة الخدمات</h2><span className="panel-count">{loading ? 'جارٍ التحميل…' : `${result.meta.total} خدمة`}</span></div>
            {loading ? <ServiceSkeleton /> : error ? <ErrorState message={error} onRetry={() => void loadServices()} /> : result.data.length === 0 ? <EmptyState hasFilters={Boolean(filters.search || filters.category_uuid || filters.is_active)} onClear={clearFilters} /> : <ServiceTable services={result.data} onEdit={setFormService} onStatus={setStatusService} />}
            {!loading && !error && result.meta.last_page > 1 && <Pagination current={result.meta.current_page} total={result.meta.last_page} onPage={(nextPage) => setPage(nextPage)} />}
          </section>
        </div>
      </div>

      <nav className="mobile-tabs" aria-label="التنقل السفلي"><a href="/services" className="mobile-tab" aria-current="page"><ServicesIcon /><span>الخدمات</span></a><a href="/service-categories" className="mobile-tab"><CategoryIcon /><span>التصنيفات</span></a></nav>
      {formService !== undefined && <ServiceFormModal categories={categories} service={formService} onClose={() => setFormService(undefined)} onSaved={saveCompleted} />}
      {statusService && <StatusConfirmationDialog service={statusService} loading={statusLoading} onClose={() => setStatusService(null)} onConfirm={() => void confirmStatus()} />}
    </main>
  );
}

function ServiceTable({ services, onEdit, onStatus }: { services: ServiceItem[]; onEdit: (service: ServiceItem) => void; onStatus: (service: ServiceItem) => void }) {
  return <><div className="service-table-wrap"><table className="service-table"><thead><tr>{['الخدمة', 'التصنيف', 'السعر', 'المدة', 'الليزر', 'الحالة', 'الإجراءات'].map((label) => <th scope="col" key={label}>{label}</th>)}</tr></thead><tbody>{services.map((service) => <tr key={service.uuid}><td><span className="service-name">{service.name}</span>{service.description && <span className="service-description">{service.description}</span>}</td><td className="muted-cell">{service.category.name}</td><td className="latin-number">{formatPrice(service.price)}</td><td><span className="latin-number">{service.duration_minutes}</span> دقيقة</td><td><LaserBadge enabled={service.is_laser_enabled} /></td><td><StatusBadge active={service.is_active} /></td><td><Actions service={service} onEdit={onEdit} onStatus={onStatus} /></td></tr>)}</tbody></table></div><div className="mobile-service-list">{services.map((service) => <article key={service.uuid} className="mobile-service-card"><div className="mobile-card-head"><div><h3>{service.name}</h3><p className="mobile-card-category">{service.category.name} · <span className="latin-number">{service.duration_minutes}</span> دقيقة</p></div><StatusBadge active={service.is_active} /></div><div className="mobile-card-meta"><span className="latin-number">{formatPrice(service.price)}</span><LaserBadge enabled={service.is_laser_enabled} /></div><div className="mobile-card-actions"><Actions service={service} onEdit={onEdit} onStatus={onStatus} /></div></article>)}</div></>;
}

function Actions({ service, onEdit, onStatus }: { service: ServiceItem; onEdit: (service: ServiceItem) => void; onStatus: (service: ServiceItem) => void }) {
  return <div className="table-actions"><button type="button" className="text-button" onClick={() => onEdit(service)}>تعديل</button><button type="button" className={`text-button ${service.is_active ? 'status-action-off' : 'status-action-on'}`} onClick={() => onStatus(service)}>{service.is_active ? 'تعطيل' : 'تفعيل'}</button></div>;
}

function LaserBadge({ enabled }: { enabled: boolean }) { return enabled ? <span className="badge badge-laser">خدمة ليزر</span> : <span className="muted-mark">—</span>; }
function StatusBadge({ active }: { active: boolean }) { return <span className={`badge ${active ? 'badge-active' : 'badge-inactive'}`}>{active ? 'مفعّلة' : 'غير مفعّلة'}</span>; }
function formatPrice(price: string) { return new Intl.NumberFormat('ar-SY-u-nu-latn', { style: 'currency', currency: 'SYP', maximumFractionDigits: 2 }).format(Number(price)); }
function ServiceSkeleton() { return <div className="skeleton-list">{Array.from({ length: 5 }, (_, index) => <div className="skeleton-row" key={index} />)}</div>; }
function ErrorState({ message, onRetry }: { message: string; onRetry: () => void }) { return <div className="state-box"><h3>تعذر تحميل الخدمات</h3><p>{message}</p><button type="button" onClick={onRetry} className="primary-button">إعادة المحاولة</button></div>; }
function EmptyState({ hasFilters, onClear }: { hasFilters: boolean; onClear: () => void }) { return <div className="state-box"><h3>{hasFilters ? 'لا توجد نتائج مطابقة' : 'لا توجد خدمات بعد'}</h3><p>{hasFilters ? 'جرّب تغيير الفلاتر أو مسحها.' : 'ابدأ بإضافة أول خدمة إلى المركز.'}</p>{hasFilters && <button type="button" onClick={onClear} className="secondary-button">مسح الفلاتر</button>}</div>; }
function Pagination({ current, total, onPage }: { current: number; total: number; onPage: (page: number) => void }) { return <nav aria-label="صفحات الخدمات" className="pagination"><button type="button" disabled={current === 1} onClick={() => onPage(current - 1)} className="secondary-button">السابق</button><span className="pagination-info">صفحة <span className="latin-number">{current}</span> من <span className="latin-number">{total}</span></span><button type="button" disabled={current === total} onClick={() => onPage(current + 1)} className="secondary-button">التالي</button></nav>; }
function ServicesIcon() { return <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" aria-hidden="true"><rect x="4" y="4" width="16" height="16" rx="4" /><path d="M8 9h8M8 14h5" /></svg>; }
function CategoryIcon() { return <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" aria-hidden="true"><path d="M4 7.5 12 4l8 3.5-8 3.5-8-3.5Z" /><path d="M4 12l8 3.5 8-3.5M4 16.5 12 20l8-3.5" /></svg>; }
function PetalIcon() { return <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" aria-hidden="true"><path d="M12 20c-3.8-2.4-6-5.1-6-8.1A3.4 3.4 0 0 1 12 9.6a3.4 3.4 0 0 1 6 2.3c0 3-2.2 5.7-6 8.1Z" /><path d="M12 9.6V20" /></svg>; }
function PlusIcon() { return <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" aria-hidden="true"><path d="M12 5v14M5 12h14" /></svg>; }
