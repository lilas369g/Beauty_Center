import type { ServiceCategory } from '../../services/types';

export function CategoryConfirmationDialog({ category, mode, loading, onClose, onConfirm }: { category: ServiceCategory; mode: 'status' | 'delete'; loading: boolean; onClose: () => void; onConfirm: () => void }) {
  const isDelete = mode === 'delete';
  const nextActive = !category.is_active;
  const title = isDelete ? 'حذف التصنيف؟' : nextActive ? 'تفعيل التصنيف؟' : 'تعطيل التصنيف؟';
  const message = isDelete ? `سيُحذف «${category.name}» نهائياً لأنه لا يحتوي على خدمات.` : nextActive ? `سيظهر «${category.name}» مجدداً ضمن خيارات الخدمة الجديدة.` : `لن تتغير الخدمات المرتبطة بـ«${category.name}».`;
  return <div className="modal-backdrop" role="presentation"><div role="dialog" aria-modal="true" aria-labelledby="category-confirmation-title" className="modal-card modal-card-small"><div className="dialog-symbol" aria-hidden="true">!</div><h2 id="category-confirmation-title" className="modal-title">{title}</h2><p className="dialog-message">{message}</p><div className="dialog-actions"><button type="button" className="secondary-button" onClick={onClose} disabled={loading}>إلغاء</button><button type="button" className={isDelete ? 'danger-button' : 'primary-button'} onClick={onConfirm} disabled={loading}>{loading ? 'جارٍ التحديث…' : isDelete ? 'حذف التصنيف' : nextActive ? 'تفعيل التصنيف' : 'تعطيل التصنيف'}</button></div></div></div>;
}
