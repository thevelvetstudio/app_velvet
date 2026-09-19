import { Head, Link, useForm } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { motion } from 'framer-motion';
import { createRoot } from 'react-dom/client';
import { FiArrowLeft, FiArrowRight, FiBriefcase, FiCheck, FiClock, FiFileText, FiUser } from 'react-icons/fi';
import BrandMark from '@/Components/BrandMark';
import PrivacyPolicyModal from '@/Components/PrivacyPolicyModal';
import VelvetDatePicker from '@/Components/VelvetDatePicker';
import VelvetPhoneInput from '@/Components/VelvetPhoneInput';
import VelvetSelect from '@/Components/VelvetSelect';

const steps = [{ label: 'Perfil', sublabel: 'Tu rol en Velvet', Icon: FiUser }, { label: 'Datos', sublabel: 'Información personal', Icon: FiFileText }, { label: 'Experiencia', sublabel: 'Tu recorrido', Icon: FiBriefcase }, { label: 'Disponibilidad', sublabel: 'Tu ritmo de trabajo', Icon: FiClock }, { label: 'Revisión', sublabel: 'Confirma y envía', Icon: FiCheck }];
const progressKey = 'velvet-apply-progress';
const getStoredProgress = () => {
    if (typeof window === 'undefined') return { currentStep: 0, completedSteps: [] };
    try { return JSON.parse(window.localStorage.getItem(progressKey)) || { currentStep: 0, completedSteps: [] }; } catch { return { currentStep: 0, completedSteps: [] }; }
};
const sources = ['Instagram', 'TikTok', 'Facebook', 'Google', 'Referido', 'Evento', 'Otro'];
const sexOptions = [{ value: 'WOMAN', label: 'Mujer' }, { value: 'MAN', label: 'Hombre' }, { value: 'TRANS_WOMAN', label: 'Mujer trans' }, { value: 'TRANS_MAN', label: 'Hombre trans' }, { value: 'NON_BINARY', label: 'No binario' }, { value: 'GENDER_FLUID', label: 'Género fluido' }, { value: 'AGENDER', label: 'Agénero' }, { value: 'SELF_DESCRIBE', label: 'Otro / Prefiero autodescribir' }, { value: 'PREFER_NOT_TO_SAY', label: 'Prefiero no decir' }];
const sexLabels = Object.fromEntries(sexOptions.map((option) => [option.value, option.label]));

