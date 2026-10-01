import { Head, router, useForm } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { FiActivity, FiCalendar, FiCheckCircle, FiClock, FiEdit3, FiLock, FiPlay, FiPlus, FiTool, FiUser, FiX } from 'react-icons/fi';
import Layout from '../Recruitment/Layout';
import VelvetDateTimeField from '@/Components/VelvetDateTimeField';
import { subscribeToRealtime } from '@/lib/ably';

const roomStatuses = [
    { value: 'AVAILABLE', label: 'Disponible' },
    { value: 'MAINTENANCE', label: 'Mantenimiento' },
    { value: 'BLOCKED', label: 'Bloqueada' },
];

const statusLabels = {
    AVAILABLE: 'Disponible',
    RESERVED: 'Reservada',
    IN_USE: 'En uso',
    MAINTENANCE: 'Mantenimiento',
    BLOCKED: 'Bloqueada',
};

const reservationStatusLabels = {
    SCHEDULED: 'Programada',
    IN_USE: 'En uso',
    COMPLETED: 'Finalizada',
    CANCELLED: 'Cancelada',
    NO_SHOW: 'No se presentó',
};

const statusStyles = {
    AVAILABLE: 'border-[#2d8669] bg-[#12372e] text-[#9af2cb]',
    RESERVED: 'border-[#80508c] bg-[#321344] text-[#e5a5f2]',
    IN_USE: 'border-[#b66a2c] bg-[#422718] text-[#ffc58e]',
    MAINTENANCE: 'border-[#755d2a] bg-[#3b3017] text-[#f5d88c]',
    BLOCKED: 'border-[#7d3144] bg-[#321622] text-[#ffb1bd]',
};

const formatDateTime = (value) => value
    ? new Intl.DateTimeFormat('es-CO', { dateStyle: 'medium', timeStyle: 'short', timeZone: 'America/Bogota' }).format(new Date(value))
    : 'Sin horario';

