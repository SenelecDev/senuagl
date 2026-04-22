<template>
  <v-container fluid class="user-management-view">
    <v-card class="rounded-lg" elevation="2">
      <v-card-title class="d-flex align-center pa-4">
        <span class="text-h5 font-weight-bold">Gestion des Utilisateurs</span>
        <v-spacer></v-spacer>
        <v-text-field
          v-model="search"
          append-inner-icon="mdi-magnify"
          label="Rechercher par nom, prénom, email, département, rôle..."
          variant="outlined"
          density="compact"
          hide-details
          class="mr-4"
          style="max-width: 400px"
          @keyup.enter="searchUsers"
        >
          <template v-slot:append>
            <v-tooltip
              text="Recherche dans : nom, prénom, email, matricule, département, rôle, statut"
              location="bottom"
            >
              <template v-slot:activator="{ props }">
                <v-icon v-bind="props" color="grey" size="small">
                  mdi-help-circle-outline
                </v-icon>
              </template>
            </v-tooltip>
          </template>
        </v-text-field>
        <v-btn
          variant="outlined"
          color="primary"
          @click="refreshAllData"
          class="mr-2"
          title="Actualiser les données"
        >
          <v-icon>mdi-refresh</v-icon>
        </v-btn>
        <v-btn
          color="primary"
          @click="openDialog()"
          prepend-icon="mdi-plus-circle"
        >
          Ajouter un utilisateur
        </v-btn>
      </v-card-title>

      <v-alert
        v-if="error"
        type="error"
        variant="tonal"
        closable
        class="ma-4"
        @click:close="clearError"
      >
        {{ error }}
      </v-alert>

      <v-data-table
        :headers="headers"
        :items="filteredUsers"
        :items-per-page="itemsPerPage"
        :items-per-page-options="[10, 20, 50, 100]"
        class="elevation-0"
        hover
        no-data-text="Aucun utilisateur trouvé"
      >
        <template v-slot:item.role="{ item }">
          <v-chip :color="getRoleColor(item.role?.nom || 'N/A')" dark small>
            {{ item.role?.nom || 'Non défini' }}
          </v-chip>
        </template>
        <template v-slot:item.department="{ item }">
          <span>{{ item.department?.name || 'Non défini' }}</span>
        </template>
        <template v-slot:item.is_active="{ item }">
          <v-chip :color="item.is_active ? 'green' : 'grey'" dark small>
            {{ item.is_active ? 'Actif' : 'Inactif' }}
          </v-chip>
        </template>
        <template v-slot:item.created_at="{ item }">
          {{ formatDate(item.created_at) }}
        </template>
        <template v-slot:item.actions="{ item }">
          <v-tooltip text="Modifier">
            <template v-slot:activator="{ props }">
              <v-icon v-bind="props" color="grey-darken-1" class="mr-2" @click="openDialog(item)">
                mdi-pencil
              </v-icon>
            </template>
          </v-tooltip>
          <v-tooltip :text="item.is_active ? 'Désactiver' : 'Activer'">
            <template v-slot:activator="{ props }">
              <v-icon
                v-bind="props"
                :color="item.is_active ? 'green-darken-1' : 'orange-darken-1'"
                class="mr-2"
                @click="toggleUserStatus(item)"
              >
                {{ item.is_active ? 'mdi-account-check' : 'mdi-account-off' }}
              </v-icon>
            </template>
          </v-tooltip>
          <v-tooltip text="Réinitialiser le mot de passe">
            <template v-slot:activator="{ props }">
              <v-icon v-bind="props" color="blue-darken-2" class="mr-2" @click="resetPassword(item)">
                mdi-lock-reset
              </v-icon>
            </template>
          </v-tooltip>
          <v-tooltip text="Supprimer">
            <template v-slot:activator="{ props }">
              <v-icon v-bind="props" color="red-darken-2" @click="confirmDelete(item)">
                mdi-delete
              </v-icon>
            </template>
          </v-tooltip>
        </template>
      </v-data-table>
    </v-card>

    <!-- Modal Ajouter/Modifier -->
    <UserModal
  v-model="dialog"
  :user="editedUser"
  :roles="roles"
  :departments="departments"
  :managers="potentialManagers"
  @submit="saveUser"