function Wordmark() { return <BrandMark className="h-10 max-w-[170px]" />; }
function useButtonArrowIcons(step, processing) {
    useEffect(() => {
        const buttons = Array.from(document.querySelectorAll('button')).filter((button) => {
            const text = button.textContent || '';
            return text.includes('Volver') || text.includes('Continuar') || text.includes('Enviar mi solicitud') || text.includes('Aplicar');
        });

        buttons.forEach((button) => {
            const isBack = (button.textContent || '').includes('Volver');
            const isForward = (button.textContent || '').includes('Continuar');
            const isSubmit = (button.textContent || '').includes('Enviar mi solicitud') || (button.textContent || '').includes('Aplicar');
            const isFirstStep = isForward && step === 0;
            const isIconNavigation = isBack || (isForward && !isFirstStep);
            if (button.dataset.velvetArrow) {
                if (isIconNavigation) {
                    button.classList.add('velvet-icon-only', isBack ? 'is-back' : 'is-forward');
                    if (!button.querySelector('.velvet-button__arrow')) {
                        const icon = document.createElement('span');
                        icon.className = 'velvet-button__arrow';
                        button.appendChild(icon);
                        createRoot(icon).render(isBack ? <FiArrowLeft size={18} strokeWidth={1.8} aria-hidden="true" /> : <FiArrowRight size={18} strokeWidth={1.8} aria-hidden="true" />);
                    }
                } else if (isSubmit) {
                    button.classList.remove('velvet-icon-only', 'is-back', 'is-forward');
                    button.childNodes.forEach((node) => {
                        if (node.nodeType === Node.TEXT_NODE) node.textContent = node.textContent.replace('Enviar mi solicitud', 'Aplicar').replace(String.fromCharCode(8592), '').replace(String.fromCharCode(8594), '');
                    });
                    button.querySelector('.velvet-button__arrow')?.remove();
                } else if (isFirstStep) {
                    button.classList.remove('velvet-icon-only', 'is-back', 'is-forward');
                    button.childNodes.forEach((node) => {
                        if (node.nodeType === Node.TEXT_NODE) node.textContent = node.textContent.replace(/[\u2190\u2192]/g, '');
                    });
                    button.querySelector('.velvet-button__arrow')?.remove();
                }
                return;
            }
            if (isSubmit) button.childNodes.forEach((node) => {
                if (node.nodeType === Node.TEXT_NODE) node.textContent = node.textContent.replace('Enviar mi solicitud', 'Aplicar');
            });
            button.childNodes.forEach((node) => {
                if (node.nodeType === Node.TEXT_NODE) node.textContent = node.textContent.replace(/[←→]/g, '');
            });
            if (!isIconNavigation) button.childNodes.forEach((node) => {
                if (node.nodeType === Node.TEXT_NODE) {
                    node.textContent = node.textContent.replace(String.fromCharCode(8592), '').replace(String.fromCharCode(8594), '');
                }
            });
            if (isIconNavigation) {
                const icon = document.createElement('span');
                icon.className = 'velvet-button__arrow';
                button.appendChild(icon);
                createRoot(icon).render(isBack ? <FiArrowLeft size={18} strokeWidth={1.8} aria-hidden="true" /> : <FiArrowRight size={18} strokeWidth={1.8} aria-hidden="true" />);
                button.classList.add('velvet-icon-only', isBack ? 'is-back' : 'is-forward');
                button.setAttribute('aria-label', isBack ? 'Volver' : 'Continuar');
                button.setAttribute('title', isBack ? 'Volver' : 'Continuar');
            }
            button.dataset.velvetArrow = 'true';
        });
    }, [step, processing]);
}
function Connector({ active, hidden }) { if (hidden) return <div className="flex-1" />; return <div className="relative h-px flex-1 bg-[#454451]"><motion.div initial={{ scaleX: 0 }} animate={{ scaleX: active ? 1 : 0 }} transition={{ duration: 0.45, ease: 'easeOut' }} className="absolute inset-0 origin-left bg-[#c23bea]" /></div>; }
function Stepper({ current }) {
    const stored = getStoredProgress();
    const completedSteps = new Set(stored.completedSteps || []);
    return <div className="apply-stepper-shell"><div className="apply-stepper">{steps.map(({ label, sublabel, Icon }, index) => {
        const completed = completedSteps.has(index) || index < current;
        const active = index === current;
        const navigable = completed || active;
        return <div key={label} className="apply-stepper__item"><div className="apply-stepper__row"><Connector hidden={index === 0} active={completedSteps.has(index - 1) || index <= current - 1} /><button type="button" disabled={!navigable} aria-current={active ? 'step' : undefined} aria-label={`${label}${completed ? ', completado' : active ? ', actual' : ', pendiente'}`} onClick={() => navigable && window.dispatchEvent(new CustomEvent('velvet:navigate-step', { detail: index }))} className={`apply-stepper__circle ${active ? 'is-active' : ''} ${completed ? 'is-completed' : ''} ${navigable ? 'is-navigable' : ''}`}>{active && <span className="apply-stepper__badge">{index + 1}</span>}{completed && !active ? <FiCheck size={21} strokeWidth={2.2} /> : <Icon size={active ? 22 : 20} strokeWidth={1.8} />}</button><Connector hidden={index === steps.length - 1} active={completed} /></div><span className={`apply-stepper__label ${active ? 'is-active' : ''}`}>{label}</span><span className="apply-stepper__sublabel">{sublabel}</span></div>;
    })}</div><div className="apply-progress"><div className="apply-progress__copy"><span>Paso {current + 1} de {steps.length}</span><span>{Math.round(((current + 1) / steps.length) * 100)}% completado</span></div><div className="apply-progress__track"><motion.div className="apply-progress__fill" initial={false} animate={{ width: `${((current + 1) / steps.length) * 100}%` }} transition={{ duration: .55, ease: [0.22, 1, 0.36, 1] }} /></div></div></div>;
}

