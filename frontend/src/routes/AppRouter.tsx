import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom';
import { ServiceListPage } from '../features/services/ServiceListPage';
import { ServiceCategoryListPage } from '../features/serviceCategories/ServiceCategoryListPage';

export function AppRouter() {
  return (
    <BrowserRouter>
      <Routes>
        <Route path="/services" element={<ServiceListPage />} />
        <Route path="/service-categories" element={<ServiceCategoryListPage />} />
        <Route path="*" element={<Navigate to="/services" replace />} />
      </Routes>
    </BrowserRouter>
  );
}
