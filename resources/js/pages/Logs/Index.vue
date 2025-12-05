<template>
    <AppLayout>
        <Head title="Logs" />

        <div class="px-4 py-6">
            <Heading
                title="Logs de Atividade"
                description="Histórico de todas as ações realizadas no sistema"
            />

            <div class="mt-6 space-y-4">
                <!-- Filters -->
                <div class="flex items-center justify-between gap-4">
                    <Input
                        v-model="search"
                        placeholder="Pesquisar..."
                        class="max-w-sm"
                        @input="onSearch"
                    />
                    <Button @click="generateTestLogs" variant="outline">
                        Gerar Logs de Teste
                    </Button>
                </div>

                <!-- Table -->
                <div class="rounded-md border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Data</TableHead>
                                <TableHead>Hora</TableHead>
                                <TableHead>Utilizador</TableHead>
                                <TableHead>Menu</TableHead>
                                <TableHead>Acção</TableHead>
                                <TableHead>Dispositivo</TableHead>
                                <TableHead>IP</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-if="!logs.data || logs.data.length === 0">
                                <TableCell colspan="7" class="h-24 text-center">
                                    Sem resultados.
                                </TableCell>
                            </TableRow>
                            <TableRow
                                v-for="log in logs.data"
                                :key="log.id"
                            >
                                <TableCell>
                                    {{ formatDate(log.created_at) }}
                                </TableCell>
                                <TableCell>
                                    {{ formatTime(log.created_at) }}
                                </TableCell>
                                <TableCell>
                                    {{
                                        log.causer
                                            ? log.causer.name
                                            : 'Sistema'
                                    }}
                                </TableCell>
                                <TableCell>
                                    {{ log.log_name || '-' }}
                                </TableCell>
                                <TableCell>
                                    {{ log.description || '-' }}
                                </TableCell>
                                <TableCell>
                                    {{
                                        log.properties?.device ||
                                        log.properties?.user_agent ||
                                        '-'
                                    }}
                                </TableCell>
                                <TableCell>
                                    {{
                                        log.properties?.ip ||
                                        log.properties?.ip_address ||
                                        '-'
                                    }}
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <!-- Pagination -->
                <div
                    v-if="logs.meta && logs.meta.last_page > 1"
                    class="flex items-center justify-between"
                >
                    <div class="text-sm text-muted-foreground">
                        Página {{ logs.meta.current_page }} de
                        {{ logs.meta.last_page }}
                    </div>
                    <div class="flex items-center gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            :disabled="logs.meta.current_page === 1"
                            @click="goToPage(logs.meta.current_page - 1)"
                        >
                            Anterior
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            :disabled="
                                logs.meta.current_page === logs.meta.last_page
                            "
                            @click="goToPage(logs.meta.current_page + 1)"
                        >
                            Próxima
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { debounce } from 'lodash-es';

import AppLayout from '@/layouts/AppLayout.vue';
import Heading from '@/components/Heading.vue';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { index } from '@/routes/logs';

interface Log {
    id: number;
    log_name: string | null;
    description: string | null;
    subject_type: string | null;
    subject_id: number | null;
    causer_type: string | null;
    causer_id: number | null;
    causer?: {
        id: number;
        name: string;
        email: string;
    };
    properties: Record<string, any> | null;
    created_at: string;
    updated_at: string;
}

interface Props {
    logs: {
        data: Log[];
        meta?: {
            current_page: number;
            last_page: number;
            per_page: number;
            total: number;
        };
    };
    filters?: {
        search?: string;
        user?: number;
        from?: string;
        to?: string;
    };
}

const props = defineProps<Props>();

const search = ref(props.filters?.search || '');

const onSearch = debounce(() => {
    router.get(
        index.url(),
        { search: search.value },
        { preserveState: true, preserveScroll: true }
    );
}, 300);

function goToPage(page: number) {
    router.get(
        index.url(),
        { ...props.filters, page },
        { preserveState: true, preserveScroll: true }
    );
}

function generateTestLogs() {
    router.post('/logs/generate-test', {}, {
        preserveScroll: true,
        onSuccess: () => {
            router.reload({ only: ['logs'] });
        }
    });
}

function formatDate(dateString: string): string {
    const date = new Date(dateString);
    return date.toLocaleDateString('pt-PT', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    });
}

function formatTime(dateString: string): string {
    const date = new Date(dateString);
    return date.toLocaleTimeString('pt-PT', {
        hour: '2-digit',
        minute: '2-digit',
    });
}
</script>
