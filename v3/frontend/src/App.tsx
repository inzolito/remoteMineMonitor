import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { AuthProvider } from './context/AuthContext';
import { ThemeProvider } from './context/ThemeContext';
import Login from './pages/Login';
import SiteDetails from './pages/SiteDetails';
import UsersPage from './pages/UsersPage';
import ClientsPage from './pages/ClientsPage';
import ProtectedRoute from './components/ProtectedRoute';
import Layout from './components/Layout';
import MonitoreoPage from './pages/MonitoreoPage';
import MonitoreoSite from './pages/MonitoreoSite';
import Home from './pages/Home';
import AlertManager from './components/AlertManager';
import SalesforceAlertManager from './components/SalesforceAlertManager';
import SuperAdmin from './pages/SuperAdmin';
import Profile from './pages/Profile';

import ShiftsPage from './pages/ShiftsPage';
import HistorialPage from './pages/HistorialPage';
import TicketsPage from './pages/TicketsPage';

import ErrorBoundary from './components/ErrorBoundary';

// Placeholder for Site Monitoring (Will create next) - This local definition is now replaced by MonitoreoSite for the route.
// If SiteMonitoringPlaceholder is needed as a fallback or for other purposes, it should be imported from a file or redefined.

const queryClient = new QueryClient();

function App() {
  return (
    <ErrorBoundary>
      <QueryClientProvider client={queryClient}>
      <ThemeProvider defaultTheme="light" storageKey="vite-ui-theme">
        <AuthProvider>
          <AlertManager />
          <SalesforceAlertManager />
          <BrowserRouter basename="/monitoreoLaboratorio/v3/frontend/dist">
            <Routes>
              <Route path="/login" element={<Login />} />
              <Route path="/login/" element={<Login />} />

              <Route element={<ProtectedRoute />}>
                <Route element={<Layout />}>
                  <Route path="/" element={<Home />} />
                  <Route path="/clients" element={<ClientsPage />} />
                  <Route path="/monitoreo" element={<MonitoreoPage />} />
                  <Route path="/monitoreo/site/:id" element={<MonitoreoSite />} />
                  <Route path="/site/:id" element={<SiteDetails />} />
                  <Route path="/users" element={<UsersPage />} />
                  <Route path="/super-admin" element={<SuperAdmin />} />
                  <Route path="/perfil" element={<Profile />} />
                  {/* Placeholder routes for others */}
                  <Route path="/tickets" element={<TicketsPage />} />
                  <Route path="/shifts" element={<ShiftsPage />} />
                  <Route path="/historial" element={<HistorialPage />} />
                </Route>
              </Route>

              <Route path="*" element={<Navigate to="/" replace />} />
            </Routes>
          </BrowserRouter>
        </AuthProvider>
      </ThemeProvider>
    </QueryClientProvider>
    </ErrorBoundary>
  );
}

export default App;
