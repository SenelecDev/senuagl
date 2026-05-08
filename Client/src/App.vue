<template>
  <v-app>
    <Sidebar v-if="!isLoginPage" :is-open="sidebarOpen" />
    <div
      class="dashboard-content"
      :class="{ 'sidebar-closed': !sidebarOpen, 'auth-layout': isLoginPage }"
    >
      <Header :sidebar-open="sidebarOpen" @toggle-sidebar="toggleSidebar" />
      <div class="content-container">
        <router-view />
      </div>
    </div>
    <!-- PrimeVue Toast global -->
    <Toast position="bottom-right" />
  </v-app>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useRoute } from 'vue-router'
import Sidebar from '@/components/layout/Sidebar.vue'
import Header from '@/components/layout/Header.vue'
import Toast from 'primevue/toast'

const sidebarOpen = ref(true)
const route = useRoute()
const isLoginPage = computed(() => route.path === '/login')

const toggleSidebar = () => {
  sidebarOpen.value = !sidebarOpen.value
}
</script>

<style>
:root {
  /* Couleurs SENELEC */
  --primary-color: #008a9b; /* Bleu-vert */
  --secondary-color: #261555; /* Violet foncé */
  --tertiary-color: #100644; /* Bleu très foncé */
}

* {
  box-sizing: border-box;
  margin: 0;
  padding: 0;
}

body,
html {
  font-family: "Inter", "Roboto", Arial, sans-serif;
  font-size: 15px;
  height: 100%;
  width: 100%;
  margin: 0;
  padding: 0;
  overflow-x: hidden;
  background-color: #f5f5f5;
}

h1 {
  font-size: 2rem;
  font-weight: 600;
  color: var(--primary-color);
}

h2 {
  font-size: 1.5rem;
  font-weight: 600;
  color: var(--primary-color);
}

h3 {
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--primary-color);
}

p {
  margin-bottom: 1rem;
  line-height: 1.6;
}

a {
  color: var(--primary-color);
  text-decoration: none;
}

a:hover {
  text-decoration: underline;
}

.btn-primary {
  background-color: var(--primary-color);
  color: white;
  border: none;
  padding: 0.5rem 1rem;
  border-radius: 0.375rem;
  cursor: pointer;
  transition: background-color 0.3s ease;
}

.btn-primary:hover {
  background-color: var(--secondary-color);
}

.card {
  background-color: white;
  border-radius: 0.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  padding: 1.5rem;
  margin-bottom: 1rem;
}

.form-group {
  margin-bottom: 1rem;
}

.form-label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 500;
  color: #374151;
}

.form-input {
  width: 100%;
  padding: 0.5rem;
  border: 1px solid #d1d5db;
  border-radius: 0.375rem;
  font-size: 1rem;
}

.form-input:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgba(0, 138, 155, 0.1);
}

.table {
  width: 100%;
  border-collapse: collapse;
  margin-bottom: 1rem;
}

.table th,
.table td {
  padding: 0.75rem;
  text-align: left;
  border-bottom: 1px solid #e5e7eb;
}

.table th {
  background-color: #f9fafb;
  font-weight: 600;
  color: #374151;
}

.table tbody tr:hover {
  background-color: #f9fafb;
}

.badge {
  display: inline-block;
  padding: 0.25rem 0.5rem;
  font-size: 0.75rem;
  font-weight: 600;
  border-radius: 0.375rem;
  text-transform: uppercase;
}

.badge-success {
  background-color: #d1fae5;
  color: #065f46;
}

.badge-warning {
  background-color: #fef3c7;
  color: #92400e;
}

.badge-danger {
  background-color: #fee2e2;
  color: #991b1b;
}

.alert {
  padding: 1rem;
  border-radius: 0.375rem;
  margin-bottom: 1rem;
}

.alert-success {
  background-color: #d1fae5;
  color: #065f46;
  border: 1px solid #a7f3d0;
}

.alert-warning {
  background-color: #fef3c7;
  color: #92400e;
  border: 1px solid #fde68a;
}

.alert-danger {
  background-color: #fee2e2;
  color: #991b1b;
  border: 1px solid #fecaca;
}

.loading {
  display: flex;
  justify-content: center;
  align-items: center;
  height: 200px;
}

