import { Link } from '@inertiajs/react';
import { FiActivity, FiArrowUpRight, FiBriefcase, FiCalendar, FiClock, FiFileText, FiShoppingBag, FiUserCheck, FiUsers } from 'react-icons/fi';
import { candidateStatusLabels, candidateTypeLabels } from '../../../lib/recruitmentLabels';

const icons = { leads: FiShoppingBag, candidates: FiUsers, interviews: FiCalendar, evaluation: FiClock, onboarding: FiFileText, active: FiUserCheck };

export function MetricCard({ item, periodLabel = 'Periodo seleccionado' }) {
    const Icon = icons[item.icon] || FiActivity;
    return <article className="rounded-lg border border-[#242833] bg-[#10121a] p-4 transition hover:border-[#5a2c6a]"><div className="flex items-start justify-between"><div className="grid h-10 w-10 place-items-center rounded-lg bg-[#291533] text-[#d968f0]"><Icon size={20} strokeWidth={1.7} /></div><span className="text-[10px] text-[#707585]">{periodLabel}</span></div><p className="mt-4 text-[13px] text-[#c9cad3]">{item.label}</p><div className="mt-1 flex items-end justify-between"><p className="font-editorial text-3xl text-white">{item.value}</p><span className={`text-xs ${item.change >= 0 ? 'text-[#55d99a]' : 'text-[#ff6b75]'}`}>{item.change >= 0 ? '↑' : '↓'} {Math.abs(item.change)}%</span></div></article>;
}

export function LineChart({ data }) {
    const width = 640;
    const height = 190;
    const max = Math.max(...data.map((item) => Math.max(item.leads, item.candidates)), 1);
    const point = (key, index) => { const x = data.length <= 1 ? 0 : (index / (data.length - 1)) * width; const y = height - ((data[index][key] / max) * (height - 22)) - 8; return `${x},${y}`; };
    const leads = data.map((_, index) => point('leads', index)).join(' ');
    const candidates = data.map((_, index) => point('candidates', index)).join(' ');
    const first = data[0]?.label || '';
    const middle = data[Math.floor(data.length / 2)]?.label || '';
    const last = data[data.length - 1]?.label || '';
    return <div className="mt-5"><div className="mb-3 flex items-center justify-center gap-6 text-[11px] text-[#c8c9d2]"><span className="flex items-center gap-2"><i className="h-2 w-5 rounded-full bg-[#a92ad8]" />Leads</span><span className="flex items-center gap-2"><i className="h-2 w-5 rounded-full bg-[#dda5e9]" />Candidatos</span></div><svg viewBox={`0 0 ${width} ${height + 35}`} className="h-52 w-full overflow-visible"><defs><linearGradient id="leadsFill" x1="0" x2="0" y1="0" y2="1"><stop offset="0" stopColor="#a92ad8" stopOpacity=".28" /><stop offset="1" stopColor="#a92ad8" stopOpacity="0" /></linearGradient></defs>{[0, 1, 2, 3, 4].map((line) => <line key={line} x1="0" x2={width} y1={8 + line * ((height - 8) / 4)} y2={8 + line * ((height - 8) / 4)} stroke="#252936" strokeWidth="1" />)}<polygon points={`0,${height} ${leads} ${width},${height}`} fill="url(#leadsFill)" /><polyline points={leads} fill="none" stroke="#b72ada" strokeWidth="3" strokeLinejoin="round" strokeLinecap="round" /><polyline points={candidates} fill="none" stroke="#dda5e9" strokeWidth="2.5" strokeLinejoin="round" strokeLinecap="round" />{[first, middle, last].map((label, index) => <text key={`${label}-${index}`} x={index === 0 ? 0 : index === 1 ? width / 2 : width} y={height + 26} textAnchor={index === 0 ? 'start' : index === 1 ? 'middle' : 'end'} fill="#818694" fontSize="11">{label}</text>)}</svg></div>;
}

