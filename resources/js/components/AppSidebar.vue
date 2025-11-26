<script setup lang="ts">
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import {
    Archive,
    BookOpen,
    Box,
    Briefcase,
    Calendar,
    CreditCard,
    FileText,
    Flag,
    LayoutGrid,
    Percent,
    Settings,
    Shield,
    ShoppingCart,
    Truck,
    User,
    Users,
} from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';

const dashboardHref = dashboard().url;

const managementItems: NavItem[] = [
    { title: 'Clientes', href: '/clients', icon: Users },
    { title: 'Fornecedores', href: '/suppliers', icon: Truck },
    { title: 'Entidades - Países', href: '/countries', icon: Flag },
];

const contactsItems: NavItem[] = [
    { title: 'Contactos', href: '/contacts', icon: User },
    { title: 'Contactos - Funções', href: '/contact-roles', icon: User },
];

const salesItems: NavItem[] = [
    { title: 'Propostas', href: '/proposals', icon: FileText },
    {
        title: 'Encomendas - Clientes',
        href: '/orders/customers',
        icon: ShoppingCart,
    },
    {
        title: 'Encomendas - Fornecedores',
        href: '/orders/suppliers',
        icon: Truck,
    },
];

const operationalItems: NavItem[] = [
    { title: 'Ordens de Trabalho', href: '/work-orders', icon: Briefcase },
    { title: 'Artigos', href: '/items', icon: Box },
];

const calendarItems: NavItem[] = [
    { title: 'Calendário', href: '/calendar', icon: Calendar },
    { title: 'Calendário - Tipos', href: '/calendar/types', icon: Calendar },
    { title: 'Calendário - Acções', href: '/calendar/actions', icon: Calendar },
];

const financeItems: NavItem[] = [
    { title: 'Contas Bancárias', href: '/bank-accounts', icon: CreditCard },
    {
        title: 'Conta Corrente Clientes',
        href: '/customers/account',
        icon: CreditCard,
    },
    {
        title: 'Faturas Fornecedores',
        href: '/supplier-invoices',
        icon: FileText,
    },
    { title: 'Financeiro - IVA', href: '/tax/vat', icon: Percent },
];

const accessItems: NavItem[] = [
    { title: 'Gestão de Acessos', href: '/access', icon: Shield },
    { title: 'Utilizadores', href: '/users', icon: Users },
    { title: 'Permissões', href: '/permissions', icon: Shield },
];

const miscItems: NavItem[] = [
    { title: 'Arquivo Digital', href: '/digital-archive', icon: Archive },
    { title: 'Logs', href: '/logs', icon: FileText },
    { title: 'Artigos', href: '/settings/articles', icon: BookOpen },
    { title: 'Empresa', href: '/company', icon: Settings },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain
                :items="[
                    {
                        title: 'Dashboard',
                        href: dashboardHref,
                        icon: LayoutGrid,
                    },
                ]"
                label="Início"
            />

            <NavMain :items="managementItems" label="Gestão" />
            <NavMain :items="contactsItems" label="Contactos" />
            <NavMain :items="salesItems" label="Vendas & Compras" />
            <NavMain :items="operationalItems" label="Operacional" />
            <NavMain :items="calendarItems" label="Calendário" />
            <NavMain :items="financeItems" label="Financeiro" />
            <NavMain :items="accessItems" label="Gestão de Acessos" />
            <NavMain :items="miscItems" label="Configurações" />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
