<template>
  <v-container fluid class="demandes-history-view pa-6">
    <v-row>
      <v-col cols="12">
        <h1 class="text-h4 font-weight-bold text-grey-darken-3">
          Historique des Demandes
        </h1>
        <p class="text-subtitle-1 text-grey-darken-1">
          Suivi de toutes les demandes de congé et de leur état d'approbation.
        </p>
      </v-col>
    </v-row>

    <v-row>
      <v-col cols="12">
        <v-card elevation="2" class="rounded-lg">
          <v-card-title class="d-flex align-center pe-2">
            <v-icon icon="mdi-history"></v-icon>
            <span class="ms-2 font-weight-bold">Toutes les demandes</span>
            <v-spacer></v-spacer>
            <v-text-field
              v-model="search"
              density="compact"
              label="Rechercher"
              prepend-inner-icon="mdi-magnify"
              variant="solo-filled"
              flat
              hide-details
              single-line
            ></v-text-field>
          </v-card-title>
          <v-divider></v-divider>
          <v-data-table
            :headers="headers"
            :items="allDemandes"
            :search="search"
            :loading="loading"
            loading-text="Chargement des données..."
            no-data-text="Aucune demande trouvée."
            items-per-page-text="Demandes par page"
            class="elevation-0"
          >
            <template v-slot:item.status="{ value }">
              <v-chip :color="getStatusColor(value)" variant="elevated" size="small">
                {{ getStatusText(value) }}
              </v-chip>
            </template>
            <template v-slot:item.niveauApprobation="{ value }">
              <v-chip :color="getNiveauColor(value)" variant="tonal" size="small">
                {{ value }}
              </v-chip>
            </template>
            <template v-slot:item.nom="{ item }">
              {{ item.prenom }} {{ item.nom }}
            </template>
          </v-data-table>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import { adminApi } from "@/services/api";

const search = ref("");
const demandes = ref([]);
const loading = ref(false);

onMounted(async () => {
  loading.value = true;
  try {
    const response = await adminApi.demandes();
    if (response.data.success) {
      demandes.value = response.data.data.data || [];
    }
  } catch (e) {
    console.error('Erreur chargement historique:', e);
  } finally {
    loading.value = false;
  }
});

const allDemandes = computed(() =>
  demandes.value.map(d => ({
    nom: d.user?.name || '—',
    prenom: d.user?.first_name || '—',
    matricule: d.user?.matricule || '—',
    typeDemande: d.type_label || d.type_demande,
    dateDebut: d.date_debut ? new Date(d.date_debut).toLocaleDateString('fr-FR') : '—',
    dateFin: d.date_fin ? new Date(d.date_fin).toLocaleDateString('fr-FR') : '—',
    status: d.statut,
    niveauApprobation: d.statut === 'approuve' ? 'Approuvé'
      : d.statut === 'rejete' ? 'Rejeté' : 'En attente',
  }))
);

const headers = ref([
  { title: "Employé", key: "nom", sortable: true },
  { title: "Matricule", key: "matricule", sortable: true },
  { title: "Type", key: "typeDemande", sortable: true },
  { title: "Date Début", key: "dateDebut", sortable: true },
  { title: "Date Fin", key: "dateFin", sortable: true },
  { title: "Statut", key: "status", sortable: true },
  { title: "Niveau d'Approbation", key: "niveauApprobation", sortable: true },
]);

const getStatusColor = (status) => {
  const colors = {
    en_attente: "orange-darken-2",
    approuve: "green-darken-1",
    rejete: "red-darken-2",
  };
  return colors[status] || "grey";
};

const getStatusText = (status) => {
  const texts = {
    en_attente: "En attente",
    approuve: "Approuvée",
    rejete: "Rejetée",
  };
  return texts[status] || status;
};

const getNiveauColor = (niveau) => {
  if (niveau === 'Approuvé') return "green-lighten-1";
  if (niveau === 'Rejeté') return "red-lighten-1";
  return "orange-lighten-1";
};
</script>

<style scoped>
.demandes-history-view {
  background-color: #f4f6f8;
}
.rounded-lg {
  border-radius: 12px;
}
</style>