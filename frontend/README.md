# Beauty Center frontend

واجهة React مستقلة حاليًا لمسار `/services`. لا تعدّل Laravel أو قاعدة البيانات؛ تستهلك API الخدمات الموجودة فقط.

## إعداد البيئة

انسخ `.env.example` إلى `.env` ثم اضبط القيم المناسبة لبيئتك:

```text
VITE_API_BASE_URL=/api
VITE_DEV_PROXY_TARGET=http://localhost:8000
```

`VITE_API_BASE_URL` هو أساس طلبات API. في التطوير المحلي، يجعل `/api` الطلبات تمر عبر proxy Vite إلى `VITE_DEV_PROXY_TARGET`، بدون تعديل CORS في Laravel. في بيئة منشورة، يمكن تعيينه لمسار API أو عنوانه المتاح عبر متغير البيئة فقط.

## التشغيل

```powershell
cd frontend
npm.cmd install
Copy-Item .env.example .env
npm.cmd run dev
```

افتح `http://localhost:5173/services`.

## التحقق

```powershell
npm.cmd run lint
npm.cmd run test
npm.cmd run build
```

البنية الرئيسية: `src/api` لعميل API المطبّع، و`src/features/services` للصفحة والمكونات والاختبارات، و`src/routes` لمسارات React Router.
