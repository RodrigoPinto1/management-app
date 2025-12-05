<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { onMounted, ref, watch } from 'vue';

const rows = ref([]);
const loading = ref(false);

const types = ref<any[]>([]);
const actions = ref<any[]>([]);
const entities = ref<any[]>([]);
const users = ref<any[]>([]);
const typeColorMap = ref<Record<string, string>>({});

const filter = ref({
    user_id: null as number | null,
    entity_id: null as number | null,
});

const selectedEvent = ref<any>(null);

const form = useForm({
    start_at: '',
    end_at: '',
    duration_minutes: null,
    shared: false,
    knowledge: '',
    entity_id: null,
    calendar_type_id: null,
    calendar_action_id: null,
    description: '',
    state: 'planned',
});

const startDate = ref<string>('');
const startTime = ref<string>('');

const showCreate = ref(false);
const isSubmitting = ref(false);
const CalendarComp = ref<any>(null);
const calendarOptions = ref<any>(null);
const calendarRef = ref<any>(null);

async function load() {
    loading.value = true;
    try {
        const qs = new URLSearchParams();
        if (filter.value.user_id)
            qs.set('user_id', String(filter.value.user_id));
        if (filter.value.entity_id)
            qs.set('entity_id', String(filter.value.entity_id));
        // no type filter - only user & entity

        const res = await fetch('/calendar/events?' + qs.toString(), {
            credentials: 'same-origin',
        });
        if (res.ok) rows.value = await res.json();
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
}

async function loadMeta() {
    try {
        const [tRes, aRes] = await Promise.all([
            fetch('/calendar/types', { credentials: 'same-origin' }),
            fetch('/calendar/actions', { credentials: 'same-origin' }),
        ]);
        if (tRes.ok) types.value = (await tRes.json()).data || [];
        if (aRes.ok) actions.value = (await aRes.json()).data || [];

        const c1 = await fetch('/entities/list?type=client', {
            credentials: 'same-origin',
        });
        const c2 = await fetch('/entities/list?type=supplier', {
            credentials: 'same-origin',
        });
        let list: any[] = [];
        if (c1.ok) list = list.concat((await c1.json()).data || []);
        if (c2.ok) list = list.concat((await c2.json()).data || []);
        entities.value = list;

        try {
            const uRes = await fetch('/users/list', {
                credentials: 'same-origin',
            });
            if (uRes.ok) users.value = (await uRes.json()).data || [];
        } catch (e) {
            // ignore
        }

        const palette = [
            '#3b82f6',
            '#10b981',
            '#f59e0b',
            '#ef4444',
            '#8b5cf6',
            '#06b6d4',
        ];
        types.value.forEach((t: any, i: number) => {
            typeColorMap.value[String(t.id)] = palette[i % palette.length];
        });
    } catch (e) {
        console.error('Failed to load calendar meta', e);
    }
}

onMounted(async () => {
    await loadMeta();
    await tryLoadFullCalendar();
    await load();
});

watch(
    () => [filter.value.user_id, filter.value.entity_id],
    async () => {
        await load();
        try {
            calendarRef.value?.getApi()?.refetchEvents();
        } catch (e) {}
    },
);

async function tryLoadFullCalendar() {
    try {
        const injectCss = (href: string) => {
            if (!document.querySelector(`link[href="${href}"]`)) {
                const l = document.createElement('link');
                l.rel = 'stylesheet';
                l.href = href;
                document.head.appendChild(l);
            }
        };

        const fcVersion = '6.1.19';
        injectCss(
            `https://cdn.jsdelivr.net/npm/@fullcalendar/core@${fcVersion}/main.min.css`,
        );
        injectCss(
            `https://cdn.jsdelivr.net/npm/@fullcalendar/daygrid@${fcVersion}/main.min.css`,
        );
        injectCss(
            `https://cdn.jsdelivr.net/npm/@fullcalendar/timegrid@${fcVersion}/main.min.css`,
        );

        const fc = await import('@fullcalendar/vue3');
        const dayGrid = await import('@fullcalendar/daygrid');
        const timeGrid = await import('@fullcalendar/timegrid');
        const interaction = await import('@fullcalendar/interaction');

        CalendarComp.value = fc.default;

        // Inject small style overrides so FullCalendar popovers and events
        // are readable in dark/light themes (force dark popover bg and white text)
        try {
            const css = `
                        /* Popover (event details) */
                        .fc-popover,
                        .fc-popover .fc-header-toolbar,
                        .fc-popover .fc-body {
                            background: var(--color-card) !important;
                            color: var(--color-card-foreground) !important;
                        }
                        /* Event blocks text */
                        .fc .fc-event, .fc-event, .fc-event * {
                            color: #ffffff !important;
                        }
                        /* Day grid more+ popover content */
                        .fc-popover .fc-list-item-title { color: var(--color-card-foreground) !important; }
                    `;
            const s = document.createElement('style');
            s.setAttribute('data-fc-overrides', '1');
            s.innerHTML = css;
            document.head.appendChild(s);
        } catch (e) {
            // ignore style injection failures
        }

        calendarOptions.value = {
            plugins: [dayGrid.default, timeGrid.default, interaction.default],
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: '',
            },
            selectable: true,
            selectMirror: true,
            height: 480,
            aspectRatio: 1.1,
            dayMaxEventRows: 2,
            dayMaxEvents: true,
            nowIndicator: false,
            displayEventTime: false,
            eventDisplay: 'list-item',
            eventContent: function (arg: any) {
                const typeId = arg.event.extendedProps?.type?.id;
                const color = typeId
                    ? typeColorMap.value[String(typeId)] || '#3b82f6'
                    : '#3b82f6';
                const title = arg.event.title || '';
                const inner = `
                    <div style="color: #ffffff;" class="inline-flex items-center gap-2 rounded-md px-2 py-0.5 text-sm overflow-hidden">
                        <span class="w-2 h-2 rounded-full" style="background:${color}"></span>
                        <span style="color: #ffffff;" class="truncate max-w-[120px] leading-none">${title}</span>
                    </div>
                `;
                return { html: inner };
            },
            select: (selectionInfo: any) => {
                const s = new Date(selectionInfo.start);
                const pad = (n: number) => String(n).padStart(2, '0');
                startDate.value = `${s.getFullYear()}-${pad(s.getMonth() + 1)}-${pad(s.getDate())}`;
                startTime.value = `${pad(s.getHours())}:${pad(s.getMinutes())}`;
                if (selectionInfo.end) {
                    const e = new Date(selectionInfo.end);
                    const diff = Math.round(
                        (e.getTime() - s.getTime()) / 60000,
                    );
                    form.duration_minutes = diff > 0 ? diff : null;
                }
                showCreate.value = true;
                try {
                    calendarRef.value?.getApi()?.unselect();
                } catch (e) {}
            },
            events: (
                fetchInfo: any,
                successCallback: any,
                failureCallback: any,
            ) => {
                const qs = new URLSearchParams();
                if (filter.value.user_id)
                    qs.set('user_id', String(filter.value.user_id));
                if (filter.value.entity_id)
                    qs.set('entity_id', String(filter.value.entity_id));
                qs.set('start', fetchInfo.startStr);
                qs.set('end', fetchInfo.endStr);

                fetch('/calendar/events?' + qs.toString(), {
                    credentials: 'same-origin',
                })
                    .then((r) => r.json())
                    .then((data) => {
                        const mapped = data.map((ev: any) => {
                            const typeId = ev.extendedProps?.type?.id;
                            if (typeId && typeColorMap.value[String(typeId)]) {
                                ev.backgroundColor =
                                    typeColorMap.value[String(typeId)];
                                ev.borderColor =
                                    typeColorMap.value[String(typeId)];
                            }
                            if (typeId && typeColorMap.value[String(typeId)]) {
                                ev.textColor = '#ffffff'; // ensure text is visible on colored background
                            } else {
                                ev.textColor = '#ffffff'; // default text color
                            }
                            ev.title =
                                ev.title ||
                                ev.extendedProps?.knowledge ||
                                ev.extendedProps?.description ||
                                'Tarefa';
                            return ev;
                        });
                        successCallback(mapped);
                    })
                    .catch((err) => failureCallback(err));
            },
            eventClick: (info: any) => {
                selectedEvent.value = {
                    id: info.event.id,
                    title: info.event.title,
                    start: info.event.start,
                    end: info.event.end,
                    extendedProps: info.event.extendedProps,
                };
                showCreate.value = false;
                setTimeout(() => {
                    selectedEvent.value && (selectedEvent.value.show = true);
                }, 10);
            },
        };
    } catch (e) {
        console.warn('FullCalendar dynamic import failed', e);
    }
}

