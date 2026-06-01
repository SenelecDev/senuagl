<template>
  <v-container fluid class="admin-home-view pa-6">
    <div class="dashboard-hero">
      <div class="hero-text">
        <h1>Tableau de bord administrateur</h1>
        <p>Vue moderne des opérations, des performances et des activités récentes.</p>
      </div>
      <div class="hero-actions">
        <v-btn color="primary" rounded elevation="3">Rapport mensuel</v-btn>
        <v-btn variant="tonal" rounded elevation="3">Paramètres</v-btn>
      </div>
    </div>

    <v-row class="kpi-row" dense>
      <v-col v-for="kpi in kpis" :key="kpi.title" cols="12" sm="6" md="3">
        <v-card elevation="3" class="kpi-card">
          <div class="kpi-card-top">
            <v-avatar :class="kpi.color" size="48">
              <v-icon :icon="kpi.icon" color="white" size="24" />
            </v-avatar>
            <span class="kpi-label">{{ kpi.title }}</span>
          </div>
          <div class="kpi-card-value">{{ kpi.value }}</div>
          <div class="kpi-card-subtitle">{{ kpi.subtitle }}</div>
        </v-card>
      </v-col>
    </v-row>

    <v-row dense class="main-grid">
      <v-col cols="12" xl="8">
        <v-card elevation="3" class="summary-card">
          <div class="section-header">
            <div>
              <h2>Résumé de la semaine</h2>
              <p>Indicateurs clés et actions recommandées pour l'administration.</p>
            </div>
            <v-chip color="purple" text-color="white" size="small">En direct</v-chip>
          </div>

          <div class="summary-grid">
            <div class="summary-item" v-for="item in weekSummary" :key="item.label">
              <div class="summary-stat">{{ item.value }}</div>
              <div class="summary-label">{{ item.label }}</div>
              <div class="summary-footer">
                <v-icon :color="item.color" size="18">{{ item.icon }}</v-icon>
                <span>{{ item.change }}</span>
              </div>
            </div>
          </div>
        </v-card>

        <v-card elevation="3" class="action-card mt-6">
          <div class="section-header">
            <div>
              <h2>Actions prioritaires</h2>
              <p>Suivez les éléments qui demandent votre attention.</p>
            </div>
          </div>

          <v-list>
            <v-list-item
              v-for="action in priorityActions"
              :key="action.id"
              class="priority-action"
            >
              <v-list-item-avatar>
                <v-icon :icon="action.icon" size="20" />
              </v-list-item-avatar>
              <v-list-item-content>
                <v-list-item-title>{{ action.title }}</v-list-item-title>
                <v-list-item-subtitle>{{ action.subtitle }}</v-list-item-subtitle>
              </v-list-item-content>
              <v-list-item-action>
                <v-chip :color="action.tagColor" small>{{ action.tag }}</v-chip>
              </v-list-item-action>
            </v-list-item>
          </v-list>
        </v-card>
      </v-col>

      <v-col cols="12" xl="4">
        <v-card elevation="3" class="recent-activity-card">
          <div class="section-header">
            <div>
              <h2>Activités récentes</h2>
              <p>Dernières actions sur le compte admin.</p>
            </div>
            <v-btn text size="small">Voir tout</v-btn>
          </div>

          <v-timeline align="start" density="comfortable">
            <v-timeline-item
              v-for="item in recentActivities"
              :key="item.id"
              :icon="item.icon"
              :color="item.color"
              :title="item.title"
              :subtitle="item.subtitle"
            >
              <div class="timeline-content">
                <p>{{ item.description }}</p>
                <span class="timeline-time">{{ item.time }}</span>
              </div>
            </v-timeline-item>
          </v-timeline>
        </v-card>

        <v-card elevation="3" class="status-card mt-6">
          <div class="section-header">
            <div>
              <h2>Statut du système</h2>
              <p>Performance globale et sécurité.</p>
            </div>
          </div>
          <div class="status-grid">
            <div class="status-item" v-for="metric in systemStatus" :key="metric.label">
              <div class="status-circle" :style="{ background: metric.bg }">
                <v-icon :icon="metric.icon" color="white" size="20" />
              </div>
              <div>
                <div class="status-value">{{ metric.value }}</div>
                <div class="status-label">{{ metric.label }}</div>
              </div>
            </div>
          </div>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import { demandesApi } from "@/services/api";
