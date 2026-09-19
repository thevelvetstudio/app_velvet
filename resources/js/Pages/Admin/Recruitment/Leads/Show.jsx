import { Head, Link, useForm } from '@inertiajs/react';
import { FiArrowLeft, FiCheckCircle, FiClock, FiMail, FiMessageCircle, FiUser } from 'react-icons/fi';
import { formatFriendlyDate, formatFriendlyDateTime } from '../../../../lib/date';
import Layout from '../Layout';

const typeLabels = { MODEL: 'Modelo Webcam', MONITOR: 'Monitor(a)' };
const statusLabels = { NEW: 'Nueva', CONTACTED: 'Contactada', PREQUALIFIED: 'Precalificada', INTERVIEW: 'Entrevista', EVALUATION: 'En evaluación', ADMITTED: 'Admitida' };
const sexLabels = { WOMAN: 'Mujer', MAN: 'Hombre', TRANS_WOMAN: 'Mujer trans', TRANS_MAN: 'Hombre trans', NON_BINARY: 'No binario', GENDER_FLUID: 'Género fluido', AGENDER: 'Agénero', SELF_DESCRIBE: 'Otro / Prefiero autodescribir', PREFER_NOT_TO_SAY: 'Prefiero no decir' };

function Detail({ label, value }) {
    return <div><dt className="text-[10px] uppercase tracking-[.18em] text-[#7f8495]">{label}</dt><dd className="mt-2 text-sm text-[#f2edf5]">{value || 'No indicado'}</dd></div>;
}

export default function Show({ lead }) {
    const form = useForm();
    const convert = () => form.post(`/admin/leads/${lead.id}/convert`);
    const fullName = [lead.first_name, lead.last_name].filter(Boolean).join(' ') || lead.full_name || 'Sin nombre';
    const birthDate = lead.birth_date ? formatFriendlyDate(lead.birth_date) : null;

    return <>
        <Head title={`Aplicación · ${lead.code}`} />
        <Layout>
            <div>
                <Link href="/admin/leads" className="inline-flex items-center gap-2 text-sm text-[#d56bea] transition hover:text-white"><FiArrowLeft size={15} /> Todas las aplicaciones</Link>
                <div className="mt-7 flex flex-col justify-between gap-5 border-b border-[#252936] pb-7 md:flex-row md:items-end">
                    <div><p className="text-[10px] uppercase tracking-[.28em] text-[#d56bea]">Detalle de aplicación · {lead.code}</p><h1 className="mt-3 font-editorial text-4xl text-white">{fullName}</h1><p className="mt-2 text-sm text-[#969baa]">{typeLabels[lead.candidate_type] || lead.candidate_type}</p></div>
                    <span className="w-fit rounded-full border border-[#9142a7]/50 bg-[#3d164d] px-3 py-1.5 text-xs text-[#f0c1fa]">{statusLabels[lead.status] || lead.status}</span>
                </div>
                <div className="mt-8 grid gap-6 xl:grid-cols-[minmax(0,1.35fr)_minmax(300px,.65fr)]">
                    <section className="rounded-xl border border-[#292d39] bg-[#11131c]/90 p-6 shadow-[0_18px_55px_rgba(0,0,0,.16)] backdrop-blur-xl sm:p-8">
                        <div className="flex items-center gap-3 border-b border-[#292d39] pb-5"><span className="grid h-10 w-10 place-items-center rounded-full bg-[#3c1749] text-[#e2a0f1]"><FiUser size={18} /></span><div><p className="text-sm font-medium text-white">Información de la persona</p><p className="mt-1 text-xs text-[#7f8495]">Datos enviados en el onboarding público.</p></div></div>
                        <dl className="mt-7 grid gap-x-8 gap-y-7 sm:grid-cols-2">
                            <Detail label="Nombre completo" value={fullName} /><Detail label="Sexo" value={sexLabels[lead.sex] || lead.sex} /><Detail label="Email" value={lead.email} /><Detail label="WhatsApp" value={lead.phone} /><Detail label="Ciudad" value={lead.city} /><Detail label="Fecha de nacimiento" value={birthDate} /><Detail label="Fuente" value={lead.source} /><Detail label="Fecha de solicitud" value={formatFriendlyDateTime(lead.created_at)} /><Detail label="Disponibilidad" value={lead.availability} /><Detail label="Modalidad" value={lead.work_mode} />
                        </dl>
                        {lead.experience && <div className="mt-8 border-t border-[#292d39] pt-7"><p className="text-[10px] uppercase tracking-[.18em] text-[#7f8495]">Experiencia</p><p className="mt-3 whitespace-pre-line text-sm leading-7 text-[#c3c6d1]">{lead.experience}</p></div>}
                        {lead.motivation && <div className="mt-8 border-t border-[#292d39] pt-7"><p className="text-[10px] uppercase tracking-[.18em] text-[#7f8495]">Motivación</p><p className="mt-3 whitespace-pre-line text-sm leading-7 text-[#c3c6d1]">{lead.motivation}</p></div>}
                        <div className="mt-8 border-t border-[#292d39] pt-7">{lead.candidate ? <p className="inline-flex items-center gap-2 text-sm text-[#8ff0bd]"><FiCheckCircle /> Convertido a candidato · {lead.candidate.code}</p> : <button onClick={convert} disabled={form.processing} className="velvet-button">{form.processing ? 'Convirtiendo…' : 'Convertir en candidato →'}</button>}</div>
                    </section>
                    <aside className="rounded-xl border border-[#292d39] bg-[#11131c]/90 p-6 shadow-[0_18px_55px_rgba(0,0,0,.16)] backdrop-blur-xl sm:p-7">
                        <div className="flex items-center gap-3"><span className="grid h-9 w-9 place-items-center rounded-full bg-[#3c1749] text-[#e2a0f1]"><FiClock size={16} /></span><div><p className="text-sm font-medium text-white">Historial de la aplicación</p><p className="mt-1 text-xs text-[#7f8495]">Actividad registrada</p></div></div>
                        <div className="mt-7 space-y-6">{lead.activities?.length ? lead.activities.map((activity) => <div key={activity.id} className="relative border-l border-[#8b28ad] pl-5"><span className="absolute -left-[5px] top-0 h-2.5 w-2.5 rounded-full bg-[#c22be8] shadow-[0_0_12px_rgba(194,43,232,.65)]" /><p className="text-sm text-[#e6e1ea]">{activity.description}</p><p className="mt-1 text-xs text-[#7f8495]">{formatFriendlyDateTime(activity.created_at)}</p></div>) : <p className="text-sm text-[#7f8495]">Aún no hay actividad registrada.</p>}</div>
                        <div className="mt-8 border-t border-[#292d39] pt-6 text-xs leading-6 text-[#858a9a]"><p className="inline-flex items-center gap-2"><FiMail size={14} className="text-[#d56bea]" /> {lead.email}</p><p className="mt-2 inline-flex items-center gap-2"><FiMessageCircle size={14} className="text-[#d56bea]" /> {lead.phone}</p></div>
                    </aside>
                </div>
            </div>
        </Layout>
    </>;
}
