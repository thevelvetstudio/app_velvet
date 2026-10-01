import { useForm } from '@inertiajs/react';
import { FiCheckCircle, FiDownload, FiFileText, FiUpload } from 'react-icons/fi';

export default function InterviewNotesUpload({ candidateId, status, documents = [] }) {
    const form = useForm({ interview_notes: null });
    const latestDocument = [...documents].reverse().find((document) => document.type === 'interview_notes');

    if (!candidateId || (status !== 'INTERVIEW' && !latestDocument)) return null;

    const upload = (event) => {
        event.preventDefault();
        form.post(`/admin/candidates/${candidateId}/interview-notes`, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    };

    return <section className="mt-8 border-t border-[#292d39] pt-7">
        <div className="flex items-start gap-3"><span className="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-[#3b1948] text-[#d56bea]"><FiFileText size={17} /></span><div><p className="text-[10px] uppercase tracking-[.2em] text-[#d56bea]">Notas de entrevista</p><p className="mt-2 text-xs leading-5 text-[#969baa]">Carga un PDF con las anotaciones del candidato. Al subirlo, el proceso pasará automáticamente a evaluación.</p></div></div>
        {status === 'INTERVIEW' && <form onSubmit={upload} className="mt-5 rounded-xl border border-[#343044] bg-[#151522] p-4"><label className="block text-xs text-[#c8c2ce]">Archivo PDF<input type="file" accept="application/pdf,.pdf" onChange={(event) => form.setData('interview_notes', event.target.files?.[0] || null)} className="mt-2 block w-full cursor-pointer rounded-lg border border-[#343044] bg-[#11121a] px-3 py-2 text-xs text-[#aaa5b5] file:mr-3 file:rounded-md file:border-0 file:bg-[#3b1948] file:px-3 file:py-2 file:text-xs file:text-[#e8b1f3]" required />{form.errors.interview_notes && <span className="mt-2 block text-xs text-[#ffb1bd]">{form.errors.interview_notes}</span>}</label><button type="submit" disabled={form.processing || !form.data.interview_notes} className="velvet-button mt-4 w-full gap-2 disabled:cursor-not-allowed disabled:opacity-50"><FiUpload size={15} />{form.processing ? 'Subiendo anotaciones…' : 'Subir PDF y pasar a evaluación'}</button></form>}
        {latestDocument && <div className="mt-4 flex items-center justify-between gap-3 rounded-lg border border-[#2d8669] bg-[#12372e]/40 px-4 py-3 text-xs text-[#9af2cb]"><span className="flex min-w-0 items-center gap-2"><FiCheckCircle size={15} /><span className="truncate">{latestDocument.original_name || 'Anotaciones de entrevista.pdf'}</span></span><span className="flex shrink-0 items-center gap-3"><a href={`/admin/candidates/${candidateId}/interview-notes/preview`} target="_blank" rel="noreferrer" className="font-medium hover:text-white">Ver PDF</a><a href={`/admin/candidates/${candidateId}/interview-notes/download`} className="inline-flex items-center gap-1 font-medium hover:text-white"><FiDownload size={13} />Descargar</a></span></div>}
    </section>;
}
