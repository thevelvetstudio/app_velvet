import '../css/app.css';
import './bootstrap';

import { createInertiaApp, router } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import { useEffect, useState } from 'react';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

const isPublicPath = (pathname) => pathname === '/'
    || pathname === '/login'
    || pathname === '/apply'
    || pathname.startsWith('/forgot-password')
    || pathname.startsWith('/reset-password')
    || pathname.startsWith('/verify-email')
    || pathname.startsWith('/confirm-password');

const pathFromVisit = (visit) => new URL(visit?.url || window.location.href, window.location.origin).pathname;

function PublicRouteOverlay() {
    const [active, setActive] = useState(() => isPublicPath(window.location.pathname));
    const [fading, setFading] = useState(false);

    useEffect(() => {
        let fadeTimer;
        let removeTimer;

        const clearTimers = () => {
            window.clearTimeout(fadeTimer);
            window.clearTimeout(removeTimer);
        };

        const schedule = () => {
            clearTimers();
            setActive(true);
            setFading(false);
            fadeTimer = window.setTimeout(() => {
                setFading(true);
                window.dispatchEvent(new Event('velvet-intro-overlay-complete'));
                removeTimer = window.setTimeout(() => setActive(false), 1400);
            }, 3200);
        };

        const onStart = (event) => {
            const publicRoute = isPublicPath(pathFromVisit(event.detail?.visit));
            window.dispatchEvent(new CustomEvent('velvet-public-route-changed', { detail: { publicRoute } }));
            clearTimers();
            setFading(false);
            setActive(publicRoute);
        };

        const onFinish = (event) => {
            const publicRoute = isPublicPath(pathFromVisit(event.detail?.visit));
            if (publicRoute) schedule();
            else {
                clearTimers();
                setFading(false);
                setActive(false);
            }
        };

        const removeStartListener = router.on('start', onStart);
        const removeFinishListener = router.on('finish', onFinish);
        if (isPublicPath(window.location.pathname)) schedule();

        return () => {
            clearTimers();
            removeStartListener();
            removeFinishListener();
        };
    }, []);

    if (!active) return null;

    return <div className={`apply-intro-overlay ${fading ? 'apply-intro-overlay--fading' : ''}`} role="status" aria-label="Cargando aplicación"><div className="apply-intro-overlay__content"><img src="/LOGO_OVERLAY.svg" alt="The Velvet Studio" className="apply-intro-overlay__logo" /><div className="apply-intro-overlay__progress-track"><div className="apply-intro-overlay__progress-bar" /></div></div></div>;
}

function DashboardRouteSkeleton() {
    const [visible, setVisible] = useState(false);

    useEffect(() => {
        const isDashboardPath = (pathname) => pathname === '/dashboard' || pathname.startsWith('/admin');
        const removeStartListener = router.on('start', (event) => {
            const pathname = pathFromVisit(event.detail?.visit);
            setVisible(isDashboardPath(pathname));
        });
        const removeFinishListener = router.on('finish', () => setVisible(false));

        return () => {
            removeStartListener();
            removeFinishListener();
        };
    }, []);

    if (!visible) return null;

    const block = (className = '') => <span className={`block animate-pulse rounded-md bg-[#1a1d28] ${className}`} />;
    return <div className="dashboard-route-skeleton" aria-busy="true" aria-label="Cargando módulo"><div className="admin-page-content"><div className="flex flex-col justify-between gap-5 md:flex-row md:items-end"><div className="space-y-3">{block('h-3 w-64')}{block('h-10 w-80')}</div><div className="flex gap-3">{block('h-8 w-24')}{block('h-10 w-36')}</div></div><div className="mt-7 flex items-center justify-between border-b border-[#20232d] pb-5">{block('h-3 w-40')}{block('h-9 w-56')}</div><section className="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6">{Array.from({ length: 6 }).map((_, index) => <div key={index} className="rounded-lg border border-[#242833] bg-[#10121a] p-4">{block('h-9 w-9')}{block('mt-5 h-3 w-24')}{block('mt-2 h-8 w-16')}{block('mt-4 h-2 w-full')}</div>)}</section><section className="mt-5 grid gap-4 xl:grid-cols-[1.55fr_.85fr]"><div className="rounded-lg border border-[#242833] bg-[#10121a] p-5">{block('h-4 w-48')}{block('mt-3 h-3 w-72')}{block('mt-8 h-56 w-full')}</div><div className="rounded-lg border border-[#242833] bg-[#10121a] p-5">{block('h-4 w-40')}{block('mt-3 h-3 w-56')}{block('mx-auto mt-8 h-40 w-40 rounded-full')}</div></section></div></div>;
}

