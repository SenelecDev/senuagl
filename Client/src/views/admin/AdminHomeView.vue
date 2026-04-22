<template>
  <v-container fluid class="admin-home-view pa-6">
    <!-- Cartes de Statistiques (KPIs) -->
    <v-row>
      <v-col v-for="kpi in kpis" :key="kpi.title" cols="12" sm="6" md="3">
        <v-card elevation="2" class="rounded-lg">
          <v-card-text class="d-flex align-center">
            <v-avatar :color="kpi.color" rounded size="56" class="elevation-1">
              <v-icon :icon="kpi.icon" size="x-large" color="white"></v-icon>
            </v-avatar>
            <div class="ml-4">
              <p class="text-h5 font-weight-bold">{{ kpi.value }}</p>
              <p class="text-subtitle-2 text-grey-darken-1">{{ kpi.title }}</p>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <v-row>
      <!-- Activités récentes -->
      <v-col cols="12" md="7">
        <v-card elevation="2" class="rounded-lg">
          <v-card-title class="font-weight-bold"
            >Activités Récentes</v-card-title
          >
          <v-divider></v-divider>
          <v-card-text>
            <v-timeline align="start" density="compact">
              <v-timeline-item
                v-for="item in recentActivities"
                :key="item.id"
                :dot-color="item.color"
                size="x-small"
              >
                <div class="d-flex justify-space-between">
                  <strong class="mr-4">{{ item.text }}</strong>
                  <div class="text-caption text-grey">{{ item.time }}</div>
                </div>
              </v-timeline-item>
            </v-timeline>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- Répartition des Rôles & Accès Rapide -->
      <v-col cols="12" md="5" class="d-flex flex-column ga-6">
        <v-card elevation="2" class="rounded-lg">
          <v-card-title class="font-weight-bold"
            >Utilisateurs par Rôle</v-card-title
          >
          <v-divider></v-divider>
          <v-list-item v-for="(count, role) in usersByRole" :key="role">
            <v-list-item-title class="font-weight-medium">{{
              role
            }}</v-list-item-title>
            <v-list-item-subtitle
              >{{ count }} utilisateur(s)</v-list-item-subtitle
            >
            <template v-slot:append>
              <v-progress-circular
                :model-value="totalUsers > 0 ? (count / totalUsers) * 100 : 0"
                :color="getRoleColor(role)"
                size="40"
              >
                <small
                  >{{
                    totalUsers > 0 ? Math.round((count / totalUsers) * 100) : 0
                  }}%</small
                >
              </v-progress-circular>
            </template>
          </v-list-item>
        </v-card>

        <v-card elevation="2" class="rounded-lg">
          <v-card-title class="font-weight-bold">Accès Rapide</v-card-title>
          <v-divider></v-divider>
          <v-list-item
            link
            to="/admin/users"
            prepend-icon="mdi-account-plus-outline"
            title="Ajouter un utilisateur"
          ></v-list-item>
          <v-list-item
            link
            to="/admin/departments"
            prepend-icon="mdi-office-building-plus-outline"
            title="Créer un département"
          ></v-list-item>
          <v-list-item
            link
            to="/admin/planning"
            prepend-icon="mdi-calendar-plus"
            title="Planifier des congés"
          ></v-list-item>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import { demandesApi } from "@/services/api";
import { useUserStore } from "@/stores/users";
import { useUsersAdminStore } from "@/stores/usersAdmin";
import { useDepartmentsStore } from "@/stores/departments";

// Stores
const userStore = useUserStore();
const usersAdminStore = useUsersAdminStore();
const departmentsStore = useDepartmentsStore();

// Data from stores
const users = computed(() => usersAdminStore.users);
const totalUsers = computed(() => usersAdminStore.totalUsers);
const usersByRole = computed(() => usersAdminStore.usersByRole);
const departments = computed(() => departmentsStore.departments);
const totalDepartments = computed(() => departmentsStore.totalDepartments);

// Loading states
const loadingUsers = computed(() => usersAdminStore.usersLoading);
const loadingDepartments = computed(() => departmentsStore.loading);
 const demandesEnAttente = ref(0);
// Initialize data
onMounted(async () => {
  try {
    await Promise.all([
      usersAdminStore.fetchUsers(1, 100, '', true),
      departmentsStore.fetchDepartments()
    ]);
    const r = await demandesApi.demandesAValider();
    if (r.data.success) {
      demandesEnAttente.value = (r.data.data.data || []).filter(d => d.statut === 'en_attente').length;
    }
  } catch(e) {
    console.error(e);
  }
})

// KPIs
const kpis = computed(() => [
  {
    title: "Utilisateurs Actifs",
    value: loadingUsers.value ? "..." : totalUsers.value,
    icon: "mdi-account-group",
    color: "blue-darken-1",
  },
  {
    title: "Départements",
    value: loadingDepartments.value ? "..." : totalDepartments.value,
    icon: "mdi-office-building",
    color: "green-darken-1",
  },
  {
    title: "Demandes en Attente",
    value:  demandesEnAttente.value,
    icon: "mdi-file-clock",
    color: "orange-darken-1",
  }, // Donnée statique pour l'exemple
  {
    title: "Logs (24h)",
    value: "53",
    icon: "mdi-alert-circle-outline",
    color: "red-darken-1",
  }, // Donnée statique pour l'exemple
]);

// Activités récentes (données factices)
const recentActivities = ref([
  {
    id: 1,
    text: 'Nouveau utilisateur "A. Ndiaye" créé.',
    time: "il y a 5 min",
    color: "green",
  },
  {
    id: 2,
    text: 'Département "IT" mis à jour.',
    time: "il y a 2 heures",
    color: "orange",
  },
  {
    id: 3,
    text: 'Connexion échouée pour "admin".',
    time: "il y a 3 heures",
    color: "red",
  },
  {
    id: 4,
    text: "Paramètres de sécurité modifiés.",
    time: "il y a 5 heures",
    color: "blue",
  },
]);

// Couleurs des rôles
const getRoleColor = (role) => {
  const colors = {
    admin: "red-darken-2",
    directeur: "purple-darken-2",
    superieur: "cyan-darken-2",
    employe: "green-darken-2",
  };
  return colors[role.toLowerCase()] || "grey";
};
</script>

<style scoped>
.admin-home-view {
  background-color: #f4f6f8;
}
.rounded-lg {
  border-radius: 12px;
}
.v-list-item {
  padding-block: 8px;
}
</style>
