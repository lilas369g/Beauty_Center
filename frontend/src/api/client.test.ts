import { describe, expect, it } from 'vitest';
import { createSafeApiError } from './client';

describe('createSafeApiError', () => {
  it('preserves field validation errors without trusting an arbitrary server message', () => {
    const error = createSafeApiError(422, { name: ['اسم التصنيف مستخدم مسبقاً.'] });

    expect(error.message).toBe('يرجى مراجعة البيانات المدخلة.');
    expect(error.errors.name[0]).toBe('اسم التصنيف مستخدم مسبقاً.');
  });

  it('never exposes raw server errors for 5xx responses', () => {
    const error = createSafeApiError(500);

    expect(error.message).toBe('حدث خلل مؤقت في الخادم. لم تُحفظ العملية، حاول مرة أخرى لاحقًا.');
    expect(error.message).not.toContain('SQLSTATE');
  });
});
