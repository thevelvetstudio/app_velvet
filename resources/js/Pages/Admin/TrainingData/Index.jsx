import { Head, useForm } from '@inertiajs/react';
import { FiDatabase, FiRefreshCw, FiShield } from 'react-icons/fi';
import Layout from '@/Pages/Admin/Recruitment/Layout';

export default function Index({ rooms = 0, models = 0, reservations = 0 }) {
    const form = useForm();
    const reset = (event) => {
        event.preventDefault();
        form.post('/admin/training-data/reset', { preserveScroll: true });
    };

    return <><Head title="Datos de prueba" /><Layout><div className="flex flex-col justify-between gap-5 border-b border-[#20232d] pb-7 md:flex-row md:items-end"><div><p className="text-[10px] uppercase tracking-[.28em] text-[#d56bea]">Configuración</p><h1 className="mt-2 font-editorial text-4xl text-white">Datos de prueba</h1><p className="mt-2 max-w-2xl text-sm text-[#969baa]">Administra el entorno sandbox usado por modelos y monitores durante su inducción.</p></div><span className="inline-flex items-center gap-2 rounded-lg border border-[#2d8669] bg-[#12372e] px-3 py-2 text-xs text-[#9af2cb]"><FiShield />Separado de producción</span></div><section className="mt-8 rounded-xl border border-[#292d39] bg-[#11131c]/90 p-6 sm:p-8"><div className="flex items-start gap-4"><span className="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-[#3c1749] text-[#e2a0f1]"><FiDatabase size={19} /></span><div><h2 className="text-lg font-medium text-white">Sandbox de inducción</h2><p className="mt-2 max-w-2xl text-sm leading-6 text-[#969baa]">Estos registros permiten practicar reservas y cambios de uso sin modificar rooms, candidatos ni reservas reales. Cada restauración reemplaza los registros globales de prueba.</p></div></div><div className="mt-8 grid gap-3 sm:grid-cols-3"><Metric label="Rooms de prueba" value={rooms} /><Metric label="Modelos de prueba" value={models} /><Metric label="Reservas de prueba" value={reservations} /></div><form onSubmit={reset} className="mt-8 flex flex-col justify-between gap-4 rounded-lg border border-[#49334f] bg-[#171522] p-4 sm:flex-row sm:items-center"><div><p className="text-sm font-medium text-white">Restaurar datos de inducción</p><p className="mt-1 text-xs text-[#969baa]">El proceso es seguro y no elimina información productiva.</p></div><button type="submit" disabled={form.processing} className="velvet-button gap-2"><FiRefreshCw className={form.processing ? 'animate-spin' : ''} />{form.processing ? 'Restaurando…' : 'Restaurar sandbox'}</button></form></section></Layout></>;
}

function Metric({ label, value }) { return <div className="rounded-lg border border-[#292d39] bg-[#151522] p-4"><p className="text-xs text-[#858a99]">{label}</p><p className="mt-2 text-2xl text-white">{value}</p></div>; }
