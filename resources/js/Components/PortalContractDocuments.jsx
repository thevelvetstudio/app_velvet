import { FiDownload, FiFileText, FiEye } from 'react-icons/fi';

const size = (bytes) => {
    if (!bytes) return '';
    if (bytes < 1024 * 1024) return `${Math.max(1, Math.round(bytes / 1024))} KB`;
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
};

export default function PortalContractDocuments({ documents = [] }) {
    return <section className="mt-8 rounded-xl border border-[#292d39] bg-[#11131c]/90 p-5"><div className="flex items-center gap-3"><span className="grid h-9 w-9 place-items-center rounded-full bg-[#3c1749] text-[#e2a0f1]"><FiFileText /></span><div><h2 className="text-sm font-medium text-white">Mis documentos</h2><p className="mt-1 text-xs text-[#7f8495]">Consulta y descarga únicamente los archivos asociados a tu proceso.</p></div></div>{documents.length ? <div className="mt-5 space-y-2">{documents.map((document) => <div key={document.id} className="flex flex-col justify-between gap-3 rounded-lg border border-[#292d39] bg-[#151522] p-4 sm:flex-row sm:items-center"><div className="flex min-w-0 items-center gap-3"><span className="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-[#321344] text-[#e5a5f2]"><FiFileText size={16} /></span><div className="min-w-0"><p className="truncate text-sm text-white">{document.name}</p><p className="mt-1 text-[11px] text-[#858a99]">{document.mime_type?.split('/').pop()?.toUpperCase()} {size(document.size) ? `· ${size(document.size)}` : ''}</p></div></div><div className="flex shrink-0 items-center gap-2"><a href={document.preview_url} target="_blank" rel="noreferrer" className="inline-flex items-center gap-1.5 rounded-lg border border-[#80508c] px-3 py-2 text-[11px] text-[#e7b2f1] transition hover:bg-[#351441]"><FiEye size={13} />Ver</a><a href={document.download_url} className="inline-flex items-center gap-1.5 rounded-lg border border-[#343044] px-3 py-2 text-[11px] text-[#c9c2cf] transition hover:border-[#a84bc2] hover:text-white"><FiDownload size={13} />Descargar</a></div></div>)}</div> : <div className="mt-5 rounded-lg border border-dashed border-[#343044] bg-[#151522]/60 p-5 text-center text-xs text-[#858a99]">Todavía no hay documentos asociados a tu proceso.</div>}</section>;
}
