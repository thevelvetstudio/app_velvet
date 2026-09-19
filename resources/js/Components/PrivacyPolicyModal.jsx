import * as Dialog from '@radix-ui/react-dialog';
import { X } from 'lucide-react';

export default function PrivacyPolicyModal({ open, onOpenChange }) {
    return (
        <Dialog.Root open={open} onOpenChange={onOpenChange}>
            <Dialog.Portal>
                <Dialog.Overlay className="fixed inset-0 z-50 bg-black/75 backdrop-blur-sm" />
                <Dialog.Content className="velvet-privacy-modal fixed left-1/2 top-1/2 z-50 max-h-[86vh] w-[calc(100%-2rem)] max-w-2xl -translate-x-1/2 -translate-y-1/2 overflow-hidden rounded-2xl border border-[#4a3154] bg-[#11121a] shadow-[0_24px_90px_rgba(0,0,0,.65)]">
                    <div className="flex items-start justify-between gap-6 border-b border-[#292936] px-6 py-5 sm:px-8">
                        <div>
                            <Dialog.Title className="font-editorial text-2xl text-white sm:text-3xl">Política de privacidad</Dialog.Title>
                            <Dialog.Description className="mt-2 text-xs leading-5 text-gray-400">Cómo usamos y protegemos la información que compartes con The Velvet Studio.</Dialog.Description>
                        </div>
                        <Dialog.Close asChild>
                            <button type="button" aria-label="Cerrar política de privacidad" className="grid h-9 w-9 shrink-0 place-items-center rounded-lg border border-[#353544] text-gray-400 transition hover:border-[#c23bea] hover:text-white"><X size={18} /></button>
                        </Dialog.Close>
                    </div>
                    <div className="max-h-[58vh] space-y-6 overflow-y-auto px-6 py-6 text-sm leading-6 text-gray-300 sm:px-8">
                        <section><h2 className="font-semibold text-[#e5a1f2]">1. Responsable</h2><p className="mt-2">The Velvet Studio será responsable del tratamiento de los datos que recibamos a través de este formulario. Para solicitudes relacionadas con privacidad puedes escribir a <a className="text-[#e5a1f2] underline" href="mailto:privacidad@thevelvetstudio.com">privacidad@thevelvetstudio.com</a>.</p></section>
                        <section><h2 className="font-semibold text-[#e5a1f2]">2. Información que recopilamos</h2><p className="mt-2">En esta etapa solicitamos únicamente datos básicos de contacto y aplicación: nombre, apellido, sexo, correo, WhatsApp, ciudad, fecha de nacimiento cuando aplique, experiencia, disponibilidad, modalidad preferida y la fuente por la que conociste Velvet.</p></section>
                        <section><h2 className="font-semibold text-[#e5a1f2]">3. Para qué usamos tus datos</h2><ul className="mt-2 list-disc space-y-1 pl-5"><li>Contactarte y dar seguimiento a tu aplicación.</li><li>Estudiar tu perfil para procesos de selección y vinculación.</li><li>Coordinar entrevistas y comunicaciones relacionadas con el proceso.</li><li>Proteger la seguridad, prevenir fraude y mantener registros internos del proceso.</li></ul></section>
                        <section><h2 className="font-semibold text-[#e5a1f2]">4. Conservación y seguridad</h2><p className="mt-2">Aplicamos medidas razonables de seguridad y restringimos el acceso a las personas que necesitan la información para gestionar el proceso. Conservaremos los datos durante el tiempo necesario para cumplir estas finalidades o mientras exista una obligación legal.</p></section>
                        <section><h2 className="font-semibold text-[#e5a1f2]">5. Tus derechos</h2><p className="mt-2">Puedes solicitar conocer, actualizar, rectificar o eliminar tus datos, así como retirar la autorización cuando sea procedente. También puedes presentar consultas o reclamos relacionados con el tratamiento de tu información a través del canal de privacidad indicado.</p></section>
                        <section><h2 className="font-semibold text-[#e5a1f2]">6. Autorización</h2><p className="mt-2">Al marcar la casilla autorizas de forma previa, expresa e informada el tratamiento de tus datos para las finalidades descritas. La entrega de información es voluntaria; sin embargo, algunos datos son necesarios para estudiar y responder tu aplicación.</p></section>
                        <p className="border-t border-[#292936] pt-5 text-xs text-gray-500">Texto base para el producto. Antes de publicar, valida la identificación legal del responsable, el correo de privacidad, los tiempos de conservación y los canales formales con asesoría legal.</p>
                    </div>
                    <div className="flex justify-end border-t border-[#292936] px-6 py-4 sm:px-8"><Dialog.Close asChild><button type="button" className="velvet-button">Entendido</button></Dialog.Close></div>
                </Dialog.Content>
            </Dialog.Portal>
        </Dialog.Root>
    );
}