import { useUsersAdminStore } from "@/stores/usersAdmin";
import { useDepartmentsStore } from "@/stores/departments";

const usersAdminStore = useUsersAdminStore();
const departmentsStore = useDepartmentsStore();

const totalUsers = computed(() => usersAdminStore.totalUsers);
const usersByRole = computed(() => usersAdminStore.usersByRole);
const totalDepartments = computed(() => departmentsStore.totalDepartments);
const loadingUsers = computed(() => usersAdminStore.usersLoading);
const loadingDepartments = computed(() => departmentsStore.loading);

const demandesEnAttente = ref(0);

onMounted(async () => {
  try {
    await Promise.all([
      usersAdminStore.fetchUsers(1, 100, "", true),
      departmentsStore.fetchDepartments(),
    ]);
    const response = await demandesApi.demandesAValider();
    if (response.data.success) {
      demandesEnAttente.value = (response.data.data.data || []).filter(
        (d) => d.statut === "en_attente"
      ).length;
    }
  } catch (e) {
    console.error(e);
  }
});

const kpis = computed(() => [
  {
    title: "Utilisateurs actifs",
    value: loadingUsers.value ? "..." : totalUsers.value,
    subtitle: "En progression hebdomadaire",
    icon: "mdi-account-group",
    color: "gradient-1",
  },
  {
    title: "Départements",
    value: loadingDepartments.value ? "..." : totalDepartments.value,
    subtitle: "Structure organisationnelle",
    icon: "mdi-office-building",
    color: "gradient-2",
  },
  {
    title: "Demandes en attente",
    value: demandesEnAttente.value,
    subtitle: "Traitement nécessaire",
    icon: "mdi-file-clock",
    color: "gradient-3",
  },
  {
    title: "Logs (24h)",
    value: "53",
    subtitle: "Activité du serveur",
    icon: "mdi-alert-circle-outline",
    color: "gradient-4",
  },
]);

const weekSummary = ref([
  {
    label: "Demandes validées",
    value: "18",
    change: "+12 %",
    icon: "mdi-check-circle-outline",
    color: "success",
  },
  {
    label: "Nouvelles demandes",
    value: "42",
    change: "+8 %",
    icon: "mdi-file-document-multiple-outline",
    color: "info",
  },
  {
    label: "Nouveaux comptes",
    value: "9",
    change: "+45 %",
    icon: "mdi-account-plus-outline",
    color: "purple",
  },
  {
    label: "Taux de réponse",
    value: "94 %",
    change: "+4 %",
    icon: "mdi-speedometer",
    color: "orange",
  },
]);

const priorityActions = ref([
  {
    id: 1,
    icon: "mdi-shield-lock-outline",
    title: "Revue de sécurité",
    subtitle: "Vérifier les permissions des administrateurs",
    tag: "Urgent",
    tagColor: "red-lighten-4",
  },
  {
    id: 2,
    icon: "mdi-account-check-outline",
    title: "Approver demandes",
    subtitle: "12 demandes en attente de validation",
    tag: "Important",
    tagColor: "yellow-lighten-3",
  },
  {
    id: 3,
    icon: "mdi-chart-line",
    title: "Analyse des performances",
    subtitle: "Consulter les tendances mensuelles",
    tag: "Statut",
    tagColor: "cyan-lighten-4",
  },
]);

const recentActivities = ref([
  {
    id: 1,
    icon: "mdi-account-plus",
    color: "green",
    title: "Utilisateur créé",
    subtitle: "A. Ndiaye",
    description: "Un nouveau compte employé a été ajouté depuis le service RH.",
    time: "5 min ago",
  },
  {
    id: 2,
    icon: "mdi-office-building-cog",
    color: "orange",
    title: "Département mis à jour",
    subtitle: "IT - Transformation digitale",
    description: "La structure du département IT a été réorganisée.",
    time: "2 h ago",
  },
  {
    id: 3,
    icon: "mdi-lock-alert",
    color: "red",
    title: "Tentative de connexion",
    subtitle: "admin",
    description: "Une connexion a échoué après 3 tentatives incorrectes.",
    time: "3 h ago",
  },
  {
    id: 4,
    icon: "mdi-shield-check",
    color: "blue",
    title: "Sécurité renforcée",
    subtitle: "Paramètres mis à jour",
    description: "Les politiques de mot de passe ont été ajustées.",
    time: "5 h ago",
  },
  {
    id: 5,
    icon: "mdi-calendar-star",
    color: "purple",
    title: "Jour férié ajouté",
    subtitle: "Ascension",
    description: "Une nouvelle date de congé a été ajoutée au calendrier RH.",
    time: "1 day ago",
  },
]);

