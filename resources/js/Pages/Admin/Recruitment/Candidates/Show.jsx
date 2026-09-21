import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { FaWhatsapp } from 'react-icons/fa6';
import { FiArrowLeft, FiArrowRight, FiCheckCircle, FiClock, FiEdit3, FiFileText, FiMail, FiShield, FiTrash2, FiUser, FiX, FiCalendar } from 'react-icons/fi';
import { formatFriendlyDateTime } from '../../../../lib/date';
import { candidateStatusLabels, candidateTypeLabels } from '../../../../lib/recruitmentLabels';
import AdminApplicationEditModal from '../../../../Components/AdminApplicationEditModal';
import Layout from '../Layout';

const statusOptions = Object.entries(candidateStatusLabels).filter(([value]) => value !== 'DISCARDED');
const completedStatuses = new Set(['PREQUALIFIED', 'ADMITTED', 'WAITING', 'DISCARDED', 'ONBOARDING', 'CONTRACTING', 'INDUCTION', 'READY_TO_ACTIVATE', 'ACTIVE', 'WITHDRAWN']);
const identityStatusLabels = { NOT_STARTED: 'Sin iniciar', IN_PROGRESS: 'En progreso', IN_REVIEW: 'En revisión manual', APPROVED: 'Identidad verificada', DECLINED: 'No aprobada', EXPIRED: 'Sesión vencida', ABANDONED: 'No completada' };

function Detail({ label, value }) {
    return <div><dt className="text-[10px] uppercase tracking-[.18em] text-[#7f8495]">{label}</dt><dd className="mt-2 text-sm text-[#f2edf5]">{value || 'No indicado'}</dd></div>;
}

