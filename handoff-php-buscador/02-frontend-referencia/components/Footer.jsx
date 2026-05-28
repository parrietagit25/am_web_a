import { useState } from 'react';
import { Link } from 'react-router-dom';

const BOTTOM_LINKS_PRIMARY = [
  { label: 'Sobre Nosotros', href: 'https://www.automarket.com.pa/es/sobre-nosotros', external: true },
  { label: 'Contactos',      to: '/rent-a-car/contactos' },
  { label: 'Blog',           to: '/rent-a-car/blog' },
  { label: 'Noticias',       href: 'https://www.automarket.com.pa/es/noticias', external: true },
];

const BOTTOM_LINKS_LEGAL = [
  { label: 'Política de Reembolso / Devolución', to: '/rent-a-car/reembolso' },
  { label: 'Términos y Condiciones',             to: '/rent-a-car/terminos' },
  { label: 'Política de Privacidad',             to: '/rent-a-car/privacidad' },
];

// Logos de las 5 líneas del grupo + Fundación — se renderizan como texto tipográfico blanco
// (el legacy usa imágenes WebP propias; replicamos visualmente con typography fuerte)
const CONOCE_TAMBIEN = [
  { name: 'RENT A CAR',        sub: '',                  href: 'https://www.automarket.com.pa/es/rent-a-car' },
  { name: 'SEMINUEVOS',        sub: '',                  href: 'https://www.automarket.com.pa/es/seminuevos' },
  { name: 'LEASING OPERATIVO', sub: '',                  href: 'https://www.automarket.com.pa/es/leasing-operativo' },
  { name: 'RENTING',           sub: '',                  href: 'https://www.automarket.com.pa/es/renting' },
  { name: 'TALLER',            sub: '',                  href: 'https://www.automarket.com.pa/es/taller' },
  { name: 'Fundación',         sub: 'Moviendo Vidas',    href: 'https://www.automarket.com.pa/es/fundacion-moviendo-vidas', italicized: true },
];

