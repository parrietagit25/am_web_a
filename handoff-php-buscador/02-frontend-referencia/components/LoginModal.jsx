import { useEffect, useState, useRef } from 'react';

/**
 * Modal de Login — UI placeholder. Sin auth backend.
 * - Submit del form muestra toast "Próximamente disponible"
 * - OAuth buttons abren window.alert("Próximamente")
 *
 * @param {{ open: boolean, onClose: () => void }} props
 */
export default function LoginModal({ open, onClose }) {
  const [tab, setTab] = useState('login'); // 'login' | 'register'
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [name, setName] = useState('');
  const [showPwd, setShowPwd] = useState(false);
  const [toast, setToast] = useState('');
  const closeBtnRef = useRef(null);

  useEffect(() => {
    if (!open) return;
    function onKey(e) { if (e.key === 'Escape') onClose(); }
    window.addEventListener('keydown', onKey);
    document.body.style.overflow = 'hidden';
    closeBtnRef.current?.focus();
    return () => {
      window.removeEventListener('keydown', onKey);
      document.body.style.overflow = '';
    };
  }, [open, onClose]);

  useEffect(() => {
    if (!toast) return;
    const t = setTimeout(() => setToast(''), 3500);
    return () => clearTimeout(t);
  }, [toast]);

  if (!open) return null;

  function handleSubmit(e) {
    e.preventDefault();
    setToast(tab === 'login'
      ? 'Función de inicio de sesión próximamente disponible.'
      : 'Función de registro próximamente disponible.'
    );
  }

  function handleOAuth(provider) {
    window.alert(`Inicio de sesión con ${provider} estará disponible próximamente.`);
  }

  return (
    <>
      {/* Overlay */}
      <div
        onClick={onClose}
        role="dialog"
        aria-modal="true"
        aria-labelledby="login-modal-title"
        style={{
          position: 'fixed', inset: 0,
          background: 'rgba(26,35,70,.55)',
          backdropFilter: 'blur(4px)',
          WebkitBackdropFilter: 'blur(4px)',
          display: 'flex', alignItems: 'center', justifyContent: 'center',
          padding: 16, zIndex: 1000,
          animation: 'fadeInUp .2s ease-out',
        }}
      >
        {/* Card */}
        <div
          onClick={e => e.stopPropagation()}
          style={{
            background: '#fff', borderRadius: 16,
            boxShadow: '0 20px 60px rgba(26,35,70,.30)',
            width: '100%', maxWidth: 420,
            padding: 'clamp(24px, 4vw, 36px)',
            position: 'relative',
            maxHeight: '90vh', overflow: 'auto',
          }}
        >
          {/* Close button */}
          <button
            ref={closeBtnRef}
            type="button"
            onClick={onClose}
            aria-label="Cerrar"
            style={{
              position: 'absolute', top: 12, right: 12,
              width: 32, height: 32, borderRadius: 8,
              border: 'none', background: 'transparent',
              cursor: 'pointer', color: 'var(--gray-500)',
              display: 'inline-flex', alignItems: 'center', justifyContent: 'center',
              transition: 'background .15s',
            }}
            onMouseEnter={e => { e.currentTarget.style.background = 'var(--gray-100)'; }}
            onMouseLeave={e => { e.currentTarget.style.background = 'transparent'; }}
          >
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
              <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
          </button>

          {/* Tabs */}
          <div style={{ display: 'flex', gap: 0, marginBottom: 24, borderBottom: '1px solid var(--gray-200)' }}>
            <button
              type="button"
              onClick={() => setTab('login')}
              style={{
                flex: 1, padding: '12px 0',
                background: 'transparent', border: 'none',
                fontSize: 15, fontWeight: 800,
                color: tab === 'login' ? 'var(--red)' : 'var(--gray-500)',
                borderBottom: `2px solid ${tab === 'login' ? 'var(--red)' : 'transparent'}`,
                marginBottom: -1, cursor: 'pointer',
                fontFamily: 'inherit', transition: 'all .15s',
              }}
            >
              Ingresa
            </button>
            <button
              type="button"
              onClick={() => setTab('register')}
              style={{
                flex: 1, padding: '12px 0',
                background: 'transparent', border: 'none',
                fontSize: 15, fontWeight: 800,
                color: tab === 'register' ? 'var(--red)' : 'var(--gray-500)',
                borderBottom: `2px solid ${tab === 'register' ? 'var(--red)' : 'transparent'}`,
                marginBottom: -1, cursor: 'pointer',
                fontFamily: 'inherit', transition: 'all .15s',
              }}
            >
              Regístrate
            </button>
          </div>

          <h2 id="login-modal-title" style={{ fontSize: 20, fontWeight: 800, color: 'var(--navy)', margin: '0 0 6px' }}>
            {tab === 'login' ? 'Bienvenido de vuelta' : 'Crea tu cuenta'}
          </h2>
          <p style={{ fontSize: 13, color: 'var(--gray-500)', margin: '0 0 22px', lineHeight: 1.5 }}>
            {tab === 'login'
              ? 'Accede a tu historial de reservas y guarda tus preferencias.'
              : 'Reserva más rápido la próxima vez con una cuenta gratuita.'}
          </p>

          {/* Form */}
          <form onSubmit={handleSubmit}>
            {tab === 'register' && (
              <div style={{ marginBottom: 14 }}>
                <label htmlFor="login-name" style={{ display: 'block', fontSize: 12, fontWeight: 700, color: 'var(--gray-600)', marginBottom: 6 }}>
                  Nombre completo
                </label>
                <input
                  id="login-name"
                  type="text"
                  value={name}
                  onChange={e => setName(e.target.value)}
                  placeholder="Juan Pérez"
                  required
                  autoComplete="name"
                  style={inputStyle}
                />
              </div>
            )}

            <div style={{ marginBottom: 14 }}>
              <label htmlFor="login-email" style={{ display: 'block', fontSize: 12, fontWeight: 700, color: 'var(--gray-600)', marginBottom: 6 }}>
                Correo electrónico
              </label>
              <input
                id="login-email"
                type="email"
                value={email}
                onChange={e => setEmail(e.target.value)}
                placeholder="tu@email.com"
                required
                autoComplete="email"
                style={inputStyle}
              />
            </div>

            <div style={{ marginBottom: 6 }}>
              <label htmlFor="login-pwd" style={{ display: 'block', fontSize: 12, fontWeight: 700, color: 'var(--gray-600)', marginBottom: 6 }}>
                Contraseña
              </label>
              <div style={{ position: 'relative' }}>
                <input
                  id="login-pwd"
                  type={showPwd ? 'text' : 'password'}
                  value={password}
                  onChange={e => setPassword(e.target.value)}
                  placeholder="••••••••"
                  required
                  autoComplete={tab === 'login' ? 'current-password' : 'new-password'}
                  style={{ ...inputStyle, paddingRight: 38 }}
                />
                <button
                  type="button"
                  onClick={() => setShowPwd(s => !s)}
                  aria-label={showPwd ? 'Ocultar contraseña' : 'Mostrar contraseña'}
                  style={{
                    position: 'absolute', right: 8, top: '50%', transform: 'translateY(-50%)',
                    background: 'transparent', border: 'none', cursor: 'pointer',
                    color: 'var(--gray-500)', padding: 6,
                  }}
                >
                  {showPwd ? (
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                      <path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/>
                      <line x1="1" y1="1" x2="23" y2="23"/>
                    </svg>
                  ) : (
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                      <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                    </svg>
                  )}
                </button>
              </div>
            </div>

            {tab === 'login' && (
              <div style={{ textAlign: 'right', marginBottom: 18 }}>
                <button
                  type="button"
                  onClick={() => setToast('Función de recuperación próximamente disponible.')}
                  style={{ background: 'none', border: 'none', color: 'var(--red)', fontSize: 12, fontWeight: 600, cursor: 'pointer', fontFamily: 'inherit' }}
                >
                  ¿Olvidaste tu contraseña?
                </button>
              </div>
            )}
            {tab === 'register' && <div style={{ marginBottom: 18 }} />}

            <button
              type="submit"
              style={{
                width: '100%', padding: '13px 18px', borderRadius: 10,
                background: 'var(--red)', color: '#fff', border: 'none',
                fontSize: 14, fontWeight: 800, letterSpacing: '.3px',
                cursor: 'pointer', fontFamily: 'inherit',
                boxShadow: '0 4px 14px rgba(190,28,40,.25)',
                transition: 'all .15s',
              }}
              onMouseEnter={e => { e.currentTarget.style.background = 'var(--red-dark)'; }}
              onMouseLeave={e => { e.currentTarget.style.background = 'var(--red)'; }}
            >
              {tab === 'login' ? 'Iniciar sesión' : 'Crear cuenta'}
            </button>
          </form>

          {/* Divider */}
          <div style={{ display: 'flex', alignItems: 'center', gap: 12, margin: '22px 0' }}>
            <div style={{ flex: 1, height: 1, background: 'var(--gray-200)' }} />
            <span style={{ fontSize: 11, color: 'var(--gray-500)', textTransform: 'uppercase', letterSpacing: '.5px' }}>
              O continúa con
            </span>
            <div style={{ flex: 1, height: 1, background: 'var(--gray-200)' }} />
          </div>

          {/* OAuth buttons */}
          <div style={{ display: 'flex', flexDirection: 'column', gap: 10 }}>
            <button
              type="button"
              onClick={() => handleOAuth('Facebook')}
              style={{
                width: '100%', padding: '11px 16px', borderRadius: 10,
                background: '#1877F2', color: '#fff', border: 'none',
                fontSize: 13.5, fontWeight: 700,
                cursor: 'pointer', fontFamily: 'inherit',
                display: 'inline-flex', alignItems: 'center', justifyContent: 'center', gap: 10,
                transition: 'opacity .15s',
              }}
              onMouseEnter={e => { e.currentTarget.style.opacity = '.92'; }}
              onMouseLeave={e => { e.currentTarget.style.opacity = '1'; }}
            >
              <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
              </svg>
              Continuar con Facebook
            </button>
            <button
              type="button"
              onClick={() => handleOAuth('Google')}
              style={{
                width: '100%', padding: '11px 16px', borderRadius: 10,
                background: '#fff', color: 'var(--gray-700)',
                border: '1.5px solid var(--gray-200)',
                fontSize: 13.5, fontWeight: 700,
                cursor: 'pointer', fontFamily: 'inherit',
                display: 'inline-flex', alignItems: 'center', justifyContent: 'center', gap: 10,
                transition: 'all .15s',
              }}
              onMouseEnter={e => { e.currentTarget.style.borderColor = 'var(--gray-400)'; }}
              onMouseLeave={e => { e.currentTarget.style.borderColor = 'var(--gray-200)'; }}
            >
              <svg width="16" height="16" viewBox="0 0 48 48">
                <path fill="#FFC107" d="M43.611 20.083H42V20H24v8h11.303c-1.649 4.657-6.08 8-11.303 8-6.627 0-12-5.373-12-12s5.373-12 12-12c3.059 0 5.842 1.154 7.961 3.039l5.657-5.657C34.046 6.053 29.268 4 24 4 12.955 4 4 12.955 4 24s8.955 20 20 20 20-8.955 20-20c0-1.341-.138-2.65-.389-3.917z"/>
                <path fill="#FF3D00" d="M6.306 14.691l6.571 4.819C14.655 15.108 18.961 12 24 12c3.059 0 5.842 1.154 7.961 3.039l5.657-5.657C34.046 6.053 29.268 4 24 4 16.318 4 9.656 8.337 6.306 14.691z"/>
                <path fill="#4CAF50" d="M24 44c5.166 0 9.86-1.977 13.409-5.192l-6.19-5.238A11.91 11.91 0 0124 36c-5.202 0-9.619-3.317-11.283-7.946l-6.522 5.025C9.505 39.556 16.227 44 24 44z"/>
                <path fill="#1976D2" d="M43.611 20.083H42V20H24v8h11.303a12.04 12.04 0 01-4.087 5.571l.003-.002 6.19 5.238C36.971 39.205 44 34 44 24c0-1.341-.138-2.65-.389-3.917z"/>
              </svg>
              Continuar con Google
            </button>
          </div>

          {/* Footer note */}
          <p style={{ fontSize: 11, color: 'var(--gray-400)', textAlign: 'center', margin: '20px 0 0', lineHeight: 1.5 }}>
            Al continuar aceptas nuestros{' '}
            <a href="/rent-a-car/terminos" style={{ color: 'var(--gray-500)', fontWeight: 600, textDecoration: 'underline' }}>Términos</a>
            {' '}y{' '}
            <a href="/rent-a-car/privacidad" style={{ color: 'var(--gray-500)', fontWeight: 600, textDecoration: 'underline' }}>Privacidad</a>.
          </p>
        </div>
      </div>

      {/* Toast */}
      {toast && (
        <div
          role="status"
          aria-live="polite"
          style={{
            position: 'fixed', bottom: 24, left: '50%', transform: 'translateX(-50%)',
            zIndex: 1100,
            background: 'var(--navy)', color: '#fff',
            padding: '12px 22px', borderRadius: 10,
            boxShadow: '0 8px 24px rgba(0,0,0,.20)',
            fontSize: 13, fontWeight: 600,
            maxWidth: 'calc(100vw - 32px)',
            animation: 'fadeInUp .25s ease-out',
          }}
        >
          {toast}
        </div>
      )}
    </>
  );
}

const inputStyle = {
  width: '100%',
  padding: '11px 14px',
  borderRadius: 9,
  border: '1.5px solid var(--gray-200)',
  fontSize: 14,
  fontFamily: 'inherit',
  color: 'var(--navy)',
  background: '#fff',
  outline: 'none',
};
