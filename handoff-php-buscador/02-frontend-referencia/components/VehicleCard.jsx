import React, { useState, useEffect, useRef, useMemo, useCallback } from 'react';
import { useNavigate } from 'react-router-dom';
import { useQueryClient } from '@tanstack/react-query';
import Icon from './Icon';
import { parseSearchUrl } from '../utils/urlParams';
import { buildCarSchema } from '../utils/seo';

/**
 * Build srcSet string for responsive WebP variants. Returns null if the
 * image is external/proxy (no variants generated for those).
 */
function buildSrcSet(imageUrl) {
  if (!imageUrl) return null;
  if (!imageUrl.endsWith('.webp')) return null;
  if (!imageUrl.includes('/images/vehicles/')) return null;
  const base = imageUrl.slice(0, -'.webp'.length);
  return `${base}-small.webp 320w, ${base}-medium.webp 640w, ${base}-large.webp 800w`;
}

const fmt = new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', minimumFractionDigits: 2 });

function VehicleCard({ vehicle, index, days, searchParamsString, wasPreviouslySelected = false }) {
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const cardRef = useRef(null);
  const [hover, setHover] = useState(false);
  const [imgLoaded, setImgLoaded] = useState(false);
  const [imgError, setImgError] = useState(false);

  // available: null=checking, false=unavailable, true/undefined=available
  const isUnavailable   = vehicle.available === false;
  const isPriceLoading  = vehicle.available === null;

  // Decode URL params with the shared helper (handles both compact & legacy).
  const parsedParams = useMemo(() => {
    if (!searchParamsString) return null;
    return parseSearchUrl(new URLSearchParams(searchParamsString));
  }, [searchParamsString]);

  const availQueryKey = useMemo(() => {
    if (!parsedParams || isUnavailable) return null;
    return [
      'availability',
      parsedParams.locationCode,
      parsedParams.returnLocationCode,
      parsedParams.pickupDate,
      parsedParams.returnDate,
      parsedParams.age,
      parsedParams.promoCode,
    ];
  }, [parsedParams, isUnavailable]);

  const prefetchAvailability = useCallback(() => {
    if (!availQueryKey || !parsedParams) return;
    queryClient.prefetchQuery({
      queryKey: availQueryKey,
      queryFn: () => fetch(
        (typeof import.meta !== 'undefined' && import.meta.env?.VITE_API_URL) || '/api/availability',
        {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(parsedParams),
        }
      ).then(r => r.json()),
      staleTime: 5 * 60 * 1000,
    });
  }, [availQueryKey, queryClient, parsedParams]);

  // IntersectionObserver for mobile prefetch (300ms threshold)
  useEffect(() => {
    const el = cardRef.current;
    if (!el || isUnavailable || !window.IntersectionObserver) return;
    let timer;
    const observer = new IntersectionObserver(([entry]) => {
      if (entry.isIntersecting) {
        timer = setTimeout(prefetchAvailability, 300);
      } else {
        clearTimeout(timer);
      }
    }, { threshold: 0.5 });
    observer.observe(el);
    return () => { observer.disconnect(); clearTimeout(timer); };
  }, [isUnavailable, prefetchAvailability]);

  // BARS returns "Best" for web-exclusive rates; only hide badge for explicit counter codes
  const isWebRate = !vehicle.rateCode || vehicle.rateCode === 'Best' || /^WEB/i.test(vehicle.rateCode);

  const specs = [
    ...(vehicle.passengers  ? [{ icon: 'person', label: `${vehicle.passengers} Pasajeros` }] : []),
    { icon: 'gear',  label: vehicle.transmission },
    { icon: 'snow',  label: vehicle.ac ? 'A/C' : 'Sin A/C' },
    ...(vehicle.traction    ? [{ icon: 'traction', label: vehicle.traction }] : []),
    ...(vehicle.bagsLarge   ? [{ icon: 'bag',     label: `${vehicle.bagsLarge} maleta${vehicle.bagsLarge !== 1 ? 's' : ''}` }] : []),
    ...(vehicle.licenseType ? [{ icon: 'license', label: `Licencia ${vehicle.licenseType}` }] : []),
    ...(vehicle.unlimitedMileage === true ? [{ icon: 'road', label: 'Km ilimitado' }] : []),
  ];

  // Image priority: first 3 cards get high priority for LCP, rest lazy
  const isAboveFold = index < 3;

  // Sum of mandatory charges
  const mandatorySum = (vehicle.mandatoryCharges || []).reduce((s, c) => s + (c.amountTotal || 0), 0);

  function handleSelect(rateType) {
    try {
      sessionStorage.setItem('selectedVehicle', JSON.stringify({ ...vehicle, selectedRateType: rateType }));
    } catch { /* storage full or private mode */ }
    navigate(`/rent-a-car/extras?${searchParamsString || ''}`);
  }

  return (
    <div
      ref={cardRef}
      className="fade-in"
      style={{
        background: 'var(--surface-base)',
        borderRadius: 16,
        overflow: 'hidden',
        boxShadow: (!isUnavailable && hover) ? '0 8px 30px rgba(0,0,0,.12)' : '0 1px 3px rgba(0,0,0,.06)',
        border: (!isUnavailable && hover)
          ? '1.5px solid var(--red-25)'
          : (wasPreviouslySelected ? '1.5px solid var(--red)' : '1.5px solid transparent'),
        transform: (!isUnavailable && hover) ? 'translateY(-2px) scale(1.01)' : 'none',
        transition: 'box-shadow .25s ease, transform .25s ease, border-color .25s ease',
        animationDelay: `${index * 60}ms`,
        position: 'relative',
        opacity: isUnavailable ? 0.5 : 1,
        filter: isUnavailable ? 'grayscale(0.4)' : 'none',
      }}
      onMouseEnter={() => { if (!isUnavailable) setHover(true); prefetchAvailability(); }}
      onMouseLeave={() => setHover(false)}
    >
      {/* Schema.org Car markup for SEO (rich snippets en SERPs) */}
      {(() => {
        const schema = buildCarSchema(vehicle);
        if (!schema) return null;
        return (
          <script
            type="application/ld+json"
            // eslint-disable-next-line react/no-danger
            dangerouslySetInnerHTML={{ __html: JSON.stringify(schema) }}
          />
        );
      })()}

      {vehicle.promo && (
        <div style={{
          position: 'absolute', top: 16, left: 16, zIndex: 2,
          background: 'var(--green)', color: '#fff', fontSize: 11, fontWeight: 700,
          padding: '4px 12px', borderRadius: 20, letterSpacing: '.5px', textTransform: 'uppercase',
          display: 'flex', alignItems: 'center', gap: 4,
        }}>
          <Icon type="tag" size={12} color="#fff" /> PROMO
        </div>
      )}

      {wasPreviouslySelected && !isUnavailable && (
        <div style={{
          position: 'absolute', top: 16, right: 16, zIndex: 2,
          background: 'var(--red)', color: '#fff', fontSize: 11, fontWeight: 700,
          padding: '4px 12px', borderRadius: 20, letterSpacing: '.3px',
          display: 'flex', alignItems: 'center', gap: 5,
          boxShadow: '0 2px 8px rgba(190,28,40,.25)',
        }}>
          <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="3" aria-hidden="true">
            <polyline points="20 6 9 17 4 12" />
          </svg>
          Tu selección
        </div>
      )}

      <div className="r-vehicle-inner">
        {/* ── Image panel ── */}
        <div
          className="r-vehicle-image-wrap"
          style={{
            position: 'relative',
            background: 'var(--surface-image)',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            padding: 20,
          }}
        >
          {!imgLoaded && (
            <div
              className="skeleton"
              style={{ width: '100%', height: '100%', position: 'absolute', inset: 0, borderRadius: 0 }}
            />
          )}
          {vehicle.image && !imgError ? (
            <img
              src={vehicle.image}
              srcSet={buildSrcSet(vehicle.image) || undefined}
              sizes="(max-width: 480px) 280px, (max-width: 768px) 360px, 280px"
              alt={vehicle.name}
              loading={isAboveFold ? 'eager' : 'lazy'}
              fetchpriority={isAboveFold ? 'high' : 'low'}
              decoding="async"
              referrerPolicy="no-referrer"
              onLoad={() => setImgLoaded(true)}
              onError={() => { setImgLoaded(true); setImgError(true); }}
              style={{
                maxWidth: '100%',
                height: 160,
                objectFit: 'contain',
                opacity: imgLoaded ? 1 : 0,
                transition: 'opacity .3s',
                filter: 'drop-shadow(0 4px 12px rgba(0,0,0,.10))',
                mixBlendMode: 'multiply',
              }}
            />
          ) : (
            <CarPlaceholder onLoad={() => setImgLoaded(true)} />
          )}
        </div>

        {/* ── Info panel ── */}
        <div className="r-vehicle-info" style={{ flex: 1, padding: '24px 28px', minWidth: 0, display: 'flex', flexDirection: 'column', justifyContent: 'space-between' }}>
          <div>
            {/* Name row */}
            <div style={{ display: 'flex', alignItems: 'baseline', gap: 8, marginBottom: 4 }}>
              <h3 style={{ fontSize: 20, fontWeight: 700, color: 'var(--navy)', fontFamily: 'var(--font-display)', letterSpacing: '-.2px' }}>
                {(vehicle.name || vehicle.sippCode || '').replace(/\s+o\s+similar\.?$/i, '')}
              </h3>
              <span style={{ fontSize: 13, color: 'var(--gray-400)', fontWeight: 400 }}>o similar</span>
            </div>

            {/* Badges row — category first, then availability */}
            <div style={{ display: 'flex', flexWrap: 'wrap', gap: 6, marginBottom: 16 }}>
              <span style={{
                display: 'inline-flex', alignItems: 'center',
                fontSize: 11, fontWeight: 600, color: 'var(--red)',
                background: 'var(--red-08)', padding: '3px 10px', borderRadius: 20,
                letterSpacing: '.3px', textTransform: 'uppercase',
              }}>
                {vehicle.category}
              </span>

              {isUnavailable ? (
                <span style={{
                  display: 'inline-flex', alignItems: 'center', gap: 4,
                  fontSize: 11, fontWeight: 700, color: 'var(--gray-500)',
                  background: 'var(--gray-200)', padding: '4px 10px', borderRadius: 20,
                  letterSpacing: '.3px',
                }}>
                  No disponible
                </span>
              ) : isPriceLoading ? (
                <span style={{
                  display: 'inline-flex', alignItems: 'center', gap: 6,
                  fontSize: 11, fontWeight: 600, color: 'var(--gray-400)',
                  background: 'var(--gray-100)', padding: '4px 10px', borderRadius: 20,
                  letterSpacing: '.3px',
                }}>
                  <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" style={{ animation: 'spin 1s linear infinite' }} aria-hidden="true"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
                  Verificando…
                </span>
              ) : vehicle.minimumDays > 1 ? (
                <span style={{
                  display: 'inline-flex', alignItems: 'center', gap: 4,
                  fontSize: 11, fontWeight: 700, color: 'var(--amber)',
                  background: 'var(--amber-bg-light)', padding: '4px 10px', borderRadius: 20,
                  letterSpacing: '.3px',
                }}>
                  <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                  Mín. {vehicle.minimumDays} días
                </span>
              ) : (
                <span style={{
                  display: 'inline-flex', alignItems: 'center', gap: 4,
                  fontSize: 11, fontWeight: 700, color: '#059669',
                  background: '#ecfdf5', padding: '4px 10px', borderRadius: 20,
                  letterSpacing: '.3px',
                }}>
                  <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="3" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                  Disponible
                </span>
              )}

              {vehicle.unlimitedMileage && (
                <span style={{
                  display: 'inline-flex', alignItems: 'center',
                  fontSize: 11, fontWeight: 600, color: 'var(--green)',
                  background: 'var(--green-08)', padding: '3px 10px', borderRadius: 20,
                  letterSpacing: '.3px', textTransform: 'uppercase',
                }}>
                  Km ilimitado
                </span>
              )}

              {vehicle.minimumAge > 25 && (
                <span style={{
                  display: 'inline-flex', alignItems: 'center', gap: 4,
                  fontSize: 11, fontWeight: 600, color: 'var(--amber)',
                  background: 'var(--amber-bg)', padding: '3px 10px', borderRadius: 20,
                  letterSpacing: '.3px', textTransform: 'uppercase',
                }}>
                  <Icon type="user" size={11} color="var(--amber)" />
                  {`+${vehicle.minimumAge} años`}
                </span>
              )}
            </div>

            {/* Specs grid — 2 columns, predecible con 6-7 items */}
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(2, 1fr)', gap: '8px 20px' }}>
              {specs.map((s, i) => (
                <div key={i} style={{ display: 'flex', alignItems: 'center', gap: 8, fontSize: 13, color: 'var(--gray-600)' }}>
                  <span style={{ color: 'var(--gray-400)', flexShrink: 0 }}><Icon type={s.icon} size={15} /></span>
                  {s.label}
                </div>
              ))}
            </div>

          </div>

          {/* ── Price + buttons (legacy parity: dual rate side-by-side) ── */}
          <div style={{
            marginTop: 20, paddingTop: 16,
            borderTop: '1px solid var(--gray-100)',
          }}>
            {isUnavailable ? (
              vehicle.isCatalogFallback && vehicle.base_price ? (
                <div>
                  <div style={{ fontSize: 11, color: 'var(--gray-400)', fontWeight: 600, marginBottom: 4, letterSpacing: '.3px' }}>
                    Precio referencial
                  </div>
                  <div style={{ display: 'flex', alignItems: 'baseline', gap: 6 }}>
                    <span style={{ fontSize: 13, color: 'var(--gray-400)', fontWeight: 500 }}>desde</span>
                    <span style={{ fontSize: 26, fontWeight: 700, color: 'var(--gray-500)', fontVariantNumeric: 'tabular-nums' }}>
                      {fmt.format(vehicle.base_price)}
                    </span>
                    <span style={{ fontSize: 13, color: 'var(--gray-400)', fontWeight: 500 }}>/día *</span>
                  </div>
                </div>
              ) : (
                <div style={{ fontSize: 13, color: 'var(--gray-400)', fontStyle: 'italic' }}>
                  No disponible para estas fechas
                </div>
              )
            ) : isPriceLoading ? (
              // When the backend gave us a base_price from the catalog (MISS
              // path with catalogFallback), show it as a reference while real
              // prices are being fetched. Otherwise fall back to skeleton.
              vehicle.base_price ? (
                <div style={{ display: 'flex', alignItems: 'flex-end', justifyContent: 'space-between', gap: 16, flexWrap: 'wrap' }}>
                  <div>
                    <div style={{ fontSize: 11, color: 'var(--gray-400)', fontWeight: 600, marginBottom: 4, letterSpacing: '.3px', textTransform: 'uppercase' }}>
                      Precio referencial
                    </div>
                    <div style={{ display: 'flex', alignItems: 'baseline', gap: 6 }}>
                      <span style={{ fontSize: 13, color: 'var(--gray-400)', fontWeight: 500 }}>desde</span>
                      <span style={{ fontSize: 26, fontWeight: 700, color: 'var(--gray-500)', fontVariantNumeric: 'tabular-nums' }}>
                        {fmt.format(vehicle.base_price)}
                      </span>
                      <span style={{ fontSize: 13, color: 'var(--gray-400)', fontWeight: 500 }}>/día</span>
                    </div>
                  </div>
                  <button
                    disabled
                    style={{
                      padding: '11px 28px', borderRadius: 10, border: 'none',
                      background: 'var(--gray-300)', color: '#fff', fontSize: 14, fontWeight: 600,
                      cursor: 'not-allowed', minHeight: 44, fontFamily: 'var(--font-body)',
                      display: 'flex', alignItems: 'center', gap: 8,
                    }}
                  >
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" style={{ animation: 'spin 1s linear infinite' }} aria-hidden="true"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
                    Verificando precio…
                  </button>
                </div>
              ) : (
                <div style={{ display: 'flex', alignItems: 'flex-end', justifyContent: 'space-between', gap: 16, flexWrap: 'wrap' }}>
                  <div>
                    <div className="skeleton" style={{ width: 90, height: 30, borderRadius: 6, marginBottom: 8 }} />
                    <div className="skeleton" style={{ width: 130, height: 14, borderRadius: 4 }} />
                  </div>
                  <button
                    disabled
                    style={{
                      padding: '11px 28px', borderRadius: 10, border: 'none',
                      background: 'var(--gray-300)', color: '#fff', fontSize: 14, fontWeight: 600,
                      cursor: 'not-allowed', minHeight: 44, fontFamily: 'var(--font-body)',
                    }}
                  >
                    Cargando precio…
                  </button>
                </div>
              )
            ) : (() => {
              const periodTotal  = vehicle.priceTotal || (vehicle.priceWeb * Math.max(days, 1));
              const counterTotal = vehicle.priceCounter * Math.max(days, 1);
              if (isWebRate && vehicle.priceCounter > vehicle.priceWeb) {
                return (
                  <RateColumns
                    webTotal={periodTotal}
                    counterTotal={counterTotal}
                    days={days}
                    onSelectWeb={() => handleSelect('web')}
                    onSelectCounter={() => handleSelect('counter')}
                    vehicleName={vehicle.name}
                  />
                );
              }
              return (
                <SingleRate
                  total={periodTotal}
                  days={days}
                  onSelect={() => handleSelect('web')}
                  vehicleName={vehicle.name}
                />
              );
            })()}
          </div>
        </div>
      </div>
    </div>
  );
}

