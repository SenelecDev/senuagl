<template>
  <v-container fluid class="department-management-view">
    <v-card class="rounded-lg" elevation="2">
      <v-card-title class="d-flex align-center pa-4">
        <span class="text-h5 font-weight-bold">Gestion des Départements</span>
        <v-spacer></v-spacer>
        <v-text-field
          v-model="search"
          append-inner-icon="mdi-magnify"
          label="Rechercher un département..."
          variant="outlined"
          density="compact"
          hide-details
          class="mr-4"
          style="max-width: 350px"
        ></v-text-field>
        <v-btn
          color="primary"
          @click="openDialog()"
          prepend-icon="mdi-plus-circle"
        >
          Ajouter un département
        </v-btn>
      </v-card-title>

      <v-data-table
        :headers="headers"
        :items="computedDepartments"
        :search="search"
        :items-per-page="10"
        class="elevation-0"
        hover
      >
        <template v-slot:item.directorName="{ item }">
          <v-chip v-if="item.directorName" color="primary" small>{{
            item.directorName
          }}</v-chip>
          <span v-else class="text-grey">Non assigné</span>
        </template>
        <template v-slot:item.employeeCount="{ item }">
          <v-chip color="blue-grey" dark small
            >{{ item.employeeCount }} employé(s)</v-chip
          >
        </template>
        <template v-slot:item.status="{ item }">
          <v-chip
            :color="item.status === 'Actif' ? 'green' : 'grey'"
            dark
            small
            >{{ item.status }}</v-chip
          >
        </template>
        <template v-slot:item.actions="{ item }">
          <v-tooltip text="Modifier">
            <template v-slot:activator="{ props }">
              <v-icon
                v-bind="props"
                color="grey-darken-1"
                class="mr-2"
                @click="openDialog(item)"
                >mdi-pencil</v-icon
              >
            </template>
          </v-tooltip>
          <v-tooltip text="Supprimer">
            <template v-slot:activator="{ props }">
              <v-icon
                v-bind="props"
                color="red-darken-2"
                @click="confirmDelete(item)"
                >mdi-delete</v-icon
              >
            </template>
          </v-tooltip>
        </template>
      </v-data-table>
    </v-card>

    <!-- Dialog for Add/Edit Department -->
    <v-dialog v-model="dialog" max-width="500px" persistent>
      <v-card>
        <v-card-title class="pa-4 bg-primary">
          <span class="text-h5">{{ formTitle }}</span>
        </v-card-title>
        <v-card-text class="pt-6">
          <v-container>
            <v-row>
              <v-col cols="12">
                <v-text-field
                  v-model="editedDepartment.name"
                  label="Nom du département"
                  variant="outlined"
                ></v-text-field>
              </v-col>
              <v-col cols="12">
                <v-autocomplete
                  v-model="editedDepartment.manager_id"
                  :items="potentialDirectors"
                  :item-title="(item) => `${item.first_name} ${item.name}`"
                  item-value="id"
                  label="Rechercher un directeur..."
                  variant="outlined"
                  clearable
                ></v-autocomplete>
              </v-col>
              <v-col cols="12">
                <v-select
                  v-model="editedDepartment.status"
                  :items="['Actif', 'Inactif']"
                  label="Statut"
                  variant="outlined"
                ></v-select>
              </v-col>
            </v-row>
          </v-container>
        </v-card-text>
        <v-card-actions class="pa-4">
          <v-spacer></v-spacer>
          <v-btn text @click="closeDialog">Annuler</v-btn>
          <v-btn color="primary" variant="elevated" @click="saveDepartment"
            >Sauvegarder</v-btn
          >
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Confirmation Dialog for Delete -->
    <v-dialog v-model="dialogDelete" max-width="500px">
      <v-card>
        <v-card-title class="text-h5"
          >Êtes-vous sûr de vouloir supprimer ce département ?</v-card-title
        >
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn text @click="closeDeleteDialog">Annuler</v-btn>
          <v-btn color="red-darken-1" variant="elevated" @click="deleteDept"
            >Supprimer</v-btn
          >
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import { useDepartmentsStore } from "@/stores/departments";
import { useUsersAdminStore } from "@/stores/usersAdmin";
import { storeToRefs } from "pinia";

const departmentsStore = useDepartmentsStore();
const { departments } = storeToRefs(departmentsStore);

const usersStore = useUsersAdminStore();
const { users } = storeToRefs(usersStore);

const search = ref("");
const dialog = ref(false);
const dialogDelete = ref(false);
const departmentToDelete = ref(null);

const editedIndex = ref(-1);
const editedDepartment = ref({
  id: null,
  name: "",
  manager_id: null,
  status: "Actif",
});
const defaultDepartment = {
  id: null,
  name: "",
  manager_id: null,
  status: "Actif",
};

const formTitle = computed(() =>
  editedIndex.value === -1 ? "Nouveau Département" : "Modifier le Département"
);

const headers = ref([
  { title: "Nom du Département", key: "name", sortable: true },
  { title: "Directeur", key: "directorName", sortable: false },
  { title: "Nombre d'Employés", key: "employeeCount", sortable: false },
  { title: "Statut", key: "status", sortable: true },
  { title: "Actions", key: "actions", sortable: false, align: "end" },
]);

const computedDepartments = computed(() => {
  return departments.value.map((dept) => {
    // L'API retourne déjà l'objet manager dans dept.manager
    const directorName = dept.manager
      ? `${dept.manager.first_name} ${dept.manager.name}`
      : null;
    const employeeCount = users.value.filter(u => u.department_id === dept.id).length;
    return {
      ...dept,
      directorName,
      employeeCount,
      status: 'Actif',
    };
  });
});

const potentialDirectors = computed(() => {
  return users.value;
});

const openDialog = (dept) => {
  editedIndex.value = dept
    ? departments.value.findIndex((d) => d.id === dept.id)
    : -1;
  editedDepartment.value = dept
    ? { ...dept, manager_id: dept.manager_id || dept.manager?.id || null }
    : { ...defaultDepartment };
  dialog.value = true;
};

const closeDialog = () => {
  dialog.value = false;
};

const saveDepartment = async () => {
  const payload = {
    name: editedDepartment.value.name,
    manager_id: editedDepartment.value.manager_id || null,
  };
  try {
    if (editedIndex.value > -1) {
      await departmentsStore.updateDepartment(editedDepartment.value.id, payload);
    } else {
      await departmentsStore.addDepartment(payload);
    }
    closeDialog();
    await departmentsStore.fetchDepartments();
    await usersStore.fetchUsers(1, 100, '', true);
  } catch(e) {
    console.error('Erreur save département:', e);
  }
};

const confirmDelete = (dept) => {
  departmentToDelete.value = dept;
  dialogDelete.value = true;
};

const deleteDept = () => {
  if (departmentToDelete.value) {
    departmentsStore.deleteDepartment(departmentToDelete.value.id);
  }
  closeDeleteDialog();
};

const closeDeleteDialog = () => {
  dialogDelete.value = false;
  departmentToDelete.value = null;
};

// Lifecycle
onMounted(async () => {
  console.log('🏢 Chargement de la vue départements...');
  try {
    await departmentsStore.fetchDepartments();
    await usersStore.fetchUsers(); // Pour avoir les utilisateurs pour les directeurs
    console.log('✅ Départements chargés:', departmentsStore.departments.length);
  } catch (error) {
    console.error('❌ Erreur chargement départements:', error);
  }
});
</script>

<style scoped>
.department-management-view {
  background-color: #f4f6f8;
}
.rounded-lg {
  border-radius: 12px;
}
</style>
