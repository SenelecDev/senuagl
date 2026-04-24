<template>
  <v-container fluid class="logs-view">
    <v-card class="rounded-lg" elevation="2">
      <v-card-title class="d-flex align-center pa-4">
        <span class="text-h5 font-weight-bold">Journaux d'Activité</span>
        <v-spacer></v-spacer>
        <v-select
          v-model="selectedLevels"
          :items="logLevels"
          label="Filtrer par niveau"
          multiple
          chips
          clearable
          variant="outlined"
          density="compact"
          hide-details
          class="mr-4"
          style="max-width: 350px"
        ></v-select>
        <v-text-field
          v-model="search"
          append-inner-icon="mdi-magnify"
          label="Rechercher..."
          variant="outlined"
          density="compact"
          hide-details
          style="max-width: 350px"
        ></v-text-field>
      </v-card-title>

      <v-data-table
  :headers="headers"
  :items="filteredLogs"
  :loading="loading"
  :items-per-page="15"
  class="elevation-0"
  hover
>
  <template v-slot:item.level="{ item }">
    <v-chip :color="getLevelColor(item.level)" dark small label>
      {{ item.level }}
    </v-chip>
  </template>
  <template v-slot:item.timestamp="{ item }">
    <span>{{ new Date(item.timestamp).toLocaleString('fr-FR') }}</span>
  </template>
</v-data-table>
    </v-card>
  </v-container>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import { adminApi } from "@/services/api";

const search = ref("");
const logLevels = ["INFO", "WARN", "ERROR", "SUCCESS"];
const selectedLevels = ref([]);
const logs = ref([]);
const loading = ref(false);

onMounted(async () => {
  loading.value = true;
  try {
    const response = await adminApi.activityLogs();
    if (response.data.success) {
      logs.value = (response.data.data.data || []).map(log => ({
        id: log.id,
        timestamp: log.created_at,
        level: log.level,
        message: log.message,
        user: log.user ? `${log.user.first_name} ${log.user.name}` : 'Système',
        module: log.module || '—',
      }));
    }
  } catch (e) {
    console.error('Erreur chargement logs:', e);
  } finally {
    loading.value = false;
  }
});

const filteredLogs = computed(() => {
  if (selectedLevels.value.length === 0) return logs.value;
  return logs.value.filter(log => selectedLevels.value.includes(log.level));
});

const getLevelColor = (level) => {
  const colors = {
    INFO: "blue-grey",
    WARN: "orange-darken-2",
    ERROR: "red-darken-2",
    SUCCESS: "green-darken-1",
  };
  return colors[level] || "grey";
};

const headers = ref([
  { title: "Date", key: "timestamp", width: "200px" },
  { title: "Niveau", key: "level", align: "center", width: "120px" },
  { title: "Utilisateur", key: "user", width: "150px" },
  { title: "Module", key: "module", width: "120px" },
  { title: "Message", key: "message", sortable: false },
]);
</script>

<style scoped>
.logs-view {
  background-color: #f4f6f8;
}
.rounded-lg {
  border-radius: 12px;
}
</style>
