import axios from 'axios';

export type ValidationErrors = Record<string, string[]>;

export class ApiError extends Error {
  constructor(
    message: string,
    public readonly status?: number,
    public readonly errors: ValidationErrors = {},
  ) {
    super(message);
  }
}

export function createSafeApiError(status?: number, errors: ValidationErrors = {}): ApiError {
  if (!status) {
    return new ApiError('تعذر الاتصال بالخادم. تحقق من الشبكة ثم أعد المحاولة.');
  }

  if (status === 422) {
    return new ApiError('يرجى مراجعة البيانات المدخلة.', status, errors);
  }

  const messages: Record<number, string> = {
    400: 'تعذر تنفيذ الطلب. تحقّق من البيانات ثم أعد المحاولة.',
    401: 'انتهت الجلسة أو يلزم تسجيل الدخول.',
    403: 'لا تملك صلاحية تنفيذ هذا الإجراء.',
    404: 'العنصر المطلوب غير موجود أو لم يعد متاحًا.',
    409: 'تعذر إكمال العملية بسبب تعارض في البيانات.',
    429: 'تم إرسال طلبات كثيرة. انتظر قليلًا ثم أعد المحاولة.',
  };

  if (status >= 500) {
    return new ApiError('حدث خلل مؤقت في الخادم. لم تُحفظ العملية، حاول مرة أخرى لاحقًا.', status);
  }

  return new ApiError(messages[status] ?? 'تعذر إكمال الطلب. حاول مرة أخرى.', status);
}

export const apiClient = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL ?? '/api',
  headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
});

// Future authentication belongs here, so feature API calls remain unchanged.
apiClient.interceptors.request.use((config) => config);

apiClient.interceptors.response.use(
  (response) => response,
  (error: unknown) => {
    if (axios.isAxiosError(error)) {
      const data = error.response?.data as { message?: string; errors?: ValidationErrors } | undefined;
      return Promise.reject(createSafeApiError(error.response?.status, error.response?.status === 422 ? data?.errors : {}));
    }

    return Promise.reject(createSafeApiError());
  },
);
