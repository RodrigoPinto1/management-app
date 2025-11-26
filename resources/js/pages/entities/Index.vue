<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

interface EntityRow {
    id: number;
    number?: number;
    nif?: string;
    name: string;
    phone?: string;
    mobile?: string;
    website?: string;
    email?: string;
}

const props = defineProps<{ type: string }>();

const type = ref(props.type || 'client');
const rows = ref<EntityRow[]>([]);
const loading = ref(false);

const form = useForm({
    is_client: type.value === 'client',
    is_supplier: type.value === 'supplier',
    nif: '',
    name: '',
    address: '',
    postal_code: '',
    city: '',
    country_id: null as number | null,
    phone: '',
    mobile: '',
    website: '',
    email: '',
    rgpd_consent: false,
    notes: '',
    is_active: true,
});

const showCreate = ref(false);
const isSubmitting = ref(false);

function getXsrfTokenFromCookie() {
    try {
        const m = document.cookie.match('(?:^|; )XSRF-TOKEN=([^;]+)');
        return m ? decodeURIComponent(m[1]) : null;
    } catch (e) {
        return null;
    }
}

async function load() {
    loading.value = true;
    try {
        const res = await fetch(
            `/entities/list?type=${encodeURIComponent(type.value)}`,
            {
                credentials: 'same-origin',
                headers: { Accept: 'application/json' },
            },
        );
        if (res.ok) {
            const json = await res.json();
            rows.value = json.data || [];
        } else {
            console.error('Failed to load entities', res.status);
        }
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
}

async function checkNif(nif: string) {
    try {
        const res = await fetch(
            `/entities/check-nif?nif=${encodeURIComponent(nif)}`,
            {
                credentials: 'same-origin',
                headers: { Accept: 'application/json' },
            },
        );
        if (!res.ok) return false;
        const json = await res.json();
        return json.exists === true;
    } catch (e) {
        console.error(e);
        return false;
    }
}

async function submit() {
    isSubmitting.value = true;

    // Basic client-side NIF existence check
    if (form.nif) {
        const exists = await checkNif(form.nif);
        if (exists) {
            (form as any).setError('nif', 'NIF já existe.');
            isSubmitting.value = false;
            return;
        }
    }

    // Use Inertia form submission which handles CSRF and validation nicely
    form.post('/entities', {
        onSuccess: async () => {
            showCreate.value = false;
            await load();
        },
        onError: (errors) => {
            // Inertia populates `form.errors` automatically; log for debugging
            console.error('Validation errors', errors);
        },
        onFinish: () => {
            isSubmitting.value = false;
        },
    });
}

onMounted(load);
</script>

<template>
    <AppLayout>
        <Head title="Entidades" />

        <div class="p-6">
            <div class="mb-4 flex items-center justify-between">
                <h1 class="text-2xl font-semibold">
                    {{ type === 'client' ? 'Clientes' : 'Fornecedores' }}
                </h1>
                <div>
                    <Button
                        @click="
                            () => {
                                showCreate = true;
                            }
                        "
                        >Novo</Button
                    >
                </div>
            </div>

            <div class="overflow-x-auto rounded bg-card shadow-sm">
                <table class="w-full table-auto">
                    <thead>
                        <tr class="text-left">
                            <th class="p-3">NIF</th>
                            <th class="p-3">Nome</th>
                            <th class="p-3">Telefone</th>
                            <th class="p-3">Telemóvel</th>
                            <th class="p-3">Website</th>
                            <th class="p-3">Email</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="loading">
                            <td colspan="6" class="p-4">A carregar...</td>
                        </tr>
                        <tr v-for="row in rows" :key="row.id" class="border-t">
                            <td class="p-3">{{ row.nif }}</td>
                            <td class="p-3">{{ row.name }}</td>
                            <td class="p-3">{{ row.phone }}</td>
                            <td class="p-3">{{ row.mobile }}</td>
                            <td class="p-3">{{ row.website }}</td>
                            <td class="p-3">{{ row.email }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Simple create modal -->
            <div
                v-if="showCreate"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
            >
                <div class="w-[720px] rounded bg-card p-6 shadow-lg">
                    <h2 class="mb-4 text-xl">Nova Entidade</h2>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <Label for="nif">NIF</Label>
                            <Input id="nif" v-model="form.nif" />
                            <p
                                v-if="form.errors?.nif"
                                class="mt-1 text-sm text-destructive"
                            >
                                {{ form.errors.nif }}
                            </p>
                        </div>
                        <div>
                            <Label for="name">Nome</Label>
                            <Input id="name" v-model="form.name" />
                            <p
                                v-if="form.errors?.name"
                                class="mt-1 text-sm text-destructive"
                            >
                                {{ form.errors.name }}
                            </p>
                        </div>

                        <div>
                            <Label for="phone">Telefone</Label>
                            <Input id="phone" v-model="form.phone" />
                            <p
                                v-if="form.errors?.phone"
                                class="mt-1 text-sm text-destructive"
                            >
                                {{ form.errors.phone }}
                            </p>
                        </div>
                        <div>
                            <Label for="mobile">Telemóvel</Label>
                            <Input id="mobile" v-model="form.mobile" />
                            <p
                                v-if="form.errors?.mobile"
                                class="mt-1 text-sm text-destructive"
                            >
                                {{ form.errors.mobile }}
                            </p>
                        </div>

                        <div>
                            <Label for="website">Website</Label>
                            <Input id="website" v-model="form.website" />
                            <p
                                v-if="form.errors?.website"
                                class="mt-1 text-sm text-destructive"
                            >
                                {{ form.errors.website }}
                            </p>
                        </div>
                        <div>
                            <Label for="email">Email</Label>
                            <Input id="email" v-model="form.email" />
                            <p
                                v-if="form.errors?.email"
                                class="mt-1 text-sm text-destructive"
                            >
                                {{ form.errors.email }}
                            </p>
                        </div>

                        <div>
                            <Label for="postal_code">Código Postal</Label>
                            <Input
                                id="postal_code"
                                v-model="form.postal_code"
                                placeholder="XXXX-XXX"
                            />
                            <p
                                v-if="form.errors?.postal_code"
                                class="mt-1 text-sm text-destructive"
                            >
                                {{ form.errors.postal_code }}
                            </p>
                        </div>
                        <div>
                            <Label for="city">Localidade</Label>
                            <Input id="city" v-model="form.city" />
                            <p
                                v-if="form.errors?.city"
                                class="mt-1 text-sm text-destructive"
                            >
                                {{ form.errors.city }}
                            </p>
                        </div>

                        <div class="col-span-2">
                            <Label for="address">Morada</Label>
                            <Input id="address" v-model="form.address" />
                            <p
                                v-if="form.errors?.address"
                                class="mt-1 text-sm text-destructive"
                            >
                                {{ form.errors.address }}
                            </p>
                        </div>

                        <div class="col-span-2">
                            <Label for="notes">Observações</Label>
                            <Input id="notes" v-model="form.notes" />
                            <p
                                v-if="form.errors?.notes"
                                class="mt-1 text-sm text-destructive"
                            >
                                {{ form.errors.notes }}
                            </p>
                        </div>

                        <div class="flex items-center gap-3">
                            <Checkbox v-model="form.rgpd_consent" />
                            <span>Consentimento RGPD</span>
                        </div>

                        <div class="flex items-center gap-3">
                            <Checkbox v-model="form.is_active" />
                            <span>Ativo</span>
                        </div>
                    </div>

                    <div class="mt-4 flex justify-end gap-2">
                        <Button
                            variant="ghost"
                            @click="
                                () => {
                                    showCreate = false;
                                }
                            "
                            >Cancelar</Button
                        >
                        <Button @click="submit">Guardar</Button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
