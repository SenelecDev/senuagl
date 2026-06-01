<template>
  <v-navigation-drawer
    v-model="drawer"
    :rail="rail"
    :width="300"
    permanent
    app
    class="admin-sidebar"
  >
    <div class="sidebar-brand">
      <img src="@/assets/images/logo-senelec.png" alt="Senelec Logo" class="logo" />
      <div class="brand-text">
        <h2>Admin Senelec</h2>
        <span>Gestion RH</span>
      </div>
      <v-btn
        variant="text"
        icon="mdi-chevron-left"
        @click.stop="rail = !rail"
        class="toggle-btn"
      ></v-btn>
    </div>

    <v-divider class="sidebar-divider"></v-divider>

    <v-list density="compact" nav class="sidebar-menu">
      <v-list-item
        v-for="item in menuItems"
        :key="item.title"
        :prepend-icon="item.icon"
        :title="item.title"
        :to="item.to"
        link
        class="sidebar-item"
      ></v-list-item>
    </v-list>
  </v-navigation-drawer>
</template>

<script setup>
import { ref } from "vue";

const drawer = ref(true);
const rail = ref(false);

const menuItems = ref([
  { title: "Tableau de Bord", icon: "mdi-view-dashboard", to: "/admin/dashboard" },
  { title: "Utilisateurs", icon: "mdi-account-group", to: "/admin/users" },
  { title: "Départements", icon: "mdi-office-building", to: "/admin/departments" },
  { title: "Planification Congés", icon: "mdi-calendar-month", to: "/admin/planning" },
  { title: "Historique Demandes", icon: "mdi-history", to: "/admin/history" },
  { title: "Journaux d'Activité", icon: "mdi-post-it-note-text-outline", to: "/admin/logs" },
  { title: "Paramètres", icon: "mdi-cog", to: "/admin/settings" },
]);
</script>

<style scoped>
.admin-sidebar {
  width: 300px;
  height: 100vh;
  background: linear-gradient(180deg, #022f4f 0%, #0f253e 100%);
  color: #f7fafc;
  border-top-right-radius: 24px;
  border-bottom-right-radius: 24px;
  margin: 0 !important;
  padding: 0 !important;
  position: fixed !important;
  top: 0 !important;
  left: 0 !important;
  z-index: 2001 !important;
}
.sidebar-brand {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 22px 18px;
}
.logo {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  object-fit: cover;
  border: 2px solid rgba(255, 255, 255, 0.18);
}
.brand-text h2 {
  margin: 0;
  font-size: 1.05rem;
  font-weight: 700;
  color: #ffffff;
}
.brand-text span {
  color: rgba(255, 255, 255, 0.72);
  font-size: 0.9rem;
}
.toggle-btn {
  margin-left: auto;
  color: rgba(255, 255, 255, 0.9);
}
.sidebar-divider {
  background-color: rgba(255, 255, 255, 0.12);
}
.sidebar-menu {
  margin-top: 16px;
}
.sidebar-item {
  color: rgba(255, 255, 255, 0.95) !important;
  margin-bottom: 8px;
  border-radius: 14px;
}
.sidebar-item .v-list-item__title {
  font-weight: 600;
}
</style>
