import { Head, Link, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { FaWhatsapp } from 'react-icons/fa6';
import { FiArrowLeft, FiCheckCircle, FiClock, FiEdit3, FiMail, FiTrash2, FiUser, FiX } from 'react-icons/fi';
import { formatFriendlyDate, formatFriendlyDateTime } from '../../../../lib/date';
import { candidateTypeLabels, leadStatusLabels } from '../../../../lib/recruitmentLabels';
import AdminApplicationEditModal from '../../../../Components/AdminApplicationEditModal';
import Layout from '../Layout';

const sexLabels = { WOMAN: 'Mujer', MAN: 'Hombre', TRANS_WOMAN: 'Mujer trans', TRANS_MAN: 'Hombre trans', NON_BINARY: 'No binario', GENDER_FLUID: 'Género fluido', AGENDER: 'Agénero', SELF_DESCRIBE: 'Otro / Prefiero autodescribir', PREFER_NOT_TO_SAY: 'Prefiero no decir' };

function Detail({ label, value }) {
    return <div><dt className="text-[10px] uppercase tracking-[.18em] text-[#7f8495]">{label}</dt><dd className="mt-2 text-sm text-[#f2edf5]">{value || 'No indicado'}</dd></div>;
}

export default function Show({ lead }) {
    const form = useForm();
    const discardForm = useForm({ reason: '' });
    const [editing, setEditing] = useState(false);
    const [discarding, setDiscarding] = useState(false);
    const convert = () => form.post(`/admin/leads/${lead.id}/convert`);
    const fullName = [lead.first_name, lead.last_name].filter(Boolean).join(' ') || lead.full_name || 'Sin nombre';
    const birthDate = lead.birth_date ? formatFriendlyDate(lead.birth_date) : null;
    const whatsappNumber = String(lead.phone || '').replace(/\D/g, '');
    const whatsappHref = whatsappNumber ? `https://wa.me/${whatsappNumber.startsWith('57') ? whatsappNumber : `57${whatsappNumber}`}?text=${encodeURIComponent(`Hola ${fullName}, te escribe el equipo de The Velvet Studio. Recibimos tu solicitud ${lead.code} y queremos contarte los siguientes pasos.`)}` : null;
    const emailHref = lead.email ? `mailto:${lead.email}?subject=${encodeURIComponent('Seguimiento de tu aplicación · The Velvet Studio')}&body=${encodeURIComponent(`Hola ${fullName},\n\nTe escribe el equipo de The Velvet Studio sobre tu solicitud ${lead.code}.\n\nQuedamos atentos para continuar tu proceso.`)}` : null;
    const discard = (event) => { event.preventDefault(); discardForm.post(`/admin/leads/${lead.id}/discard`, { preserveScroll: true, onSuccess: () => setDiscarding(false) }); };

    return <>
        <Head title={`Aplicación · ${lead.code}`} />
        <Layout>
            <div>
                <Link href="/admin/leads" className="inline-flex items-center gap-2 text-sm text-[#d56bea] transition hover:text-white"><FiArrowLeft size={15} /> Todas las aplicaciones</Link>
                <div className="mt-7 flex flex-col justify-between gap-5 border-b border-[#252936] pb-7 md:flex-row md:items-end">
                    <div><p className="text-[10px] uppercase tracking-[.28em] text-[#d56bea]">Detalle de aplicación · {lead.code}</p><h1 className="mt-3 font-editorial text-4xl text-white">{fullName}</h1><p className="mt-2 text-sm text-[#969baa]">{candidateTypeLabels[lead.candidate_type] || lead.candidate_type}</p></div>
                    <div className="flex flex-wrap items-center gap-2"><span className="w-fit rounded-full border border-[#9142a7]/50 bg-[#3d164d] px-3 py-1.5 text-xs text-[#f0c1fa]">{leadStatusLabels[lead.status] || lead.status}</span><button type="button" onClick={() => setEditing((value) => !value)} className="inline-flex items-center gap-2 rounded-lg border border-[#49334f] px-3 py-2 text-xs text-[#e6c4ed] transition hover:border-[#a84bc2] hover:text-white"><FiEdit3 size={14} />{editing ? 'Cerrar edición' : 'Editar datos'}</button>{!lead.candidate && lead.status !== 'DISCARDED' && <button type="button" onClick={() => setDiscarding(true)} className="inline-flex items-center gap-2 rounded-lg border border-[#673344] px-3 py-2 text-xs text-[#ffb1bd] transition hover:border-[#bb4c66] hover:bg-[#321622]"><FiTrash2 size={14} />Descartar</button>}</div>
                </div>
                <AdminApplicationEditModal open={editing} onClose={() => setEditing(false)} endpoint={`/admin/leads/${lead.id}`} person={lead} candidateType={lead.candidate_type} title="Editar información de la aplicación" />
                <div className="mt-8 grid gap-6 xl:grid-cols-[minmax(0,1.35fr)_minmax(300px,.65fr)]">
                    <section className="rounded-xl border border-[#292d39] bg-[#11131c]/90 p-6 shadow-[0_18px_55px_rgba(0,0,0,.16)] backdrop-blur-xl sm:p-8">
                        <div className="flex items-center gap-3 border-b border-[#292d39] pb-5"><span className="grid h-10 w-10 place-items-center rounded-full bg-[#3c1749] text-[#e2a0f1]"><FiUser size={18} /></span><div><p className="text-sm font-medium text-white">Información de la persona</p><p className="mt-1 text-xs text-[#7f8495]">Datos enviados en el onboarding público.</p></div></div>
                        <dl className="mt-7 grid gap-x-8 gap-y-7 sm:grid-cols-2">
                            <Detail label="Nombre completo" value={fullName} /><Detail label="Sexo" value={sexLabels[lead.sex] || lead.sex} /><Detail label="Email" value={lead.email} /><Detail label="WhatsApp" value={lead.phone} /><Detail label="Ciudad" value={lead.city} /><Detail label="Fecha de nacimiento" value={birthDate} /><Detail label="Fuente" value={lead.source} /><Detail label="Fecha de solicitud" value={formatFriendlyDateTime(lead.created_at)} /><Detail label="Disponibilidad" value={lead.availability} /><Detail label="Modalidad" value={lead.work_mode} />
                        </dl>
                        {lead.experience && <div className="mt-8 border-t border-[#292d39] pt-7"><p className="text-[10px] uppercase tracking-[.18em] text-[#7f8495]">Experiencia</p><p className="mt-3 whitespace-pre-line text-sm leading-7 text-[#c3c6d1]">{lead.experience}</p></div>}
                        {lead.motivation && <div className="mt-8 border-t border-[#292d39] pt-7"><p className="text-[10px] uppercase tracking-[.18em] text-[#7f8495]">Motivación</p><p className="mt-3 whitespace-pre-line text-sm leading-7 text-[#c3c6d1]">{lead.motivation}</p></div>}
                        {lead.discard_reason && <div className="mt-8 border-t border-[#292d39] pt-7"><p className="text-[10px] uppercase tracking-[.18em] text-[#7f8495]">Motivo del descarte</p><p className="mt-3 rounded-lg border border-[#673344] bg-[#321622]/50 px-3 py-2 text-sm leading-6 text-[#ffb1bd]">{lead.discard_reason}</p></div>}
                        <div className="mt-8 border-t border-[#292d39] pt-7">{lead.candidate ? <p className="inline-flex items-center gap-2 text-sm text-[#8ff0bd]"><FiCheckCircle /> Convertido a candidato · {lead.candidate.code}</p> : lead.status === 'DISCARDED' ? <p className="inline-flex items-center gap-2 text-sm text-[#ffb1bd]"><FiTrash2 /> Lead descartado</p> : <button type="button" onClick={convert} disabled={form.processing} className="velvet-button">{form.processing ? 'Convirtiendo…' : 'Convertir en candidato →'}</button>}</div>
                    </section>
                    <aside className="rounded-xl border border-[#292d39] bg-[#11131c]/90 p-6 shadow-[0_18px_55px_rgba(0,0,0,.16)] backdrop-blur-xl sm:p-7">
                        <div className="flex items-center gap-3"><span className="grid h-9 w-9 place-items-center rounded-full bg-[#3c1749] text-[#e2a0f1]"><FiClock size={16} /></span><div><p className="text-sm font-medium text-white">Historial de la aplicación</p><p className="mt-1 text-xs text-[#7f8495]">Actividad registrada</p></div></div>
                        <div className="mt-7 space-y-6">{lead.activities?.length ? lead.activities.map((activity) => <div key={activity.id} className="relative border-l border-[#8b28ad] pl-5"><span className="absolute -left-[5px] top-0 h-2.5 w-2.5 rounded-full bg-[#c22be8] shadow-[0_0_12px_rgba(194,43,232,.65)]" /><p className="text-sm text-[#e6e1ea]">{activity.description}</p><p className="mt-1 text-xs text-[#7f8495]">{formatFriendlyDateTime(activity.created_at)}</p></div>) : <p className="text-sm text-[#7f8495]">Aún no hay actividad registrada.</p>}</div>
                        <div className="mt-8 border-t border-[#292d39] pt-6"><p className="text-[10px] uppercase tracking-[.18em] text-[#7f8495]">Contacto directo</p><div className="mt-4 space-y-3"><a href={emailHref || undefined} className={`flex items-center gap-3 rounded-lg border px-3 py-3 transition ${emailHref ? 'border-[#302c3c] bg-[#151622] hover:border-[#88429a] hover:bg-[#21172a]' : 'pointer-events-none border-[#262934] bg-[#12141c] opacity-50'}`}><span className="grid h-8 w-8 place-items-center rounded-full bg-[#3b1948] text-[#d56bea]"><FiMail size={15} /></span><span className="min-w-0"><strong className="block text-xs font-medium text-[#e7e1eb]">Enviar correo</strong><small className="mt-1 block text-[10px] text-[#7f8495]">{lead.email || 'Correo no indicado'}</small></span></a><a href={whatsappHref || undefined} target="_blank" rel="noreferrer" className={`flex items-center gap-3 rounded-lg border px-3 py-3 transition ${whatsappHref ? 'border-[#254b49] bg-[#122321] hover:border-[#36ae97] hover:bg-[#173d37]' : 'pointer-events-none border-[#262934] bg-[#12141c] opacity-50'}`}><span className="grid h-8 w-8 place-items-center rounded-full bg-[#16483f] text-[#8ee2ca]"><FaWhatsapp size={15} /></span><span className="min-w-0"><strong className="block text-xs font-medium text-[#e7e1eb]">Escribir por WhatsApp</strong><small className="mt-1 block text-[10px] text-[#7f8495]">{lead.phone || 'WhatsApp no indicado'}</small></span></a></div></div>
                    </aside>
                </div>
            </div>
            {discarding && <div className="fixed inset-0 z-50 grid place-items-center bg-black/70 p-4"><form onSubmit={discard} className="w-full max-w-md rounded-2xl border border-[#49334f] bg-[#151522] p-6 shadow-2xl"><div className="flex items-start justify-between gap-4"><div><h2 className="text-lg font-medium text-white">Descartar lead</h2><p className="mt-2 text-xs leading-5 text-[#969baa]">Indica por qué no continuará en el proceso. Este motivo quedará en el historial.</p></div><button type="button" onClick={() => setDiscarding(false)} className="text-[#9297a7] hover:text-white" aria-label="Cerrar"><FiX /></button></div><textarea required minLength="5" value={discardForm.data.reason} onChange={(event) => discardForm.setData('reason', event.target.value)} className="velvet-input mt-5" rows="4" placeholder="Ej. Datos de contacto inválidos…" />{discardForm.errors.reason && <p className="mt-2 text-xs text-[#ffb1bd]">{discardForm.errors.reason}</p>}<div className="mt-5 flex justify-end gap-3"><button type="button" onClick={() => setDiscarding(false)} className="rounded-lg border border-[#343044] px-4 py-2 text-xs text-[#c9c2cf]">Cancelar</button><button disabled={discardForm.processing} className="rounded-lg bg-[#7d3144] px-4 py-2 text-xs font-medium text-white">{discardForm.processing ? 'Descartando…' : 'Confirmar descarte'}</button></div></form></div>}
        </Layout>
    </>;
}
