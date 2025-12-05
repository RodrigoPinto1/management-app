<template>
    <AppLayout>
        <Head title="Gestão de Acessos - Permissões" />

        <div class="px-4 py-6">
            <Heading
                title="Gestão de Acessos - Permissões"
                description="Gerir grupos de permissões e seus acessos"
            />

            <div class="mt-8 grid gap-8 lg:grid-cols-3">
                <!-- Formulário -->
                <div class="lg:col-span-1">
                    <div class="rounded-lg border p-6">
                        <h3 class="mb-4 text-lg font-medium">
                            {{ editingRole ? 'Editar Grupo' : 'Novo Grupo' }}
                        </h3>

                        <form @submit.prevent="submitForm" class="space-y-4">
                            <!-- Nome do Grupo -->
                            <div>
                                <Label>Nome do Grupo</Label>
                                <Input
                                    v-model="form.name"
                                    placeholder="ex: Gerente, Operador"
                                    class="mt-2"
                                />
                                <div
                                    v-if="form.errors.name"
                                    class="mt-1 text-sm text-red-600"
                                >
                                    {{ form.errors.name }}
                                </div>
                            </div>

                            <!-- Menus e Permissões -->
                            <div class="space-y-4">
                                <Label>Permissões por Menu</Label>

                                <div
                                    v-for="menu in availableMenus"
                                    :key="menu.key"
                                    class="space-y-2 rounded-md border p-3"
                                >
                                    <h4 class="text-sm font-medium">
                                        {{ menu.label }}
                                    </h4>
                                    <div class="grid grid-cols-2 gap-2">
                                        <label class="flex items-center gap-2">
                                            <input
                                                type="checkbox"
                                                :checked="
                                                    hasPermission(
                                                        menu.key,
                                                        'create',
                                                    )
                                                "
                                                @change="
                                                    togglePermission(
                                                        menu.key,
                                                        'create',
                                                    )
                                                "
                                                class="h-4 w-4 rounded border"
                                            />
                                            <span class="text-sm">Criar</span>
                                        </label>
                                        <label class="flex items-center gap-2">
                                            <input
                                                type="checkbox"
                                                :checked="
                                                    hasPermission(
                                                        menu.key,
                                                        'read',
                                                    )
                                                "
                                                @change="
                                                    togglePermission(
                                                        menu.key,
                                                        'read',
                                                    )
                                                "
                                                class="h-4 w-4 rounded border"
                                            />
                                            <span class="text-sm">Ver</span>
                                        </label>
                                        <label class="flex items-center gap-2">
                                            <input
                                                type="checkbox"
                                                :checked="
                                                    hasPermission(
                                                        menu.key,
                                                        'update',
                                                    )
                                                "
                                                @change="
                                                    togglePermission(
                                                        menu.key,
                                                        'update',
                                                    )
                                                "
                                                class="h-4 w-4 rounded border"
                                            />
                                            <span class="text-sm">Editar</span>
                                        </label>
                                        <label class="flex items-center gap-2">
                                            <input
                                                type="checkbox"
                                                :checked="
                                                    hasPermission(
                                                        menu.key,
                                                        'delete',
                                                    )
                                                "
                                                @change="
                                                    togglePermission(
                                                        menu.key,
                                                        'delete',
                                                    )
                                                "
                                                class="h-4 w-4 rounded border"
                                            />
                                            <span class="text-sm"
                                                >Eliminar</span
                                            >
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Status -->
                            <div>
                                <Label>Estado</Label>
                                <select
                                    v-model="form.is_active"
                                    class="mt-2 block w-full rounded-md border border-gray-300 px-3 py-2"
                                >
                                    <option :value="true">Ativo</option>
                                    <option :value="false">Inativo</option>
                                </select>
                            </div>

                            <!-- Botões -->
                            <div class="flex gap-2">
                                <Button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="flex-1"
                                >
                                    {{ editingRole ? 'Atualizar' : 'Criar' }}
                                </Button>
                                <Button
                                    v-if="editingRole"
                                    type="button"
                                    variant="ghost"
                                    @click="resetForm"
                                >
                                    Cancelar
                                </Button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Tabela -->
                <div class="lg:col-span-2">
                    <div class="rounded-lg border">
                        <div class="overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Nome do Grupo</TableHead>
                                        <TableHead>Utilizadores</TableHead>
                                        <TableHead>Estado</TableHead>
                                        <TableHead>Ações</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow
                                        v-if="!roles || roles.length === 0"
                                    >
                                        <TableCell
                                            colspan="4"
                                            class="h-24 text-center"
                                        >
                                            Sem grupos de permissões.
                                        </TableCell>
                                    </TableRow>
                                    <TableRow
                                        v-for="role in roles"
                                        :key="role.id"
                                    >
                                        <TableCell class="font-medium">
                                            {{ role.name }}
                                        </TableCell>
                                        <TableCell>
                                            {{ role.users_count }}
                                        </TableCell>
                                        <TableCell>
                                            <span
                                                class="inline-block rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-800"
                                            >
                                                Ativo
                                            </span>
                                        </TableCell>
                                        <TableCell>
                                            <div class="flex gap-2">
                                                <Button
                                                    size="sm"
                                                    variant="outline"
                                                    @click="editRole(role)"
                                                >
                                                    Editar
                                                </Button>
                                                <Button
                                                    size="sm"
                                                    variant="destructive"
                                                    @click="deleteRole(role.id)"
                                                >
                                                    Eliminar
                                                </Button>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { index } from '@/routes/permissions';