const formatInputDateTime = (value) => {
    if (!value) return '';
    const date = new Date(value);
    const parts = new Intl.DateTimeFormat('en-CA', { timeZone: 'America/Bogota', year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', hourCycle: 'h23' }).formatToParts(date);
    const get = (type) => parts.find((part) => part.type === type)?.value || '';
    return `${get('year')}-${get('month')}-${get('day')}T${get('hour')}:${get('minute')}`;
};

function StatusBadge({ value }) {
    return <span className={`inline-flex rounded-full border px-2.5 py-1 text-[11px] ${statusStyles[value] || 'border-[#343044] text-[#aaa5b5]'}`}>{statusLabels[value] || reservationStatusLabels[value] || value}</span>;
}

function RoomIcon({ status }) {
    if (status === 'IN_USE') return <FiActivity size={18} />;
    if (status === 'MAINTENANCE') return <FiTool size={18} />;
    if (status === 'BLOCKED') return <FiLock size={18} />;
    if (status === 'RESERVED') return <FiCalendar size={18} />;
    return <FiCheckCircle size={18} />;
}

function RoomCard({ room, canManage, onReserve, onEdit, onStatus, onReservationStatus }) {
    const current = room.current;
    const displayName = current?.candidate_name || current?.user_name;
    return <article className="rounded-xl border border-[#292d39] bg-[#11131c]/90 p-5 shadow-[0_18px_55px_rgba(0,0,0,.14)] transition hover:border-[#51365b]">
        <div className="flex items-start justify-between gap-3">
            <div className="flex items-center gap-3">
                <span className={`grid h-10 w-10 place-items-center rounded-xl ${room.effective_status === 'IN_USE' ? 'bg-[#5a321d] text-[#ffc58e]' : 'bg-[#3c1749] text-[#e2a0f1]'}`}><RoomIcon status={room.effective_status} /></span>
                <div><p className="text-[10px] uppercase tracking-[.2em] text-[#d56bea]">{room.code}</p><h2 className="mt-1 text-base font-medium text-white">{room.name}</h2></div>
            </div>
            <StatusBadge value={room.effective_status} />
        </div>
        {room.description && <p className="mt-4 text-xs leading-5 text-[#858a99]">{room.description}</p>}
        {current ? <div className="mt-5 rounded-lg border border-[#6e4326] bg-[#241a16] p-4"><div className="flex items-center justify-between gap-3"><p className="text-[10px] uppercase tracking-[.18em] text-[#d99a62]">Uso actual</p><StatusBadge value={current.status} /></div><p className="mt-2 flex items-center gap-2 text-sm text-[#f2e5db]"><FiUser size={14} />{displayName || 'Responsable interno'}</p><p className="mt-1 text-xs text-[#b89c88]">{current.purpose} · termina {formatDateTime(current.ends_at)}</p><div className="mt-3 flex gap-2">{canManage && current.status === 'SCHEDULED' && <button type="button" onClick={() => onReservationStatus(current.id, 'IN_USE')} className="inline-flex items-center gap-1.5 rounded-md border border-[#9e5d28] px-2.5 py-1.5 text-[11px] text-[#ffc58e] hover:bg-[#50301d]"><FiPlay size={12} />Iniciar</button>}{canManage && current.status === 'IN_USE' && <button type="button" onClick={() => onReservationStatus(current.id, 'COMPLETED')} className="inline-flex items-center gap-1.5 rounded-md border border-[#5c8f78] px-2.5 py-1.5 text-[11px] text-[#a7e9c8] hover:bg-[#163e31]"><FiCheckCircle size={12} />Finalizar</button>}</div></div> : <div className="mt-5 rounded-lg border border-dashed border-[#343044] px-4 py-5 text-center"><p className="text-sm text-[#a6a1b0]">Sin uso en este momento</p><p className="mt-1 text-[11px] text-[#6f7381]">La room está disponible según su configuración.</p></div>}
        {room.upcoming && <div className="mt-4 rounded-lg border border-[#292d39] bg-[#151522] p-3"><p className="text-[10px] uppercase tracking-[.18em] text-[#7f8495]">Próxima reserva</p><p className="mt-2 text-xs text-[#e7e1eb]">{room.upcoming.candidate_name || room.upcoming.user_name || 'Responsable interno'}</p><p className="mt-1 flex items-center gap-1.5 text-[11px] text-[#858a99]"><FiClock size={12} />{formatDateTime(room.upcoming.starts_at)} · {room.upcoming.purpose}</p></div>}
        {canManage && <div className="mt-5 flex flex-wrap justify-end gap-2 border-t border-[#292d39] pt-4"><button type="button" onClick={() => onEdit(room)} className="inline-flex items-center gap-1.5 rounded-md border border-[#343044] px-2.5 py-1.5 text-[11px] text-[#c8c2ce] hover:border-[#80508c] hover:text-white"><FiEdit3 size={12} />Editar</button>{room.effective_status === 'AVAILABLE' && <button type="button" onClick={() => onReserve(room)} className="inline-flex items-center gap-1.5 rounded-md bg-[#a92ad8] px-2.5 py-1.5 text-[11px] font-medium text-white hover:bg-[#c13dea]"><FiCalendar size={12} />Programar uso</button>}{room.status === 'AVAILABLE' ? <button type="button" onClick={() => onStatus(room, 'MAINTENANCE')} className="inline-flex items-center gap-1.5 rounded-md border border-[#755d2a] px-2.5 py-1.5 text-[11px] text-[#f5d88c] hover:bg-[#3b3017]"><FiTool size={12} />Mantenimiento</button> : <button type="button" onClick={() => onStatus(room, 'AVAILABLE')} className="inline-flex items-center gap-1.5 rounded-md border border-[#2d8669] px-2.5 py-1.5 text-[11px] text-[#9af2cb] hover:bg-[#12372e]"><FiCheckCircle size={12} />Habilitar</button>}</div>}
    </article>;
}

export default function Index({ rooms = [], candidates = [], users = [], usageLogs = [], canManage = false }) {
    const [roomModal, setRoomModal] = useState(null);
    const [reservationModal, setReservationModal] = useState(null);
    const roomForm = useForm({ code: '', name: '', description: '', status: 'AVAILABLE', is_active: true });
    const reservationForm = useForm({ room_id: '', candidate_id: '', user_id: '', purpose: 'Uso de room', starts_at: '', ends_at: '', notes: '' });

    const reload = () => router.reload({ only: ['rooms', 'usageLogs'], preserveScroll: true });
    useEffect(() => subscribeToRealtime((_data, eventName) => { if (eventName?.startsWith('room.')) reload(); }), []);

    const openNewRoom = () => { roomForm.reset(); roomForm.setData({ code: '', name: '', description: '', status: 'AVAILABLE', is_active: true }); setRoomModal({ mode: 'create' }); };
    const openEditRoom = (room) => { roomForm.setData({ code: room.code, name: room.name, description: room.description || '', status: room.status, is_active: room.is_active }); setRoomModal({ mode: 'edit', room }); };
    const saveRoom = (event) => { event.preventDefault(); roomForm[roomModal.mode === 'edit' ? 'patch' : 'post'](roomModal.mode === 'edit' ? `/admin/rooms/${roomModal.room.id}` : '/admin/rooms', { preserveScroll: true, onSuccess: () => { setRoomModal(null); roomForm.reset(); } }); };
    const openReservation = (room) => { reservationForm.reset(); reservationForm.setData({ room_id: room.id, candidate_id: '', user_id: '', purpose: 'Uso de room', starts_at: '', ends_at: '', notes: '' }); setReservationModal(room); };
    const saveReservation = (event) => { event.preventDefault(); reservationForm.post('/admin/rooms/reservations', { preserveScroll: true, onSuccess: () => { setReservationModal(null); reservationForm.reset(); } }); };
    const updateReservationStatus = (id, status) => router.patch(`/admin/rooms/reservations/${id}/status`, { status }, { preserveScroll: true });
    const updateRoomStatus = (room, status) => router.patch(`/admin/rooms/${room.id}/status`, { status }, { preserveScroll: true });

    const stats = { available: rooms.filter((room) => room.effective_status === 'AVAILABLE').length, reserved: rooms.filter((room) => room.effective_status === 'RESERVED').length, inUse: rooms.filter((room) => room.effective_status === 'IN_USE').length, maintenance: rooms.filter((room) => ['MAINTENANCE', 'BLOCKED'].includes(room.effective_status)).length };

    return <><Head title="Rooms" /><Layout><div className="mx-auto max-w-[1440px]"><div className="flex flex-col justify-between gap-5 md:flex-row md:items-end"><div><p className="text-[10px] uppercase tracking-[.28em] text-[#d56bea]">Operación</p><h1 className="mt-3 font-editorial text-4xl text-white sm:text-5xl">Rooms</h1><p className="mt-2 max-w-2xl text-sm text-[#969baa]">Consulta disponibilidad, programa usos y registra en tiempo real quién está utilizando cada habitación.</p></div>{canManage && <button type="button" onClick={openNewRoom} className="velvet-button gap-2"><FiPlus />Nueva room</button>}</div>
        <section className="mt-8 grid gap-3 sm:grid-cols-2 xl:grid-cols-4"><div className="rounded-xl border border-[#292d39] bg-[#11131c]/90 p-5"><FiCheckCircle className="text-[#78deb3]" /><p className="mt-4 text-2xl text-white">{stats.available}</p><p className="mt-1 text-xs text-[#858a99]">Disponibles</p></div><div className="rounded-xl border border-[#292d39] bg-[#11131c]/90 p-5"><FiCalendar className="text-[#dca0eb]" /><p className="mt-4 text-2xl text-white">{stats.reserved}</p><p className="mt-1 text-xs text-[#858a99]">Reservadas</p></div><div className="rounded-xl border border-[#292d39] bg-[#11131c]/90 p-5"><FiActivity className="text-[#ffc58e]" /><p className="mt-4 text-2xl text-white">{stats.inUse}</p><p className="mt-1 text-xs text-[#858a99]">En uso ahora</p></div><div className="rounded-xl border border-[#292d39] bg-[#11131c]/90 p-5"><FiTool className="text-[#f5d88c]" /><p className="mt-4 text-2xl text-white">{stats.maintenance}</p><p className="mt-1 text-xs text-[#858a99]">Mantenimiento / bloqueadas</p></div></section>
        <div className="mt-7 flex items-center gap-2 text-xs text-[#65e6ad]"><span className="h-2 w-2 rounded-full bg-[#65e6ad] shadow-[0_0_10px_#65e6ad]" />Estados sincronizados en tiempo real</div>
        <section className="mt-4 grid gap-5 lg:grid-cols-2 xl:grid-cols-3">{rooms.length ? rooms.map((room) => <RoomCard key={room.id} room={room} canManage={canManage} onReserve={openReservation} onEdit={openEditRoom} onStatus={updateRoomStatus} onReservationStatus={updateReservationStatus} />) : <div className="rounded-xl border border-dashed border-[#343044] px-5 py-20 text-center text-sm text-[#777d8f] lg:col-span-2 xl:col-span-3">Todavía no hay rooms configuradas.</div>}</section>
        <section className="mt-7 rounded-xl border border-[#292d39] bg-[#11131c]/90 p-6"><div className="flex items-center gap-3"><span className="grid h-9 w-9 place-items-center rounded-full bg-[#3c1749] text-[#e2a0f1]"><FiActivity /></span><div><h2 className="text-sm font-medium text-white">Historial de uso</h2><p className="mt-1 text-xs text-[#7f8495]">Registro de cambios de disponibilidad y utilización.</p></div></div><div className="mt-5 overflow-x-auto"><table className="w-full min-w-[720px] text-left text-xs"><thead className="border-b border-[#292d39] text-[10px] uppercase tracking-[.16em] text-[#7f8495]"><tr><th className="pb-3">Room</th><th className="pb-3">Cambio</th><th className="pb-3">Responsable</th><th className="pb-3">Fecha</th><th className="pb-3">Nota</th></tr></thead><tbody className="divide-y divide-[#292d39]">{usageLogs.length ? usageLogs.map((log) => <tr key={log.id}><td className="py-3 text-[#eee8f2]">{log.room}</td><td className="py-3"><span className="text-[#858a99]">{statusLabels[log.from_status] || reservationStatusLabels[log.from_status] || '—'}</span><span className="mx-2 text-[#d56bea]">→</span><span className="text-[#e5a5f2]">{statusLabels[log.to_status] || reservationStatusLabels[log.to_status] || log.to_status}</span></td><td className="py-3 text-[#b8b3c0]">{log.changed_by || 'Sistema'}</td><td className="py-3 text-[#858a99]">{formatDateTime(log.created_at)}</td><td className="py-3 text-[#858a99]">{log.notes || '—'}</td></tr>) : <tr><td colSpan="5" className="py-8 text-center text-[#777d8f]">Aún no hay actividad registrada.</td></tr>}</tbody></table></div></section>
    </div></Layout>
    {roomModal && <div className="fixed inset-0 z-[80] grid place-items-center bg-black/75 p-4 backdrop-blur-sm" role="presentation" onMouseDown={(event) => event.target === event.currentTarget && setRoomModal(null)}><section role="dialog" aria-modal="true" className="w-full max-w-lg rounded-2xl border border-[#4c3157] bg-[#11121a] p-6 shadow-[0_25px_80px_rgba(0,0,0,.6)]"><div className="flex items-start justify-between gap-4"><div><p className="text-[10px] uppercase tracking-[.25em] text-[#d56bea]">Configuración</p><h2 className="mt-2 font-editorial text-3xl text-white">{roomModal.mode === 'edit' ? 'Editar room' : 'Nueva room'}</h2></div><button type="button" onClick={() => setRoomModal(null)} className="rounded-lg p-2 text-[#858a99] hover:bg-[#25152e] hover:text-white"><FiX /></button></div><form onSubmit={saveRoom} className="mt-6 space-y-4"><label className="block text-xs text-[#c8c2ce]">Código<input value={roomForm.data.code} onChange={(event) => roomForm.setData('code', event.target.value)} className="velvet-input" placeholder="ROOM-01" required />{roomForm.errors.code && <span className="mt-1 block text-xs text-[#ffb1bd]">{roomForm.errors.code}</span>}</label><label className="block text-xs text-[#c8c2ce]">Nombre<input value={roomForm.data.name} onChange={(event) => roomForm.setData('name', event.target.value)} className="velvet-input" placeholder="Room Principal" required />{roomForm.errors.name && <span className="mt-1 block text-xs text-[#ffb1bd]">{roomForm.errors.name}</span>}</label><label className="block text-xs text-[#c8c2ce]">Descripción<textarea value={roomForm.data.description ?? ''} onChange={(event) => roomForm.setData('description', event.target.value)} className="velvet-input" rows="3" placeholder="Características o restricciones de la room." /></label><label className="block text-xs text-[#c8c2ce]">Estado<select value={roomForm.data.status} onChange={(event) => roomForm.setData('status', event.target.value)} className="velvet-input">{roomStatuses.map((status) => <option key={status.value} value={status.value}>{status.label}</option>)}</select></label><div className="flex justify-end gap-3 border-t border-[#292d39] pt-5"><button type="button" onClick={() => setRoomModal(null)} className="rounded-lg border border-[#343044] px-4 py-2.5 text-xs text-[#c8c2ce]">Cancelar</button><button type="submit" disabled={roomForm.processing} className="velvet-button">{roomForm.processing ? 'Guardando…' : 'Guardar room'}</button></div></form></section></div>}
    {reservationModal && <div className="fixed inset-0 z-[80] grid place-items-center bg-black/75 p-4 backdrop-blur-sm" role="presentation" onMouseDown={(event) => event.target === event.currentTarget && setReservationModal(null)}><section role="dialog" aria-modal="true" className="max-h-[92vh] w-full max-w-2xl overflow-y-auto rounded-2xl border border-[#4c3157] bg-[#11121a] p-6 shadow-[0_25px_80px_rgba(0,0,0,.6)]"><div className="flex items-start justify-between gap-4"><div><p className="text-[10px] uppercase tracking-[.25em] text-[#d56bea]">Programar uso</p><h2 className="mt-2 font-editorial text-3xl text-white">{reservationModal.name}</h2><p className="mt-1 text-xs text-[#858a99]">{reservationModal.code}</p></div><button type="button" onClick={() => setReservationModal(null)} className="rounded-lg p-2 text-[#858a99] hover:bg-[#25152e] hover:text-white"><FiX /></button></div><form onSubmit={saveReservation} className="mt-6 grid gap-4 sm:grid-cols-2"><label className="block text-xs text-[#c8c2ce] sm:col-span-2">Quién utilizará la room<select required value={reservationForm.data.candidate_id ? 'candidate:' + reservationForm.data.candidate_id : reservationForm.data.user_id ? 'user:' + reservationForm.data.user_id : ''} onChange={(event) => { const [type, id] = event.target.value.split(':'); reservationForm.setData({ ...reservationForm.data, candidate_id: type === 'candidate' ? id : '', user_id: type === 'user' ? id : '' }); }} className="velvet-input"><option value="">Selecciona una persona</option><optgroup label={'Modelos (' + candidates.length + ')'}>{candidates.map((candidate) => <option key={'candidate-' + candidate.id} value={'candidate:' + candidate.id}>{candidate.name} · {candidate.code}</option>)}</optgroup>{users.length > 0 && <optgroup label={'Usuarios operativos (' + users.length + ')'}>{users.map((user) => <option key={'user-' + user.id} value={'user:' + user.id}>{user.name}</option>)}</optgroup>}</select>{reservationForm.errors.candidate_id && <span className="mt-1 block text-xs text-[#ffb1bd]">{reservationForm.errors.candidate_id}</span>}{reservationForm.errors.user_id && <span className="mt-1 block text-xs text-[#ffb1bd]">{reservationForm.errors.user_id}</span>}</label><label className="block text-xs text-[#c8c2ce] sm:col-span-2">Propósito<input value={reservationForm.data.purpose} onChange={(event) => reservationForm.setData('purpose', event.target.value)} className="velvet-input" placeholder="Sesión de estudio" required /></label><VelvetDateTimeField label="Inicio" value={reservationForm.data.starts_at} onChange={(event) => reservationForm.setData('starts_at', event.target.value)} error={reservationForm.errors.starts_at} /><VelvetDateTimeField label="Fin" value={reservationForm.data.ends_at} onChange={(event) => reservationForm.setData('ends_at', event.target.value)} error={reservationForm.errors.ends_at} /><label className="block text-xs text-[#c8c2ce] sm:col-span-2">Notas<textarea value={reservationForm.data.notes ?? ''} onChange={(event) => reservationForm.setData('notes', event.target.value)} className="velvet-input" rows="3" placeholder="Observaciones operativas." /></label><div className="flex justify-end gap-3 border-t border-[#292d39] pt-5 sm:col-span-2"><button type="button" onClick={() => setReservationModal(null)} className="rounded-lg border border-[#343044] px-4 py-2.5 text-xs text-[#c8c2ce]">Cancelar</button><button type="submit" disabled={reservationForm.processing} className="velvet-button">{reservationForm.processing ? 'Guardando…' : 'Programar uso'}</button></div></form></section></div>}
    </>;
}
