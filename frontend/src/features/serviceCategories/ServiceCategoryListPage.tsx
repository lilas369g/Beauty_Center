import { useCallback, useEffect, useState } from 'react';
import { ApiError } from '../../api/client';
import { serviceCategoriesApi, type ServiceCategoryFilters } from '../../api/serviceCategories';
import { CategoryConfirmationDialog } from './components/CategoryConfirmationDialog';
import { ServiceCategoryFormModal } from './components/ServiceCategoryFormModal';
import type { PaginatedResponse, ServiceCategory } from '../services/types';

const emptyResult: PaginatedResponse<ServiceCategory> = { data: [], meta: { current_page: 1, last_page: 1, per_page: 20, total: 0 } };
type StatusFilter = '' | 'true' | 'false';

export function ServiceCategoryListPage() {
  const [filters, setFilters] = useState({ search: '', is_active: '' as StatusFilter });
  const [page, setPage] = useState(1);
  const [result, setResult] = useState(emptyResult);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [notice, setNotice] = useState<string | null>(null);
  const [formCategory, setFormCategory] = useState<ServiceCategory | null | undefined>(undefined);
  const [confirmation, setConfirmation] = useState<{ category: ServiceCategory; mode: 'status' | 'delete' } | null>(null);
  const [confirming, setConfirming] = useState(false);

  const loadCategories = useCallback(async (requestedPage = page) => {
    setLoading(true); setError(null);
    const request: ServiceCategoryFilters = { per_page: 20, page: requestedPage };
    if (filters.search.trim()) request.search = filters.search.trim();
    if (filters.is_active) request.is_active = filters.is_active === 'true';
    try { setResult(await serviceCategoriesApi.list(request)); }
    catch (requestError) { setError(requestError instanceof Error ? requestError.message : 'تعذر تحميل التصنيفات.'); }
    finally { setLoading(false); }
  }, [filters, page]);

  useEffect(() => { void loadCategories(); }, [loadCategories]);
  const updateFilter = <K extends keyof typeof filters>(key: K, value: (typeof filters)[K]) => { setPage(1); setFilters((current) => ({ ...current, [key]: value })); };
  const clearFilters = () => { setPage(1); setFilters({ search: '', is_active: '' }); };
  const saved = async (message: string) => { setFormCategory(undefined); setNotice(message); setPage(1); await loadCategories(1); };

  const confirm = async () => {
    if (!confirmation) return;
    setConfirming(true);
    try {
      if (confirmation.mode === 'delete') {
        await serviceCategoriesApi.remove(confirmation.category.uuid);
        setResult((current) => ({ ...current, data: current.data.filter((item) => item.uuid !== confirmation.category.uuid), meta: { ...current.meta, total: current.meta.total - 1 } }));
        setNotice('تم حذف التصنيف.');
      } else {
        await serviceCategoriesApi.updateStatus(confirmation.category.uuid, !confirmation.category.is_active);
        setResult((current) => ({ ...current, data: current.data.map((item) => item.uuid === confirmation.category.uuid ? { ...item, is_active: !item.is_active } : item) }));
        setNotice(confirmation.category.is_active ? 'تم تعطيل التصنيف.' : 'تم تفعيل التصنيف.');
      }
      setConfirmation(null);
    } catch (requestError) { setNotice(requestError instanceof ApiError ? requestError.message : 'تعذر تحديث التصنيف.'); }
    finally { setConfirming(false); }
  };

  return <main className="app-shell"><aside className="sidebar" aria-label="التنقل الرئيسي"><a href="/services" className="brand"><span className="brand-mark"><PetalIcon /></span><span><span className="brand-name">Beauty Center</span><span className="brand-subtitle">لوحة الإدارة</span></span></a><nav className="sidebar-nav"><a href="/services" className="sidebar-link"><ServicesIcon /><span>الخدمات</span></a><a href="/service-categories" className="sidebar-link" aria-current="page"><CategoryIcon /><span>تصنيفات الخدمات</span></a></nav><p className="sidebar-footnote">إدارة مركز التجميل</p></aside><div className="workspace"><header className="topbar"><span className="topbar-eyebrow">تصنيفات الخدمات</span><div className="topbar-profile"><span>لوحة الإدارة</span><span className="profile-avatar" aria-hidden="true">BC</span></div></header><div className="page-grid"><section className="page-heading"><div><p className="page-kicker">مركز التجميل</p><h1 className="page-title">تصنيفات الخدمات</h1><p className="page-description">رتّب تصنيفات الخدمات واضبط حالتها قبل استخدامها في الخدمات الجديدة.</p></div><button type="button" className="primary-button page-add-button" onClick={() => setFormCategory(null)}><PlusIcon /> إضافة تصنيف</button></section>{notice && <div role="status" className="notice"><span>{notice}</span><button type="button" className="notice-close" onClick={() => setNotice(null)} aria-label="إخفاء الرسالة">×</button></div>}<section className="filter-panel" aria-label="فلاتر التصنيفات"><div className="filter-grid category-filter-grid"><div><label htmlFor="category-search" className="field-label">البحث</label><input id="category-search" className="field-control" value={filters.search} onChange={(event) => updateFilter('search', event.target.value)} placeholder="ابحث باسم التصنيف" /></div><div><label htmlFor="category-status-filter" className="field-label">الحالة</label><select id="category-status-filter" className="field-control" value={filters.is_active} onChange={(event) => updateFilter('is_active', event.target.value as StatusFilter)}><option value="">الكل</option><option value="true">مفعّلة</option><option value="false">غير مفعّلة</option></select></div><button type="button" className="secondary-button" onClick={clearFilters}>مسح الفلاتر</button></div></section><section className="service-panel" aria-live="polite"><div className="panel-heading"><h2 className="panel-title">قائمة التصنيفات</h2><span className="panel-count">{loading ? 'جارٍ التحميل…' : `${result.meta.total} تصنيف`}</span></div>{loading ? <Skeleton /> : error ? <ErrorState message={error} onRetry={() => void loadCategories()} /> : result.data.length ? <CategoryTable categories={result.data} onEdit={setFormCategory} onConfirm={setConfirmation} /> : <EmptyState filtered={Boolean(filters.search || filters.is_active)} onClear={clearFilters} />}{!loading && !error && result.meta.last_page > 1 && <Pagination current={result.meta.current_page} total={result.meta.last_page} onPage={setPage} />}</section></div></div><nav className="mobile-tabs" aria-label="التنقل السفلي"><a href="/services" className="mobile-tab"><ServicesIcon /><span>الخدمات</span></a><a href="/service-categories" className="mobile-tab" aria-current="page"><CategoryIcon /><span>التصنيفات</span></a></nav>{formCategory !== undefined && <ServiceCategoryFormModal category={formCategory} onClose={() => setFormCategory(undefined)} onSaved={saved} />}{confirmation && <CategoryConfirmationDialog category={confirmation.category} mode={confirmation.mode} loading={confirming} onClose={() => setConfirmation(null)} onConfirm={() => void confirm()} />}</main>;
}

