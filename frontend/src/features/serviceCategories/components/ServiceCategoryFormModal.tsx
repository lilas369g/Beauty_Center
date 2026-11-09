import { useEffect, useState, type FormEvent, type ReactNode } from 'react';
import { ApiError } from '../../../api/client';
import { serviceCategoriesApi, type ServiceCategoryPayload } from '../../../api/serviceCategories';
import type { ServiceCategory } from '../../services/types';

type FormValues = { name: string; description: string; display_order: string; is_active: boolean; };
const blankValues: FormValues = { name: '', description: '', display_order: '1', is_active: true };

function toValues(category: ServiceCategory | null): FormValues {
  return category ? { name: category.name, description: category.description ?? '', display_order: String(category.display_order), is_active: category.is_active } : blankValues;
}

export function ServiceCategoryFormModal({ category, onClose, onSaved }: { category: ServiceCategory | null; onClose: () => void; onSaved: (message: string) => Promise<void> }) {
  const [values, setValues] = useState<FormValues>(() => toValues(category));
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [submitting, setSubmitting] = useState(false);
  useEffect(() => setValues(toValues(category)), [category]);
  const setValue = <K extends keyof FormValues>(key: K, value: FormValues[K]) => { setValues((current) => ({ ...current, [key]: value })); setErrors((current) => ({ ...current, [key]: '' })); };

  const submit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    const nextErrors: Record<string, string> = {};
    if (!values.name.trim()) nextErrors.name = 'اسم التصنيف مطلوب.';
    if (!Number.isInteger(Number(values.display_order)) || Number(values.display_order) <= 0) nextErrors.display_order = 'أدخل ترتيباً صحيحاً أكبر من صفر.';
    setErrors(nextErrors);
    if (Object.keys(nextErrors).length) return;
    const payload: ServiceCategoryPayload = { name: values.name.trim(), description: values.description.trim() || null, display_order: Number(values.display_order), is_active: values.is_active };
    setSubmitting(true);
    try {
      if (category) await serviceCategoriesApi.update(category.uuid, payload); else await serviceCategoriesApi.create(payload);
      await onSaved(category ? 'تم تعديل التصنيف بنجاح.' : 'تمت إضافة التصنيف بنجاح.');
    } catch (error) {
      if (error instanceof ApiError && Object.keys(error.errors).length) setErrors(Object.fromEntries(Object.entries(error.errors).map(([key, messages]) => [key, messages[0]])));
      else setErrors({ form: error instanceof Error ? error.message : 'تعذر حفظ التصنيف.' });
    } finally { setSubmitting(false); }
  };

  return <div className="modal-backdrop" role="presentation"><div role="dialog" aria-modal="true" aria-labelledby="category-form-title" className="modal-card"><div className="modal-heading"><div><p className="modal-kicker">تصنيفات الخدمات</p><h2 id="category-form-title" className="modal-title">{category ? 'تعديل التصنيف' : 'إضافة تصنيف'}</h2></div><button type="button" onClick={onClose} disabled={submitting} className="icon-button" aria-label="إغلاق النموذج"><CloseIcon /></button></div><form onSubmit={submit} noValidate className="form-stack">{errors.form && <p role="alert" className="form-error">{errors.form}</p>}<FieldError error={errors.name}><label htmlFor="category-name" className="field-label">اسم التصنيف</label><input id="category-name" value={values.name} onChange={(event) => setValue('name', event.target.value)} className="field-control" disabled={submitting} autoFocus /></FieldError><FieldError error={errors.description}><label htmlFor="category-description" className="field-label">الوصف <span className="muted-mark">(اختياري)</span></label><textarea id="category-description" value={values.description} onChange={(event) => setValue('description', event.target.value)} className="field-control textarea-control" disabled={submitting} /></FieldError><FieldError error={errors.display_order}><label htmlFor="category-order" className="field-label">ترتيب العرض</label><input id="category-order" type="number" min="1" step="1" inputMode="numeric" value={values.display_order} onChange={(event) => setValue('display_order', event.target.value)} className="field-control" disabled={submitting} /></FieldError><label className="toggle-field"><span><span className="toggle-title">حالة التصنيف</span><span className="toggle-description">تظهر التصنيفات المفعّلة فقط عند إنشاء خدمة جديدة.</span></span><input type="checkbox" checked={values.is_active} onChange={(event) => setValue('is_active', event.target.checked)} className="toggle-input" disabled={submitting} aria-label="التصنيف مفعّل" /></label><div className="form-actions"><button type="button" className="secondary-button" onClick={onClose} disabled={submitting}>إلغاء</button><button type="submit" className="primary-button" disabled={submitting}>{submitting ? 'جارٍ الحفظ…' : category ? 'حفظ التعديلات' : 'إضافة التصنيف'}</button></div></form></div></div>;
}

function FieldError({ children, error }: { children: ReactNode; error?: string }) { return <div>{children}{error && <p role="alert" className="field-error">{error}</p>}</div>; }
function CloseIcon() { return <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18" /></svg>; }