export default function Show({ candidate }) {
    const sendForm = useForm();
    const discardForm = useForm({ reason: '' });
    const [editing, setEditing] = useState(false);
    const [discarding, setDiscarding] = useState(false);
    const fullName = [candidate.lead.first_name, candidate.lead.last_name].filter(Boolean).join(' ');
    const whatsappNumber = String(candidate.lead.phone || '').replace(/\D/g, '');
    const whatsappHref = whatsappNumber ? `https://wa.me/${whatsappNumber.startsWith('57') ? whatsappNumber : `57${whatsappNumber}`}?text=${encodeURIComponent(`Hola ${fullName}, te escribe el equipo de The Velvet Studio. Revisa tu correo para completar los requisitos iniciales de tu proceso.`)}` : null;
    const emailHref = candidate.lead.email ? `mailto:${candidate.lead.email}?subject=${encodeURIComponent('Requisitos iniciales · The Velvet Studio')}` : null;
    const formCompleted = Boolean(candidate.prequalification_completed_at);
    const canSendForm = !formCompleted && !completedStatuses.has(candidate.status);
    const formSent = Boolean(candidate.prequalification_sent_at);
    const interview = candidate.interviews?.find((item) => ['INVITED', 'SCHEDULED'].includes(item.status));
    const identityStatus = candidate.identity_verification_status || 'NOT_STARTED';
    const identityData = candidate.identity_verification_data || {};

    const discard = (event) => {
        event.preventDefault();
        discardForm.post(`/admin/candidates/${candidate.id}/discard`, { preserveScroll: true, onSuccess: () => setDiscarding(false) });
    };

    const changeStatus = (event) => {
        router.patch(`/admin/candidates/${candidate.id}/status`, { status: event.target.value }, { preserveScroll: true });
    };

    const sendPrequalification = () => {
        sendForm.post(`/admin/candidates/${candidate.id}/prequalification`, { preserveScroll: true });
    };

    return <>
        <Head title={candidate.code} />
        <Layout title="Candidate 360">
            <div>
                <Link href="/admin/candidates" className="inline-flex items-center gap-2 text-sm text-[#d56bea] transition hover:text-white"><FiArrowLeft size={15} /> Candidatos</Link>
                <div className="mt-7 flex flex-col justify-between gap-5 border-b border-[#252936] pb-7 md:flex-row md:items-end">
                    <div><p className="text-[10px] uppercase tracking-[.28em] text-[#d56bea]">Perfil de candidata · {candidate.code}</p><h1 className="mt-3 font-editorial text-4xl text-white">{fullName}</h1><p className="mt-2 text-sm text-[#969baa]">{candidateTypeLabels[candidate.candidate_type] || candidate.candidate_type}</p></div>
                    <div className="flex flex-wrap items-center gap-2"><span className="w-fit rounded-full border border-[#9142a7]/50 bg-[#3d164d] px-3 py-1.5 text-xs text-[#f0c1fa]">{candidateStatusLabels[candidate.status] || candidate.status}</span><button type="button" onClick={() => setEditing((value) => !value)} className="inline-flex items-center gap-2 rounded-lg border border-[#49334f] px-3 py-2 text-xs text-[#e6c4ed] transition hover:border-[#a84bc2] hover:text-white"><FiEdit3 size={14} />{editing ? 'Cerrar edición' : 'Editar datos'}</button>{candidate.status !== 'DISCARDED' && <button type="button" onClick={() => setDiscarding(true)} className="inline-flex items-center gap-2 rounded-lg border border-[#673344] px-3 py-2 text-xs text-[#ffb1bd] transition hover:border-[#bb4c66] hover:bg-[#321622]"><FiTrash2 size={14} />Descartar</button>}</div>
                </div>
                <AdminApplicationEditModal open={editing} onClose={() => setEditing(false)} endpoint={`/admin/candidates/${candidate.id}`} person={candidate.lead} candidateType={candidate.candidate_type} title="Editar información del candidato" />
                <div className="mt-8 grid gap-6 xl:grid-cols-[minmax(0,1.35fr)_minmax(300px,.65fr)]">
                    <section className="rounded-xl border border-[#292d39] bg-[#11131c]/90 p-6 shadow-[0_18px_55px_rgba(0,0,0,.16)] backdrop-blur-xl sm:p-8">
                        <div className="flex items-center gap-3 border-b border-[#292d39] pb-5"><span className="grid h-10 w-10 place-items-center rounded-full bg-[#3c1749] text-[#e2a0f1]"><FiUser size={18} /></span><div><p className="text-sm font-medium text-white">Información de la persona</p><p className="mt-1 text-xs text-[#7f8495]">Datos enviados en el onboarding público.</p></div></div>
                        <dl className="mt-7 grid gap-x-8 gap-y-7 sm:grid-cols-2"><Detail label="Nombre completo" value={fullName} /><Detail label="Email" value={candidate.lead.email} /><Detail label="WhatsApp" value={candidate.lead.phone} /><Detail label="Ciudad" value={candidate.lead.city} /><Detail label="Disponibilidad" value={candidate.lead.availability} /><Detail label="Modalidad" value={candidate.lead.work_mode} /></dl>
                        {candidate.lead.experience && <div className="mt-8 border-t border-[#292d39] pt-7"><p className="text-[10px] uppercase tracking-[.18em] text-[#7f8495]">Experiencia</p><p className="mt-3 whitespace-pre-line text-sm leading-7 text-[#c3c6d1]">{candidate.lead.experience}</p></div>}
                        <div className="mt-8 border-t border-[#292d39] pt-7"><div className="flex items-center justify-between gap-4"><div><p className="text-[10px] uppercase tracking-[.18em] text-[#7f8495]">Estado del proceso</p><p className="mt-2 text-xs text-[#969baa]">Actualiza la etapa cuando completes cada revisión.</p></div>{candidate.status === 'DISCARDED' ? <span className="text-xs text-[#ffb1bd]">Candidato descartado</span> : <select value={candidate.status} onChange={changeStatus} className="velvet-dashboard-select" aria-label="Estado del candidato">{statusOptions.map(([value, label]) => <option key={value} value={value}>{label}</option>)}</select>}</div>{candidate.discard_reason && <p className="mt-4 rounded-lg border border-[#673344] bg-[#321622]/50 px-3 py-2 text-xs leading-5 text-[#ffb1bd]"><strong>Motivo del descarte:</strong> {candidate.discard_reason}</p>}</div>
                    </section>
                    <aside className="rounded-xl border border-[#292d39] bg-[#11131c]/90 p-6 shadow-[0_18px_55px_rgba(0,0,0,.16)] backdrop-blur-xl sm:p-7">
                        <div className="space-y-8">
                            <div className="rounded-xl border border-[#343044] bg-[#151522] p-5 sm:p-6"><div className="flex items-start gap-3"><span className="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-[#3c1749] text-[#e2a0f1]"><FiShield size={17} /></span><div><p className="text-sm font-medium text-[#f2edf5]">Verificación de identidad</p><p className="mt-1 text-xs leading-5 text-[#969baa]">Documento, selfie y mayoría de edad.</p></div></div><span className={`mt-5 inline-flex rounded-full border px-3 py-1.5 text-[11px] ${identityStatus === 'APPROVED' ? 'border-[#2d8669] bg-[#12372e] text-[#9af2cb]' : identityStatus === 'DECLINED' ? 'border-[#7d3144] bg-[#321622] text-[#ffb1bd]' : 'border-[#5e3b69] bg-[#2b1735] text-[#e7b9f1]'}`}>{identityStatusLabels[identityStatus] || identityStatus}</span>{identityStatus === 'APPROVED' && <dl className="mt-5 space-y-2 text-xs"><div className="flex justify-between gap-4"><dt className="text-[#7f8495]">Mayor de edad</dt><dd className="text-[#9af2cb]">{identityData.age_verified ? 'Confirmada' : 'Pendiente'}</dd></div>{identityData.date_of_birth && <div className="flex justify-between gap-4"><dt className="text-[#7f8495]">Fecha validada</dt><dd className="text-[#c9c2cf]">{identityData.date_of_birth}</dd></div>}</dl>}{candidate.identity_verification_failure_reason && <p className="mt-4 text-xs leading-5 text-[#ffb1bd]">{candidate.identity_verification_failure_reason}</p>}</div>
                            <div><div className="flex items-center gap-3"><span className="grid h-9 w-9 place-items-center rounded-full bg-[#3c1749] text-[#e2a0f1]"><FiClock size={16} /></span><div><p className="text-sm font-medium text-white">Siguiente paso</p><p className="mt-1 text-xs text-[#7f8495]">Precalificación inicial</p></div></div><div className="mt-5 rounded-xl border border-[#343044] bg-[#151522] p-5 sm:p-6"><div className="flex items-start gap-3"><span className="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-[#3b1948] text-[#d56bea]"><FiFileText size={18} /></span><div><p className="text-sm font-medium text-[#f2edf5]">Formulario de requisitos</p><p className="mt-1 text-xs leading-5 text-[#969baa]">La candidata recibirá un enlace personal en su correo para completar la información inicial.</p></div></div><button type="button" onClick={sendPrequalification} disabled={!canSendForm || sendForm.processing} className="velvet-button mt-5 w-full gap-2">{sendForm.processing ? 'Enviando…' : formSent ? 'Reenviar formulario' : 'Enviar formulario'}<FiArrowRight size={15} /></button>{formCompleted && <p className="mt-4 flex items-center gap-2 text-xs text-[#8ff0bd]"><FiCheckCircle /> Formulario completado</p>}{formSent && !formCompleted && <p className="mt-4 text-[11px] leading-5 text-[#8f94a3]">Enviado el {formatFriendlyDateTime(candidate.prequalification_sent_at)}. El enlace vence el {formatFriendlyDateTime(candidate.prequalification_expires_at)}.</p>}{sendForm.errors.prequalification && <p className="mt-4 rounded-md border border-[#7d3144] bg-[#321622] px-3 py-2 text-xs text-[#ffb1bd]">{sendForm.errors.prequalification}</p>}</div></div>
                            <div className="border-t border-[#292d39] pt-7"><p className="text-[10px] uppercase tracking-[.2em] text-[#7f8495]">Contacto directo</p><div className="mt-5 space-y-3"><a href={emailHref || undefined} className={`flex items-center gap-3 rounded-xl border px-4 py-4 transition ${emailHref ? 'border-[#302c3c] bg-[#151622] hover:border-[#88429a] hover:bg-[#21172a]' : 'pointer-events-none border-[#262934] bg-[#12141c] opacity-50'}`}><span className="grid h-9 w-9 place-items-center rounded-full bg-[#3b1948] text-[#d56bea]"><FiMail size={15} /></span><span className="min-w-0"><strong className="block text-xs font-medium text-[#e7e1eb]">Enviar correo</strong><small className="mt-1 block truncate text-[10px] text-[#7f8495]">{candidate.lead.email || 'Correo no indicado'}</small></span></a><a href={whatsappHref || undefined} target="_blank" rel="noreferrer" className={`flex items-center gap-3 rounded-xl border px-4 py-4 transition ${whatsappHref ? 'border-[#254b49] bg-[#122321] hover:border-[#36ae97] hover:bg-[#173d37]' : 'pointer-events-none border-[#262934] bg-[#12141c] opacity-50'}`}><span className="grid h-9 w-9 items-center justify-center rounded-full bg-[#16483f] text-[#8ee2ca]"><FaWhatsapp size={15} /></span><span className="min-w-0"><strong className="block text-xs font-medium text-[#e7e1eb]">Escribir por WhatsApp</strong><small className="mt-1 block text-[10px] text-[#7f8495]">{candidate.lead.phone || 'WhatsApp no indicado'}</small></span></a></div></div>
                        </div>
                    </aside>
                </div>
                <section className="mt-6 rounded-xl border border-[#292d39] bg-[#11131c]/90 p-6 shadow-[0_18px_55px_rgba(0,0,0,.16)] sm:p-7"><div className="flex items-center gap-3"><span className="grid h-9 w-9 place-items-center rounded-full bg-[#3c1749] text-[#e2a0f1]"><FiClock size={16} /></span><div><p className="text-sm font-medium text-white">Historial del proceso</p><p className="mt-1 text-xs text-[#7f8495]">Actividad registrada</p></div></div><div className="mt-6 grid gap-5 md:grid-cols-2">{candidate.activities?.length ? candidate.activities.map((item) => <div key={item.id} className="relative border-l border-[#8b28ad] pl-5"><span className="absolute -left-[5px] top-0 h-2.5 w-2.5 rounded-full bg-[#c22be8] shadow-[0_0_12px_rgba(194,43,232,.65)]" /><p className="text-sm text-[#e6e1ea]">{item.description}</p><p className="mt-1 text-xs text-[#7f8495]">{formatFriendlyDateTime(item.created_at)}</p></div>) : <p className="text-sm text-[#7f8495]">Aún no hay actividad registrada.</p>}</div></section>
            </div>
            {discarding && <div className="fixed inset-0 z-50 grid place-items-center bg-black/70 p-4"><form onSubmit={discard} className="w-full max-w-md rounded-2xl border border-[#49334f] bg-[#151522] p-6 shadow-2xl"><div className="flex items-start justify-between gap-4"><div><h2 className="text-lg font-medium text-white">Descartar candidato</h2><p className="mt-2 text-xs leading-5 text-[#969baa]">Indica por qué no continuará en el proceso. Este motivo quedará en el historial.</p></div><button type="button" onClick={() => setDiscarding(false)} className="text-[#9297a7] hover:text-white" aria-label="Cerrar"><FiX /></button></div><textarea required minLength="5" value={discardForm.data.reason} onChange={(event) => discardForm.setData('reason', event.target.value)} className="velvet-input mt-5" rows="4" placeholder="Ej. No aprobó la verificación de identidad…" />{discardForm.errors.reason && <p className="mt-2 text-xs text-[#ffb1bd]">{discardForm.errors.reason}</p>}<div className="mt-5 flex justify-end gap-3"><button type="button" onClick={() => setDiscarding(false)} className="rounded-lg border border-[#343044] px-4 py-2 text-xs text-[#c9c2cf]">Cancelar</button><button disabled={discardForm.processing} className="rounded-lg bg-[#7d3144] px-4 py-2 text-xs font-medium text-white">{discardForm.processing ? 'Descartando…' : 'Confirmar descarte'}</button></div></form></div>}
        </Layout>
    </>;
}
