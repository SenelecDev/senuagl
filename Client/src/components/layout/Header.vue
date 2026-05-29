<template>
  <v-card v-if="!isLoginPage" height="64" flat class="dashboard-toolbar-card" :style="cardStyle">
    <v-toolbar class="text-white dashboard-toolbar" :style="toolbarStyle">
      <v-btn
        icon="mdi-menu"
        @click="$emit('toggle-sidebar')"
        class="hamburger-btn"
      ></v-btn>
      <v-toolbar-title class="text-center">{{ title }}</v-toolbar-title>
      <div class="toolbar-actions">
        <v-btn icon @click="logout" class="logout-btn-toolbar">
          <v-icon>mdi-logout</v-icon>
        </v-btn>
      </div>
    </v-toolbar>
  </v-card>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const props = defineProps({
  sidebarOpen: {
    type: Boolean,
    default: true
  }
})

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const isLoginPage = computed(() => route.path === '/login')
const title = computed(() => {
  const titles = {
    '/': 'Tableau de bord',
    '/agents': 'Gestion des agents',
    '/postes': 'Gestion des postes',
    '/postes-vacants': 'Postes vacants',
    '/statistiques': 'Statistiques',
    '/avancements': 'Historique des avancements',
    '/budget': 'Suivi Budgétaire'
  }
  return titles[route.path] || 'UAGL - DSI'
})

const cardStyle = computed(() => {
  let style = {
    position: 'fixed',
    top: '0',
    left: '0',
    height: '64px',
    zIndex: '2000',
    transition: 'left 0.3s ease, width 0.3s ease'
  };
  if (props.sidebarOpen) {
    style.left = '250px';
    style.width = 'calc(100vw - 250px)';
  } else {
    style.left = '0';
    style.width = '100vw';
  }
  return style;
})

const toolbarStyle = computed(() => {
  return {
    background: "linear-gradient(90deg, #008a9b 0%, #261555 100%)",
    height: "64px"
  };
})

const logout = async () => {
  await authStore.logout()
  router.push('/login')
}

defineEmits(['toggle-sidebar'])
</script>
