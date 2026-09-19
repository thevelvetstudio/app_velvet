import InputError from '@/Components/InputError';
import { Transition } from '@headlessui/react';
import { useForm } from '@inertiajs/react';
import { useRef } from 'react';

export default function UpdatePasswordForm() {
    const passwordInput = useRef();
    const currentPasswordInput = useRef();
    const { data, setData, errors, put, reset, processing, recentlySuccessful } = useForm({ current_password: '', password: '', password_confirmation: '' });

    const updatePassword = (event) => {
        event.preventDefault();
        put(route('password.update'), { preserveScroll: true, onSuccess: () => reset(), onError: (formErrors) => { if (formErrors.password) { reset('password', 'password_confirmation'); passwordInput.current?.focus(); } if (formErrors.current_password) { reset('current_password'); currentPasswordInput.current?.focus(); } } });
    };

    return <div>
        <header><p className="text-[10px] uppercase tracking-[.22em] text-[#d56bea]">Seguridad</p><h2 className="mt-2 font-editorial text-2xl text-white">Cambiar contraseña</h2><p className="mt-2 text-sm leading-6 text-[#969baa]">Mantén segura tu cuenta usando una contraseña única y robusta.</p></header>
        <form onSubmit={updatePassword} className="mt-7 space-y-5">
            <div><label htmlFor="current_password" className="text-xs font-medium text-[#e9e5ed]">Contraseña actual</label><input id="current_password" ref={currentPasswordInput} type="password" className="velvet-input" value={data.current_password} onChange={(event) => setData('current_password', event.target.value)} autoComplete="current-password" /><InputError message={errors.current_password} className="mt-2" /></div>
            <div><label htmlFor="password" className="text-xs font-medium text-[#e9e5ed]">Nueva contraseña</label><input id="password" ref={passwordInput} type="password" className="velvet-input" value={data.password} onChange={(event) => setData('password', event.target.value)} autoComplete="new-password" /><InputError message={errors.password} className="mt-2" /></div>
            <div><label htmlFor="password_confirmation" className="text-xs font-medium text-[#e9e5ed]">Confirmar contraseña</label><input id="password_confirmation" type="password" className="velvet-input" value={data.password_confirmation} onChange={(event) => setData('password_confirmation', event.target.value)} autoComplete="new-password" /><InputError message={errors.password_confirmation} className="mt-2" /></div>
            <div className="flex items-center gap-4 pt-2"><button type="submit" disabled={processing} className="velvet-button">{processing ? 'Actualizando…' : 'Actualizar contraseña'}</button><Transition show={recentlySuccessful} enter="transition ease-out duration-200" enterFrom="opacity-0" leave="transition ease-in duration-200" leaveTo="opacity-0"><p className="text-xs text-[#8ff0bd]">Contraseña actualizada.</p></Transition></div>
        </form>
    </div>;
}