const SOCIAL_LINKS = [
  { name: 'Instagram', href: 'https://www.instagram.com/automarketrentacar', svg: <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg> },
  { name: 'Facebook',  href: 'https://www.facebook.com/automarketrentacar',  svg: <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg> },
  { name: 'TikTok',    href: '#',                                            svg: <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-5.2 1.74 2.89 2.89 0 012.31-4.64 2.93 2.93 0 01.88.13V9.4a6.84 6.84 0 00-1-.05A6.33 6.33 0 005.8 20.1a6.34 6.34 0 0010.86-4.43v-7a8.16 8.16 0 004.77 1.52v-3.4a4.85 4.85 0 01-1.84-.1z"/></svg> },
  { name: 'YouTube',   href: '#',                                            svg: <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M23.498 6.186a3.016 3.016 0 00-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 00.502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 002.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 002.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg> },
];

function FooterLink({ item, style }) {
  if (item.external) {
    return <a href={item.href} target="_blank" rel="noopener noreferrer" style={style}>{item.label}</a>;
  }
  if (item.to) {
    return <Link to={item.to} style={style}>{item.label}</Link>;
  }
  return <a href={item.href} style={style}>{item.label}</a>;
}

function BrandTile({ brand, hover, setHover, i }) {
  const isHover = hover === i;
  return (
    <a
      key={brand.name}
      href={brand.href}
      target="_blank"
      rel="noopener noreferrer"
      onMouseEnter={() => setHover(i)}
      onMouseLeave={() => setHover(null)}
      style={{
        display: 'flex', alignItems: 'center', justifyContent: 'center',
        padding: '14px 8px',
        background: 'transparent',
        textDecoration: 'none',
        textAlign: 'center',
        transition: 'all .2s',
        transform: isHover ? 'translateY(-2px)' : 'none',
        opacity: isHover ? 1 : .9,
      }}
    >
      <div>
        <div style={{
          fontSize: brand.italicized ? 18 : 20,
          fontWeight: brand.italicized ? 600 : 900,
          fontStyle: brand.italicized ? 'italic' : 'normal',
          color: '#fff',
          letterSpacing: brand.italicized ? '.2px' : '.5px',
          lineHeight: 1,
        }}>
          {brand.name}
        </div>
        {brand.sub && (
          <div style={{ fontSize: 11, color: 'rgba(255,255,255,.7)', fontStyle: 'italic', marginTop: 2 }}>
            {brand.sub}
          </div>
        )}
      </div>
    </a>
  );
}

export default function Footer() {
  const [hover, setHover] = useState(null);
  const [socialHover, setSocialHover] = useState(null);

  return (
    <footer style={{ background: 'var(--navy)', color: '#fff', marginTop: 48 }}>
      {/* ── Main top section ─────────────────────────────────────────── */}
      <div style={{ maxWidth: 1200, margin: '0 auto', padding: '40px 24px 24px' }}>
        <div className="r-footer-main" style={{ display: 'grid', gridTemplateColumns: 'minmax(0, 1.3fr) minmax(0, 1fr)', gap: 40, alignItems: 'start' }}>

          {/* ── Col izquierda: Brand + dirección + tels ── */}
          <div>
            <div style={{ marginBottom: 18 }}>
              <img
                src="/images/logo-am.svg"
                alt="Automarket"
                style={{ height: 36, display: 'block', filter: 'brightness(0) invert(1)' }}
                onError={e => { e.currentTarget.style.display = 'none'; }}
              />
              {/* Fallback tipográfico si logo no carga */}
              <div style={{ display: 'none' }}>
                <span style={{ fontSize: 28, fontWeight: 900, color: '#fff', letterSpacing: 1 }}>AUTOMARKET</span>
              </div>
            </div>

            <h3 style={{ fontSize: 17, fontWeight: 700, color: '#fff', margin: '0 0 18px', letterSpacing: '.3px' }}>
              Juntos transformamos movilidad en satisfacción
            </h3>

            <div style={{ fontSize: 13, color: 'rgba(255,255,255,.7)', lineHeight: 1.8 }}>
              <div>Tocumen Commercial Park, Local 17 Edificio Automarket</div>
              <div style={{ marginTop: 6 }}>
                <a href="tel:+5072792700" style={{ color: '#fff', textDecoration: 'none', fontWeight: 600 }}>(507) 279-2700</a>
                <span style={{ margin: '0 8px', color: 'rgba(255,255,255,.3)' }}>|</span>
                <a href="tel:+50767470070" style={{ color: '#fff', textDecoration: 'none', fontWeight: 600 }}>(507) 6747-0070</a>
              </div>
              <div style={{ marginTop: 4 }}>
                Llama gratis desde USA: <a href="tel:18667009904" style={{ color: 'rgba(255,255,255,.85)', textDecoration: 'none' }}>1-866-700-9904</a>
                <a href="https://wa.me/50767470070" target="_blank" rel="noopener noreferrer" style={{ display: 'inline-flex', alignItems: 'center', gap: 4, color: '#25d366', textDecoration: 'none', marginLeft: 12, fontWeight: 600 }}>
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347M12.05 21.785h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26C2.167 6.444 6.6 2.01 12.053 2.01c2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884"/></svg>
                  (507) 6747-0070
                </a>
              </div>
            </div>

            {/* Primary + Legal links inline */}
            <div style={{ marginTop: 22, paddingTop: 18, borderTop: '1px solid rgba(255,255,255,.10)' }}>
              <div style={{ display: 'flex', flexWrap: 'wrap', gap: '8px 20px', marginBottom: 8 }}>
                {BOTTOM_LINKS_PRIMARY.map(item => (
                  <FooterLink
                    key={item.label}
                    item={item}
                    style={{ color: 'rgba(255,255,255,.7)', textDecoration: 'none', fontSize: 13, fontWeight: 500, transition: 'color .15s' }}
                  />
                ))}
              </div>
              <div style={{ display: 'flex', flexWrap: 'wrap', gap: '6px 16px' }}>
                {BOTTOM_LINKS_LEGAL.map(item => (
                  <FooterLink
                    key={item.label}
                    item={item}
                    style={{ color: 'rgba(255,255,255,.45)', textDecoration: 'none', fontSize: 12, transition: 'color .15s' }}
                  />
                ))}
              </div>
            </div>

            {/* Socials inline */}
            <div style={{ marginTop: 18, display: 'flex', alignItems: 'center', gap: 10 }}>
              <span style={{ fontSize: 11, color: 'rgba(255,255,255,.45)', textTransform: 'uppercase', letterSpacing: 1, fontWeight: 600 }}>Síguenos</span>
              {SOCIAL_LINKS.map((s, i) => (
                <a key={s.name} href={s.href} target={s.href !== '#' ? '_blank' : undefined} rel={s.href !== '#' ? 'noopener noreferrer' : undefined}
                   aria-label={s.name}
                   onMouseEnter={() => setSocialHover(i)} onMouseLeave={() => setSocialHover(null)}
                   style={{ width: 32, height: 32, borderRadius: 8, background: socialHover === i ? 'var(--red)' : 'rgba(255,255,255,.08)', display: 'flex', alignItems: 'center', justifyContent: 'center', color: '#fff', textDecoration: 'none', transition: 'all .2s' }}>
                  {s.svg}
                </a>
              ))}
            </div>
          </div>

          {/* ── Col derecha: CONOCE TAMBIÉN + pagos ── */}
          <div>
            <div style={{ fontSize: 11, fontWeight: 700, color: 'rgba(255,255,255,.6)', textTransform: 'uppercase', letterSpacing: 1.5, marginBottom: 16 }}>
              Conoce también:
            </div>

            <div className="r-conoce-grid" style={{ display: 'grid', gridTemplateColumns: 'repeat(2, 1fr)', gap: '8px 16px', marginBottom: 24 }}>
              {CONOCE_TAMBIEN.map((b, i) => (
                <BrandTile key={b.name} brand={b} i={i} hover={hover} setHover={setHover} />
              ))}
            </div>

            {/* Payments */}
            <div style={{ paddingTop: 16, borderTop: '1px solid rgba(255,255,255,.10)' }}>
              <div style={{ fontSize: 11, color: 'rgba(255,255,255,.55)', marginBottom: 10 }}>
                Aceptamos pagos seguros con:
              </div>
              <div style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
                <div style={{ background: '#fff', borderRadius: 5, padding: '5px 12px', display: 'flex', alignItems: 'center' }}>
                  <svg width="40" height="14" viewBox="0 0 40 14"><text x="0" y="12" fontFamily="Arial Black, sans-serif" fontSize="13" fontWeight="900" fill="#1A1F71" fontStyle="italic">VISA</text></svg>
                </div>
                <div style={{ background: '#fff', borderRadius: 5, padding: '5px 12px', display: 'flex', alignItems: 'center', gap: 0 }}>
                  <span style={{ width: 20, height: 20, borderRadius: '50%', background: '#EB001B', display: 'inline-block' }} />
                  <span style={{ width: 20, height: 20, borderRadius: '50%', background: '#F79E1B', display: 'inline-block', marginLeft: -8, mixBlendMode: 'multiply' }} />
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* ── Bottom band — copyright ──────────────────────────────── */}
      <div style={{ background: 'rgba(0,0,0,.25)' }}>
        <div style={{ maxWidth: 1200, margin: '0 auto', padding: '14px 24px', textAlign: 'center' }}>
          <div style={{ fontSize: 12, color: 'rgba(255,255,255,.55)' }}>
            Todos los derechos Reservados. © {new Date().getFullYear()} - Panama Car Rental, S.A. Por{' '}
            <a
              href="https://pixelmediapublicidad.com"
              target="_blank"
              rel="noopener noreferrer"
              style={{ color: 'rgba(255,255,255,.85)', fontWeight: 700, textDecoration: 'none' }}
            >
              Pixel Media Publicidad.
            </a>
          </div>
        </div>
      </div>
    </footer>
  );
}