/>

    <!-- Confirmation suppression -->
    <v-dialog v-model="dialogDelete" max-width="500px">
      <v-card>
        <v-card-title class="text-h5">Confirmer la suppression</v-card-title>
        <v-card-text>
          Êtes-vous sûr de vouloir supprimer l'utilisateur
          <strong>{{ userToDelete?.name }} {{ userToDelete?.first_name }}</strong> ?
          <br /><br />Cette action est irréversible.
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn text @click="closeDeleteDialog">Annuler</v-btn>
          <v-btn color="red-darken-1" variant="elevated" @click="deleteUser">Supprimer</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Reset Password Modal -->
    <ResetPasswordModal
      v-model="dialogResetPassword"
      :user="userToResetPassword"
      @submit="handleResetPassword"
    />
  </v-container>
</template>

<script setup>
import { ref, computed, onMounted, watch } from "vue";
import { useUsersAdminStore } from "@/stores/usersAdmin";
import { useUserStore } from "@/stores/users";
import { useToast } from "primevue/usetoast";
import { storeToRefs } from "pinia";
import UserModal from "@/components/admin/UserModal.vue";
import ResetPasswordModal from "@/components/admin/ResetPasswordModal.vue";

const usersStore = useUsersAdminStore();
const userStore = useUserStore();
const toast = useToast();

const { users, roles, departments, error, pagination } = storeToRefs(usersStore);

const search = ref("");
const dialog = ref(false);
const dialogDelete = ref(false);
const dialogResetPassword = ref(false);
const userToDelete = ref(null);
const userToResetPassword = ref(null);
const currentPage = ref(1);
const itemsPerPage = ref(10);
const editedIndex = ref(-1);

const editedUser = ref({
  id: null, name: "", first_name: "", matricule: "",
  email: "", phone: "", department_id: null,
  role_id: null, manager_id: null, is_active: true,
  password: "", password_confirmation: "",
});

const defaultUser = {
  id: null, name: "", first_name: "", matricule: "",
  email: "", phone: "", department_id: null,
  role_id: null, manager_id: null, is_active: true,
  password: "", password_confirmation: "",
};

const filteredUsers = computed(() => {
  if (!search.value?.trim()) return users.value;
  const term = search.value.toLowerCase().trim();
  return users.value.filter(u =>
    u.name?.toLowerCase().includes(term) ||
    u.first_name?.toLowerCase().includes(term) ||
    u.email?.toLowerCase().includes(term) ||
    u.matricule?.toLowerCase().includes(term) ||
    u.department?.name?.toLowerCase().includes(term) ||
    u.role?.nom?.toLowerCase().includes(term) ||
    (u.is_active ? 'actif' : 'inactif').includes(term)
  );
});

const headers = ref([
  { title: "Nom", key: "name", sortable: false },
  { title: "Prénom", key: "first_name", sortable: false },
  { title: "Matricule", key: "matricule", sortable: false },
  { title: "Email", key: "email", sortable: false },
  { title: "Département", key: "department", sortable: false },
  { title: "Rôle", key: "role", sortable: false },
  { title: "Statut", key: "is_active", sortable: false },
  { title: "Date création", key: "created_at", sortable: false },
  { title: "Actions", key: "actions", sortable: false, align: "end" },
]);

const searchUsers = () => {};

const getRoleColor = (role) => {
  const colors = {
    Admin: "red-darken-2", "Directeur RH": "purple-darken-2",
    "Responsable RH": "indigo-darken-2", "Directeur Unite": "blue-darken-2",
    Superieur: "cyan-darken-2", Employe: "green-darken-2",
  };
  return colors[role] || "grey";
};

const formatDate = (d) => d ? new Date(d).toLocaleDateString("fr-FR") : "N/A";
const clearError = () => { usersStore.error = null; };

const openDialog = (user) => {
  if (user) {
    editedIndex.value = users.value.findIndex((u) => u.id === user.id);
    editedUser.value = {
      id: user.id,
      name: user.name,
      first_name: user.first_name,
      matricule: user.matricule,
      email: user.email,
      phone: user.phone || "",
      department_id: user.department?.id || null,
      role_id: user.role?.id || null,
      manager_id: user.manager_id || null,
      is_active: user.is_active !== undefined ? user.is_active : true,
      password: "",
      password_confirmation: "",
    };
  } else {
    editedIndex.value = -1;
    editedUser.value = { ...defaultUser };
  }
  dialog.value = true;
};

const closeDialog = () => {
  dialog.value = false;
  editedUser.value = { ...defaultUser };
  editedIndex.value = -1;
};

