import { Link } from '@inertiajs/react';
import BrandMark from '@/Components/BrandMark';

export default function GuestLayout({ children, variant = 'default' }) {
    if (variant === 'login') {
        return (
            <div className="velvet-login-shell">
                <aside className="velvet-login-brand-panel">
                    <Link href="/" className="velvet-login-brand-panel__logo-link">
                        <BrandMark className="velvet-login-brand-panel__logo" />
                    </Link>

                    <div className="velvet-login-brand-panel__image" aria-hidden="true">
                        <img src="/onboarding/profile-model.webp" alt="" />
                        <div className="velvet-login-brand-panel__image-overlay" />
                    </div>

                    <div className="velvet-login-brand-panel__copy">
                        <p className="velvet-login-brand-panel__eyebrow">The Velvet Studio</p>
                        <h2>
                            El talento encuentra
                            <br />
                            <em>su lugar.</em>
                        </h2>
                        <div className="velvet-login-brand-panel__rule" />
                        <p className="velvet-login-brand-panel__description">
                            Gestiona procesos,
                            <br />
                            personas y oportunidades
                            <br />
                            en un solo lugar.
                        </p>
                    </div>

                    <p className="velvet-login-brand-panel__footer">
                        VELVET OS · 2026
                        <span>REAL PEOPLE. BIGGER STORIES.</span>
                    </p>
                </aside>

                <main className="velvet-login-main">{children}</main>
            </div>
        );
    }

    return (
        <div className="min-h-screen bg-[#fbfafc] lg:grid lg:grid-cols-[.9fr_1.1fr]">
            <div className="hidden bg-[#17131a] p-12 text-white lg:flex lg:flex-col lg:justify-between">
                <Link href="/"><BrandMark className="h-11 max-w-[170px]" /></Link>
                <div><p className="text-xs uppercase tracking-[.3em] text-[#c68bd1]">The Velvet Studio</p><p className="mt-6 max-w-md text-5xl font-semibold leading-none tracking-[-.05em]">El talento encuentra su lugar.</p><p className="mt-6 max-w-sm leading-7 text-white/60">Accede al espacio interno para gestionar procesos, personas y oportunidades.</p></div>
                <p className="text-xs uppercase tracking-[.25em] text-white/40">Velvet Onboarding · 2026</p>
            </div>
            <div className="flex min-h-screen flex-col bg-[#090a10] px-6 py-8 sm:px-12 lg:px-24">
                <div className="flex justify-between lg:justify-end"><Link href="/" className="lg:hidden"><BrandMark className="h-9 max-w-[145px]" /></Link><Link href="/" className="text-sm text-gray-500 hover:text-[#883499]">Volver al sitio</Link></div>
                <div className="mx-auto flex w-full max-w-md flex-1 flex-col justify-center py-16">{children}</div>
            </div>
        </div>
    );
}
