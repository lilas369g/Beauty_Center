import { useEffect, useState, type FormEvent, type ReactNode } from 'react';
import { ApiError } from '../../../api/client';
import { servicesApi } from '../../../api/services';
import type { ServiceCategoryOption, ServiceItem, ServicePayload } from '../types';

type FormValues = { category_uuid: string; name: string; description: string; price: string; duration_minutes: string; is_active: boolean; };

interface ServiceFormModalProps {
  categories: ServiceCategoryOption[];
  service: ServiceItem | null;
  onClose: () => void;
  onSaved: (message: string) => Promise<void>;
}

const blankValues: FormValues = { category_uuid: '', name: '', description: '', price: '', duration_minutes: '', is_active: true };

function toValues(service: ServiceItem | null): FormValues {
  if (!service) return blankValues;
  return { category_uuid: service.category.uuid, name: service.name, description: service.description ?? '', price: service.price, duration_minutes: String(service.duration_minutes), is_active: service.is_active };
}

export function ServiceFormModal({ categories, service, onClose, onSaved }: ServiceFormModalProps) {
  const [values, setValues] = useState<FormValues>(() => toValues(service));
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [submitting, setSubmitting] = useState(false);

  useEffect(() => setValues(toValues(service)), [service]);

  const selectableCategories = service && !categories.some((category) => category.uuid === service.category.uuid)
    ? [{ ...service.category, display_order: 0, is_active: false }, ...categories]
    : categories;

  const updateValue = <K extends keyof FormValues>(key: K, value: FormValues[K]) => {
    setValues((current) => ({ ...current, [key]: value }));
    setErrors((current) => ({ ...current, [key]: '' }));
  };

  const validate = (): boolean => {
    const nextErrors: Record<string, string> = {};
    if (!values.category_uuid) nextErrors.category_uuid = 'اختر التصنيف.';
    if (!values.name.trim()) nextErrors.name = 'اسم الخدمة مطلوب.';
    if (values.price === '' || Number(values.price) < 0) nextErrors.price = 'أدخل سعراً غير سالب.';
    if (!Number.isInteger(Number(values.duration_minutes)) || Number(values.duration_minutes) <= 0) nextErrors.duration_minutes = 'أدخل مدة صحيحة أكبر من صفر.';
    setErrors(nextErrors);
    return Object.keys(nextErrors).length === 0;
  };

  const submit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    if (!validate()) return;
    const payload: ServicePayload = { category_uuid: values.category_uuid, name: values.name.trim(), description: values.description.trim() || null, price: Number(values.price), duration_minutes: Number(values.duration_minutes), is_active: values.is_active };

    setSubmitting(true);
    try {
      if (service) await servicesApi.update(service.uuid, payload);
      else await servicesApi.create(payload);
      await onSaved(service ? 'تم تعديل الخدمة بنجاح.' : 'تمت إضافة الخدمة بنجاح.');
    } catch (error) {
      if (error instanceof ApiError && Object.keys(error.errors).length > 0) setErrors(Object.fromEntries(Object.entries(error.errors).map(([field, messages]) => [field, messages[0]])));
      else setErrors({ form: error instanceof Error ? error.message : 'تعذر حفظ الخدمة.' });
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <div className="modal-backdrop" role="presentation">
      <div role="dialog" aria-modal="true" aria-labelledby="service-form-title" className="modal-card">
        <div className="modal-heading">
          <div><p className="modal-kicker">الخدمات</p><h2 id="service-form-title" className="modal-title">{service ? 'تعديل الخدمة' : 'إضافة خدمة جديدة'}</h2></div>
          <button type="button" onClick={onClose} disabled={submitting} className="icon-button" aria-label="إغلاق النموذج"><CloseIcon /></button>
        </div>

        <form onSubmit={submit} noValidate className="form-stack">
          {errors.form && <p role="alert" className="form-error">{errors.form}</p>}
          <FieldError error={errors.category_uuid}><label htmlFor="service-category" className="field-label">التصنيف</label><select id="service-category" value={values.category_uuid} onChange={(event) => updateValue('category_uuid', event.target.value)} className="field-control" disabled={submitting}><option value="">اختر تصنيفاً</option>{selectableCategories.map((category) => <option key={category.uuid} value={category.uuid}>{category.name}{!category.is_active ? ' (غير مفعّل)' : ''}</option>)}</select></FieldError>
          <FieldError error={errors.name}><label htmlFor="service-name" className="field-label">اسم الخدمة</label><input id="service-name" value={values.name} onChange={(event) => updateValue('name', event.target.value)} className="field-control" disabled={submitting} autoFocus /></FieldError>
          <FieldError error={errors.description}><label htmlFor="service-description" className="field-label">الوصف <span className="muted-mark">(اختياري)</span></label><textarea id="service-description" value={values.description} onChange={(event) => updateValue('description', event.target.value)} className="field-control textarea-control" disabled={submitting} /></FieldError>
          <div className="form-grid"><FieldError error={errors.price}><label htmlFor="service-price" className="field-label">السعر</label><input id="service-price" type="number" min="0" step="0.01" inputMode="decimal" value={values.price} onChange={(event) => updateValue('price', event.target.value)} className="field-control" disabled={submitting} /></FieldError><FieldError error={errors.duration_minutes}><label htmlFor="service-duration" className="field-label">المدة بالدقائق</label><input id="service-duration" type="number" min="1" step="1" inputMode="numeric" value={values.duration_minutes} onChange={(event) => updateValue('duration_minutes', event.target.value)} className="field-control" disabled={submitting} /></FieldError></div>
          <label className="toggle-field"><span><span className="toggle-title">حالة الخدمة</span><span className="toggle-description">ستظهر الخدمات غير المفعّلة في لوحة الإدارة فقط.</span></span><input type="checkbox" checked={values.is_active} onChange={(event) => updateValue('is_active', event.target.checked)} disabled={submitting} className="toggle-input" aria-label="الخدمة مفعّلة" /></label>
          <div className="form-actions"><button type="button" onClick={onClose} disabled={submitting} className="secondary-button">إلغاء</button><button type="submit" disabled={submitting} className="primary-button">{submitting ? 'جارٍ الحفظ…' : service ? 'حفظ التعديلات' : 'إضافة الخدمة'}</button></div>
        </form>
      </div>
    </div>
  );
}

function FieldError({ children, error }: { children: ReactNode; error?: string }) { return <div>{children}{error && <p role="alert" className="field-error">{error}</p>}</div>; }
function CloseIcon() { return <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18" /></svg>; }
