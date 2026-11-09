import type { ServiceItem } from '../types';

interface StatusConfirmationDialogProps { service: ServiceItem; loading: boolean; onClose: () => void; onConfirm: () => void; }

export function StatusConfirmationDialog({ service, loading, onClose, onConfirm }: StatusConfirmationDialogProps) {
  const nextState = !service.is_active;
  return (
    <div className="modal-backdrop" role="presentation">
      <div role="dialog" aria-modal="true" aria-labelledby="status-dialog-title" className="modal-card modal-card-small">
        <div className="dialog-symbol" aria-hidden="true">!</div>
        <h2 id="status-dialog-title" className="modal-title">{nextState ? 'تفعيل الخدمة؟' : 'تعطيل الخدمة؟'}</h2>
        <p className="dialog-message">{nextState ? `ستصبح «${service.name}» متاحة للاستخدام.` : `لن تُحذف «${service.name}»، لكن سيتم تعطيلها.`}</p>
        <div className="dialog-actions"><button type="button" onClick={onClose} disabled={loading} className="secondary-button">إلغاء</button><button type="button" onClick={onConfirm} disabled={loading} className={nextState ? 'primary-button' : 'danger-button'}>{loading ? 'جارٍ التحديث…' : nextState ? 'تفعيل الخدمة' : 'تعطيل الخدمة'}</button></div>
      </div>
    </div>
  );
}