function CategoryTable({ categories, onEdit, onConfirm }: { categories: ServiceCategory[]; onEdit: (category: ServiceCategory) => void; onConfirm: (value: { category: ServiceCategory; mode: 'status' | 'delete' }) => void }) {
  return <><div className="service-table-wrap"><table className="service-table category-table"><thead><tr>{['التصنيف', 'الوصف', 'ترتيب العرض', 'عدد الخدمات', 'الحالة', 'الإجراءات'].map((label) => <th key={label} scope="col">{label}</th>)}</tr></thead><tbody>{categories.map((category) => <tr key={category.uuid}><td><span className="service-name">{category.name}</span></td><td className="muted-cell">{category.description || '—'}</td><td><span className="latin-number">{category.display_order}</span></td><td><span className="latin-number">{category.services_count}</span></td><td><StatusBadge active={category.is_active} /></td><td><CategoryActions category={category} onEdit={onEdit} onConfirm={onConfirm} /></td></tr>)}</tbody></table></div><div className="mobile-service-list">{categories.map((category) => <article key={category.uuid} className="mobile-service-card"><div className="mobile-card-head"><div><h3>{category.name}</h3><p className="mobile-card-category">ترتيب العرض: <span className="latin-number">{category.display_order}</span> · الخدمات: <span className="latin-number">{category.services_count}</span></p></div><StatusBadge active={category.is_active} /></div>{category.description && <p className="mobile-card-category">{category.description}</p>}<div className="mobile-card-actions"><CategoryActions category={category} onEdit={onEdit} onConfirm={onConfirm} /></div></article>)}</div></>;
}