function toInputValue(date: Date | string) {
    const d = new Date(date);
    const pad = (n: number) => String(n).padStart(2, '0');
    const year = d.getFullYear();
    const month = pad(d.getMonth() + 1);
    const day = pad(d.getDate());
    const hour = pad(d.getHours());
    const minute = pad(d.getMinutes());
    return `${year}-${month}-${day}T${hour}:${minute}`;
}

function openCreate() {
    form.reset();
    const now = new Date();
    const pad = (n: number) => String(n).padStart(2, '0');
    startDate.value = `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}`;
    startTime.value = `${pad(now.getHours())}:${pad(now.getMinutes())}`;
    showCreate.value = true;
}

async function submit() {
    isSubmitting.value = true;
    try {
        const s = new Date(`${startDate.value}T${startTime.value}`);
        form.start_at = toInputValue(s);
        if (form.duration_minutes) {
            const e = new Date(
                s.getTime() + Number(form.duration_minutes) * 60000,
            );
            form.end_at = toInputValue(e);
        } else {
            form.end_at = '';
        }

        await form.post('/calendar/events', {
            onSuccess: async () => {
                showCreate.value = false;
                await load();
                try {
                    calendarRef.value?.getApi()?.refetchEvents();
                } catch (e) {}
            },
            onFinish: () => {
                isSubmitting.value = false;
            },
        });
    } catch (e) {
        console.error('submit failed', e);
        isSubmitting.value = false;
    }
}

