<script setup>
import { ref, watch, onUnmounted } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import { Link, usePage } from '@inertiajs/vue3';

const showingNavigationDropdown = ref(false);
const page = usePage();

// Fermer le menu quand on navigue - utiliser watch sur l'URL de la page
watch(() => page.url, () => {
    showingNavigationDropdown.value = false;
});

// Empêcher le scroll du body quand le menu est ouvert
watch(showingNavigationDropdown, (isOpen) => {
    if (typeof document !== 'undefined') {
        if (isOpen) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }
    }
});

// Nettoyer au démontage
onUnmounted(() => {
    if (typeof document !== 'undefined') {
        document.body.style.overflow = '';
    }
});

const closeMenu = () => {
    showingNavigationDropdown.value = false;
};
</script>

<template>
    <div>
        <div class="min-h-screen bg-gray-100">
            <nav
                class="border-b border-gray-100 bg-white"
            >
                <!-- Primary Navigation Menu -->
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="flex h-16 justify-between">
                        <div class="flex">
                            <!-- Logo -->
                            <div>
                                <Link :href="route('dashboard')">
                                    <ApplicationLogo
                                        class="block h-9 w-auto fill-current text-gray-800"
                                    />
                                </Link>
                            </div>

                            <!-- Navigation Links -->
                            <div
                                class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex"
                            >
                                <NavLink
                                    :href="route('dashboard')"
                                    :active="route().current('dashboard')"
                                >
                                    Tableau de bord
                                </NavLink>
                                <NavLink
                                    :href="route('membres.index')"
                                    :active="route().current('membres.*')"
                                >
                                    Membres
                                </NavLink>
                                <NavLink
                                    :href="route('cotisations.index')"
                                    :active="route().current('cotisations.*')"
                                >
                                    Cotisations
                                </NavLink>
                                <NavLink
                                    :href="route('depenses-medicales.index')"
                                    :active="route().current('depenses-medicales.*')"
                                >
                                    Dépenses
                                </NavLink>
                            </div>
                        </div>

                        <div class="hidden sm:ms-6 sm:flex sm:items-center">
                            <!-- Settings Dropdown -->
                            <div class="relative ms-3">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <span class="inline-flex rounded-md">
                                            <button
                                                type="button"
                                                class="inline-flex items-center rounded-md border border-transparent bg-white px-3 py-2 text-sm font-medium leading-4 text-gray-500 transition duration-150 ease-in-out hover:text-gray-700 focus:outline-none"
                                            >
                                                {{ $page.props.auth.user.name }}

                                                <svg
                                                    class="-me-0.5 ms-2 h-4 w-4"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 20 20"
                                                    fill="currentColor"
                                                >
                                                    <path
                                                        fill-rule="evenodd"
                                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                        clip-rule="evenodd"
                                                    />
                                                </svg>
                                            </button>
                                        </span>
                                    </template>

                                    <template #content>
                                        <DropdownLink
                                            :href="route('profile.edit')"
                                        >
                                            Profile
                                        </DropdownLink>
                                        <DropdownLink
                                            :href="route('logout')"
                                            method="post"
                                            as="button"
                                        >
                                            Log Out
                                        </DropdownLink>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>

                        <!-- Hamburger -->
                        <div class="-me-2 flex items-center sm:hidden">
                            <button
                                @click="
                                    showingNavigationDropdown =
                                        !showingNavigationDropdown
                                "
                                class="inline-flex items-center justify-center rounded-md p-2 text-gray-400 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-500 focus:bg-gray-100 focus:text-gray-500 focus:outline-none"
                            >
                                <svg
                                    class="h-6 w-6"
                                    stroke="currentColor"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        :class="{
                                            hidden: showingNavigationDropdown,
                                            'inline-flex':
                                                !showingNavigationDropdown,
                                        }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M4 6h16M4 12h16M4 18h16"
                                    />
                                    <path
                                        :class="{
                                            hidden: !showingNavigationDropdown,
                                            'inline-flex':
                                                showingNavigationDropdown,
                                        }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Overlay pour fermer le menu -->
                <div
                    v-if="showingNavigationDropdown"
                    @click="closeMenu"
                    class="fixed inset-0 z-40 bg-black bg-opacity-50 transition-opacity duration-300 sm:hidden"
                ></div>

                <!-- Responsive Navigation Menu - Menu latéral flottant -->
                <div
                    :class="{
                        'translate-x-0': showingNavigationDropdown,
                        '-translate-x-full': !showingNavigationDropdown,
                    }"
                    class="fixed left-0 top-0 z-50 h-full w-64 transform bg-white shadow-lg transition-transform duration-300 ease-in-out sm:hidden"
                >
                    <div class="flex h-16 items-center justify-between border-b border-gray-200 px-4">
                        <Link :href="route('dashboard')" @click="closeMenu">
                            <ApplicationLogo
                                class="block h-8 w-auto fill-current text-gray-800"
                            />
                        </Link>
                        <button
                            @click="closeMenu"
                            class="inline-flex items-center justify-center rounded-md p-2 text-gray-400 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-500 focus:bg-gray-100 focus:text-gray-500 focus:outline-none"
                        >
                            <svg
                                class="h-6 w-6"
                                stroke="currentColor"
                                fill="none"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                        </button>
                    </div>

                    <div class="overflow-y-auto" style="height: calc(100% - 4rem);">
                        <div class="space-y-1 px-2 py-4">
                            <div @click="closeMenu">
                                <ResponsiveNavLink
                                    :href="route('dashboard')"
                                    :active="route().current('dashboard')"
                                >
                                    Tableau de bord
                                </ResponsiveNavLink>
                            </div>
                            <div @click="closeMenu">
                                <ResponsiveNavLink
                                    :href="route('membres.index')"
                                    :active="route().current('membres.*')"
                                >
                                    Membres
                                </ResponsiveNavLink>
                            </div>
                            <div @click="closeMenu">
                                <ResponsiveNavLink
                                    :href="route('cotisations.index')"
                                    :active="route().current('cotisations.*')"
                                >
                                    Cotisations
                                </ResponsiveNavLink>
                            </div>
                            <div @click="closeMenu">
                                <ResponsiveNavLink
                                    :href="route('depenses-medicales.index')"
                                    :active="route().current('depenses-medicales.*')"
                                >
                                    Dépenses
                                </ResponsiveNavLink>
                            </div>
                        </div>

                        <!-- Responsive Settings Options -->
                        <div class="border-t border-gray-200 px-2 py-4">
                            <div class="px-2 pb-3">
                                <div class="text-base font-medium text-gray-800">
                                    {{ $page.props.auth.user.name }}
                                </div>
                                <div class="text-sm font-medium text-gray-500">
                                    {{ $page.props.auth.user.email }}
                                </div>
                            </div>

                            <div class="space-y-1">
                                <div @click="closeMenu">
                                    <ResponsiveNavLink 
                                        :href="route('profile.edit')"
                                    >
                                        Profile
                                    </ResponsiveNavLink>
                                </div>
                                <div @click="closeMenu">
                                    <ResponsiveNavLink
                                        :href="route('logout')"
                                        method="post"
                                        as="button"
                                    >
                                        Log Out
                                    </ResponsiveNavLink>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Page Heading -->
            <header
                class="bg-white shadow"
                v-if="$slots.header"
            >
                <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <!-- Page Content -->
            <main>
                <FlashMessages />
                <slot />
            </main>
        </div>
    </div>
</template>
