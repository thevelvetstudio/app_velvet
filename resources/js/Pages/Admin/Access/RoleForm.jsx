import { Head, Link, useForm } from '@inertiajs/react';
import { FiArrowLeft, FiMinus, FiPlus } from 'react-icons/fi';
import Layout from '../Recruitment/Layout';
import { Switch } from '../../../Components/ui/switch';

function PermissionSwitch({ permission, checked, onChange }) {
    return (
        <div className={`flex min-h-[66px] items-center justify-between gap-4 rounded-xl border px-4 py-3 transition duration-200 ${checked ? 'border-[#9e35b8]/80 bg-gradient-to-r from-[#32163e] to-[#211525] shadow-[0_8px_24px_rgba(161,42,190,.12)]' : 'border-[#292d39] bg-[#151722] hover:border-[#5c3967] hover:bg-[#1a1824]'}`}>
            <div className="min-w-0">
                <p className={`truncate text-sm font-medium ${checked ? 'text-white' : 'text-[#d6d3dc]'}`}>{permission.name}</p>
                <p className="mt-1 truncate font-mono text-[10px] tracking-wide text-[#777d8f]">{permission.slug}</p>
            </div>
            <Switch
                checked={checked}
                onCheckedChange={onChange}
                aria-label={`${checked ? 'Desactivar' : 'Activar'} permiso ${permission.name}`}
            />
        </div>
    );
}

function PermissionGroup({ group, selected, onToggle, onToggleAll }) {
    const selectedCount = group.permissions.filter((permission) => selected.includes(permission.id)).length;
    const allSelected = selectedCount === group.permissions.length;

    return (
        <section className="self-start overflow-hidden rounded-2xl border border-[#292d39] bg-[#151722]/90 shadow-[0_12px_34px_rgba(0,0,0,.14)] transition hover:border-[#4b3654]">
            <div className="flex items-start justify-between gap-3 border-b border-[#292d39] bg-[#11131c]/70 px-4 py-4 sm:px-5">
                <div>
                    <div className="flex items-center gap-2">
                        <span className="h-2 w-2 rounded-full bg-[#c23bea] shadow-[0_0_10px_rgba(194,59,234,.65)]" />
                        <h3 className="text-[10px] font-semibold uppercase tracking-[.2em] text-[#d8c4dc]">{group.group}</h3>
                    </div>
                    <p className="mt-1 pl-4 text-[10px] text-[#777d8f]">{selectedCount} de {group.permissions.length} activos</p>
                </div>
                <button
                    type="button"
                    onClick={() => onToggleAll(group.permissions, allSelected)}
                    className="inline-flex shrink-0 items-center gap-1.5 rounded-lg border border-transparent px-2 py-1.5 text-[10px] font-medium text-[#d989e9] transition hover:border-[#6e2c7b] hover:bg-[#32163e] hover:text-white"
                >
                    {allSelected ? <FiMinus size={12} /> : <FiPlus size={12} />}
                    {allSelected ? 'Desactivar todos' : 'Activar todos'}
                </button>
            </div>
            <div className="space-y-2 p-3 sm:p-4">
                {group.permissions.map((permission) => (
                    <PermissionSwitch
                        key={permission.id}
                        permission={permission}
                        checked={selected.includes(permission.id)}
                        onChange={() => onToggle(permission.id)}
                    />
                ))}
            </div>
        </section>
    );
}