export function Donut({ distribution }) {
    const total = distribution.reduce((sum, item) => sum + item.count, 0);
    const model = distribution.find((item) => item.type === 'MODEL')?.count || 0;
    const percent = total ? Math.round((model / total) * 100) : 0;
    return <div className="flex items-center gap-5"><div className="grid h-32 w-32 shrink-0 place-items-center rounded-full" style={{ background: `conic-gradient(#a92ad8 0 ${percent}%, #dca4e8 ${percent}% 100%)` }}><div className="grid h-20 w-20 place-items-center rounded-full bg-[#10121a] text-center"><strong className="font-editorial text-2xl text-white">{total}</strong><span className="-mt-2 text-[10px] text-[#999baa]">Total</span></div></div><div className="space-y-4 text-xs">{distribution.map((item) => <div key={item.type} className="flex items-center gap-2 text-[#c9cad3]"><i className={`h-3 w-3 rounded-sm ${item.type === 'MODEL' ? 'bg-[#a92ad8]' : 'bg-[#dca4e8]'}`} /><span>{item.label}</span><span className="text-[#727786]">{total ? Math.round((item.count / total) * 100) : 0}% ({item.count})</span></div>)}</div></div>;
}

export function Funnel({ pipeline }) {
    const max = Math.max(pipeline[0]?.count || 0, 1);
    return <div className="space-y-1.5">{pipeline.map((item, index) => <div key={item.key} className="flex items-center gap-3 text-xs"><i className={`h-4 w-1 rounded-full ${index < 2 ? 'bg-[#a92ad8]' : index < 5 ? 'bg-[#9574b1]' : 'bg-[#28bc88]'}`} /><span className="w-24 shrink-0 text-[#c9cad3]">{item.label}</span><div className="flex h-6 flex-1 items-center justify-center bg-[#492055] text-[11px] font-semibold text-[#f2d8f6]" style={{ clipPath: 'polygon(4% 0, 96% 0, 90% 100%, 10% 100%)', width: `${Math.max((item.count / max) * 100, item.count ? 18 : 8)}%` }}>{item.count}</div><span className="w-8 text-right text-[#858a99]">{max ? Math.round((item.count / max) * 100) : 0}%</span></div>)}</div>;
}

export function CandidateTable({ candidates }) {
    const labels = { ...candidateTypeLabels, ...candidateStatusLabels };
    return <div className="overflow-x-auto"><table className="w-full min-w-[620px] text-left text-xs"><thead className="border-b border-[#252936] text-[9px] uppercase tracking-wider text-[#858a99]"><tr><th className="px-4 py-3">Nombre</th><th className="px-3 py-3">Tipo</th><th className="px-3 py-3">Ciudad</th><th className="px-3 py-3">Código</th><th className="px-3 py-3">Estado</th></tr></thead><tbody className="divide-y divide-[#20232d]">{candidates.length ? candidates.map((candidate) => <tr key={candidate.id} className="transition hover:bg-[#171522]"><td className="px-4 py-3"><Link href={`/admin/candidates/${candidate.id}`} className="flex items-center gap-2.5 text-[#f3f0f7] hover:text-[#dc83ed]"><span className="grid h-7 w-7 place-items-center rounded-full bg-[#522064] text-[10px] font-semibold text-[#efc8f6]">{candidate.name?.split(' ').map((part) => part[0]).join('').slice(0, 2)}</span>{candidate.name}</Link></td><td className="px-3 py-3"><span className="rounded bg-[#32163e] px-2 py-1 text-[10px] text-[#dc91ec]">{labels[candidate.type] || candidate.type}</span></td><td className="px-3 py-3 text-[#abb0bd]">{candidate.city || '—'}</td><td className="px-3 py-3 font-mono text-[10px] text-[#8f94a3]">{candidate.code}</td><td className="px-3 py-3"><span className="rounded-full bg-[#182d3e] px-2.5 py-1 text-[10px] text-[#8ebce0]">{labels[candidate.status] || candidate.status}</span></td></tr>) : <tr><td colSpan="5" className="px-4 py-12 text-center text-[#777c8b]">No hay candidatos todavía.</td></tr>}</tbody></table></div>;
}

export function PanelTitle({ eyebrow, title, href }) { return <div className="flex items-center justify-between"><div><p className="text-[10px] font-semibold uppercase tracking-[.22em] text-[#d36ee8]">{eyebrow}</p><h2 className="mt-1 font-editorial text-xl text-white">{title}</h2></div>{href && <Link href={href} className="flex items-center gap-1 text-xs text-[#d98aec] hover:text-white">Ver todos <FiArrowUpRight size={14} /></Link>}</div>; }
