<template>
  <v-container fluid class="leave-planning-view">
    <v-card class="rounded-lg" elevation="2">
      <v-card-title class="d-flex align-center pa-4">
        <span class="text-h5 font-weight-bold">Planification Annuelle</span>
        <v-spacer></v-spacer>
        <v-btn
          color="primary"
          @click="openActiveDialog"
          prepend-icon="mdi-plus-circle"
          :key="tab"
        >
          Ajouter {{ tab === "plans" ? "une période" : "un jour férié" }}
        </v-btn>
      </v-card-title>

      <v-tabs v-model="tab" color="primary" align-tabs="center" class="mb-4">
        <v-tab value="plans">Périodes de Congé</v-tab>
        <v-tab value="holidays">Jours Fériés</v-tab>
      </v-tabs>

      <v-window v-model="tab">
        <v-window-item value="plans">
          <v-data-table
            :headers="planHeaders"
            :items="leavePlans"
            class="elevation-0"
          >
            <template v-slot:item.actions="{ item }">
              <v-icon small class="mr-2" @click="openPlanDialog(item)"
                >mdi-pencil</v-icon
              >
              <v-icon small @click="deletePlan(item.id)">mdi-delete</v-icon>
            </template>
          </v-data-table>
        </v-window-item>

        <v-window-item value="holidays">
          <v-data-table
            :headers="holidayHeaders"
            :items="holidays"
            class="elevation-0"
          >
            <template v-slot:item.actions="{ item }">
              <v-icon small class="mr-2" @click="openHolidayDialog(item)"
                >mdi-pencil</v-icon
              >
              <v-icon small @click="deleteHoliday(item.id)">mdi-delete</v-icon>
            </template>
          </v-data-table>
        </v-window-item>
      </v-window>
    </v-card>

    <!-- Dialog pour les Périodes de Congé -->
    <v-dialog v-model="planDialog" max-width="500px">
      <v-card>
        <v-card-title>{{ planFormTitle }}</v-card-title>
        <v-card-text>
          <v-text-field
            v-model="editedPlan.name"
            label="Nom de la période"
          ></v-text-field>
          <v-text-field
            v-model="editedPlan.startDate"
            label="Date de début"
            type="date"
          ></v-text-field>
          <v-text-field
            v-model="editedPlan.endDate"
            label="Date de fin"
            type="date"
          ></v-text-field>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn text @click="closePlanDialog">Annuler</v-btn>
          <v-btn color="primary" @click="savePlan">Sauvegarder</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Dialog pour les Jours Fériés -->
    <v-dialog v-model="holidayDialog" max-width="500px">
      <v-card>
        <v-card-title>{{ holidayFormTitle }}</v-card-title>
        <v-card-text>
          <v-text-field
            v-model="editedHoliday.name"
            label="Nom du jour férié"
          ></v-text-field>
          <v-text-field
            v-model="editedHoliday.date"
            label="Date"
            type="date"
          ></v-text-field>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn text @click="closeHolidayDialog">Annuler</v-btn>
          <v-btn color="primary" @click="saveHoliday">Sauvegarder</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import { planningApi } from "@/services/api";

const tab = ref("plans");
const leavePlans = ref([]);
const holidays = ref([]);
const planDialog = ref(false);
const holidayDialog = ref(false);
const editedPlan = ref({});
const editedHoliday = ref({});
const loading = ref(false);

const planHeaders = ref([
  { title: "Nom", key: "name" },
  { title: "Début", key: "start_date" },
  { title: "Fin", key: "end_date" },
  { title: "Actions", key: "actions", sortable: false },
]);

const holidayHeaders = ref([
  { title: "Nom", key: "name" },
  { title: "Date", key: "date" },
  { title: "Actions", key: "actions", sortable: false },
]);

const planFormTitle = computed(() => editedPlan.value.id ? "Modifier la Période" : "Nouvelle Période");
const holidayFormTitle = computed(() => editedHoliday.value.id ? "Modifier le Jour Férié" : "Nouveau Jour Férié");

onMounted(async () => {
  await Promise.all([fetchPlans(), fetchHolidays()]);
});

async function fetchPlans() {
  const r = await planningApi.plans.list();
  if (r.data.success) leavePlans.value = r.data.data;
}

async function fetchHolidays() {
  const r = await planningApi.holidays.list();
  if (r.data.success) holidays.value = r.data.data;
}

function openPlanDialog(plan) {
  editedPlan.value = plan ? { ...plan, startDate: plan.start_date, endDate: plan.end_date } : {};
  planDialog.value = true;
}
function closePlanDialog() { planDialog.value = false; }

async function savePlan() {
  const payload = { name: editedPlan.value.name, start_date: editedPlan.value.startDate, end_date: editedPlan.value.endDate };
  if (editedPlan.value.id) {
    await planningApi.plans.update(editedPlan.value.id, payload);
  } else {
    await planningApi.plans.create(payload);
  }
  closePlanDialog();
  await fetchPlans();
}

async function deletePlan(id) {
  if (confirm('Supprimer cette période ?')) {
    await planningApi.plans.delete(id);
    await fetchPlans();
  }
}

function openHolidayDialog(holiday) {
  editedHoliday.value = holiday ? { ...holiday } : {};
  holidayDialog.value = true;
}
function closeHolidayDialog() { holidayDialog.value = false; }

async function saveHoliday() {
  const payload = { name: editedHoliday.value.name, date: editedHoliday.value.date };
  if (editedHoliday.value.id) {
    await planningApi.holidays.update(editedHoliday.value.id, payload);
  } else {
    await planningApi.holidays.create(payload);
  }
  closeHolidayDialog();
  await fetchHolidays();
}

async function deleteHoliday(id) {
  if (confirm('Supprimer ce jour férié ?')) {
    await planningApi.holidays.delete(id);
    await fetchHolidays();
  }
}

function openActiveDialog() {
  if (tab.value === "plans") openPlanDialog();
  else openHolidayDialog();
}
</script>

<style scoped>
.leave-planning-view {
  background-color: #f4f6f8;
}
.rounded-lg {
  border-radius: 12px;
}
</style>
