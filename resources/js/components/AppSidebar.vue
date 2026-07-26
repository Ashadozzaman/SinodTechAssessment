<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import { Building2, LayoutGrid, Notebook, Package, ShoppingCart, Trophy, UserRound, UserX, Users } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
        icon: LayoutGrid,
    },
    {
        title: 'Users',
        href: '/users',
        icon: Users,
        permission: 'users.view',
    },
    {
        title: 'Roles',
        href: '/roles',
        icon: Notebook,
        permission: 'roles.view',
    },
    {
        title: 'Branches',
        href: '/branches',
        icon: Building2,
        permission: 'branches.view',
    },
    {
        title: 'Products',
        href: '/products',
        icon: Package,
        permission: 'products.view',
    },
    {
        title: 'Customers',
        href: '/customers',
        icon: UserRound,
        permission: 'customers.view',
    },
    {
        title: 'Sales',
        href: '/sales',
        icon: ShoppingCart,
        permission: 'sales.view',
    },
    {
        title: 'Lost Customers',
        href: '/lost-customers',
        icon: UserX,
        permission: 'customers.lost',
    },
    {
        title: 'KPI Leaderboard',
        href: '/kpi-leaderboard',
        icon: Trophy,
        permission: 'kpi.view',
    },
];

const footerNavItems: NavItem[] = [
    // {
    //     title: 'Github Repo',
    //     href: 'https://github.com/laravel/vue-starter-kit',
    //     icon: Folder,
    // },
    // {
    //     title: 'Documentation',
    //     href: 'https://laravel.com/docs/starter-kits#vue',
    //     icon: BookOpen,
    // },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="route('dashboard')">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
