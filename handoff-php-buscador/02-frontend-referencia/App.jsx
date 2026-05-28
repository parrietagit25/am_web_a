import { BrowserRouter, Routes, Route, useLocation } from 'react-router-dom';
import { Suspense, lazy, useEffect } from 'react';
import BuscadorVehiculos from './pages/BuscadorVehiculos';
import WhatsAppButton from './components/WhatsAppButton';
import NotFoundPage from './pages/NotFoundPage';

function ScrollToTop() {
  const { pathname } = useLocation();
  useEffect(() => { window.scrollTo(0, 0); }, [pathname]);
  return null;
}

const SeleccionVehiculos = lazy(() => import('./pages/SeleccionVehiculos'));
const ExtrasPage         = lazy(() => import('./pages/ExtrasPage'));
const ReservaPage        = lazy(() => import('./pages/ReservaPage'));
const AgenciaPortalPage  = lazy(() => import('./pages/AgenciaPortalPage'));
const AdminPage          = lazy(() => import('./pages/AdminPage'));
const MiReservaPage      = lazy(() => import('./pages/MiReservaPage'));
const SucursalesPage     = lazy(() => import('./pages/SucursalesPage'));
const FlotaPage          = lazy(() => import('./pages/FlotaPage'));
const RequisitosPage     = lazy(() => import('./pages/RequisitosPage'));
const ContactosPage      = lazy(() => import('./pages/ContactosPage'));
const TerminosPage       = lazy(() => import('./pages/TerminosPage'));
const PrivacidadPage     = lazy(() => import('./pages/PrivacidadPage'));
const ReembolsoPage      = lazy(() => import('./pages/ReembolsoPage'));
const PagoSeguroPage     = lazy(() => import('./pages/PagoSeguroPage'));
const BlogPage           = lazy(() => import('./pages/BlogPage'));
const BlogDetailPage     = lazy(() => import('./pages/BlogDetailPage'));

function PageSkeleton() {
  return (
    <div style={{ minHeight: '100vh', background: 'var(--gray-100)', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
      <div className="skeleton" style={{ width: 280, height: 20, borderRadius: 8 }} />
    </div>
  );
}

export default function App() {
  return (
    <BrowserRouter future={{ v7_startTransition: true, v7_relativeSplatPath: true }}>
      <Suspense fallback={<PageSkeleton />}>
        <Routes>
          <Route path="/"              element={<BuscadorVehiculos />} />

          {/* Rutas canónicas /rent-a-car/* (preferidas) */}
          <Route path="/rent-a-car/seleccion"     element={<SeleccionVehiculos />} />
          <Route path="/rent-a-car/extras"        element={<ExtrasPage />} />
          <Route path="/rent-a-car/reserva"       element={<ReservaPage />} />
          <Route path="/rent-a-car/mi-reserva"    element={<MiReservaPage />} />
          <Route path="/rent-a-car/sucursales"    element={<SucursalesPage />} />
          <Route path="/rent-a-car/flota"         element={<FlotaPage />} />
          <Route path="/rent-a-car/requisitos"    element={<RequisitosPage />} />
          <Route path="/rent-a-car/contactos"     element={<ContactosPage />} />
          <Route path="/rent-a-car/terminos"      element={<TerminosPage />} />
          <Route path="/rent-a-car/privacidad"    element={<PrivacidadPage />} />
          <Route path="/rent-a-car/reembolso"     element={<ReembolsoPage />} />
          <Route path="/rent-a-car/pago-seguro"   element={<PagoSeguroPage />} />
          <Route path="/rent-a-car/blog"          element={<BlogPage />} />
          <Route path="/rent-a-car/blog/:slug"    element={<BlogDetailPage />} />

          {/* Rutas legacy (alias — server.js hace 301 a /rent-a-car/* en prod) */}
          <Route path="/seleccion"     element={<SeleccionVehiculos />} />
          <Route path="/extras"        element={<ExtrasPage />} />
          <Route path="/reserva"       element={<ReservaPage />} />
          <Route path="/mi-reserva"    element={<MiReservaPage />} />
          <Route path="/sucursales"    element={<SucursalesPage />} />
          <Route path="/flota"         element={<FlotaPage />} />
          <Route path="/requisitos"    element={<RequisitosPage />} />
          <Route path="/contactos"     element={<ContactosPage />} />
          <Route path="/terminos"      element={<TerminosPage />} />
          <Route path="/privacidad"    element={<PrivacidadPage />} />
          <Route path="/reembolso"     element={<ReembolsoPage />} />
          <Route path="/pago-seguro"   element={<PagoSeguroPage />} />
          <Route path="/blog"          element={<BlogPage />} />
          <Route path="/blog/:slug"    element={<BlogDetailPage />} />

          {/* Rutas que no migran (no son /rent-a-car/*) */}
          <Route path="/agencia/:slug" element={<AgenciaPortalPage />} />
          <Route path="/admin"         element={<AdminPage />} />

          <Route path="*"              element={<NotFoundPage />} />
        </Routes>
      </Suspense>
      <ScrollToTop />
      <WhatsAppButton />
    </BrowserRouter>
  );
}