export default React.memo(VehicleCard);

/* ── Pricing sub-components (legacy automarket.com.pa parity) ── */

function PriceNumber({ value, color = 'var(--navy)' }) {
  const formatted = fmt.format(value);
  const dotIdx = formatted.lastIndexOf('.');
  const whole = formatted.slice(0, dotIdx + 1);
  const cents = formatted.slice(dotIdx + 1);
  return (
    <span style={{ fontSize: 26, fontWeight: 700, color, fontVariantNumeric: 'tabular-nums' }}>
      {whole}<sup style={{ fontSize: 14 }}>{cents}</sup>
    </span>
  );
}

function RedButton({ onClick, ariaLabel, children, secondary }) {
  return (
    <button
      onClick={onClick}
      aria-label={ariaLabel}
      style={{
        padding: '0 18px', height: 40, borderRadius: 8, border: 'none',
        background: 'var(--red)', color: '#fff',
        fontSize: 13, fontWeight: 600, cursor: 'pointer',
        whiteSpace: 'nowrap', fontFamily: 'var(--font-body)',
        boxShadow: secondary ? 'none' : '0 4px 14px var(--red-35)',
        opacity: secondary ? 0.92 : 1,
        transition: 'transform .15s, opacity .15s',
      }}
      onMouseEnter={e => { e.currentTarget.style.transform = 'scale(1.03)'; e.currentTarget.style.opacity = '1'; }}
      onMouseLeave={e => { e.currentTarget.style.transform = 'scale(1)'; e.currentTarget.style.opacity = secondary ? '0.92' : '1'; }}
    >
      {children}
    </button>
  );
}

