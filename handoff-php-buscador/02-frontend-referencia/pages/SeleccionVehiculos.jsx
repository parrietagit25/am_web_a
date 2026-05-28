import { useState, useEffect, useMemo } from 'react';
import { useSearchParams, useNavigate } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import Header from '../components/Header';
import Footer from '../components/Footer';
import ServiceTabs from '../components/ServiceTabs';
import Stepper from '../components/Stepper';
import VehicleCard from '../components/VehicleCard';
import SkeletonCard from '../components/SkeletonCard';
import FilterBar from '../components/FilterBar';
import BookingSummary from '../components/BookingSummary';
import Icon from '../components/Icon';
import { SUCURSALES } from '../utils/constants';
import { parseSearchUrl, buildSearchUrl } from '../utils/urlParams';

const CATEGORY_ORDER = ['Económico','Compacto','Intermedio','Estándar','Full Size','SUV','Premium','Lujo','Van'];

function calcDays(pickupDate, returnDate) {
  if (!pickupDate || !returnDate) return 0;
  const d1 = new Date(pickupDate);
  const d2 = new Date(returnDate);
  const diff = Math.round((d2 - d1) / (1000 * 60 * 60 * 24));
  return diff > 0 ? diff : 0;
}

export default function SeleccionVehiculos() {
  useEffect(() => { document.title = 'Selecciona tu Vehículo | Automarket Rent-A-Car'; }, []);
  const [searchParams] = useSearchParams();
  const navigate = useNavigate();

  // parseSearchUrl handles both the new compact keys (l, d1, d2, a) and the
  // legacy long keys (locationCode, pickupDate, ...) so old bookmarks still
  // resolve correctly after the URL shortening rollout.
  const params = parseSearchUrl(searchParams);
  const days = calcDays(params.pickupDate, params.returnDate);

  // The string we forward to downstream pages (VehicleCard → ExtrasPage) is
  // rebuilt in the new compact form so the URL chain stays short throughout
  // the funnel — not just on the first /seleccion landing.
  const searchParamsString = buildSearchUrl(params);

  // Phase 1: catalog vehicles (instant, from in-memory server cache)
  const [catalogVehicles, setCatalogVehicles] = useState([]);
  const [filter, setFilter] = useState('Todos');

  // Previously-selected vehicle (set when user clicked a card on this page
  // and then went forward). When they navigate Back from /extras we read it
  // so the card shows a subtle "Seleccionado anteriormente" highlight.
  const previouslySelectedSipp = (() => {
    try {
      const raw = sessionStorage.getItem('selectedVehicle');
      return raw ? JSON.parse(raw)?.sippCode || null : null;
    } catch { return null; }
  })();

  const apiUrl = import.meta.env.VITE_API_URL || '/api/availability';
  const catalogUrl = '/api/catalog';

  // Phase 1: load catalog immediately on mount (no params dependency)
  useEffect(() => {
    fetch(catalogUrl)
      .then(r => r.json())
      .then(d => setCatalogVehicles(d.vehicles || []))
      .catch(() => {});
  }, []);

  // Redirect if no location
  useEffect(() => {
    if (!params.locationCode) navigate('/');
  }, [params.locationCode]);

  // Phase 2: availability via React Query (SWR — returns stale or miss immediately)
  const { data: availData, isLoading: availLoading, error: availError } = useQuery({
    queryKey: ['availability:v2', params.locationCode, params.returnLocationCode, params.pickupDate, params.pickupTime, params.returnDate, params.returnTime, params.age, params.promoCode],
    queryFn: async ({ signal }) => {
      const res = await fetch(apiUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          locationCode: params.locationCode,
          returnLocationCode: params.returnLocationCode || params.locationCode,
          pickupDate: params.pickupDate,
          pickupTime: params.pickupTime,
          returnDate: params.returnDate,
          returnTime: params.returnTime,
          age: params.age,
          promoCode: params.promoCode,
        }),
        signal,
      });
      const data = await res.json();
      if (!res.ok) throw new Error(data.error || `Error ${res.status}`);
      return data;
    },
    staleTime: 5 * 60 * 1000,
    gcTime: 5 * 60 * 1000,
    refetchInterval: (query) => {
      const d = query?.state?.data;
      return d?.miss === true ? 4000 : false;
    },
    enabled: !!params.locationCode,
  });

  const isMiss = availData?.miss === true;
  const isClosedDay = availData?.source === 'LIVE_EMPTY' &&
    (availData?.reason === 'LOCATION_CLOSED' || !!availData?.warning);
  const emptyReason = (!isMiss && availData?.source === 'LIVE_EMPTY' && !isClosedDay)
    ? (availData?.reason || 'NO_AVAILABILITY')
    : null;
  const error = availError ? (availError.message || 'No se pudo obtener la disponibilidad') : null;

  // Build availability map (null = still loading)
  const availabilityMap = useMemo(() => {
    if (availLoading && !availData) return null;
    if (isMiss) return new Map(); // no BARS data yet
    const vehicles = availData?.vehicles || [];
    return new Map(vehicles.map(v => [v.sippCode, v]));
  }, [availData, availLoading, isMiss]);

  const availabilityLoading = availLoading && !availData;

  // Merge catalog + availability — only show vehicles BARS confirmed available
  const { availableVehicles, hasCatalogFallbackPrices } = useMemo(() => {
    // MISS: backend returned catalogFallback (catalog entries with base_price).
    // Show those cards immediately with `available: null` so they render in the
    // "Verificando precio…" state. React Query will refetch every 4s; when real
    // BARS data arrives this branch stops firing and prices swap in seamlessly.
    if (isMiss) {
      const fallback = availData?.catalogFallback || [];
      const ordered = [...fallback].sort((a, b) => {
        const ai = CATEGORY_ORDER.indexOf(a.category);
        const bi = CATEGORY_ORDER.indexOf(b.category);
        if (ai !== bi) return (ai === -1 ? 99 : ai) - (bi === -1 ? 99 : bi);
        return (a.base_price || 0) - (b.base_price || 0);
      });
      return {
        availableVehicles: ordered.map(c => ({
          ...c,
          id: c.sippCode,
          available: null,  // → VehicleCard renders the "Verificando…" state
        })),
        hasCatalogFallbackPrices: true,
      };
    }

    // Catalog empty + availability loaded → fall back to BARS data directly
    if (!catalogVehicles.length && availabilityMap !== null) {
      const sorted = Array.from(availabilityMap.values())
        .map(v => ({ ...v, available: true }))
        .sort((a, b) => {
          const ai = CATEGORY_ORDER.indexOf(a.category);
          const bi = CATEGORY_ORDER.indexOf(b.category);
          if (ai !== bi) return (ai === -1 ? 99 : ai) - (bi === -1 ? 99 : bi);
          return (a.priceWeb || 0) - (b.priceWeb || 0);
        });
      return { availableVehicles: sorted, hasCatalogFallbackPrices: false };
    }

    // Catalog empty + availability still loading → keep skeletons
    if (!catalogVehicles.length) return { availableVehicles: [], hasCatalogFallbackPrices: false };

    // Normal two-phase path: catalog loaded, waiting for BARS
    if (availabilityMap === null) {
      return {
        availableVehicles: catalogVehicles.map(c => ({ ...c, id: c.sippCode, available: null })),
        hasCatalogFallbackPrices: false,
      };
    }

    // Secondary lookup by name: handles when catalog has SFAR but BARS returned SFMR
    const availByName = new Map();
    for (const [, v] of availabilityMap) {
      const k = (v.name || '').toLowerCase().trim();
      const ex = availByName.get(k);
      if (!ex || (v.priceWeb || Infinity) < (ex.priceWeb || Infinity)) {
        availByName.set(k, v);
      }
    }

    // Only include vehicles that BARS confirmed available — no grayed-out cards
    const avail = [];
    const usedBarsKeys = new Set();
    for (const cat of catalogVehicles) {
      const barsVehicle = availabilityMap.get(cat.sippCode)
        || availByName.get((cat.name || '').toLowerCase().trim());
      if (barsVehicle && !usedBarsKeys.has(barsVehicle.sippCode)) {
        usedBarsKeys.add(barsVehicle.sippCode);
        avail.push({ ...barsVehicle, available: true });
      }
      // Not available → simply skip (don't show grayed-out card)
    }
    return { availableVehicles: avail, hasCatalogFallbackPrices: false };
  }, [catalogVehicles, availabilityMap, isMiss, availData]);

  // Apply category filter to available vehicles only
  const filteredAvailable = filter === 'Todos'
    ? availableVehicles
    : availableVehicles.filter(v => v.category === filter);

  // Show full skeletons only if catalog hasn't loaded yet
  const showSkeletons = catalogVehicles.length === 0 && availabilityMap === null;

  return (
    <div style={{ minHeight: '100vh', background: 'var(--gray-100)' }}>
      <Header />

      {/* Thin progress bar — visible only while availability is loading */}
      <LoadingBar visible={availabilityLoading} />

      <main id="main-content" className="r-inner-pad" style={{ maxWidth: 1200, margin: '0 auto' }}>
        <Stepper current={1} />

        <h1 style={{ fontSize: 26, fontWeight: 700, color: 'var(--navy)', marginBottom: 24 }}>
          {availabilityLoading && !catalogVehicles.length ? 'Buscando vehículos disponibles…' : 'Escoge tu categoría'}
        </h1>

        <div className="r-results-grid">
          {/* Left: vehicle list */}
          <div>
            {/* FilterBar — shown as soon as catalog is ready */}
            {!showSkeletons && !error && (
              <FilterBar activeFilter={filter} onFilter={setFilter} vehicles={availableVehicles} />
            )}

            <div style={{ display: 'flex', flexDirection: 'column', gap: 16 }}>

              {/* Full skeletons while catalog loads */}
              {showSkeletons && (
                <>
                  <SkeletonCard />
                  <SkeletonCard />
                  <SkeletonCard />
                </>
              )}

              {/* Slow-load hint while waiting for availability prices */}
              {!showSkeletons && availabilityLoading && (
                <div style={{
                  textAlign: 'center', padding: '10px 16px',
                  background: 'var(--gray-100)', borderRadius: 10,
                  border: '1px solid var(--gray-200)',
                  fontSize: 13, color: 'var(--gray-500)',
                  display: 'flex', alignItems: 'center', justifyContent: 'center', gap: 8,
                }}>
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" style={{ animation: 'spin 1s linear infinite', flexShrink: 0 }} aria-hidden="true"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
                  Consultando disponibilidad…
                </div>
              )}

              {/* MISS hint — same compact style as the regular loading hint.
                  The catalog cards already render with "Verificando precio…"
                  badges, so we just acknowledge the state without scaring the
                  user with "vuelve a buscar". Polling auto-updates the cards. */}
              {!showSkeletons && isMiss && (
                <div style={{
                  textAlign: 'center', padding: '10px 16px',
                  background: 'var(--gray-100)', borderRadius: 10,
                  border: '1px solid var(--gray-200)',
                  fontSize: 13, color: 'var(--gray-500)',
                  display: 'flex', alignItems: 'center', justifyContent: 'center', gap: 8,
                }}>
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" style={{ animation: 'spin 1s linear infinite', flexShrink: 0 }} aria-hidden="true"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
                  Calculando precios para tu búsqueda…
                </div>
              )}

              {/* Error state */}
              {error && (
                <ErrorState message={error} onRetry={() => window.location.reload()} onModify={() => navigate('/')} />
              )}

              {/* Available vehicles (filtered) */}
              {!showSkeletons && filteredAvailable.map((v, i) => (
                <VehicleCard
                  key={v.sippCode || v.id}
                  vehicle={v}
                  index={i}
                  days={days}
                  searchParamsString={searchParamsString}
                  wasPreviouslySelected={v.sippCode === previouslySelectedSipp}
                />
              ))}

              {/* Closed-day banner: BARS returned Warning 214 (location closed on return date) */}
              {!showSkeletons && !error && isClosedDay && (
                <div style={{
                  display: 'flex', alignItems: 'flex-start', gap: 12,
                  padding: '14px 18px', borderRadius: 10,
                  background: 'var(--amber-surface)', border: '1px solid var(--amber-border)',
                  fontSize: 14, color: 'var(--amber-dark)',
                }}>
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" style={{ flexShrink: 0, marginTop: 2 }} aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                  <div>
                    <strong style={{ display: 'block', marginBottom: 2 }}>Sucursal no disponible en esta fecha</strong>
                    Esta sucursal no opera devoluciones el día seleccionado. Por favor elige otra fecha de devolución.
                  </div>
                </div>
              )}

              {/* Empty state: BARS responded but zero vehicles (not a closed-day case) */}
              {!showSkeletons && !error && !isMiss && !isClosedDay && availabilityMap !== null && availableVehicles.length === 0 && (
                <EmptyState
                  locationCode={params.locationCode}
                  reason={emptyReason}
                  onModify={() => navigate('/')}
                  onRetry={() => window.location.reload()}
                />
              )}

              {/* No results for current filter */}
              {!showSkeletons && !error && availabilityMap !== null && filteredAvailable.length === 0 && availableVehicles.length > 0 && (
                <div style={{ textAlign: 'center', padding: 60, color: 'var(--gray-400)', fontSize: 15 }}>
                  No hay vehículos disponibles en esta categoría
                </div>
              )}

              {/* Legal footnote when showing catalog reference prices */}
              {hasCatalogFallbackPrices && (
                <p style={{ fontSize: 11, color: 'var(--gray-400)', marginTop: 12, lineHeight: 1.6 }}>
                  * Precio referencial desde catálogo. El precio definitivo se confirma al seleccionar el vehículo. Sujeto a disponibilidad. Ley 45 de 2007 · Ley 473 de 2025.
                </p>
              )}

            </div>
          </div>

          {/* Right: sidebar */}
          <BookingSummary params={params} />
        </div>

        <div style={{ height: 40 }} />
      </main>
      <Footer />
    </div>
  );
}

