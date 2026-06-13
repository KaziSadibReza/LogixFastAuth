import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { HashRouter, Routes, Route } from 'react-router-dom';
import { AdminDataProvider } from './context/AdminDataContext';
import { SettingsProvider } from './context/SettingsContext';
import { ToastProvider } from './ui/Toaster';
import { AdminLayout } from './layout/AdminLayout';
import { GeneralPage } from './pages/GeneralPage';
import { AuthPage } from './pages/AuthPage';
import { MailPage } from './pages/MailPage';
import { SmsPage } from './pages/SmsPage';
import { IntegrationsPage } from './pages/IntegrationsPage';
import { AppearancePage } from './pages/AppearancePage';
import { SecurityPage } from './pages/SecurityPage';
import './styles/admin.scss';

const admin = window.SLR_ADMIN;
const rootEl = document.getElementById('slr-admin-root');

if (admin && rootEl) {
  createRoot(rootEl).render(
    <StrictMode>
      <ToastProvider>
        <SettingsProvider>
          <AdminDataProvider>
          <HashRouter>
            <Routes>
              <Route element={<AdminLayout i18n={admin.i18n} />}>
                <Route index element={<GeneralPage />} />
                <Route path="auth" element={<AuthPage />} />
                <Route path="mail" element={<MailPage />} />
                <Route path="sms" element={<SmsPage />} />
                <Route path="integrations" element={<IntegrationsPage />} />
                <Route path="appearance" element={<AppearancePage />} />
                <Route path="security" element={<SecurityPage />} />
              </Route>
            </Routes>
          </HashRouter>
          </AdminDataProvider>
        </SettingsProvider>
      </ToastProvider>
    </StrictMode>
  );
}
