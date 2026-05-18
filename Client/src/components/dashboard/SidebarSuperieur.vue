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
            <span class="user-role">{{ role }}</span>
          </div>
        </div>
      </div>
    </div>
    <nav class="sidebar-nav">
      <!-- Section Tableau de bord -->
      <router-link
        :to="{ name: `${rolePrefix}Dashboard` }"
        class="nav-item"
        active-class="active"
      >
        <i class="fas fa-tachometer-alt"></i>
        <span>Dashboard</span>
      </router-link>

      <!-- Section Mon Espace -->
      <div class="nav-section">
        <div class="section-title">Mon Espace</div>
        <router-link
          :to="{ name: `${rolePrefix}GestionDemandes` }"
          class="nav-item"
          active-class="active"
        >
          <i class="fas fa-tasks"></i>
          <span>Gestion des Demandes</span>
        </router-link>
        <router-link
          :to="{ name: `${rolePrefix}EtatDemandes` }"
          class="nav-item"
          active-class="active"
        >
          <i class="fas fa-list-check"></i>
          <span>État des demandes</span>
        </router-link>
        <router-link
          :to="{ name: `${rolePrefix}SoldeConges` }"
          class="nav-item"
          active-class="active"
        >
          <i class="fas fa-wallet"></i>
          <span>Solde de congés</span>
        </router-link>
        <router-link
          :to="{ name: `${rolePrefix}HistoriqueConges` }"
          class="nav-item"
          active-class="active"
        >
          <i class="fas fa-history"></i>
          <span>Historique</span>
        </router-link>
      </div>

      <!-- Section Validation (uniquement pour les rôles concernés) -->
      <div v-if="role !== 'Employé'" class="nav-section">
        <div class="section-title">Validation</div>
        <router-link
          :to="{ name: `${rolePrefix}DemandesEnAttente` }"
          class="nav-item"
          active-class="active"
        >
          <i class="fas fa-list"></i>
          <span>Demandes en attente</span>
        </router-link>
        <router-link
          :to="{ name: `${rolePrefix}ValidationDemandes` }"
          class="nav-item"
          active-class="active"
        >
          <i class="fas fa-check-circle"></i>
          <span>Validation</span>
        </router-link>

        <!-- ✅ CALENDRIER ÉQUIPE — visible pour tous les rôles validateurs -->
        <router-link
          :to="{ name: `${rolePrefix}CalendrierEquipe` }"
          class="nav-item"
          active-class="active"
        >
          <i class="fas fa-calendar-alt"></i>
          <span>Calendrier Équipe</span>
        </router-link>
      </div>

      <!-- Section Administration (uniquement pour le Directeur RH) -->
      <div v-if="role === 'Directeur RH'" class="nav-section">
        <div class="section-title">Administration</div>
        <router-link
          :to="{ name: 'directeurRHDocumentsAdministratifs' }"
          class="nav-item"
          active-class="active"
        >
          <i class="fas fa-file-contract"></i>
          <span>Documents</span>
        </router-link>
      </div>
    </nav>
    <div style="padding: 16px; margin-top: auto">
      <button class="logout-btn-employe" @click="logout">Déconnexion</button>
    </div>
  </div>
</template>

<script>
export default {
  name: "SidebarSuperieur",
  props: {
    isOpen: {
      type: Boolean,
      default: true,
    },
    role: {
      type: String,
      default: "Supérieur Hiérarchique"
    }
  },
  emits: ["toggle-sidebar"],
  data() {
    return {
      user: {
        nom: "Diop",
        prenom: "Mansour",
        fonction: "Chef de Service",
      },
      demandesEnAttente: 3,
    };
  },
  computed: {
    userInitials() {
      return this.user.prenom.charAt(0) + this.user.nom.charAt(0);
    },
    rolePrefix() {
      switch (this.role) {
        case "Directeur d'Unité":
          return "directeurUnite";
        case "Responsable RH":
          return "responsableRH";
        case "Directeur RH":
          return "directeurRH";
        default:
          return "superieur";
      }
    }
  },
  methods: {
    logout() {
      localStorage.removeItem("user");
      this.$router.push("/");
    },
  },
};
</script>

<style scoped>
.sidebar {
  width: 250px;
  height: 100vh;
  background-color: var(--primary-color);
  backdrop-filter: blur(10px);
  color: white;
  padding: 0;
  display: flex;
  flex-direction: column;
  position: fixed;
  left: 0;
  top: 0;
  z-index: 100;
  box-shadow: 4px 0 25px rgba(0, 0, 0, 0.1);
  transition: all 0.3s ease;
}

.sidebar.is-open {
  transform: translateX(0%);
}

.sidebar:not(.is-open) {
  transform: translateX(-250px);
}

.user-profile-section {
  background-color: var(--secondary-color);
  padding: 20px 0;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  margin-bottom: 0;
  flex-shrink: 0;
  text-align: center;
}

.logo-container {
  display: flex;
  justify-content: center;
  align-items: center;
  margin-bottom: 20px;
  padding: 0 20px;
}

.senelec-logo {
  width: 100px;
  height: auto;
  object-fit: contain;
  transition: all 0.3s ease;
}

.senelec-logo:hover {
  transform: scale(1.05);
  filter: drop-shadow(0 0 10px rgba(255, 255, 255, 0.3));
}

.user-info {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 0 25px;
  transition: transform 0.3s ease;
}

.user-info:hover {
  transform: translateX(0);
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

.nav-section {
  margin: 8px 0;
  padding-top: 8px;
}

.nav-section:not(:first-child) {
  border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.section-title {
  padding: 0 20px;
  margin-bottom: 8px;
  font-size: 12px;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.5);
  letter-spacing: 1px;
  font-weight: 600;
}

.nav-item {
  display: flex;
  align-items: center;
  padding: 10px 20px;
  color: rgba(255, 255, 255, 0.8);
  text-decoration: none;
  transition: all 0.3s ease;
  position: relative;
  margin: 2px 8px;
  border-radius: 8px;
  min-height: 40px;
}

.nav-item i {
  width: 20px;
  margin-right: 12px;
  font-size: 16px;
  flex-shrink: 0;
}

.nav-item span {
  flex: 1;
  line-height: 1.4;
  font-size: 13px;
  white-space: normal;
  word-wrap: break-word;
}

.nav-item:hover {
  color: white;
  background-color: rgba(38, 21, 85, 0.8);
}

.nav-item.active {
  color: white;
  background-color: #261555;
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

.nav-item.active i,
.nav-item:hover i {
  color: white;
}

.nav-item.disabled {
  opacity: 0.5;
  cursor: not-allowed;
  pointer-events: none;
}

.nav-item.disabled:hover {
  background: transparent;
  transform: none;
}

.nav-item.disabled i {
  color: rgba(177, 0, 100, 0.5);
}

.badge {
  background-color: #ff4757;
  color: white;
  border-radius: 12px;
  padding: 2px 8px;
  font-size: 11px;
  font-weight: 600;
  margin-left: 8px;
}

@media (max-width: 768px) {
  .sidebar {
    width: 200px;
  }

  .sidebar:not(.is-open) {
    transform: translateX(-200px);
  }

  .nav-item {
    padding: 10px 20px;
  }

  .nav-item i {
    font-size: 20px;
    margin-right: 12px;
  }

  .nav-item span {
    font-size: 13px;
  }
}

.logout-btn-employe {
  width: 100%;
  padding: 10px 0;
  background-color: #008a9b;
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s;
}
.logout-btn-employe:hover {
  background-color: #005f6b;
}
</style>