/* ── Indeterminate loading bar ── */
function LoadingBar({ visible }) {
  if (!visible) return null;
  return (
    <div
      role="progressbar"
      aria-label="Cargando disponibilidad"
      aria-valuemin={0}
      aria-valuemax={100}
      style={{
        position: 'fixed', top: 0, left: 0, right: 0, height: 3,
        zIndex: 9999, background: 'var(--red-15)', overflow: 'hidden',
      }}
    >
      <div style={{
        height: '100%', width: '60%', background: 'var(--red)',
        transformOrigin: 'left',
        animation: 'progressSlide 1.6s ease-in-out infinite',
      }} />
    </div>
  );
}

/* ── Empty state ── */
function EmptyState({ locationCode, reason, onModify, onRetry }) {
  const card = {
    background: '#fff', borderRadius: 16, padding: '48px 32px',
    boxShadow: '0 1px 3px rgba(0,0,0,.06)', textAlign: 'center',
  };

  if (reason === 'BARS_TIMEOUT') {
    return (
      <div style={card}>
        <div style={{
          width: 64, height: 64, borderRadius: '50%',
          background: 'var(--amber-surface)', display: 'flex', alignItems: 'center',
          justifyContent: 'center', margin: '0 auto 20px',
        }}>
          <Icon type="clock" size={28} color="var(--amber)" />
        </div>
        <h3 style={{ fontSize: 20, fontWeight: 700, color: 'var(--navy)', marginBottom: 10 }}>
          Consulta en proceso
        </h3>
        <p style={{ fontSize: 14, color: 'var(--gray-500)', maxWidth: 420, margin: '0 auto 24px', lineHeight: 1.6 }}>
          El servicio de reservas para esta sucursal está tardando más de lo esperado.
          Intenta de nuevo en unos minutos.
        </p>
        <div style={{ display: 'flex', gap: 12, justifyContent: 'center' }}>
          <button
            onClick={onModify}
            style={{
              padding: '10px 24px', borderRadius: 10,
              border: '1.5px solid var(--gray-200)', background: '#fff',
              color: 'var(--navy)', fontSize: 14, fontWeight: 600, cursor: 'pointer',
            }}
          >
            Cambiar búsqueda
          </button>
          <button
            onClick={onRetry}
            style={{
              padding: '10px 28px', borderRadius: 10, border: 'none',
              background: 'var(--red)', color: '#fff', fontSize: 14, fontWeight: 600, cursor: 'pointer',
            }}
          >
            Reintentar
          </button>
        </div>
      </div>
    );
  }

  if (reason === 'RATE_NOT_CONFIGURED') {
    return (
      <div style={card}>
        <div style={{
          width: 64, height: 64, borderRadius: '50%',
          background: 'var(--gray-100)', display: 'flex', alignItems: 'center',
          justifyContent: 'center', margin: '0 auto 20px',
        }}>
          <Icon type="alert" size={28} color="var(--gray-400)" />
        </div>
        <h3 style={{ fontSize: 20, fontWeight: 700, color: 'var(--navy)', marginBottom: 10 }}>
          Combinación no disponible
        </h3>
        <p style={{ fontSize: 14, color: 'var(--gray-500)', maxWidth: 420, margin: '0 auto 24px', lineHeight: 1.6 }}>
          Esta sucursal no tiene tarifas configuradas para las fechas seleccionadas.
          Prueba con otra sucursal o cambia las fechas.
        </p>
        <button
          onClick={onModify}
          style={{
            padding: '10px 28px', borderRadius: 10, border: 'none',
            background: 'var(--red)', color: '#fff', fontSize: 14, fontWeight: 600, cursor: 'pointer',
          }}
        >
          Cambiar búsqueda
        </button>
      </div>
    );
  }

  // Default: NO_AVAILABILITY (or null reason from non-LIVE_EMPTY sources)
  const sucursal = SUCURSALES.find(s => s.code === locationCode);
  const isLimited = sucursal?.note != null;

  return (
    <div style={card}>
      <div style={{
        width: 64, height: 64, borderRadius: '50%',
        background: 'var(--red-08)', display: 'flex', alignItems: 'center',
        justifyContent: 'center', margin: '0 auto 20px',
      }}>
        <Icon type="car" size={28} color="var(--red)" />
      </div>

      <h3 style={{ fontSize: 20, fontWeight: 700, color: 'var(--navy)', marginBottom: 10 }}>
        Sin disponibilidad en {sucursal?.shortName || locationCode}
      </h3>

      <p style={{ fontSize: 14, color: 'var(--gray-500)', maxWidth: 420, margin: '0 auto 8px', lineHeight: 1.6 }}>
        No encontramos vehículos disponibles para las fechas seleccionadas en esta sucursal. Intenta con fechas diferentes o una duración de renta más larga.
      </p>

      {isLimited && (
        <div style={{
          display: 'inline-flex', alignItems: 'flex-start', gap: 10,
          background: 'var(--amber-surface)', border: '1px solid var(--amber-border)',
          borderRadius: 12, padding: '12px 18px', margin: '16px auto',
          maxWidth: 480, textAlign: 'left',
        }}>
          <Icon type="alert" size={16} color="var(--amber)" />
          <div>
            <div style={{ fontSize: 13, fontWeight: 600, color: 'var(--amber)', marginBottom: 3 }}>
              Sucursal con disponibilidad limitada
            </div>
            <div style={{ fontSize: 13, color: 'var(--amber-dark)', lineHeight: 1.5 }}>
              {sucursal.note}. Prueba con fechas más lejanas o considera el Aeropuerto de Tocumen (PTY), que tiene disponibilidad inmediata.
            </div>
          </div>
        </div>
      )}

      <div style={{ display: 'flex', gap: 12, justifyContent: 'center', marginTop: 24 }}>
        <button
          onClick={onModify}
          style={{
            padding: '10px 28px', borderRadius: 10, border: 'none',
            background: 'var(--red)', color: '#fff', fontSize: 14, fontWeight: 600, cursor: 'pointer',
          }}
        >
          Cambiar fechas o sucursal
        </button>
      </div>
    </div>
  );
}

