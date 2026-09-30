import { Head, Link } from '@inertiajs/react';
import Layout from '../Recruitment/Layout';
import { formatFriendlyDateTime, formatRelativeTime } from '../../../lib/date';

export default function Index({ notifications, channel, channels }) {
    const items = notifications?.data || [];
    const links = notifications?.links || [];

    return <>
        <Head title="Historial de notificaciones" />
        <Layout>
            <div className="mx-auto max-w-[1200px]">
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                    <div><p className="text-[10px] uppercase tracking-[.28em] text-[#d56bea]">Centro de actividad</p><h1 className="mt-2 font-editorial text-4xl text-white">Historial de notificaciones</h1><p className="mt-2 text-sm text-[#969baa]">Consulta las notificaciones recibidas y accede directamente al módulo relacionado.</p></div>
                    <div className="flex flex-wrap gap-2"><Link href="/admin/notifications/history" className={`rounded-lg border px-3 py-2 text-xs ${!channel ? 'border-[#a92ad8] bg-[#3d164d] text-white' : 'border-[#343044] text-[#b9bac8]'}`}>Todas</Link>{channels.map((item) => <Link key={item} href={`/admin/notifications/history?channel=${encodeURIComponent(item)}`} className={`rounded-lg border px-3 py-2 text-xs ${channel === item ? 'border-[#a92ad8] bg-[#3d164d] text-white' : 'border-[#343044] text-[#b9bac8]'}`}>{item}</Link>)}</div>
                </div>
                <section className="mt-6 overflow-hidden rounded-xl border border-[#292d39] bg-[#11131c]/90"><div className="divide-y divide-[#292d39]">{items.length ? items.map((item) => { const data = item.data || {}; const href = data.interview_id ? '/admin/interviews' : data.candidate_id ? `/admin/candidates/${data.candidate_id}` : data.lead_id ? `/admin/leads/${data.lead_id}` : '/admin/notifications/history'; const reference = data.code || (data.lead_id ? `Lead #${data.lead_id}` : null); return <article key={item.id} className="flex items-center gap-4 px-5 py-4"><div className="min-w-0 flex-1"><div className="flex items-center gap-2"><h2 className="text-sm font-medium text-white">{item.title}</h2>{!item.read_at && <span className="rounded-full bg-[#a92ad8] px-2 py-0.5 text-[9px] text-white">Nueva</span>}</div><p className="mt-1 text-xs text-[#a5a7b4]">{item.description}</p>{reference && <p className="mt-1 text-[10px] font-medium text-[#d56bea]">{reference}</p>}<p className="mt-2 text-[10px] text-[#777d8f]" title={formatFriendlyDateTime(item.created_at)}>{item.channel}  ·  {formatRelativeTime(item.created_at)}</p></div><Link href={href} className="grid h-8 w-8 shrink-0 place-items-center rounded-md border border-[#713080] text-lg text-[#e5a1f2] hover:bg-[#713080] hover:text-white" title="Abrir módulo relacionado" aria-label="Abrir módulo relacionado">↗</Link></article>; }) : <p className="px-5 py-16 text-center text-sm text-[#777d8f]">No hay notificaciones registradas.</p>}</div></section>
                {links.length > 3 && <nav className="mt-5 flex flex-wrap justify-center gap-2" aria-label="Paginación de notificaciones">{links.map((link, index) => link.url ? <Link key={index} href={link.url} className={`rounded-lg border px-3 py-2 text-xs ${link.active ? 'border-[#a92ad8] bg-[#55166e] text-white' : 'border-[#343044] text-[#aaa5b5] hover:border-[#a92ad8] hover:text-white'}`} dangerouslySetInnerHTML={{ __html: link.label }} /> : <span key={index} className="rounded-lg border border-[#343044] px-3 py-2 text-xs text-[#555a6b]" dangerouslySetInnerHTML={{ __html: link.label }} />)}</nav>}
                {notifications?.total > 0 && <p className="mt-3 text-center text-[11px] text-[#777d8f]">Mostrando {notifications.from}–{notifications.to} de {notifications.total} notificaciones</p>}
            </div>
        </Layout>
    </>;
}