function DaysHint({ days }) {
  if (!days || days < 1) return null;
  return (
    <div style={{ fontSize: 11, color: 'var(--gray-400)', fontWeight: 500, marginTop: 2 }}>
      para {days} día{days !== 1 ? 's' : ''}
    </div>
  );
}

/** Two-column price + button stack — totals for the full rental period. */
function RateColumns({ webTotal, counterTotal, days, onSelectWeb, onSelectCounter, vehicleName }) {
  return (
    <div style={{
      display: 'flex', justifyContent: 'flex-end', alignItems: 'flex-end',
      gap: 18, flexWrap: 'wrap',
    }}>
      <div style={{ display: 'flex', flexDirection: 'column', alignItems: 'center', gap: 8 }}>
        <div style={{ textAlign: 'center' }}>
          <div style={{ display: 'flex', alignItems: 'baseline', gap: 4, justifyContent: 'center' }}>
            <span style={{ fontSize: 12, color: 'var(--gray-500)', fontWeight: 500 }}>USD</span>
            <PriceNumber value={webTotal} />
          </div>
          <DaysHint days={days} />
        </div>
        <RedButton onClick={onSelectWeb} ariaLabel={`Web Exclusivo ${vehicleName}`} secondary>
          WebExclusivo ›
        </RedButton>
      </div>
      <div style={{ display: 'flex', flexDirection: 'column', alignItems: 'center', gap: 8 }}>
        <div style={{ textAlign: 'center' }}>
          <div style={{ display: 'flex', alignItems: 'baseline', gap: 4, justifyContent: 'center' }}>
            <span style={{ fontSize: 12, color: 'var(--gray-500)', fontWeight: 500 }}>USD</span>
            <PriceNumber value={counterTotal} />
          </div>
          <DaysHint days={days} />
        </div>
        <RedButton onClick={onSelectCounter} ariaLabel={`Reservar ${vehicleName}`}>
          Reservar ›
        </RedButton>
      </div>
    </div>
  );
}

