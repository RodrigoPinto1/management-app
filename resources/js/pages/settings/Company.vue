<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Configurações - Empresa" />

        <div class="px-4 py-6">
            <div class="grid gap-6 lg:grid-cols-3">
                <div class="space-y-6 lg:col-span-2">
                    <HeadingSmall
                        title="Empresa"
                        description="Personalize os dados da empresa que aparecem na aplicação e em documentos impressos"
                    />

                    <form @submit.prevent="submit" class="space-y-6">
                        <div class="grid grid-cols-1 gap-4">
                            <div>
                                <Label>Nome</Label>
                                <Input v-model="form.name" class="mt-2" />
                            </div>

                            <div>
                                <Label>Morada</Label>
                                <Input v-model="form.address" class="mt-2" />
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <Label>Código Postal</Label>
                                    <Input
                                        v-model="form.postal_code"
                                        class="mt-2"
                                    />
                                </div>
                                <div>
                                    <Label>Localidade</Label>
                                    <Input
                                        v-model="form.locality"
                                        class="mt-2"
                                    />
                                </div>
                            </div>

                            <div>
                                <Label>NIF</Label>
                                <Input v-model="form.tax_number" class="mt-2" />
                            </div>

                            <div>
                                <Label>Logotipo</Label>
                                <input
                                    type="file"
                                    @change="onFileChange"
                                    class="mt-2 block"
                                    accept="image/*"
                                />
                                <InputError
                                    :message="form.errors.logo"
                                    class="mt-2"
                                />
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <Button :disabled="form.processing" type="submit"
                                >Guardar</Button
                            >
                            <Button
                                variant="ghost"
                                type="button"
                                @click="resetForm"
                                >Cancelar</Button
                            >
                        </div>
                    </form>
                </div>

                <aside class="lg:col-span-1">
                    <div class="rounded border p-4">
                        <h4 class="mb-2 text-sm font-medium">
                            Pré-visualização
                        </h4>
                        <div class="flex flex-col items-center">
                            <div
                                class="flex h-32 w-32 items-center justify-center overflow-hidden rounded bg-muted"
                            >
                                <img
                                    v-if="preview"
                                    :src="preview"
                                    alt="logo"
                                    class="h-full object-contain"
                                />
                                <div
                                    v-else
                                    class="text-sm text-muted-foreground"
                                >
                                    Sem logotipo
                                </div>
                            </div>

                            <div class="mt-4 text-center">
                                <div class="text-base font-semibold">
                                    {{ form.name || 'Nome da Empresa' }}
                                </div>
                                <div class="text-sm text-muted-foreground">
                                    {{ form.address }}
                                </div>
                                <div class="text-sm text-muted-foreground">
                                    {{ form.postal_code }} {{ form.locality }}
                                </div>
                                <div class="text-sm text-muted-foreground">
                                    NIF: {{ form.tax_number }}
                                </div>
                            </div>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { edit, update } from '@/routes/company';
import { type BreadcrumbItem } from '@/types';

const props = defineProps<{ company?: Record<string, any> }>();

const company = props.company || null;

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Configurações - Empresa',
        href: edit.url(),
    },
];

const form = useForm({
    name: company?.name || '',
    address: company?.address || '',
    postal_code: company?.postal_code || '',
    locality: company?.locality || '',
    tax_number: company?.tax_number || '',
    logo: null,
});

const preview = ref<string | null>(
    company?.logo ? `/storage/${company.logo}` : null,
);

function onFileChange(e: Event) {
    const input = e.target as HTMLInputElement;
    const f = input.files ? input.files[0] : null;
    console.log('File selected:', f);
    if (!f) return;
    form.logo = f;
    const objectUrl = URL.createObjectURL(f);
    console.log('Preview URL:', objectUrl);
    preview.value = objectUrl;
}

function submit() {
    form.post(update.url());
}
function resetForm() {
    form.reset({
        name: company?.name || '',
        address: company?.address || '',
        postal_code: company?.postal_code || '',
        locality: company?.locality || '',
        tax_number: company?.tax_number || '',
        logo: null,
    });
    preview.value = company?.logo ? `/storage/${company.logo}` : null;
}
</script>