function CategoryActions({ category, onEdit, onConfirm }: { category: ServiceCategory; onEdit: (category: ServiceCategory) => void; onConfirm: (value: { category: ServiceCategory; mode: 'status' | 'delete' }) => void }) { return <div className="table-actions"><button type="button" className="text-button" onClick={() => onEdit(category)}>تعديل</button><button type="button" className={`text-button ${category.is_active ? 'status-action-off' : 'status-action-on'}`} onClick={() => onConfirm({ category, mode: 'status' })}>{category.is_active ? 'تعطيل' : 'تفعيل'}</button><button type="button" className="text-button status-action-off" onClick={() => onConfirm({ category, mode: 'delete' })} disabled={category.services_count > 0} title={category.services_count > 0 ? 'عطّل التصنيف بدلاً من حذفه لأنه مرتبط بخدمات.' : undefined}>حذف</button></div>; }
function StatusBadge({ active }: { active: boolean }) { return <span className={`badge ${active ? 'badge-active' : 'badge-inactive'}`}>{active ? 'مفعّلة' : 'غير مفعّلة'}</span>; }
function Skeleton() { return <div className="skeleton-list">{Array.from({ length: 5 }, (_, index) => <div className="skeleton-row" key={index} />)}</div>; }
function ErrorState({ message, onRetry }: { message: string; onRetry: () => void }) { return <div className="state-box"><h3>تعذر تحميل التصنيفات</h3><p>{message}</p><button type="button" className="primary-button" onClick={onRetry}>إعادة المحاولة</button></div>; }
function EmptyState({ filtered, onClear }: { filtered: boolean; onClear: () => void }) { return <div className="state-box"><h3>{filtered ? 'لا توجد نتائج مطابقة' : 'لا توجد تصنيفات بعد'}</h3><p>{filtered ? 'جرّب تغيير الفلاتر أو مسحها.' : 'ابدأ بإضافة أول تصنيف للخدمات.'}</p>{filtered && <button type="button" className="secondary-button" onClick={onClear}>مسح الفلاتر</button>}</div>; }
function Pagination({ current, total, onPage }: { current: number; total: number; onPage: (page: number) => void }) { return <nav className="pagination" aria-label="صفحات التصنيفات"><button type="button" className="secondary-button" disabled={current === 1} onClick={() => onPage(current - 1)}>السابق</button><span className="pagination-info">صفحة <span className="latin-number">{current}</span> من <span className="latin-number">{total}</span></span><button type="button" className="secondary-button" disabled={current === total} onClick={() => onPage(current + 1)}>التالي</button></nav>; }
function ServicesIcon() { return <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" aria-hidden="true"><rect x="4" y="4" width="16" height="16" rx="4" /><path d="M8 9h8M8 14h5" /></svg>; }
function CategoryIcon() { return <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" aria-hidden="true"><path d="M4 7.5 12 4l8 3.5-8 3.5-8-3.5Z" /><path d="M4 12l8 3.5 8-3.5M4 16.5 12 20l8-3.5" /></svg>; }
function PetalIcon() { return <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" aria-hidden="true"><path d="M12 20c-3.8-2.4-6-5.1-6-8.1A3.4 3.4 0 0 1 12 9.6a3.4 3.4 0 0 1 6 2.3c0 3-2.2 5.7-6 8.1Z" /><path d="M12 9.6V20" /></svg>; }
function PlusIcon() { return <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" aria-hidden="true"><path d="M12 5v14M5 12h14" /></svg>; }