export default function Apply({ type = 'MODEL', draft = null }) {
    const [step, setStep] = useState(() => getStoredProgress().currentStep || 0);
    const [error, setError] = useState('');
    const [privacyOpen, setPrivacyOpen] = useState(false);
    const form = useForm({ candidate_type: type, first_name: '', last_name: '', sex: '', phone: '', email: '', city: '', birth_date: '', experience: '', availability: 'Tiempo completo', work_mode: type === 'MONITOR' ? 'En estudio' : 'Desde casa', source: '', motivation: '', data_consent: false });
    const set = (name) => (event) => form.setData(name, event.target.value);
    useEffect(() => {
        if (!draft) return;
        form.setData({ ...form.data, ...draft.data, candidate_type: draft.candidate_type || form.data.candidate_type });
        setStep(draft.current_step || 0);
    }, [draft]);
    useEffect(() => { if (form.data.candidate_type === 'MONITOR' && form.data.work_mode !== 'En estudio') form.setData('work_mode', 'En estudio'); }, [form.data.candidate_type]);
    useEffect(() => {
        const selectProfileFromCard = (event) => {
            const button = event.target.closest('button');
            if (!button) return;
            const text = button.textContent || '';
            if (text.includes('Modelo Webcam')) form.setData('candidate_type', 'MODEL');
            if (text.includes('Monitor(a)')) form.setData('candidate_type', 'MONITOR');
        };
        document.addEventListener('click', selectProfileFromCard, true);
        return () => document.removeEventListener('click', selectProfileFromCard, true);
    }, []);
    useButtonArrowIcons(step, form.processing);
    const textField = (name, label, props = {}) => <label className="block text-xs font-medium text-gray-200">{label}<input {...props} value={form.data[name]} onChange={set(name)} className="velvet-input" />{form.errors[name] && <span className="mt-1 block text-xs text-red-400">{form.errors[name]}</span>}</label>;
    const validate = () => { const required = step === 1 ? ['first_name', 'last_name', 'sex', 'email', 'phone', 'city'] : step === 3 ? ['availability', 'work_mode', 'source'] : []; const missing = required.find((name) => !form.data[name]); if (missing) { setError('Completa los campos requeridos para continuar.'); return false; } if (step === 1 && form.data.candidate_type === 'MODEL' && !form.data.birth_date) { setError('Indica tu fecha de nacimiento para verificar la mayoría de edad.'); return false; } if (step === 3 && form.data.candidate_type === 'MONITOR' && form.data.work_mode !== 'En estudio') { setError('Los monitores trabajan únicamente en el estudio.'); return false; } setError(''); return true; };
    useEffect(() => {
        const navigate = (event) => setStep(event.detail);
        window.addEventListener('velvet:navigate-step', navigate);
        return () => window.removeEventListener('velvet:navigate-step', navigate);
    }, []);
    const next = async () => {
        if (!validate()) return;
        if (window.localStorage.getItem('velvet-cookie-consent') !== 'accepted') {
            await window.axios.post('/cookie-consent', { preferences: { essential: true, analytics: false, personalization: false } });
            window.localStorage.setItem('velvet-cookie-consent', 'accepted');
            window.dispatchEvent(new Event('velvet-cookie-consent-accepted'));
        }
        const nextStep = Math.min(step + 1, 4);
        const progress = getStoredProgress();
        progress.currentStep = nextStep;
        progress.completedSteps = Array.from(new Set([...(progress.completedSteps || []), step]));
        window.localStorage.setItem(progressKey, JSON.stringify(progress));
        await window.axios.post('/apply/draft', { candidate_type: form.data.candidate_type, current_step: nextStep, data: form.data });
        setStep(nextStep);
    };
    const submit = (event) => { event.preventDefault(); if (!form.data.data_consent) { setError('Debes aceptar la política de tratamiento de datos.'); return; } form.post('/apply', { onSuccess: () => { window.localStorage.removeItem(progressKey); window.axios.delete('/apply/draft'); }, onError: (errors) => { if (errors.email || errors.phone) { setStep(1); setError('Ya existe una aplicación o vinculación con los datos ingresados. Revisa el correo y el teléfono.'); } } }); };
    const card = (value, title, copy) => <motion.button type="button" onClick={() => form.setData('candidate_type', value)} whileHover={{ y: -4, scale: 1.01 }} whileTap={{ scale: 0.98 }} transition={{ duration: 0.18 }} className={`group overflow-hidden rounded-lg border text-left transition ${form.data.candidate_type === value ? 'border-[#c23bea] bg-[#351044] shadow-[0_0_30px_rgba(194,59,234,.24)]' : 'border-[#353544] bg-[#11121a] hover:border-[#8c42a4]'}`}><div className="relative aspect-[4/5] overflow-hidden"><img src={value === 'MODEL' ? '/onboarding/profile-model.png' : '/onboarding/profile-monitor-symbolic.png'} alt="" className="h-full w-full object-cover transition duration-500 group-hover:scale-105" /><div className="absolute inset-0 bg-gradient-to-t from-[#11121a] via-[#883499]/10 to-transparent" /><div className="absolute inset-0 bg-gradient-to-br from-[#c23bea]/20 via-transparent to-black/30" /></div><div className="bg-[#11121a] p-6"><div className="flex items-center justify-between"><p className="font-editorial text-2xl text-white">{title}</p><span className={`grid h-6 w-6 place-items-center rounded-full border text-xs ${form.data.candidate_type === value ? 'border-[#c23bea] bg-[#c23bea] text-white' : 'border-gray-500 text-transparent'}`}>✓</span></div><p className="mt-3 text-sm leading-6 text-gray-400">{copy}</p></div></motion.button>;
    const review = [['Perfil', form.data.candidate_type === 'MODEL' ? 'Modelo Webcam' : 'Monitor(a)'], ['Nombre', `${form.data.first_name} ${form.data.last_name}`], ['Sexo', sexLabels[form.data.sex] || '—'], ['Contacto', `${form.data.email} · ${form.data.phone}`], ['Ciudad', form.data.city], ['Disponibilidad', `${form.data.work_mode} · ${form.data.availability}`], ['Fuente', form.data.source]];
    const workModeOptions = form.data.candidate_type === 'MONITOR' ? ['En estudio'] : ['Desde casa', 'En estudio', 'Híbrido'];
    return <><Head title="Aplicar a Velvet" /><main className="min-h-screen bg-[#090a10] px-4 py-5 text-[#f7f1fb] sm:px-8"><header className="mx-auto max-w-[920px]"><Link href="/"><Wordmark /></Link></header><section className="mx-auto max-w-[920px] pb-16 pt-12"><Stepper current={step} /><div className="mx-auto mt-12 max-w-[770px]"><p className="text-[11px] font-semibold uppercase tracking-[.35em] text-[#d36ee8]">The Velvet Studio · Aplicación</p><h1 className="mt-5 text-4xl leading-none tracking-[-.04em] text-white sm:text-5xl">{['¿Qué te describe mejor?', 'Datos personales', 'Tu experiencia', 'Tu disponibilidad', 'Revisa tu información'][step]}</h1><p className="mt-4 max-w-lg text-sm leading-6 text-gray-400">{['Selecciona el perfil al que deseas aplicar. Podrás contarnos más en los siguientes pasos.', 'Solo necesitamos lo esencial para contactarte y estudiar tu aplicación.', 'La experiencia es opcional y nos ayuda a conocerte mejor.', 'Cuéntanos cómo te gustaría trabajar.', 'Verifica tus datos antes de enviar la aplicación.'][step]}</p><form onSubmit={submit} className="mt-9">{step === 0 && <div className="grid gap-4 sm:grid-cols-2">{card('MODEL', 'Modelo Webcam', 'Construye tu perfil y comienza tu proceso con Velvet.')}{card('MONITOR', 'Monitor(a)', 'Haz parte del equipo que acompaña y desarrolla el talento Velvet.')}</div>}{step === 1 && <div className="grid gap-x-5 gap-y-5 sm:grid-cols-2">{textField('first_name', 'Nombre', { required: true })}{textField('last_name', 'Apellido', { required: true })}<VelvetSelect label="Sexo" value={form.data.sex} onChange={set('sex')} options={sexOptions} error={form.errors.sex} /><div className="sm:col-span-2">{textField('email', 'Email', { type: 'email', required: true })}</div><div className="sm:col-span-2"><label className="block text-xs font-medium text-gray-200">WhatsApp<VelvetPhoneInput value={form.data.phone} onChange={(value) => form.setData('phone', value)} error={form.errors.phone} /></label></div>{textField('city', 'Ciudad', { required: true })}{form.data.candidate_type === 'MODEL' && <VelvetDatePicker label="Fecha de nacimiento" value={form.data.birth_date} onChange={set('birth_date')} error={form.errors.birth_date} />}</div>}{step === 2 && <label className="block text-xs font-medium text-gray-200">Cuéntanos brevemente sobre tu experiencia<textarea value={form.data.experience} onChange={set('experience')} rows="7" className="velvet-input" /><span className="mt-2 block text-xs text-gray-500">Puedes dejar este campo vacío.</span></label>}{step === 3 && <div className="grid gap-5 sm:grid-cols-2"><VelvetSelect label="Modalidad preferida" value={form.data.work_mode} onChange={set('work_mode')} options={workModeOptions} /><VelvetSelect label="Disponibilidad horaria" value={form.data.availability} onChange={set('availability')} options={['Tiempo completo', 'Medio tiempo', 'Por horas']} /><div className="sm:col-span-2"><VelvetSelect label="¿Cómo conociste Velvet?" value={form.data.source} onChange={set('source')} options={sources} error={form.errors.source} /></div></div>}{step === 4 && <><div className="velvet-panel divide-y divide-[#292936] rounded-lg">{review.map(([label, value]) => <div key={label} className="flex justify-between gap-6 p-4 text-xs"><span className="text-gray-500">{label}</span><span className="max-w-[70%] text-right text-gray-200">{value || '—'}</span></div>)}</div><div className="mt-6 flex items-start gap-3 text-xs text-gray-400"><input id="data-consent" type="checkbox" checked={form.data.data_consent} onChange={(event) => form.setData('data_consent', event.target.checked)} className="mt-1 rounded border-[#353544] bg-[#11121a] text-[#c23bea] focus:ring-[#c23bea]" /><label htmlFor="data-consent">Confirmo que la información es correcta y acepto la <button type="button" onClick={() => setPrivacyOpen(true)} className="font-medium text-[#e5a1f2] underline decoration-[#c23bea] underline-offset-4 transition hover:text-white">política de tratamiento de datos</button>.</label></div></>}{error && <p className="mt-5 text-xs text-red-400">{error}</p>}<div className="mt-10 flex justify-between"><button type="button" onClick={() => setStep((value) => Math.max(0, value - 1))} disabled={step === 0} className="rounded-lg border border-[#353544] px-5 py-3 text-xs text-gray-300 hover:border-[#c23bea] disabled:invisible">← Volver</button>{step < 4 ? <button type="button" onClick={next} className="velvet-button rounded-lg">Continuar →</button> : <button type="submit" disabled={form.processing} className="velvet-button rounded-lg">{form.processing ? 'Enviando…' : 'Enviar mi solicitud →'}</button>}</div></form></div></section></main><PrivacyPolicyModal open={privacyOpen} onOpenChange={setPrivacyOpen} /></>;
}
