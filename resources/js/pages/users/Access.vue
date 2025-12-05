<template>
    <AppLayout>
        <div class="space-y-6">
            <div>
                <h1 class="text-3xl font-bold">
                    Gestão de Acessos - Utilizadores
                </h1>
                <p class="text-gray-500">
                    Gerir utilizadores e atribuir grupos de permissões
                </p>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Formulário -->
                <div class="lg:col-span-1">
                    <div class="rounded-lg bg-white p-6 shadow">
                        <h2 class="mb-4 text-xl font-semibold">
                            {{
                                editingUser
                                    ? 'Editar Utilizador'
                                    : 'Novo Utilizador'
                            }}
                        </h2>

                        <form @submit.prevent="submitForm" class="space-y-4">
                            <!-- Nome -->
                            <div>
                                <Label for="name">Nome *</Label>
                                <Input
                                    id="name"
                                    v-model="form.name"
                                    type="text"
                                    placeholder="Nome completo"
                                    @blur="validateField('name')"
                                />
                                <p
                                    v-if="errors.name"
                                    class="mt-1 text-sm text-red-500"
                                >
                                    {{ errors.name }}
                                </p>
                            </div>

                            <!-- Email -->
                            <div>
                                <Label for="email">Email *</Label>
                                <Input
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    placeholder="email@example.com"
                                    @blur="validateField('email')"
                                />
                                <p
                                    v-if="errors.email"
                                    class="mt-1 text-sm text-red-500"
                                >
                                    {{ errors.email }}
                                </p>
                            </div>

                            <!-- Telemóvel -->
                            <div>
                                <Label for="phone">Telemóvel</Label>
                                <Input
                                    id="phone"
                                    v-model="form.phone"
                                    type="tel"
                                    placeholder="+351 912 345 678"
                                />
                            </div>

                            <!-- Grupo de Permissões -->
                            <div>
                                <Label for="role_id"
                                    >Grupo de Permissões *</Label
                                >
                                <select
                                    id="role_id"
                                    v-model="form.role_id"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                    @blur="validateField('role_id')"
                                >
                                    <option value="">Selecione um grupo</option>
                                    <option
                                        v-for="role in roles"
                                        :key="role.id"
                                        :value="role.id"
                                    >
                                        {{ role.name }}
                                    </option>
                                </select>
                                <p
                                    v-if="errors.role_id"
                                    class="mt-1 text-sm text-red-500"
                                >
                                    {{ errors.role_id }}
                                </p>
                            </div>

                            <!-- Estado -->
                            <div>
                                <Label for="is_active">Estado</Label>
                                <select
                                    id="is_active"
                                    v-model="form.is_active"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                >
                                    <option :value="true">Ativo</option>
                                    <option :value="false">Inativo</option>
                                </select>
                            </div>

                            <!-- Botões -->
                            <div class="flex gap-2 pt-4">
                                <Button
                                    type="submit"
                                    class="flex-1"
                                    :disabled="loading"
                                >
                                    {{
                                        loading
                                            ? 'Guardando...'
                                            : editingUser
                                              ? 'Atualizar'
                                              : 'Criar'
                                    }}
                                </Button>
                                <Button
                                    v-if="editingUser"
                                    type="button"
                                    variant="outline"
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
                    <div class="rounded-lg bg-white p-6 shadow">
                        <h2 class="mb-4 text-xl font-semibold">Utilizadores</h2>

                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="border-b">
                                    <tr>
                                        <th
                                            class="px-4 py-3 text-left font-semibold"
                                        >
                                            Nome
                                        </th>
                                        <th
                                            class="px-4 py-3 text-left font-semibold"
                                        >
                                            Email
                                        </th>
                                        <th
                                            class="px-4 py-3 text-left font-semibold"
                                        >
                                            Telemóvel
                                        </th>
                                        <th
                                            class="px-4 py-3 text-left font-semibold"
                                        >
                                            Grupo
                                        </th>
                                        <th
                                            class="px-4 py-3 text-left font-semibold"
                                        >
                                            Estado
                                        </th>
                                        <th
                                            class="px-4 py-3 text-center font-semibold"
                                        >
                                            Ações
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="user in users"
                                        :key="user.id"
                                        class="border-b hover:bg-gray-800"
                                    >
                                        <td class="px-4 py-3">
                                            {{ user.name }}
                                        </td>
                                        <td class="px-4 py-3">
                                            {{ user.email }}
                                        </td>
                                        <td class="px-4 py-3">
                                            {{ user.phone || '-' }}
                                        </td>
                                        <td class="px-4 py-3">
                                            <span
                                                class="rounded bg-blue-100 px-2 py-1 text-xs font-medium text-blue-800"
                                            >
                                                {{ user.role_name || '-' }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span
                                                :class="[
                                                    'rounded px-2 py-1 text-xs font-medium',
                                                    user.is_active
                                                        ? 'bg-green-100 text-green-800'
                                                        : 'bg-gray-100 text-gray-800',
                                                ]"
                                            >
                                                {{
                                                    user.is_active
                                                        ? 'Ativo'
                                                        : 'Inativo'
                                                }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <div
                                                class="flex justify-center gap-2"
                                            >
                                                <Button
                                                    size="sm"
                                                    variant="ghost"
                                                    class="group-hover:!text-white"
                                                    @click="editUser(user)"
                                                >
                                                    Editar
                                                </Button>
                                                <Button
                                                    size="sm"
                                                    variant="ghost"
                                                    class="text-red-600 hover:text-red-800"
                                                    @click="deleteUser(user)"
                                                >
                                                    Eliminar
                                                </Button>
                                            </div>
                                        </td>
                                    </tr>

                                    <tr v-if="!users.length">
                                        <td
                                            colspan="6"
                                            class="py-6 text-center text-gray-500"
                                        >
                                            Nenhum utilizador encontrado
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import Button from '@/components/ui/button/Button.vue';
import Input from '@/components/ui/input/Input.vue';
import Label from '@/components/ui/label/Label.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import * as userRoutes from '@/routes/users';
import { router, usePage } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';

interface User {
    id: number | string;
    name: string;
    email: string;
    phone?: string;
    role_id?: number;
    role_name?: string;
    is_active: boolean;
}

interface Role {
    id: number | string;
    name: string;
}

const props = defineProps<{
    users: User[];
    roles: Role[];
}>();

const page = usePage();

const users = ref<User[]>(props.users || []);
const roles = ref<Role[]>(props.roles || []);
const editingUser = ref<User | null>(null);
const loading = ref(false);

const form = reactive({
    name: '',
    email: '',
    phone: '',
    role_id: '',
    is_active: true,
});

const errors = reactive({
    name: '',
    email: '',
    role_id: '',
});

const resetForm = () => {
    form.name = '';
    form.email = '';
    form.phone = '';
    form.role_id = '';
    form.is_active = true;
    editingUser.value = null;
    Object.keys(errors).forEach((key) => {
        errors[key as keyof typeof errors] = '';
    });
};

const validateField = (field: string) => {
    switch (field) {
        case 'name':
            errors.name = form.name ? '' : 'O nome é obrigatório';
            break;
        case 'email':
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            errors.email =
                form.email && emailRegex.test(form.email)
                    ? ''
                    : 'Email inválido';
            break;
        case 'role_id':
            errors.role_id = form.role_id
                ? ''
                : 'O grupo de permissões é obrigatório';
            break;
    }
};

const submitForm = () => {
    validateField('name');
    validateField('email');
    validateField('role_id');

    if (errors.name || errors.email || errors.role_id) {
        return;
    }

    loading.value = true;

    if (editingUser.value) {
        router.put(userRoutes.update(editingUser.value), form, {
            onSuccess: () => {
                resetForm();
                loading.value = false;
                setTimeout(() => {
                    router.visit(userRoutes.index().url);
                }, 500);
            },
            onError: () => {
                loading.value = false;
            },
        });
    } else {
        router.post(userRoutes.store(), form, {
            onSuccess: () => {
                resetForm();
                loading.value = false;
                setTimeout(() => {
                    router.visit(userRoutes.index().url);
                }, 500);
            },
            onError: () => {
                loading.value = false;
            },
        });
    }
};

const editUser = (user: User) => {
    editingUser.value = user;
    form.name = user.name;
    form.email = user.email;
    form.phone = user.phone || '';
    form.role_id = user.role_id || '';
    form.is_active = user.is_active;
};

const deleteUser = (user: User) => {
    if (confirm(`Tem a certeza que quer eliminar o utilizador ${user.name}?`)) {
        router.delete(userRoutes.destroy(user), {
            onSuccess: () => {
                users.value = users.value.filter((u) => u.id !== user.id);
            },
        });
    }
};
</script>
