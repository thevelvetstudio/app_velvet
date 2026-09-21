import { useEffect } from 'react';
import { FiArrowRight, FiX } from 'react-icons/fi';
import { useForm } from '@inertiajs/react';
import VelvetDatePicker from '@/Components/VelvetDatePicker';
import VelvetPhoneInput from '@/Components/VelvetPhoneInput';
import VelvetSelect from '@/Components/VelvetSelect';
import { colombianCities } from '@/lib/colombianCities';

const sexOptions = [
    { value: 'WOMAN', label: 'Mujer' },
    { value: 'MAN', label: 'Hombre' },
    { value: 'TRANS_WOMAN', label: 'Mujer trans' },
    { value: 'TRANS_MAN', label: 'Hombre trans' },
    { value: 'NON_BINARY', label: 'No binario' },
    { value: 'GENDER_FLUID', label: 'Género fluido' },
    { value: 'AGENDER', label: 'Agénero' },
    { value: 'SELF_DESCRIBE', label: 'Otro / Prefiero autodescribir' },
    { value: 'PREFER_NOT_TO_SAY', label: 'Prefiero no decir' },
];
const availabilityOptions = ['Tiempo completo', 'Medio tiempo', 'Por horas'];
const sourceOptions = ['Instagram', 'TikTok', 'Facebook', 'Google', 'Referido', 'Evento', 'Otro'];

export default function AdminApplicationEditModal({ open, onClose, endpoint, person, candidateType, title }) {
    const form = useForm({
        candidate_type: candidateType || 'MODEL',
        first_name: person.first_name || '',
        last_name: person.last_name || '',
        sex: person.sex || '',
        phone: person.phone || '',
        email: person.email || '',
        city: person.city || 'Manizales',
        birth_date: person.birth_date?.slice(0, 10) || '',
        experience: person.experience || '',
        motivation: person.motivation || '',
        availability: person.availability || 'Tiempo completo',
        work_mode: person.work_mode || (candidateType === 'MONITOR' ? 'En estudio' : 'Desde casa'),
        source: person.source || '',
    });

    useEffect(() => {
        if (form.data.candidate_type === 'MONITOR' && form.data.work_mode !== 'En estudio') {
            form.setData('work_mode', 'En estudio');
        }
    }, [form.data.candidate_type]);

    if (!open) return null;

    const set = (name) => (event) => form.setData(name, event.target.value);
    const workModeOptions = form.data.candidate_type === 'MONITOR' ? ['En estudio'] : ['Desde casa', 'En estudio', 'Híbrido'];
    const textField = (name, label, props = {}) => (
        <label className="apply-field-label">
            {label}
            <input {...props} value={form.data[name]} onChange={set(name)} className={`velvet-input ${form.errors[name] ? 'is-error' : ''}`} />
            {form.errors[name] && <span className="apply-field-error">{form.errors[name]}</span>}
        </label>
    );
    const save = (event) => {
        event.preventDefault();
        form.put(endpoint, { preserveScroll: true, onSuccess: onClose });
    };

    return <div className="fixed inset-0 z-[70] flex items-start justify-center overflow-y-auto bg-[#030408]/80 p-4 backdrop-blur-sm sm:p-6 lg:p-10" role="dialog" aria-modal="true" aria-labelledby="edit-application-title">
        <form onSubmit={save} className="my-auto w-full max-w-5xl rounded-2xl border border-[#593263] bg-[#11121c] p-5 shadow-[0_30px_100px_rgba(0,0,0,.7)] sm:p-7 lg:p-9">
            <div className="flex items-start justify-between gap-5 border-b border-[#292d39] pb-5"><div><p className="text-[10px] uppercase tracking-[.22em] text-[#d56bea]">Edición administrativa</p><h2 id="edit-application-title" className="mt-2 text-xl font-semibold text-[#f7f1fb]">{title || 'Editar información de la aplicación'}</h2><p className="mt-1 text-xs text-[#969baa]">Usa los mismos campos del onboarding. Los cambios quedan registrados en el historial.</p></div><button type="button" onClick={onClose} className="rounded-lg p-2 text-[#9297a7] transition hover:bg-white/10 hover:text-white" aria-label="Cerrar edición"><FiX size={20} /></button></div>
            <div className="mt-7 grid gap-x-5 gap-y-5 sm:grid-cols-2">
                <VelvetSelect label="Tipo de perfil" value={form.data.candidate_type} onChange={set('candidate_type')} options={[{ value: 'MODEL', label: 'Modelo Webcam' }, { value: 'MONITOR', label: 'Monitor(a)' }]} />
                {textField('first_name', 'Nombre', { required: true, autoComplete: 'given-name' })}
                {textField('last_name', 'Apellido', { required: true, autoComplete: 'family-name' })}
                <VelvetSelect label="Sexo" value={form.data.sex} onChange={set('sex')} options={sexOptions} error={form.errors.sex} />
                {textField('email', 'Email', { type: 'email', required: true, autoComplete: 'email' })}
                <label className="apply-field-label">WhatsApp<VelvetPhoneInput value={form.data.phone} onChange={(value) => form.setData('phone', value)} error={form.errors.phone} /></label>
                <VelvetSelect label="Ciudad" value={form.data.city} onChange={set('city')} options={colombianCities} error={form.errors.city} />
                {form.data.candidate_type === 'MODEL' ? <VelvetDatePicker label="Fecha de nacimiento" value={form.data.birth_date} onChange={set('birth_date')} error={form.errors.birth_date} /> : <div />}
                <VelvetSelect label="Modalidad preferida" value={form.data.work_mode} onChange={set('work_mode')} options={workModeOptions} error={form.errors.work_mode} />
                <VelvetSelect label="Disponibilidad horaria" value={form.data.availability} onChange={set('availability')} options={availabilityOptions} error={form.errors.availability} />
                <VelvetSelect label="¿Cómo conociste Velvet?" value={form.data.source} onChange={set('source')} options={sourceOptions} error={form.errors.source} />
                <label className="apply-field-label sm:col-span-2">Experiencia relevante<textarea value={form.data.experience} onChange={set('experience')} rows="5" className={`velvet-input ${form.errors.experience ? 'is-error' : ''}`} placeholder="Cuéntanos sobre tu experiencia relacionada con el perfil." />{form.errors.experience && <span className="apply-field-error">{form.errors.experience}</span>}</label>
                <label className="apply-field-label sm:col-span-2">¿Por qué quieres trabajar con Velvet?<textarea value={form.data.motivation} onChange={set('motivation')} rows="4" className={`velvet-input ${form.errors.motivation ? 'is-error' : ''}`} placeholder="Comparte tus motivaciones y expectativas." />{form.errors.motivation && <span className="apply-field-error">{form.errors.motivation}</span>}</label>
            </div>
            <div className="mt-8 flex flex-col-reverse justify-end gap-3 border-t border-[#292d39] pt-5 sm:flex-row"><button type="button" onClick={onClose} className="rounded-lg border border-[#343044] px-5 py-3 text-sm text-[#c9c2cf] transition hover:border-[#6b5872] hover:text-white">Cancelar</button><button type="submit" disabled={form.processing} className="velvet-button gap-2">{form.processing ? 'Guardando…' : 'Guardar cambios'}<FiArrowRight size={16} /></button></div>
        </form>
    </div>;
}