export default function RoleForm({ role, roles, permissionGroups }) {
    const form = useForm({
        name: role?.name || '',
        slug: role?.slug || '',
        description: role?.description || '',
        parent_role_id: role?.parent_role_id || '',
        permissions: role?.permissions?.map((permission) => permission.id) || [],
    });

    const submit = (event) => {
        event.preventDefault();
        const options = { preserveScroll: true };
        role ? form.put(`/admin/access/${role.id}`, options) : form.post('/admin/access', options);
    };

    const togglePermission = (id) => form.setData(
        'permissions',
        form.data.permissions.includes(id)
            ? form.data.permissions.filter((permissionId) => permissionId !== id)
            : [...form.data.permissions, id],
    );

    const toggleGroup = (permissions, shouldClear) => {
        const ids = permissions.map((permission) => permission.id);
        form.setData(
            'permissions',
            shouldClear
                ? form.data.permissions.filter((id) => !ids.includes(id))
                : [...new Set([...form.data.permissions, ...ids])],
        );
    };

    return (
        <>
            <Head title={role ? `Editar ${role.name}` : 'Crear rol'} />
            <Layout>
                <div className="mx-auto max-w-[1180px]">
                    <Link href="/admin/access" className="inline-flex items-center gap-2 text-sm text-[#d56bea] hover:text-white">
                        <FiArrowLeft size={15} /> Roles y permisos
                    </Link>

                    <div className="mt-7">
                        <p className="text-[10px] uppercase tracking-[.28em] text-[#d56bea]">Configuración de acceso</p>
                        <h1 className="mt-2 font-editorial text-4xl text-white">{role ? 'Editar rol' : 'Crear rol'}</h1>
                        <p className="mt-2 text-sm text-[#969baa]">Asigna identidad, jerarquía organizacional y permisos a este perfil.</p>
                    </div>

                    <form onSubmit={submit} className="mt-8 space-y-6">
                        <section className="rounded-2xl border border-[#292d39] bg-[#11131c]/90 p-6 shadow-[0_18px_55px_rgba(0,0,0,.12)] sm:p-8">
                            <div className="grid gap-5 sm:grid-cols-2">
                                <label className="text-xs font-medium text-[#e9e5ed]">
                                    Nombre del rol
                                    <input className="velvet-input" value={form.data.name} onChange={(event) => form.setData('name', event.target.value)} placeholder="Ej. Coordinador" required />
                                    {form.errors.name && <span className="mt-2 block text-xs text-red-400">{form.errors.name}</span>}
                                </label>
                                <label className="text-xs font-medium text-[#e9e5ed]">
                                    Identificador
                                    <input className="velvet-input" value={form.data.slug} onChange={(event) => form.setData('slug', event.target.value.toLowerCase().replace(/\s+/g, '-'))} placeholder="coordinador" required />
                                    {form.errors.slug && <span className="mt-2 block text-xs text-red-400">{form.errors.slug}</span>}
                                </label>
                                <label className="text-xs font-medium text-[#e9e5ed]">
                                    Rol superior
                                    <select className="velvet-input velvet-select" value={form.data.parent_role_id} onChange={(event) => form.setData('parent_role_id', event.target.value)}>
                                        <option value="">Sin rol superior</option>
                                        {roles.map((parent) => <option key={parent.id} value={parent.id}>{parent.name}</option>)}
                                    </select>
                                    {form.errors.parent_role_id && <span className="mt-2 block text-xs text-red-400">{form.errors.parent_role_id}</span>}
                                </label>
                                <div className="flex items-end text-xs leading-5 text-[#777d8f]">La jerarquía organiza la estructura, pero no hereda permisos automáticamente.</div>
                                <label className="text-xs font-medium text-[#e9e5ed] sm:col-span-2">
                                    Descripción
                                    <textarea className="velvet-input" rows="3" value={form.data.description} onChange={(event) => form.setData('description', event.target.value)} placeholder="Describe el alcance de este rol." />
                                    {form.errors.description && <span className="mt-2 block text-xs text-red-400">{form.errors.description}</span>}
                                </label>
                            </div>
                        </section>

                        <section className="rounded-2xl border border-[#292d39] bg-[#11131c]/90 p-6 shadow-[0_18px_55px_rgba(0,0,0,.12)] sm:p-8">
                            <div className="flex flex-col justify-between gap-4 border-b border-[#292d39] pb-5 sm:flex-row sm:items-end">
                                <div>
                                    <p className="text-[10px] uppercase tracking-[.22em] text-[#d56bea]">Control de acceso</p>
                                    <h2 className="mt-2 font-editorial text-2xl text-white">Permisos</h2>
                                    <p className="mt-2 text-xs text-[#858a99]">Activa únicamente las capacidades necesarias para este rol.</p>
                                </div>
                                <div className="rounded-xl border border-[#6e2c7b] bg-gradient-to-br from-[#32163e] to-[#1a1421] px-4 py-3 text-right shadow-[0_8px_25px_rgba(161,42,190,.12)]">
                                    <p className="text-2xl font-semibold leading-none text-white">{form.data.permissions.length}</p>
                                    <p className="mt-1 text-[9px] uppercase tracking-[.16em] text-[#d7a5df]">permisos activos</p>
                                </div>
                            </div>
                            <div className="mt-6 grid items-start gap-5 md:grid-cols-2 xl:grid-cols-3">
                                {permissionGroups.map((group) => <PermissionGroup key={group.group} group={group} selected={form.data.permissions} onToggle={togglePermission} onToggleAll={toggleGroup} />)}
                            </div>
                        </section>

                        <div className="flex items-center justify-between gap-4">
                            <Link href="/admin/access" className="rounded-lg border border-[#353846] px-5 py-3 text-sm text-[#c7c4cf] hover:bg-white/5">Cancelar</Link>
                            <button type="submit" disabled={form.processing} className="velvet-button">{form.processing ? 'Guardando…' : role ? 'Guardar cambios →' : 'Crear rol →'}</button>
                        </div>
                    </form>
                </div>
            </Layout>
        </>
    );
}