const systemStatus = ref([
  {
    label: "Uptime",
    value: "99.9 %",
    icon: "mdi-server",
    bg: "linear-gradient(135deg, #4f46e5, #3b82f6)",
  },
  {
    label: "Sécurité",
    value: "Protéger",
    icon: "mdi-shield-lock",
    bg: "linear-gradient(135deg, #059669, #10b981)",
  },
  {
    label: "Base de données",
    value: "Stable",
    icon: "mdi-database",
    bg: "linear-gradient(135deg, #f97316, #fb923c)",
  },
]);
</script>

<style scoped>
.admin-home-view {
  background: radial-gradient(circle at top left, rgba(59, 130, 246, 0.08), transparent 35%),
    #f7fafc;
}
.dashboard-hero {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 18px;
  margin-bottom: 24px;
  padding: 24px 24px 16px;
  border-radius: 24px;
  background: linear-gradient(135deg, #0f172a 0%, #111827 100%);
  color: #fff;
}
.hero-text h1 {
  margin: 0;
  font-size: 2rem;
  font-weight: 700;
}
.hero-text p {
  color: rgba(255, 255, 255, 0.72);
  margin: 8px 0 0;
}
.hero-actions {
  display: flex;
  gap: 12px;
}
.kpi-row {
  margin-bottom: 24px;
}
.kpi-card {
  min-height: 170px;
  border-radius: 18px;
  padding: 20px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  background: #ffffff;
}
.kpi-card-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 14px;
}
.kpi-label {
  color: #6b7280;
  font-weight: 600;
  letter-spacing: 0.02em;
}
.kpi-card-value {
  font-size: 2.25rem;
  font-weight: 700;
  margin: 16px 0 8px;
}
.kpi-card-subtitle {
  color: #6b7280;
}
.gradient-1 {
  background: linear-gradient(135deg, #2563eb, #0ea5e9);
}
.gradient-2 {
  background: linear-gradient(135deg, #10b981, #14b8a6);
}
.gradient-3 {
  background: linear-gradient(135deg, #f97316, #fb8c00);
}
.gradient-4 {
  background: linear-gradient(135deg, #8b5cf6, #6366f1);
}
.main-grid {
  gap: 24px;
}
.summary-card,
.recent-activity-card,
.action-card,
.status-card {
  border-radius: 22px;
  padding: 24px;
}
.section-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 16px;
  margin-bottom: 18px;
}
.section-header h2 {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 700;
}
.section-header p {
  margin: 6px 0 0;
  color: #6b7280;
}
.summary-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 16px;
}
.summary-item {
  padding: 18px;
  border-radius: 16px;
  background: #f8fafc;
  min-height: 120px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
}
.summary-stat {
  font-size: 1.75rem;
  font-weight: 700;
}
.summary-label {
  color: #4b5563;
  margin-top: 8px;
}
.summary-footer {
  display: flex;
  align-items: center;
  gap: 8px;
  color: #374151;
  font-size: 0.95rem;
}
.action-card {
  background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
}
.priority-action {
  padding: 16px 0;
  border-bottom: 1px solid #e5e7eb;
}
.priority-action:last-child {
  border-bottom: none;
}
.timeline-content {
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.timeline-time {
  color: #6b7280;
  font-size: 0.9rem;
}
.status-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 14px;
}
.status-item {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 16px;
  border-radius: 16px;
  background: #f8fafc;
}
.status-circle {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  display: grid;
  place-items: center;
}
.status-value {
  font-weight: 700;
}
.status-label {
  color: #6b7280;
  font-size: 0.95rem;
}
@media (max-width: 1280px) {
  .dashboard-hero {
    flex-direction: column;
    align-items: flex-start;
  }
  .hero-actions {
    width: 100%;
    justify-content: flex-start;
  }
}
@media (max-width: 960px) {
  .summary-grid {
    grid-template-columns: 1fr;
  }
  .status-grid {
    grid-template-columns: 1fr;
  }
}
</style>
