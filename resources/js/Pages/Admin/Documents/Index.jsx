import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { FiCheckCircle, FiChevronRight, FiDownload, FiEye, FiFileText, FiSearch, FiShield, FiUsers, FiX } from 'react-icons/fi';
import Layout from '../Recruitment/Layout';
import ConfirmationDialog from '@/Components/ConfirmationDialog';

const typeLabels = {
    identity: 'Identidad',
    interview_notes: 'Notas de entrevista',
    contract_document: 'Contratación',
};

const statusLabels = { PENDING: 'Pendiente', VERIFIED: 'Verificado', REJECTED: 'Rechazado' };

const size = (bytes) => {
    if (!bytes) return '';
    if (bytes < 1024 * 1024) return `${Math.max(1, Math.round(bytes / 1024))} KB`;
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
};

const date = (value) => value ? new Intl.DateTimeFormat('es-CO', { dateStyle: 'medium', timeStyle: 'short', timeZone: 'America/Bogota' }).format(new Date(value)) : '—';

function Status({ value }) {
    const styles = {
        VERIFIED: 'border-[#2d8669] bg-[#12372e] text-[#9af2cb]',
        REJECTED: 'border-[#713246] bg-[#3b1726] text-[#ffb1bd]',
        PENDING: 'border-[#725525] bg-[#352515] text-[#ffe1a1]',
    };
    return <span className={`inline-flex rounded-full border px-2.5 py-1 text-[10px] ${styles[value] || 'border-[#393b4a] text-[#aaa5b5]'}`}>{statusLabels[value] || value}</span>;
}

function personStatus(documents) {
    if (documents.some((document) => document.status === 'REJECTED')) return 'REJECTED';
    if (documents.some((document) => document.status === 'PENDING')) return 'PENDING';
    return 'VERIFIED';
}