function CookieConsentBanner() {
    const [visible, setVisible] = useState(false);
    const [preferencesOpen, setPreferencesOpen] = useState(false);
    const [preferences, setPreferences] = useState({ essential: true, analytics: false, personalization: false });

    useEffect(() => {
        const accepted = () => setVisible(false);
        const show = () => { if (isPublicPath(window.location.pathname) && window.localStorage.getItem('velvet-cookie-consent') !== 'accepted') setVisible(true); };
        const routeChanged = (event) => { if (!event.detail?.publicRoute) setVisible(false); };
        if (isPublicPath(window.location.pathname) && window.location.pathname !== '/apply') show();
        window.addEventListener('velvet-intro-overlay-complete', show);
        window.addEventListener('velvet-cookie-consent-accepted', accepted);
        window.addEventListener('velvet-public-route-changed', routeChanged);
        return () => { window.removeEventListener('velvet-intro-overlay-complete', show); window.removeEventListener('velvet-cookie-consent-accepted', accepted); window.removeEventListener('velvet-public-route-changed', routeChanged); };
    }, []);

    const save = async (nextPreferences) => {
        window.localStorage.setItem('velvet-cookie-consent', 'accepted');
        setVisible(false);
        setPreferencesOpen(false);
        await window.axios.post('/cookie-consent', { preferences: nextPreferences });
    };

    const acceptAll = () => save({ essential: true, analytics: true, personalization: true });
    const rejectOptional = () => save({ essential: true, analytics: false, personalization: false });

    if (!visible) return null;
    return <><div className="cookie-consent-backdrop" aria-hidden="true" /><aside className="cookie-consent-banner" role="dialog" aria-modal="true" aria-label="Consentimiento de cookies"><div><p className="cookie-consent-banner__title">Tu privacidad importa</p><p className="cookie-consent-banner__copy">Configura tus preferencias para continuar. Las cookies esenciales permiten guardar tu aplicación.</p></div><div className="cookie-consent-banner__actions"><button type="button" onClick={() => window.dispatchEvent(new Event('velvet-open-cookie-preferences'))} className="cookie-consent-banner__link">Configurar cookies</button><button type="button" onClick={() => save({ essential: true, analytics: true, personalization: true })} className="velvet-button cookie-consent-banner__button">Aceptar todas</button></div></aside></>;
}

function CookiePreferencesEntry() {
    const [open, setOpen] = useState(false);
    const [preferences, setPreferences] = useState({ essential: true, analytics: false, personalization: false });
    const [publicRoute, setPublicRoute] = useState(() => isPublicPath(window.location.pathname));

    useEffect(() => {
        const openModal = () => setOpen(true);
        const routeChanged = (event) => { setPublicRoute(Boolean(event.detail?.publicRoute)); if (!event.detail?.publicRoute) setOpen(false); };
        window.addEventListener('velvet-open-cookie-preferences', openModal);
        window.addEventListener('velvet-public-route-changed', routeChanged);
        return () => { window.removeEventListener('velvet-open-cookie-preferences', openModal); window.removeEventListener('velvet-public-route-changed', routeChanged); };
    }, []);

    if (!publicRoute) return null;
    const save = async (nextPreferences) => {
        window.localStorage.setItem('velvet-cookie-consent', 'accepted');
        setOpen(false);
        await window.axios.post('/cookie-consent', { preferences: nextPreferences });
    };

    return <>{!open && <button type="button" onClick={() => setOpen(true)} className="cookie-preferences-entry">Configurar cookies</button>}{open && <div className="cookie-consent-modal__backdrop" role="presentation"><section className="cookie-consent-modal" role="dialog" aria-modal="true" aria-labelledby="cookie-preferences-title"><div className="flex items-start justify-between gap-4"><div><p id="cookie-preferences-title" className="cookie-consent-modal__title">Personaliza tus cookies</p><p className="cookie-consent-modal__copy">Las cookies esenciales permiten guardar tu aplicación. Las demás son opcionales.</p></div><button type="button" onClick={() => setOpen(false)} className="cookie-consent-modal__close" aria-label="Cerrar">×</button></div><div className="cookie-consent-modal__options"><label className="cookie-consent-modal__option"><span><strong>Esenciales</strong><small>Seguridad, sesión y recuperación del formulario.</small></span><input type="checkbox" checked disabled /></label><label className="cookie-consent-modal__option"><span><strong>Analítica</strong><small>Ayuda a entender el uso general del sitio.</small></span><input type="checkbox" checked={preferences.analytics} onChange={(event) => setPreferences({ ...preferences, analytics: event.target.checked })} /></label><label className="cookie-consent-modal__option"><span><strong>Personalización</strong><small>Recuerda preferencias para mejorar la experiencia.</small></span><input type="checkbox" checked={preferences.personalization} onChange={(event) => setPreferences({ ...preferences, personalization: event.target.checked })} /></label></div><div className="cookie-consent-modal__actions"><button type="button" onClick={() => save(preferences)} className="velvet-button">Guardar preferencias</button></div></section></div>}</>;
}

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.jsx`,
            import.meta.glob('./Pages/**/*.jsx'),
        ),
    setup({ el, App, props }) {
        const root = createRoot(el);

        root.render(<><App {...props} /><PublicRouteOverlay /><DashboardRouteSkeleton /><CookieConsentBanner /><CookiePreferencesEntry /></>);
    },
    progress: {
        color: '#4B5563',
    },
});
