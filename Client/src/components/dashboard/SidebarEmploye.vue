<template>
  <div class="sidebar" :class="{ 'is-open': isOpen }">
    <div class="user-profile-section">
      <div class="logo-container">
        <img
          src="../../assets/images/logo-senelec.png"
          alt="Logo SENELEC"
          class="senelec-logo"
        />
      </div>
      <div class="user-info">
        <div class="user-details">
          <span class="user-name">{{ user.prenom }} {{ user.nom }}</span>
          <span class="user-function">{{ user.fonction }}</span>
          <div class="user-role-container">
            <div class="connection-indicator"></div>
            <span class="user-role">Employé</span>
          </div>
        </div>
      </div>
    </div>
    <nav class="sidebar-nav">
      <router-link
        to="/employe/dashboard"
        class="nav-item"
        active-class="active"
      >
        <i class="fas fa-tachometer-alt"></i>
        <span>Dashboard</span>
      </router-link>
      <router-link
        to="/employe/gestion-demandes"
        class="nav-item"
        active-class="active"
      >
        <i class="fas fa-tasks"></i>
        <span>Gestion des Demandes</span>
      </router-link>
      <router-link
        to="/employe/etat-demandes"
        class="nav-item"
        active-class="active"
      >
        <i class="fas fa-list-check"></i>
        <span>État des demandes</span>
      </router-link>
      <router-link to="/employe/solde" class="nav-item" active-class="active">
        <i class="fas fa-wallet"></i>
        <span>Solde de congés</span>
      </router-link>
      <router-link
        to="/employe/historique"
        class="nav-item"
        active-class="active"
      >
        <i class="fas fa-history"></i>
        <span>Historique</span>
      </router-link>
    </nav>
    <div style="padding: 16px; margin-top: auto">
      <button class="logout-btn-employe" @click="logout">Déconnexion</button>
    </div>
  </div>
</template>

<script>
import { useUsersStore } from '@/stores/auth';

export default {
  name: "SidebarEmploye",
  props: {
    isOpen: {
      type: Boolean,
      default: true,
    },
  },
  emits: ["toggle-sidebar"],

  computed: {
    user() {
      const store = useUsersStore();
      const u = store.currentUser;
      const stored = u || JSON.parse(localStorage.getItem('user') || '{}');
      return {
        nom:      stored?.name       ?? '',
        prenom:   stored?.first_name ?? '',
        fonction: stored?.department?.name ?? '',
      };
    },
    userInitials() {
      return (this.user.prenom.charAt(0) + this.user.nom.charAt(0)).toUpperCase();
    },
  },

  methods: {
    async logout() {
      const store = useUsersStore();
      await store.logout();
      this.$router.push("/");
    },
  },
};
</script>

<style>
@import '../../assets/styles/sidebar.css';

/* Styles spécifiques au composant */
.user-info {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 0 25px;
  transition: transform 0.3s ease;
}

.user-details {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
}

.user-name {
  font-weight: 600;
  margin-bottom: 4px;
  font-size: 15px;
  color: rgba(255, 255, 255, 0.95);
}

.user-function {
  font-size: 12px;
  color: rgba(255, 255, 255, 0.7);
  margin-bottom: 2px;
}

.user-role-container {
  display: flex;
  align-items: center;
}

.user-role {
  font-size: 11px;
  color: #ffd700;
  font-weight: 500;
  background: rgba(255, 215, 0, 0.1);
  padding: 2px 6px;
  border-radius: 4px;
  display: inline-block;
  width: fit-content;
  margin-top: 5px;
}

.connection-indicator {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  background-color: #4caf50;
  margin-right: 5px;
  animation: blink 2s infinite;
  box-shadow: 0 0 10px rgba(76, 175, 80, 0.5);
}

@keyframes blink {
  0%,
  50% {
    opacity: 1;
    transform: scale(1);
  }
  25%,
  75% {
    opacity: 0.7;
    transform: scale(0.9);
  }
}

.sidebar-nav {
  padding: 20px 0;
  flex-grow: 1;
  overflow-y: auto;
}

.nav-item {
  display: flex;
  align-items: flex-start;
  padding: 12px 20px;
  color: rgba(255, 255, 255, 0.8);
  text-decoration: none;
  transition: all 0.3s ease;
  position: relative;
  margin: 4px 8px;
  border-radius: 8px;
  min-height: 48px;
}

.nav-item i {
  width: 24px;
  margin-right: 12px;
  font-size: 18px;
  margin-top: 2px;
  flex-shrink: 0;
}

.nav-item span {
  flex: 1;
  line-height: 1.4;
  font-size: 14px;
  white-space: normal;
  word-wrap: break-word;
}

.nav-item:hover {
  color: white;
  background-color: rgba(38, 21, 85, 0.8); /* Couleur mauve SENELEC */
}

.nav-item.active {
  color: white;
  background-color: #261555; /* Couleur mauve SENELEC */
  font-weight: 600;
}

.nav-item.active::before {
  content: '';
  position: absolute;
  left: 0;
  top: 0;
  bottom: 0;
  width: 4px;
  background-color: white;
  border-radius: 0 4px 4px 0;
}

/* Ajustement pour les icônes quand le texte est sur deux lignes */
.nav-item.active i,
.nav-item:hover i {
  color: white;
}
</style>