interface Role {
    id: number;
    name: string;
    users_count: number;
    is_active: boolean;
    permissions: Array<{
        menu: string;
        can_create: boolean;
        can_read: boolean;
        can_update: boolean;
        can_delete: boolean;
    }>;
}

interface Props {
    roles: Role[];
    menus: Record<string, string>;
}

const props = defineProps<Props>();

const form = useForm({
    name: '',
    is_active: true,
    permissions: {} as Record<string, string[]>,
});

const editingRole = ref<Role | null>(null);

const availableMenus = [
    { key: 'menu-management', label: 'Gestão' },
    { key: 'menu-sales', label: 'Vendas' },
    { key: 'menu-calendar', label: 'Calendário' },
    { key: 'menu-settings', label: 'Configurações' },
    { key: 'menu-finance', label: 'Financeiro' },
];

function hasPermission(menu: string, action: string): boolean {
    return form.permissions[menu]?.includes(action) ?? false;
}

function togglePermission(menu: string, action: string) {
    if (!form.permissions[menu]) {
        form.permissions[menu] = [];
    }

    const index = form.permissions[menu].indexOf(action);
    if (index > -1) {
        form.permissions[menu].splice(index, 1);
    } else {
        form.permissions[menu].push(action);
    }
}

function submitForm() {
    if (editingRole.value) {
        form.put(`/permissions/${editingRole.value.id}`, {
            onSuccess: () => {
                resetForm();
                router.reload({ only: ['roles'] });
            },
        });
    } else {
        form.post(index.url(), {
            onSuccess: () => {
                resetForm();
                router.reload({ only: ['roles'] });
            },
        });
    }
}

function editRole(role: Role) {
    editingRole.value = role;
    form.name = role.name;
    form.is_active = role.is_active;

    // Populate permissions
    form.permissions = {};
    role.permissions.forEach((perm) => {
        const menu = perm.menu;
        if (!form.permissions[menu]) {
            form.permissions[menu] = [];
        }
        if (perm.can_create) form.permissions[menu].push('create');
        if (perm.can_read) form.permissions[menu].push('read');
        if (perm.can_update) form.permissions[menu].push('update');
        if (perm.can_delete) form.permissions[menu].push('delete');
    });
}

function resetForm() {
    form.reset();
    editingRole.value = null;
    form.permissions = {};
}

function deleteRole(roleId: number) {
    if (confirm('Tem a certeza que quer eliminar este grupo?')) {
        router.delete(`/permissions/${roleId}`, {
            onSuccess: () => {
                router.reload({ only: ['roles'] });
            },
        });
    }
}
</script>