export default function Index({ documents, filters = {}, types = [], stats = {}, canManage = false }) {
    const [search, setSearch] = useState(filters.search || '');
    const [type, setType] = useState(filters.type || '');
    const [status, setStatus] = useState(filters.status || '');
    const [selectedPersonId, setSelectedPersonId] = useState(null);
    const [pendingBulkAction, setPendingBulkAction] = useState(null);
    const people = documents?.data || [];
    const selectedPerson = people.find((person) => person.id === selectedPersonId);

    const filter = (event) => {
        event.preventDefault();
        router.get('/admin/documents', { search: search || undefined, type: type || undefined, status: status || undefined }, { preserveState: true, preserveScroll: true, replace: true });
    };

    const updateStatus = (document, nextStatus) => {
        router.patch(`/admin/documents/${document.id}/status`, { status: nextStatus }, { preserveScroll: true });
    };

    const updatePersonStatus = (person, nextStatus) => {
        setPendingBulkAction({ person, status: nextStatus });
    };

    const confirmBulkStatus = () => {
        if (!pendingBulkAction) return;
        const { person, status: nextStatus } = pendingBulkAction;
        router.patch(`/admin/documents/people/${person.id}/status`, { status: nextStatus }, { preserveScroll: true });
        setPendingBulkAction(null);
    };

    return <><Head title="Documentos" /><Layout><div className="mx-auto max-w-[1440px]"><div className="flex flex-col justify-between gap-5 md:flex-row md:items-end"><div><p className="text-[10px] uppercase tracking-[.28em] text-[#d56bea]">Gestión documental</p><h1 className="mt-2 font-editorial text-4xl text-white sm:text-5xl">Documentos</h1><p className="mt-2 max-w-2xl text-sm text-[#969baa]">Consulta la documentación asociada a cada persona y controla su estado desde un único módulo.</p></div><div className="inline-flex items-center gap-2 rounded-lg border border-[#343044] bg-[#151522] px-4 py-3 text-xs text-[#aaa5b5]"><FiShield className="text-[#d56bea]" />Acceso protegido por rol</div></div>
        <div className="mt-8 grid gap-4 sm:grid-cols-3"><div className="velvet-dashboard-panel"><FiFileText className="text-[#d56bea]" size={20} /><p className="mt-4 text-2xl text-white">{stats.total || 0}</p><p className="mt-1 text-xs text-[#858a99]">Documentos encontrados</p></div><div className="velvet-dashboard-panel"><FiUsers className="text-[#d56bea]" size={20} /><p className="mt-4 text-2xl text-white">{stats.people || 0}</p><p className="mt-1 text-xs text-[#858a99]">Personas asociadas</p></div><div className="velvet-dashboard-panel"><FiShield className="text-[#ffe1a1]" size={20} /><p className="mt-4 text-2xl text-white">{stats.pending || 0}</p><p className="mt-1 text-xs text-[#858a99]">Pendientes de revisión</p></div></div>
        <form onSubmit={filter} className="mt-7 grid gap-3 rounded-xl border border-[#292d39] bg-[#11131c]/90 p-4 md:grid-cols-[1fr_220px_220px_auto] md:items-end"><label className="block text-xs text-[#c8c2ce]">Buscar persona, correo o código<div className="relative"><FiSearch className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-[#777d8f]" size={15} /><input value={search} onChange={(event) => setSearch(event.target.value)} className="velvet-input mt-2 pl-9" placeholder="Ej. nombre@correo.com" /></div></label><label className="block text-xs text-[#c8c2ce]">Tipo<select value={type} onChange={(event) => setType(event.target.value)} className="velvet-input mt-2"><option value="">Todos los tipos</option>{types.map((item) => <option key={item} value={item}>{typeLabels[item] || item}</option>)}</select></label><label className="block text-xs text-[#c8c2ce]">Estado<select value={status} onChange={(event) => setStatus(event.target.value)} className="velvet-input mt-2"><option value="">Todos los estados</option>{Object.entries(statusLabels).map(([value, label]) => <option key={value} value={value}>{label}</option>)}</select></label><button className="velvet-button justify-center"><FiSearch />Filtrar</button></form>
        <section className="mt-6 overflow-hidden rounded-xl border border-[#292d39] bg-[#11131c]/90"><div className="flex items-center justify-between gap-3 border-b border-[#292d39] px-5 py-4"><div><h2 className="text-sm font-medium text-white">Repositorio documental</h2><p className="mt-1 text-xs text-[#7f8495]">Modelos, monitores y candidatos con archivos asociados a su proceso.</p></div>{canManage && <span className="rounded-full border border-[#713080] bg-[#32133d] px-3 py-1 text-[10px] text-[#edb4f8]">Gestión habilitada</span>}</div><div className="p-5">{people.length ? <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">{people.map((person) => <button type="button" key={person.id} onClick={() => setSelectedPersonId(person.id === selectedPersonId ? null : person.id)} className={selectedPersonId === person.id ? 'rounded-xl border border-[#b63bd9] bg-[#2a1435] p-4 text-left shadow-[0_10px_30px_rgba(182,59,217,.16)] transition' : 'rounded-xl border border-[#292d39] bg-[#151522] p-4 text-left transition hover:border-[#713080] hover:bg-[#1b1725]'}><div className="flex items-start gap-3"><span className="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-[#3c1749] text-[#e2a0f1]"><FiUsers size={17} /></span><div className="min-w-0"><p className="truncate text-sm font-medium text-white">{person.person?.name || 'Sin persona asociada'}</p><p className="mt-1 truncate text-[11px] text-[#858a99]">{person.person?.code || '—'} · {person.person?.email || 'Sin correo'}</p><p className="mt-2 text-[10px] uppercase tracking-[.14em] text-[#d5a1e1]">{person.person?.type === 'MODEL' ? 'Modelo' : person.person?.type === 'MONITOR' ? 'Monitor' : 'Candidato'}</p></div></div><div className="mt-4 flex items-center justify-between border-t border-[#292d39] pt-3"><span className="text-xs text-[#c8c2ce]">{person.documents.length} {person.documents.length === 1 ? 'archivo' : 'archivos'}</span><Status value={personStatus(person.documents)} /></div><div className="mt-3 flex items-center justify-end gap-1 text-[11px] text-[#d5a1e1]">Ver documentación <FiChevronRight size={14} /></div></button>)}</div> : <div className="rounded-lg border border-dashed border-[#343044] p-12 text-center text-sm text-[#777d8f]">No hay documentos que coincidan con los filtros.</div>}{selectedPerson && <div className="mt-6 rounded-xl border border-[#49334f] bg-[#151522] p-5"><div className="flex flex-col justify-between gap-3 border-b border-[#292d39] pb-4 sm:flex-row sm:items-start"><div><p className="text-[10px] uppercase tracking-[.2em] text-[#d56bea]">Documentación asociada</p><h3 className="mt-2 text-lg font-medium text-white">{selectedPerson.person?.name}</h3><p className="mt-1 text-xs text-[#858a99]">{selectedPerson.person?.code} · {selectedPerson.person?.email}</p></div><div className="flex flex-wrap items-center justify-end gap-2"><span className="text-[10px] uppercase tracking-wide text-[#858a99]">Estado general</span><Status value={personStatus(selectedPerson.documents)} />{canManage && <><button type="button" onClick={() => updatePersonStatus(selectedPerson, 'VERIFIED')} className="inline-flex items-center gap-1.5 rounded-lg border border-[#2d8669] bg-[#12372e] px-3 py-2 text-[11px] text-[#9af2cb] transition hover:bg-[#1a4b3c]"><FiCheckCircle size={13} />Verificar todos</button><button type="button" onClick={() => updatePersonStatus(selectedPerson, 'REJECTED')} className="inline-flex items-center gap-1.5 rounded-lg border border-[#713246] bg-[#3b1726] px-3 py-2 text-[11px] text-[#ffb1bd] transition hover:bg-[#4a1e2d]"><FiX size={13} />Rechazar todos</button></>}</div></div><div className="mt-4 space-y-2">{selectedPerson.documents.map((document) => <div key={document.id} className="flex flex-col justify-between gap-3 rounded-lg border border-[#292d39] bg-[#11131c] p-4 md:flex-row md:items-center"><div className="min-w-0"><p className="truncate text-xs font-medium text-[#eee8f2]">{document.name}</p><p className="mt-1 text-[10px] uppercase tracking-wide text-[#7f8495]">{typeLabels[document.type] || document.type} · {document.mime_type?.split('/').pop()?.toUpperCase()} {size(document.size) && '· ' + size(document.size)} · {date(document.created_at)}</p></div><div className="flex flex-wrap items-center gap-2">{canManage ? <select value={document.status} onChange={(event) => updateStatus(document, event.target.value)} className="rounded-lg border border-[#343044] bg-[#151522] px-2.5 py-2 text-[11px] text-[#d8d2df] focus:border-[#b63bd9] focus:outline-none"><option value="PENDING">Pendiente</option><option value="VERIFIED">Verificado</option><option value="REJECTED">Rechazado</option></select> : <Status value={document.status} />}<a href={document.preview_url} target="_blank" rel="noreferrer" className="inline-flex items-center gap-1.5 rounded-lg border border-[#80508c] px-3 py-2 text-[11px] text-[#e7b2f1] transition hover:bg-[#351441]"><FiEye size={13} />Ver</a><a href={document.download_url} className="inline-flex items-center gap-1.5 rounded-lg border border-[#343044] px-3 py-2 text-[11px] text-[#c9c2cf] transition hover:border-[#a84bc2] hover:text-white"><FiDownload size={13} />Descargar</a></div></div>)}</div></div>}</div></section>
    </div><ConfirmationDialog open={Boolean(pendingBulkAction)} onOpenChange={(open) => !open && setPendingBulkAction(null)} title={pendingBulkAction?.status === 'VERIFIED' ? 'Verificar todos los documentos' : 'Rechazar todos los documentos'} description={pendingBulkAction ? `${pendingBulkAction.status === 'VERIFIED' ? 'Se marcarán como verificados' : 'Se marcarán como rechazados'} todos los archivos de ${pendingBulkAction.person.person?.name}.` : ''} confirmLabel={pendingBulkAction?.status === 'VERIFIED' ? 'Verificar todos' : 'Rechazar todos'} onConfirm={confirmBulkStatus} tone={pendingBulkAction?.status === 'REJECTED' ? 'danger' : 'default'} /></Layout></>;
}
