<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

interface ProposalRow {
    id: number;
    number?: number;
    date?: string;
    valid_until?: string;
    entity_id?: number;
    status?: string;
    total?: string;
}

const rows = ref<ProposalRow[]>([]);
const loading = ref(false);
const showCreate = ref(false);
const isSubmitting = ref(false);

const form = useForm({
    date: new Date().toISOString().slice(0, 10),
    valid_until: null as string | null,
    entity_id: null as number | null,
    status: 'draft',
    lines: [
        {
            reference: '',
            name: '',
            quantity: 1,
            unit_price: 0,
            cost_price: null,
            supplier_id: null,
        },
    ],
});

async function load() {
    loading.value = true;
    try {
        const res = await fetch('/proposals/list', {
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

function addLine() {
    (form.lines as any).push({
        reference: '',
        name: '',
        quantity: 1,
        unit_price: 0,
        cost_price: null,
        supplier_id: null,
    });
}

function removeLine(index: number) {
    (form.lines as any).splice(index, 1);
}

function clearForm() {
    form.reset();
    form.clearErrors();
    form.date = new Date().toISOString().slice(0, 10);
    form.valid_until = null;
    form.lines = [
        {
            reference: '',
            name: '',
            quantity: 1,
            unit_price: 0,
            cost_price: null,
            supplier_id: null,
        },
    ];
}

async function submit() {
    isSubmitting.value = true;
    // default valid_until to date + 30d if empty
    if (!form.valid_until) {
        const d = new Date(form.date);
        d.setDate(d.getDate() + 30);
        form.valid_until = d.toISOString().slice(0, 10);
    }

    form.post('/proposals', {
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
        <Head title="Propostas" />

        <div class="p-6">
            <div class="mb-4 flex items-center justify-between">
                <h1 class="text-2xl font-semibold">Propostas</h1>
                <div>
                    <Button
                        @click="
                            () => {
                                clearForm();
                                showCreate = true;
                            }
                        "
                        >Nova Proposta</Button
                    >
                </div>
            </div>

            <div class="overflow-x-auto rounded bg-card shadow-sm">
                <table class="w-full table-auto">
                    <thead>
                        <tr class="text-left">
                            <th class="p-3">Data</th>
                            <th class="p-3">Número</th>
                            <th class="p-3">Validade</th>
                            <th class="p-3">Cliente</th>
                            <th class="p-3">Valor Total</th>
                            <th class="p-3">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="loading">
                            <td colspan="6" class="p-4">A carregar...</td>
                        </tr>
                        <tr v-for="r in rows" :key="r.id" class="border-t">
                            <td class="p-3">{{ r.date }}</td>
                            <td class="p-3">{{ r.number }}</td>
                            <td class="p-3">{{ r.valid_until }}</td>
                            <td class="p-3">{{ r.entity_id }}</td>
                            <td class="p-3">{{ r.total }}</td>
                            <td class="p-3">{{ r.status }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Create modal -->
            <div
                v-if="showCreate"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
            >
                <div class="w-[900px] rounded bg-card p-6 shadow-lg">
                    <h2 class="mb-4 text-xl">Nova Proposta</h2>

                    <div class="mb-4 grid grid-cols-2 gap-4">
                        <div>
                            <Label for="date">Data</Label>
                            <Input id="date" type="date" v-model="form.date" />
                            <p
                                v-if="form.errors?.date"
                                class="mt-1 text-sm text-destructive"
                            >
                                {{ form.errors.date }}
                            </p>
                        </div>
                        <div>
                            <Label for="valid_until">Validade</Label>
                            <Input
                                id="valid_until"
                                type="date"
                                v-model="form.valid_until"
                            />
                            <p
                                v-if="form.errors?.valid_until"
                                class="mt-1 text-sm text-destructive"
                            >
                                {{ form.errors.valid_until }}
                            </p>
                        </div>

                        <div class="col-span-2">
                            <Label for="entity_id">Cliente (ID)</Label>
                            <Input
                                id="entity_id"
                                v-model.number="form.entity_id"
                                placeholder="Coloque o ID do cliente (usar API)"
                            />
                            <p
                                v-if="form.errors?.entity_id"
                                class="mt-1 text-sm text-destructive"
                            >
                                {{ form.errors.entity_id }}
                            </p>
                        </div>
                    </div>

                    <div class="mb-4">
                        <h3 class="mb-2 font-semibold">Linhas</h3>
                        <div
                            v-for="(line, idx) in form.lines"
                            :key="idx"
                            class="mb-2 grid grid-cols-6 items-end gap-2"
                        >
                            <div>
                                <Label>Referência</Label>
                                <Input v-model="line.reference" />
                            </div>
                            <div>
                                <Label>Nome</Label>
                                <Input v-model="line.name" />
                            </div>
                            <div>
                                <Label>Quantidade</Label>
                                <Input
                                    type="number"
                                    v-model.number="line.quantity"
                                />
                            </div>
                            <div>
                                <Label>Preço Unit.</Label>
                                <Input
                                    type="number"
                                    step="0.01"
                                    v-model.number="line.unit_price"
                                />
                            </div>
                            <div>
                                <Label>Preço Custo</Label>
                                <Input
                                    type="number"
                                    step="0.01"
                                    v-model.number="line.cost_price"
                                />
                            </div>
                            <div class="flex gap-2">
                                <Button
                                    variant="ghost"
                                    @click.prevent="removeLine(idx)"
                                    >Remover</Button
                                >
                            </div>
                        </div>

                        <div>
                            <Button variant="ghost" @click.prevent="addLine"
                                >Adicionar Linha</Button
                            >
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
                        <Button @click.prevent="submit">Guardar</Button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