/* ── Error state ── */
function ErrorState({ message, onRetry, onModify }) {
  const isNetwork = message && (message.includes('fetch') || message.includes('network') || message.includes('Failed'));
  const friendlyMessage = isNetwork
    ? 'Verifica tu conexión a internet e inténtalo de nuevo.'
    : 'El servicio no está disponible en este momento. Puedes reintentar o cambiar tu búsqueda.';

  return (
    <div style={{
      background: '#fff', borderRadius: 16, padding: '40px 32px',
      boxShadow: '0 1px 3px rgba(0,0,0,.06)', textAlign: 'center',
    }}>
      <div style={{ color: 'var(--red)', marginBottom: 16, display: 'flex', justifyContent: 'center' }}>
        <Icon type="alert" size={40} color="var(--red)" />
      </div>
      <h3 style={{ fontSize: 18, fontWeight: 700, color: 'var(--navy)', marginBottom: 8 }}>
        No pudimos verificar disponibilidad
      </h3>
      <p style={{ fontSize: 14, color: 'var(--gray-500)', maxWidth: 400, margin: '0 auto 24px', lineHeight: 1.6 }}>
        {friendlyMessage}
      </p>
      <div style={{ display: 'flex', gap: 12, justifyContent: 'center', flexWrap: 'wrap' }}>
        <button
          onClick={onModify}
          style={{
            padding: '10px 24px', borderRadius: 10, border: '1.5px solid var(--gray-200)',
            background: '#fff', color: 'var(--navy)', fontSize: 14, fontWeight: 600, cursor: 'pointer',
          }}
        >
          Cambiar búsqueda
        </button>
        <button
          onClick={onRetry}
          style={{
            padding: '10px 28px', borderRadius: 10, border: 'none',
            background: 'var(--red)', color: '#fff', fontSize: 14, fontWeight: 600, cursor: 'pointer',
          }}
        >
          Reintentar
        </button>
      </div>
    </div>
  );
}
