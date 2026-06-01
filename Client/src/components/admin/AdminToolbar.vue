<template>
  <v-card height="64" flat class="admin-toolbar-card">
    <v-toolbar class="text-white admin-toolbar" :style="toolbarStyle">
      <v-toolbar-title class="text-center">{{ title }}</v-toolbar-title>
      <div class="toolbar-actions">
        <v-menu offset-y max-width="400">
          <template #activator="{ props }">
            <v-btn icon v-bind="props">
              <v-badge 
                :content="pendingDemandesCount" 
                color="#dc2626" 
                overlap
                :model-value="pendingDemandesCount > 0"
              >
                <v-icon>mdi-bell</v-icon>
              </v-badge>
            </v-btn>
          </template>
          <v-list class="notification-dropdown" max-height="400">
            <v-list-item class="notification-header">
              <v-list-item-title class="text-h6">Notifications</v-list-item-title>
            </v-list-item>
            <v-divider></v-divider>
            
            <div v-if="pendingDemandesCount === 0" class="no-notifications">
              <v-list-item>
                <div class="text-center py-4">
                  <v-icon size="48" color="grey-lighten-2">mdi-bell-outline</v-icon>
                  <p class="text-grey mt-2">Aucune notification</p>
                </div>
              </v-list-item>
            </div>
            
            <v-list-item 
              v-for="demande in pendingDemandes.slice(0, 5)" 
              :key="demande.id"
              class="notification-item"
            >
              <template v-slot:prepend>
                <v-avatar size="32" color="primary">
                  <v-icon size="16" color="white">mdi-file-document</v-icon>
                </v-avatar>
              </template>
              
              <v-list-item-title class="text-wrap">{{ demande.prenom }} {{ demande.nom }}</v-list-item-title>
              <v-list-item-subtitle class="text-wrap">{{ demande.typeDemande }}</v-list-item-subtitle>
            </v-list-item>
            
            <v-divider v-if="pendingDemandesCount > 5"></v-divider>
            <v-list-item v-if="pendingDemandesCount > 0" to="/admin/history">
              <v-list-item-title class="text-center text-primary">
                Voir toutes les notifications ({{ pendingDemandesCount }})
              </v-list-item-title>
            </v-list-item>
          </v-list>
        </v-menu>
        <v-btn icon @click="handleLogout" class="logout-btn-toolbar">
          <v-icon>mdi-logout</v-icon>
        </v-btn>
      </div>
    </v-toolbar>
  </v-card>
</template>

<script>
import { computed, onMounted } from "vue";
import { useRouter } from "vue-router";
import { useUserStore } from "@/stores/users";
import { useDemandesStore } from "@/stores/demandes";

export default {
  name: "AdminToolbar",
  setup() {
    const router = useRouter();
    const userStore = useUserStore();
    const demandesStore = useDemandesStore();

    const pendingDemandes = computed(() => demandesStore.demandesEnAttente);
    const pendingDemandesCount = computed(() => pendingDemandes.value.length);
    const currentUser = computed(() => userStore.user);

    const handleLogout = async () => {
      try {
        await userStore.logout();
        router.push("/");
      } catch (error) {
        console.error("Erreur lors de la déconnexion:", error);
        window.location.href = "/";
      }
    };

    onMounted(async () => {
      if (!currentUser.value && userStore.token) {
        await userStore.fetchUser();
      }
    });

    return {
      pendingDemandes,
      pendingDemandesCount,
      handleLogout,
      toolbarStyle: {
        background: "linear-gradient(90deg, #008a9b 0%, #261555 100%)",
        height: "64px",
        marginLeft: "300px",
        width: "calc(100vw - 300px)",
        transition: "margin-left 0.3s ease, width 0.3s ease",
      },
    };
  },
  props: {
    title: {
      type: String,
      default: "Tableau de bord",
    },
  },
};
</script>

<style scoped>
.admin-toolbar-card {
  border-radius: 0;
  box-shadow: none;
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  width: 100vw;
  height: 64px;
  z-index: 1999;
  background: transparent;
  margin: 0 !important;
  padding: 0 !important;
}
.admin-toolbar {
  min-height: 64px;
  padding-left: 8px;
  padding-right: 8px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin: 0;
}
.toolbar-actions {
  display: flex;
  align-items: center;
  gap: 8px;
}
.logout-btn-toolbar {
  color: #fff;
  margin-left: 8px;
}
.v-toolbar-title {
  flex: 1;
  text-align: center;
  font-weight: 600;
  font-size: 20px;
}
.notification-dropdown {
  max-width: 400px;
  border-radius: 12px;
}
.notification-header {
  padding: 16px !important;
  background-color: #f8fafc;
}
.notification-item {
  padding: 12px 16px !important;
  border-bottom: 1px solid #f1f5f9;
}
.notification-item:hover {
  background-color: #f8fafc;
}
.no-notifications {
  padding: 20px;
}
@media (max-width: 768px) {
  .admin-toolbar-card {
    left: 0 !important;
    width: 100vw !important;
  }
  .admin-toolbar {
    margin-left: 0 !important;
    width: 100vw !important;
  }
  .v-toolbar-title {
    font-size: 18px;
  }
}
</style>
