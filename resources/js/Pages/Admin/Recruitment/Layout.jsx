import { Link, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import { FiBell, FiBriefcase, FiCalendar, FiChevronDown, FiClipboard, FiFileText, FiGrid, FiLogOut, FiMenu, FiSearch, FiSettings, FiSun, FiUser, FiUserCheck, FiUsers, FiX } from 'react-icons/fi';
import BrandMark from '@/Components/BrandMark';
import { useAuthorization } from '@/lib/authorization';

const sections = [
    { label: 'Reclutamiento', items: [['Leads', '/admin/leads', FiUsers], ['Candidatos', '/admin/candidates', FiUserCheck], ['Entrevistas', '/admin/interviews', FiCalendar], ['Calendario', '/admin/calendar', FiCalendar], ['Pipeline', '/admin/recruitment', FiGrid]] },
    { label: 'Onboarding', items: [['Procesos', '/admin/processes', FiClipboard], ['Validaciones', '/admin/validations', FiFileText]] },
    { label: 'Personas', items: [['Modelos', '/admin/candidates?type=MODEL', FiUsers], ['Monitores', '/admin/candidates?type=MONITOR', FiBriefcase]] },
    { label: 'Configuración', items: [['Workflows', '/admin/workflows', FiSettings], ['Usuarios', '/admin/users', FiUsers], ['Roles y permisos', '/admin/access', FiSettings]] },
];

function Wordmark() {
    return <BrandMark className="h-11 max-w-[164px]" />;
}

function isActive(label, url) {
    const [path, query = ''] = url.split('?');
    if (label === 'Modelos') return path === '/admin/candidates' && query.includes('type=MODEL');
    if (label === 'Monitores') return path === '/admin/candidates' && query.includes('type=MONITOR');
    if (label === 'Entrevistas') return path === '/admin/interviews';
    if (label === 'Calendario') return path === '/admin/calendar';
    if (label === 'Candidatos') return path === '/admin/candidates' && !query.includes('type=') && !query.includes('status=');
    if (label === 'Leads') return path === '/admin/leads' && !query.includes('status=NEW');
    if (label === 'Validaciones') return path === '/admin/validations';
    if (label === 'Pipeline') return path === '/admin/recruitment' || path === '/admin';
    if (label === 'Procesos') return path === '/admin/processes';
    if (label === 'Usuarios') return path === '/admin/users';
    if (label === 'Roles y permisos') return path === '/admin/access';
    if (label === 'Workflows') return path === '/admin/workflows';
    return false;
}

const requiredPermissions = {
    Leads: 'leads.view',
    Candidatos: 'candidates.view',
    Entrevistas: 'interviews.view',
    Calendario: 'calendar.view',
    Pipeline: 'dashboard.view',
    Procesos: 'onboarding.view',
    Validaciones: 'documents.verify',
    Modelos: 'models.view_all',
    Monitores: 'models.view_all',
    Workflows: 'settings.manage',
    Usuarios: 'users.view',
    'Roles y permisos': 'roles.view',
};

function Sidebar({ onClose, url, user, can }) {
    const canLabel = (label) => can(requiredPermissions[label]);
    return <aside className="flex h-full w-[224px] flex-col border-r border-[#1f222d] bg-[#090a10] px-4 py-5"><div className="px-3"><Wordmark /></div><nav className="sidebar-scroll mt-8 flex-1 space-y-6 overflow-y-auto">{sections.map((section) => { const visibleItems = section.items.filter(([label]) => canLabel(label)); if (!visibleItems.length) return null; return <div key={section.label}><p className="mb-2 px-3 text-[10px] font-semibold uppercase tracking-[.2em] text-[#8990a1]">{section.label}</p><div className="space-y-1">{visibleItems.map(([label, href, Icon]) => { const active = isActive(label, url); return <Link key={label} href={href} onClick={onClose} className={`group flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm transition ${active ? 'bg-[#55166e] text-white shadow-[0_8px_22px_rgba(136,52,153,.22)]' : 'text-[#b9bac8] hover:bg-[#171522] hover:text-white'}`}><Icon size={17} strokeWidth={1.7} /><span>{label}</span>{active && <span className="ml-auto h-1.5 w-1.5 rounded-full bg-[#e6a0f2]" />}</Link>; })}</div></div>; })}</nav><div className="mt-5 overflow-hidden rounded-lg border border-[#242632] bg-[#11121a]"><div className="relative h-32 overflow-hidden"><img src="/onboarding/modelo.webp" alt="" className="h-full w-full object-cover opacity-70" /><div className="absolute inset-0 bg-gradient-to-t from-[#11121a] via-[#351044]/25 to-transparent" /><p className="absolute bottom-3 left-3 right-3 font-editorial text-lg leading-none text-white">Talento real.<br />Historias más grandes.</p></div></div></aside>;
}

function ProfileMenu({ user, open, onToggle, menuRef }) {
    const initials = user?.name?.split(' ').map((part) => part[0]).join('').slice(0, 2) || 'VA';
    return <div ref={menuRef} className="relative hidden sm:block"><button type="button" onClick={onToggle} aria-haspopup="menu" aria-expanded={open} className="flex items-center gap-2 rounded-lg px-2 py-1.5 text-left transition hover:bg-[#171522]"><span className="grid h-9 w-9 place-items-center rounded-full bg-[#3b2046] text-xs font-semibold text-[#f0c5fa]">{initials}</span><span className="hidden leading-tight lg:block"><span className="block text-xs font-medium text-white">{user?.name || 'Velvet Admin'}</span><span className="block text-[11px] text-[#9296a4]">{user?.roles?.[0]?.name || 'Sin rol'}</span></span><FiChevronDown size={15} className={`ml-1 text-[#a1a4af] transition ${open ? 'rotate-180' : ''}`} /></button>{open && <div role="menu" className="absolute right-0 top-12 z-50 w-56 overflow-hidden rounded-xl border border-[#34303d] bg-[#15121d] p-1.5 shadow-[0_18px_45px_rgba(0,0,0,.5)]"><div className="border-b border-[#292936] px-3 py-2.5"><p className="text-xs font-medium text-white">{user?.name || 'Velvet Admin'}</p><p className="mt-1 truncate text-[11px] text-[#858a99]">{user?.email}</p></div><Link href="/profile" role="menuitem" className="mt-1 flex items-center gap-3 rounded-lg px-3 py-2.5 text-xs text-[#d5d2dd] hover:bg-[#2b1735] hover:text-white"><FiUser size={15} />Visitar perfil</Link><Link href="/logout" method="post" as="button" role="menuitem" className="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-left text-xs text-[#d5d2dd] hover:bg-[#2b1735] hover:text-white"><FiLogOut size={15} />Cerrar sesión</Link></div>}</div>;
}

function DashboardSkeleton() {
    const block = (className = '') => <span className={`block animate-pulse rounded-md bg-[#1a1d28] ${className}`} />;

    return <div className="admin-skeleton" aria-busy="true" aria-label="Cargando módulo"><div className="flex flex-col justify-between gap-5 md:flex-row md:items-end"><div className="space-y-3">{block('h-3 w-64')}{block('h-10 w-80')}</div><div className="flex gap-3">{block('h-8 w-24')}{block('h-10 w-36')}</div></div><div className="mt-7 flex items-center justify-between border-b border-[#20232d] pb-5">{block('h-3 w-40')}{block('h-9 w-56')}</div><section className="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6">{Array.from({ length: 6 }).map((_, index) => <div key={index} className="rounded-lg border border-[#242833] bg-[#10121a] p-4">{block('h-9 w-9')}{block('mt-5 h-3 w-24')}{block('mt-2 h-8 w-16')}{block('mt-4 h-2 w-full')}</div>)}</section><section className="mt-5 grid gap-4 xl:grid-cols-[1.55fr_.85fr]"><div className="rounded-lg border border-[#242833] bg-[#10121a] p-5">{block('h-4 w-48')}{block('mt-3 h-3 w-72')}{block('mt-8 h-56 w-full')}</div><div className="rounded-lg border border-[#242833] bg-[#10121a] p-5">{block('h-4 w-40')}{block('mt-3 h-3 w-56')}{block('mx-auto mt-8 h-40 w-40 rounded-full')}</div></section><section className="mt-4 grid gap-4 xl:grid-cols-2"><div className="rounded-lg border border-[#242833] bg-[#10121a] p-5">{block('h-4 w-36')}{block('mt-6 h-44 w-full')}</div><div className="rounded-lg border border-[#242833] bg-[#10121a] p-5">{block('h-4 w-32')}{Array.from({ length: 4 }).map((_, index) => <div key={index} className="mt-5 flex items-center gap-3">{block('h-8 w-8 rounded-full')}{block('h-3 flex-1')}{block('h-3 w-10')}</div>)}</div></section></div>;
}

export default function Layout({ children }) {
    const [open, setOpen] = useState(false);
    const [profileOpen, setProfileOpen] = useState(false);
    const menuRef = useRef(null);
    const page = usePage();
    const user = page.props.auth?.user;
    const { can } = useAuthorization();

    useEffect(() => {
        const closeMenu = (event) => { if (menuRef.current && !menuRef.current.contains(event.target)) setProfileOpen(false); };
        document.addEventListener('mousedown', closeMenu);
        return () => document.removeEventListener('mousedown', closeMenu);
    }, []);

    return <div className="min-h-screen bg-[#090a10] text-[#f7f1fb]"><div className={`fixed inset-0 z-40 bg-black/70 transition lg:hidden ${open ? 'visible opacity-100' : 'invisible opacity-0'}`} onClick={() => setOpen(false)} /><div className={`fixed inset-y-0 left-0 z-50 transition-transform duration-300 lg:translate-x-0 ${open ? 'translate-x-0' : '-translate-x-full'}`}><div className="absolute right-3 top-3 lg:hidden"><button type="button" onClick={() => setOpen(false)} className="rounded-lg p-2 text-gray-400 hover:bg-white/10 hover:text-white"><FiX /></button></div><Sidebar onClose={() => setOpen(false)} url={page.url} user={user} can={can} /></div><div className="lg:pl-[224px]"><header className="sticky top-0 z-30 flex h-[66px] items-center justify-between border-b border-[#1f222d] bg-[#090a10]/95 px-5 backdrop-blur-xl lg:px-6"><div className="flex items-center gap-4"><button type="button" onClick={() => setOpen(true)} className="rounded-lg p-2 text-gray-300 hover:bg-white/10 lg:hidden"><FiMenu /></button><div className="hidden h-10 w-[480px] items-center gap-3 rounded-lg border border-[#262a36] bg-[#10121a] px-3 text-sm text-[#868b9b] md:flex"><FiSearch size={17} /><span>Buscar candidatos, leads, entrevistas…</span><kbd className="ml-auto rounded border border-[#2f3340] px-2 py-0.5 text-[10px] text-[#adb0bd]">⌘ K</kbd></div></div><div className="flex items-center gap-4"><button type="button" className="hidden text-[#c8cad3] hover:text-white sm:block"><FiSun size={18} /></button><button type="button" className="relative text-[#c8cad3] hover:text-white"><FiBell size={19} /><span className="absolute -right-2 -top-2 grid h-4 min-w-4 place-items-center rounded-full bg-[#a92ad8] px-1 text-[9px] text-white">3</span></button><ProfileMenu user={user} open={profileOpen} onToggle={() => setProfileOpen((value) => !value)} menuRef={menuRef} /></div></header><main><div className="admin-page-content">{children}</div></main></div></div>;
}
