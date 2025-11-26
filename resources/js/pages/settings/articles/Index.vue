<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

interface ArticleRow {
    id: number;
    reference?: string;
    photo?: string;
    name: string;
    description?: string;
    price: string;
}

const rows = ref<ArticleRow[]>([]);
const loading = ref(false);
const showCreate = ref(false);
const isSubmitting = ref(false);

const form = useForm({
    reference: '',
    name: '',
    description: '',
    price: 0,
    vat_rate: null,
    photo: '',
    notes: '',
    is_active: true,
});

async function load() {
    loading.value = true;
    try {
        const res = await fetch('/settings/articles/list', {
            credentials: 'same-origin',
            headers: { Accept: 'application/json' },
        });
        if (res.ok) {
            const json = await res.json();
            rows.value = json.data || [];
        }
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
}

async function submit() {
    isSubmitting.value = true;
    form.post('/settings/articles', {
        onSuccess: async () => {
            showCreate.value = false;
            await load();
        },
        onError: (errors) => console.error('Validation errors', errors),
        onFinish: () => (isSubmitting.value = false),
    });
}

onMounted(load);
</script>

<template>
    <AppLayout>
        <Head title="Configurações - Artigos" />

        <div class="p-6">
            <div class="mb-4 flex items-center justify-between">
                <h1 class="text-2xl font-semibold">Configurações — Artigos</h1>
                <div>
                    <Button
                        @click="
                            () => {
                                showCreate = true;
                            }
                        "
                        >Novo Artigo</Button
                    >
                </div>
            </div>

            <div class="overflow-x-auto rounded bg-card shadow-sm">
                <table class="w-full table-auto">
                    <thead>
                        <tr class="text-left">
                            <th class="p-3">Referência</th>
                            <th class="p-3">Foto</th>
                            <th class="p-3">Nome</th>
                            <th class="p-3">Descrição</th>
                            <th class="p-3">Preço</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="loading">
                            <td colspan="5" class="p-4">A carregar...</td>
                        </tr>
                        <tr v-for="row in rows" :key="row.id" class="border-t">
                            <td class="p-3">{{ row.reference }}</td>
                            <td class="p-3">
                                <img
                                    v-if="row.photo"
                                    :src="row.photo"
                                    alt="foto"
                                    class="h-10 w-10 rounded"
                                />
                            </td>
                            <td class="p-3">{{ row.name }}</td>
                            <td class="p-3">{{ row.description }}</td>
                            <td class="p-3">{{ row.price }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- create modal -->
            <div
                v-if="showCreate"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
            >
                <div class="w-[720px] rounded bg-card p-6 shadow-lg">
                    <h2 class="mb-4 text-xl">Novo Artigo</h2>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <Label for="reference">Referência</Label>
                            <Input id="reference" v-model="form.reference" />
                            <p
                                v-if="form.errors?.reference"
                                class="mt-1 text-sm text-destructive"
                            >
                                {{ form.errors.reference }}
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

                        <div class="col-span-2">
                            <Label for="description">Descrição</Label>
                            <Input
                                id="description"
                                v-model="form.description"
                            />
                            <p
                                v-if="form.errors?.description"
                                class="mt-1 text-sm text-destructive"
                            >
                                {{ form.errors.description }}
                            </p>
                        </div>

                        <div>
                            <Label for="price">Preço</Label>
                            <Input
                                id="price"
                                type="number"
                                v-model.number="form.price"
                            />
                            <p
                                v-if="form.errors?.price"
                                class="mt-1 text-sm text-destructive"
                            >
                                {{ form.errors.price }}
                            </p>
                        </div>

                        <div>
                            <Label for="vat_rate">IVA (%)</Label>
                            <Input
                                id="vat_rate"
                                type="number"
                                step="0.01"
                                v-model.number="form.vat_rate"
                                placeholder="e.g. 23.00"
                            />
                            <p
                                v-if="form.errors?.vat_rate"
                                class="mt-1 text-sm text-destructive"
                            >
                                {{ form.errors.vat_rate }}
                            </p>
                        </div>

                        <div>
                            <Label for="photo">Foto (URL)</Label>
                            <Input id="photo" v-model="form.photo" />
                            <p
                                v-if="form.errors?.photo"
                                class="mt-1 text-sm text-destructive"
                            >
                                {{ form.errors.photo }}
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