.spinner {
  border: 4px solid #f3f3f3;
  border-top: 4px solid var(--primary-color);
  border-radius: 50%;
  width: 40px;
  height: 40px;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

/* Styles pour les sidebars */
.sidebar {
  width: 250px;
  height: 100vh;
  background-color: var(--primary-color);
  backdrop-filter: blur(10px);
  color: white;
  padding: 0;
  display: flex;
  flex-direction: column;
  position: fixed;
  left: 0;
  top: 0;
  z-index: 100;
  box-shadow: 4px 0 25px rgba(0, 0, 0, 0.1);
  transition: all 0.3s ease;
}

.sidebar.is-open {
  transform: translateX(0%);
}

.sidebar:not(.is-open) {
  transform: translateX(-250px);
}

.sidebar-nav {
  padding: 20px 0;
  flex-grow: 1;
  overflow-y: auto;
  scrollbar-width: none;
  -ms-overflow-style: none;
}

.sidebar-nav::-webkit-scrollbar {
  display: none;
}

.nav-item {
  display: flex;
  align-items: flex-start;
  padding: 12px 20px;
  color: rgba(255, 255, 255, 0.8);
  text-decoration: none;
  transition: all 0.3s ease;
  position: relative;
  margin: 4px 8px;
  border-radius: 8px;
  min-height: 48px;
}

.nav-item i {
  width: 24px;
  margin-right: 12px;
  font-size: 18px;
  margin-top: 2px;
  flex-shrink: 0;
}

.nav-item span {
  flex: 1;
  line-height: 1.4;
  font-size: 14px;
  white-space: normal;
  word-wrap: break-word;
}

.nav-item:hover {
  color: white;
  background-color: rgba(255, 255, 255, 0.1);
}

.nav-item.active {
  color: white;
  background-color: rgba(255, 255, 255, 0.15);
  font-weight: 600;
}

.nav-item.active::before {
  content: '';
  position: absolute;
  left: 0;
  top: 0;
  bottom: 0;
  width: 4px;
  background-color: white;
  border-radius: 0 4px 4px 0;
}

.nav-item.active i,
.nav-item:hover i {
  color: white;
}

.user-profile-section {
  background-color: var(--secondary-color);
  padding: 20px 0;
}

.logo-container {
  text-align: center;
  padding: 20px 0;
}

.senelec-logo {
  max-width: 80%;
  height: auto;
}

/* Styles pour les toolbars */
.dashboard-toolbar-card {
  border-radius: 0 !important;
  box-shadow: none !important;
  padding: 0 !important;
  margin: 0 !important;
}

.dashboard-toolbar {
  min-height: 64px;
  padding-left: 8px;
  padding-right: 8px;
  height: 64px;
  color: white !important;
  border-radius: 0 !important;
}

.toolbar-actions {
  display: flex;
  align-items: center;
  gap: 8px;
}

.hamburger-btn {
  margin-right: 8px;
}

.logout-btn-toolbar {
  color: white;
  background: none;
  border: none;
  cursor: pointer;
  padding: 8px;
  border-radius: 4px;
  transition: background-color 0.3s ease;
}

.logout-btn-toolbar:hover {
  background-color: rgba(255, 255, 255, 0.1);
}

.logout-btn-employe {
  width: 100%;
  padding: 12px;
  background-color: var(--secondary-color);
  color: white;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-size: 14px;
  font-weight: 600;
  transition: background-color 0.3s ease;
}

.logout-btn-employe:hover {
  background-color: var(--tertiary-color);
}

/* Styles pour les vues */
.dashboard-content {
  margin-top: 64px;
  transition: margin-left 0.3s ease;
}

.dashboard-content.sidebar-closed {
  margin-left: 0;
}

.dashboard-content:not(.sidebar-closed) {
  margin-left: 250px;
}

.dashboard-content.auth-layout {
  margin-left: 0;
  margin-top: 0;
}

.content-container {
  padding: 24px;
  min-height: calc(100vh - 64px);
  background-color: #f5f5f5;
}

.auth-layout .content-container {
  min-height: 100vh;
  padding: 0;
}

/* Responsive */
@media (max-width: 768px) {
  .sidebar {
    transform: translateX(-250px);
  }

  .sidebar.is-open {
    transform: translateX(0%);
  }

  .dashboard-toolbar {
    margin-left: 0 !important;
    width: 100vw !important;
  }

  .dashboard-content {
    margin-left: 0 !important;
  }

  .content-container {
    padding: 16px;
  }
}
</style>
