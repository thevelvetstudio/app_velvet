import { Head, Link } from '@inertiajs/react';
import { FiArrowLeft, FiFileText, FiShield } from 'react-icons/fi';
import BrandMark from '../../Components/BrandMark';

export default function LegalLayout({ title, description, eyebrow, children, updatedAt = '20 de septiembre de 2026' }) {
    return <>
        <Head title={`${title} · The Velvet Studio`} />
        <main className="min-h-screen bg-[#090a10] px-4 py-8 text-[#f7f1fb] sm:px-6 lg:py-12">
            <div className="mx-auto max-w-4xl">
                <header className="flex flex-wrap items-center justify-between gap-4 border-b border-[#292936] pb-6">
                    <Link href="/" aria-label="Ir al inicio"><BrandMark className="h-10 w-auto" /></Link>
                    <Link href="/apply" className="inline-flex items-center gap-2 rounded-lg border border-[#42304a] px-4 py-2 text-xs text-[#d9c9df] transition hover:border-[#c23bea] hover:text-white"><FiArrowLeft size={14} /> Volver a la aplicación</Link>
                </header>
                <section className="mt-12 rounded-2xl border border-[#292d39] bg-[#11131c]/90 p-6 shadow-[0_18px_55px_rgba(0,0,0,.2)] sm:p-10">
                    <div className="flex items-start gap-4 border-b border-[#292d39] pb-8"><span className="grid h-12 w-12 shrink-0 place-items-center rounded-full bg-[#3c1749] text-[#e2a0f1]"><FiShield size={22} /></span><div><p className="text-xs font-semibold uppercase tracking-[.28em] text-[#d56bea]">{eyebrow}</p><h1 className="mt-3 font-editorial text-4xl text-white sm:text-5xl">{title}</h1><p className="mt-4 max-w-3xl text-sm leading-6 text-[#aaa4b2]">{description}</p><p className="mt-3 text-xs text-[#7f8495]">Última actualización: {updatedAt}</p></div></div>
                    <article className="legal-document mt-8 space-y-8 text-sm leading-7 text-[#c8c3cd]">{children}</article>
                    <footer className="mt-10 flex flex-col gap-4 border-t border-[#292936] pt-6 text-xs text-[#7f8495] sm:flex-row sm:items-center sm:justify-between"><span className="inline-flex items-center gap-2"><FiFileText size={14} /> The Velvet Studio</span><nav className="flex flex-wrap gap-x-5 gap-y-2"><a href="/politica-de-privacidad" target="_blank" rel="noreferrer" className="transition hover:text-[#e5a1f2]">Privacidad</a><a href="/terminos-y-condiciones" target="_blank" rel="noreferrer" className="transition hover:text-[#e5a1f2]">Términos y condiciones</a><a href="mailto:privacidad@thevelvetstudio.com" className="transition hover:text-[#e5a1f2]">Contacto de privacidad</a></nav></footer>
                </section>
            </div>
        </main>
    </>;
}