async function deleteEvent(id: any) {
    if (!confirm('Confirmar eliminação?')) return;
    try {
        const tokenMeta = document.querySelector('meta[name="csrf-token"]');
        const csrf = tokenMeta ? tokenMeta.getAttribute('content') : '';
        const res = await fetch('/calendar/events/' + id, {
            method: 'DELETE',
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                Accept: 'application/json',
            },
        });
        if (res.ok) {
            selectedEvent.value = null;
            await load();
            try {
                calendarRef.value?.getApi()?.refetchEvents();
            } catch (e) {}
        } else {
            console.error('Failed to delete event', await res.text());
        }
    } catch (e) {
        console.error(e);
    }
}
</script>

<template>
    <AppLayout>
        <Head title="Calendário" />
        <div class="p-6">
            <div class="mb-4 flex items-center justify-between">
                <h1 class="text-2xl font-semibold">Calendário</h1>
                <div class="flex items-center gap-3">
                    <select v-model="filter.user_id" class="input">
                        <option :value="null">Todos Utilizadores</option>
                        <option v-if="users.length === 0" :value="null">
                            (current user)
                        </option>
                        <option v-for="u in users" :key="u.id" :value="u.id">
                            {{ u.name }}
                        </option>
                    </select>
                    <select v-model="filter.entity_id" class="input">
                        <option :value="null">Todas Entidades</option>
                        <option v-for="e in entities" :key="e.id" :value="e.id">
                            {{ e.name }}
                        </option>
                    </select>
                    <!-- Tipo filter removed per spec: only Utilizador and Entidade filters remain -->
                    <button class="btn" @click="openCreate">Novo</button>
                </div>
            </div>

            <div class="overflow-x-auto rounded bg-card p-4 text-foreground">
                <div v-if="CalendarComp">
                    <component
                        :is="CalendarComp"
                        :options="calendarOptions"
                        ref="calendarRef"
                    />
                </div>

                <div v-else>
                    <p class="mb-4">
                        FullCalendar não está disponível — mostro uma lista
                        mínima de eventos. Para ativar a vista completa, execute
                        <code>npm install</code>.
                    </p>

                    <table class="w-full table-auto text-foreground">
                        <thead>
                            <tr class="text-left">
                                <th>Início</th>
                                <th>Fim</th>
                                <th>Tipo</th>
                                <th>Ação</th>
                                <th>Entidade</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="loading">
                                <td colspan="6">A carregar...</td>
                            </tr>
                            <tr v-for="r in rows" :key="r.id">
                                <td>{{ r.start }}</td>
                                <td>{{ r.end }}</td>
                                <td>{{ r.extendedProps?.type?.name ?? '' }}</td>
                                <td>
                                    {{ r.extendedProps?.action?.name ?? '' }}
                                </td>
                                <td>
                                    {{ r.extendedProps?.entity?.name ?? '' }}
                                </td>
                                <td>{{ r.extendedProps?.state ?? '' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Create modal -->
            <div
                v-if="showCreate"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
            >
                <div class="w-full max-w-3xl rounded bg-card p-6 shadow-lg">
                    <div class="flex items-start justify-between">
                        <h2 class="mb-2 text-xl">Novo Evento</h2>
                        <button
                            class="text-sm text-muted hover:text-foreground"
                            @click="showCreate = false"
                        >
                            ✕
                        </button>
                    </div>

                    <div class="mt-3 grid grid-cols-12 gap-4">
                        <div class="col-span-6">
                            <div class="grid grid-cols-1 gap-3">
                                <div>
                                    <Label for="start_date">Data</Label>
                                    <Input
                                        id="start_date"
                                        v-model="startDate"
                                        type="date"
                                        class="mt-1"
                                    />
                                </div>
                                <div>
                                    <Label for="start_time">Hora</Label>
                                    <Input
                                        id="start_time"
                                        v-model="startTime"
                                        type="time"
                                        class="mt-1"
                                    />
                                </div>
                                <!-- Title removed to match spec: fields are Data, Hora, Duração, Partilha, Conhecimento, Entidade, Tipo, Acção, Descrição, Estado -->
                                <div>
                                    <Label for="knowledge">Conhecimento</Label>
                                    <Input
                                        id="knowledge"
                                        v-model="form.knowledge"
                                        type="text"
                                        class="mt-1"
                                        placeholder="Ex: Reunião, Chamada"
                                    />
                                </div>
                            </div>

                            <p
                                v-if="form.errors.start_at"
                                class="mt-1 text-sm text-destructive"
                            >
                                {{ form.errors.start_at }}
                            </p>

                            <Label for="duration_minutes" class="mt-3"
                                >Duração (min)</Label
                            >
                            <Input
                                id="duration_minutes"
                                v-model="form.duration_minutes"
                                type="number"
                                min="0"
                                class="mt-1"
                            />
                            <p
                                v-if="form.errors.duration_minutes"
                                class="mt-1 text-sm text-destructive"
                            >
                                {{ form.errors.duration_minutes }}
                            </p>

                            <Label class="mt-3">Partilha</Label>
                            <div class="mt-1">
                                <label class="inline-flex items-center gap-2">
                                    <Checkbox v-model="form.shared" />
                                    <span class="text-sm"
                                        >Partilhar com equipa</span
                                    >
                                </label>
                            </div>
                        </div>

                        <div class="col-span-6">
                            <Label for="entity_id">Entidade</Label>
                            <select
                                id="entity_id"
                                v-model="form.entity_id"
                                class="input mt-1 w-full"
                            >
                                <option :value="null">Sem entidade</option>
                                <option
                                    v-for="e in entities"
                                    :key="e.id"
                                    :value="e.id"
                                >
                                    {{ e.name }}
                                </option>
                            </select>
                            <p
                                v-if="form.errors.entity_id"
                                class="mt-1 text-sm text-destructive"
                            >
                                {{ form.errors.entity_id }}
                            </p>

                            <div class="mt-3 grid grid-cols-2 gap-2">
                                <div>
                                    <Label for="calendar_type_id">Tipo</Label>
                                    <select
                                        id="calendar_type_id"
                                        v-model="form.calendar_type_id"
                                        class="input mt-1 w-full"
                                    >
                                        <option :value="null">
                                            Escolher tipo
                                        </option>
                                        <option
                                            v-for="t in types"
                                            :key="t.id"
                                            :value="t.id"
                                        >
                                            {{ t.name }}
                                        </option>
                                    </select>
                                    <p
                                        v-if="form.errors.calendar_type_id"
                                        class="mt-1 text-sm text-destructive"
                                    >
                                        {{ form.errors.calendar_type_id }}
                                    </p>
                                </div>
                                <div>
                                    <Label for="calendar_action_id">Ação</Label>
                                    <select
                                        id="calendar_action_id"
                                        v-model="form.calendar_action_id"
                                        class="input mt-1 w-full"
                                    >
                                        <option :value="null">
                                            Escolher ação
                                        </option>
                                        <option
                                            v-for="a in actions"
                                            :key="a.id"
                                            :value="a.id"
                                        >
                                            {{ a.name }}
                                        </option>
                                    </select>
                                    <p
                                        v-if="form.errors.calendar_action_id"
                                        class="mt-1 text-sm text-destructive"
                                    >
                                        {{ form.errors.calendar_action_id }}
                                    </p>
                                </div>
                            </div>

                            <Label for="knowledge" class="mt-3"
                                >Conhecimento</Label
                            >
                            <Input
                                id="knowledge"
                                v-model="form.knowledge"
                                type="text"
                                class="mt-1"
                                placeholder="Ex: Reunião, Chamada"
                            />
                            <p
                                v-if="form.errors.knowledge"
                                class="mt-1 text-sm text-destructive"
                            >
                                {{ form.errors.knowledge }}
                            </p>

                            <div class="mt-3">
                                <Label for="state">Estado</Label>
                                <select
                                    id="state"
                                    v-model="form.state"
                                    class="input mt-1 w-full"
                                >
                                    <option value="planned">Planeado</option>
                                    <option value="confirmed">
                                        Confirmado
                                    </option>
                                    <option value="done">Concluído</option>
                                    <option value="cancelled">Cancelado</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-span-12">
                            <Label for="description">Descrição</Label>
                            <textarea
                                id="description"
                                v-model="form.description"
                                class="input mt-1 min-h-[90px] w-full"
                            ></textarea>
                            <p
                                v-if="form.errors.description"
                                class="mt-1 text-sm text-destructive"
                            >
                                {{ form.errors.description }}
                            </p>
                        </div>

                        <div
                            class="col-span-12 mt-4 flex items-center justify-end gap-2"
                        >
                            <Button
                                variant="ghost"
                                @click="showCreate = false"
                                type="button"
                                >Cancelar</Button
                            >
                            <Button
                                :disabled="isSubmitting"
                                @click="submit"
                                type="button"
                            >
                                <span v-if="isSubmitting">A criar...</span>
                                <span v-else>Criar</span>
                            </Button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Event detail modal -->
            <div
                v-if="selectedEvent && selectedEvent.show"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
            >
                <div
                    class="w-[560px] rounded bg-card p-6 text-foreground shadow-lg"
                >
                    <h2 class="mb-2 text-lg text-foreground">
                        {{ selectedEvent.title }}
                    </h2>
                    <p class="text-sm text-foreground">
                        {{
                            selectedEvent.start
                                ? new Date(selectedEvent.start).toLocaleString()
                                : ''
                        }}
                        -
                        {{
                            selectedEvent.end
                                ? new Date(selectedEvent.end).toLocaleString()
                                : ''
                        }}
                    </p>
                    <div class="mt-3 text-sm text-foreground">
                        <p>
                            <strong>Tipo:</strong>
                            {{ selectedEvent.extendedProps?.type?.name ?? '-' }}
                        </p>
                        <p>
                            <strong>Ação:</strong>
                            {{
                                selectedEvent.extendedProps?.action?.name ?? '-'
                            }}
                        </p>
                        <p>
                            <strong>Entidade:</strong>
                            {{
                                selectedEvent.extendedProps?.entity?.name ?? '-'
                            }}
                        </p>
                        <p
                            v-if="selectedEvent.extendedProps?.knowledge"
                            class="mt-2"
                        >
                            <strong>Conhecimento:</strong>
                            {{ selectedEvent.extendedProps.knowledge }}
                        </p>
                        <p
                            v-if="selectedEvent.extendedProps?.description"
                            class="mt-2"
                        >
                            <strong>Descrição:</strong>
                            {{ selectedEvent.extendedProps.description }}
                        </p>
                        <p class="mt-2">
                            <strong>Estado:</strong>
                            <span
                                class="ml-2 inline-flex items-center rounded px-2 py-0.5 text-xs font-medium"
                                :class="{
                                    'bg-yellow-500 text-white':
                                        selectedEvent.extendedProps?.state ===
                                        'confirmed',
                                    'bg-green-600 text-white':
                                        selectedEvent.extendedProps?.state ===
                                        'done',
                                    'bg-gray-500 text-white':
                                        selectedEvent.extendedProps?.state ===
                                        'planned',
                                    'bg-red-600 text-white':
                                        selectedEvent.extendedProps?.state ===
                                        'cancelled',
                                }"
                            >
                                {{
                                    (
                                        selectedEvent.extendedProps?.state ??
                                        '-'
                                    ).toString()
                                }}
                            </span>
                        </p>
                    </div>
                    <div class="mt-4 flex justify-end gap-2">
                        <button
                            class="btn-ghost"
                            @click="selectedEvent.show = false"
                        >
                            Fechar
                        </button>
                        <Button
                            variant="destructive"
                            type="button"
                            @click="deleteEvent(selectedEvent.id)"
                            >Eliminar</Button
                        >
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