const saveUser = async (formData) => {
  if (!formData.name || !formData.first_name || !formData.matricule || !formData.email || !formData.department_id || !formData.role_id) {
    toast.add({ severity: "warn", summary: "Champs requis", detail: "Veuillez compléter tous les champs obligatoires.", life: 4000 });
    return;
  }
  if (editedIndex.value === -1) {
    if (!formData.password || !formData.password_confirmation) {
      toast.add({ severity: "warn", summary: "Mot de passe requis", detail: "Un mot de passe est obligatoire.", life: 4000 });
      return;
    }
    if (formData.password !== formData.password_confirmation) {
      toast.add({ severity: "error", summary: "Mots de passe différents", detail: "Les mots de passe ne correspondent pas.", life: 4000 });
      return;
    }
  }

  const mappedData = {
    prenom: formData.first_name,
    nom: formData.name,
    email: formData.email,
    matricule: formData.matricule,
    telephone: formData.phone || "",
    department_id: formData.department_id,
    role_id: formData.role_id,
    manager_id: formData.manager_id || null,
    date_embauche: new Date().toISOString().split("T")[0],
  };

  if (editedIndex.value === -1 && formData.password) {
    mappedData.password = formData.password;
  }

  try {
    if (editedIndex.value > -1) {
      await usersStore.updateUser(formData.id, mappedData);
      toast.add({ severity: "success", summary: "Utilisateur modifié", detail: `${formData.first_name} ${formData.name} mis à jour.`, life: 3000 });
    } else {
      await usersStore.addUser(mappedData);
      toast.add({ severity: "success", summary: "Utilisateur créé", detail: `${formData.first_name} ${formData.name} ajouté.`, life: 3000 });
    }
    closeDialog();
    await loadAllUsers();
  } catch (e) {
    toast.add({ severity: "error", summary: "Erreur", detail: "Impossible de sauvegarder.", life: 5000 });
  }
};

const confirmDelete = (user) => { userToDelete.value = user; dialogDelete.value = true; };

const deleteUser = async () => {
  if (!userToDelete.value) return;
  try {
    await usersStore.removeUser(userToDelete.value.id);
    toast.add({ severity: "success", summary: "Supprimé", detail: `${userToDelete.value.first_name} ${userToDelete.value.name} supprimé.`, life: 3000 });
    closeDeleteDialog();
    await loadAllUsers();
  } catch (e) {
    toast.add({ severity: "error", summary: "Erreur", detail: "Impossible de supprimer.", life: 5000 });
  }
};

const closeDeleteDialog = () => { dialogDelete.value = false; userToDelete.value = null; };

const toggleUserStatus = async (user) => {
  try {
    await usersStore.toggleUserStatus(user.id);
    const updated = users.value.find(u => u.id === user.id);
    const statusText = updated?.is_active ? "activé" : "désactivé";
    toast.add({ severity: "info", summary: `Compte ${statusText}`, detail: `${user.first_name} ${user.name} ${statusText}.`, life: 3000 });
  } catch (e) {
    toast.add({ severity: "error", summary: "Erreur", detail: "Impossible de modifier le statut.", life: 4000 });
  }
};

const resetPassword = (user) => { userToResetPassword.value = user; dialogResetPassword.value = true; };

const handleResetPassword = async (passwordData) => {
  try {
    await usersStore.resetUserPasswordWithData(userToResetPassword.value.id, passwordData);
    toast.add({ severity: "success", summary: "Mot de passe réinitialisé", detail: `Mot de passe de ${userToResetPassword.value.first_name} réinitialisé.`, life: 3000 });
    dialogResetPassword.value = false;
    userToResetPassword.value = null;
  } catch (e) {
    toast.add({ severity: "error", summary: "Erreur", detail: "Impossible de réinitialiser.", life: 4000 });
  }
};

const potentialManagers = computed(() =>
  users.value.filter(u =>
    ['Superieur', 'Directeur RH', 'Responsable RH', 'Directeur Unité'].includes(u.role?.nom)
  )
);

const refreshAllData = async () => {
  await Promise.all([usersStore.fetchRoles(true), usersStore.fetchDepartments(true), loadAllUsers()]);
};

const loadAllUsers = async () => {
  await usersStore.fetchUsers(1, 100, "", true);
};

onMounted(() => {
  loadAllUsers();
  usersStore.fetchRoles();
  usersStore.fetchDepartments();
});

watch(search, () => {});
</script>