/** Single-price card footer — one price + one "Reservar" button. */
function SingleRate({ total, days, onSelect, vehicleName }) {
  return (
    <div style={{
      display: 'flex', justifyContent: 'flex-end', alignItems: 'flex-end',
      gap: 16, flexWrap: 'wrap',
    }}>
      <div style={{ display: 'flex', flexDirection: 'column', alignItems: 'flex-end', gap: 8 }}>
        <div style={{ textAlign: 'right' }}>
          <div style={{ display: 'flex', alignItems: 'baseline', gap: 4, justifyContent: 'flex-end' }}>
            <span style={{ fontSize: 12, color: 'var(--gray-500)', fontWeight: 500 }}>USD</span>
            <PriceNumber value={total} />
          </div>
          <DaysHint days={days} />
        </div>
        <RedButton onClick={onSelect} ariaLabel={`Reservar ${vehicleName}`}>
          Reservar ›
        </RedButton>
      </div>
    </div>
  );
}

function CarPlaceholder({ onLoad }) {
  useEffect(() => { onLoad(); }, []);
  return (
    <svg
      width="200"
      height="120"
      viewBox="0 0 200 120"
      fill="none"
      xmlns="http://www.w3.org/2000/svg"
      style={{ opacity: 0.25 }}
      aria-hidden="true"
    >
      <rect x="20" y="55" width="160" height="40" rx="8" fill="#9ca3af" />
      <rect x="45" y="30" width="110" height="35" rx="10" fill="#9ca3af" />
      <circle cx="55" cy="95" r="18" fill="#9ca3af" />
      <circle cx="55" cy="95" r="10" fill="#d1d5db" />
      <circle cx="145" cy="95" r="18" fill="#9ca3af" />
      <circle cx="145" cy="95" r="10" fill="#d1d5db" />
      <rect x="65" y="33" width="70" height="28" rx="6" fill="#d1d5db" opacity="0.5" />
    </svg>
  );
}
