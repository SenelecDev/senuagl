<template>
  <div class="sidebar" :class="{ 'is-open': isOpen }">
    <div class="sidebar-brand">
      <div class="logo-container">
        <img
          src="../../assets/images/logo-senelec.png"
          alt="Logo UAGL"
          class="senelec-logo"
        />
      </div>
      <div class="brand-copy">
        <strong>UAGL DSI</strong>
      </div>
    </div>

    <div class="sidebar-section-label">Navigation</div>

    <nav class="sidebar-nav">
      <router-link
        v-for="item in menuItems"
        :key="item.path"
        :to="item.path"
        class="nav-item"
        active-class="active"
      >
        <i :class="item.icon"></i>
        <span>{{ item.name }}</span>
      </router-link>
    </nav>
  </div>
</template>

<script setup>
defineProps({
  isOpen: {
    type: Boolean,
    default: true
  }
})

const menuItems = [
  { path: '/', name: 'Dashboard', icon: 'fas fa-tachometer-alt' },
  { path: '/agents', name: 'Agents', icon: 'fas fa-users' },
  { path: '/postes', name: 'Postes', icon: 'fas fa-briefcase' },
  { path: '/postes-vacants', name: 'Postes vacants', icon: 'fas fa-map-marker-alt' },
  { path: '/statistiques', name: 'Statistiques', icon: 'fas fa-chart-bar' },
  { path: '/notes-appreciation', name: 'Notes d\'appréciation', icon: 'fas fa-star' },
  { path: '/promotions', name: 'Promotions', icon: 'fas fa-arrow-up' },
  { path: '/avancements-liste-priorite', name: 'Avancements', icon: 'fas fa-arrow-up' },
  { path: '/avancements', name: 'Historique avancements', icon: 'fas fa-exchange-alt' },
  { path: '/budget', name: 'Budget', icon: 'fas fa-coins' }
]
</script>

<style scoped>
.sidebar {
  background: var(--primary-color);
  color: #ffffff;
  border-right: 0;
  box-shadow: 4px 0 25px rgba(0, 0, 0, 0.1);
}

.sidebar-brand {
  padding: 22px 18px 18px;
  background: var(--secondary-color);
  border-bottom: 1px solid rgba(255, 255, 255, 0.16);
}

.logo-container {
  display: flex;
  justify-content: center;
  padding: 0;
  margin-bottom: 14px;
}

.senelec-logo {
  width: 104px;
  max-width: 104px;
  height: auto;
}

.brand-copy {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 3px;
  text-align: center;
}

.brand-copy strong {
  color: #ffffff;
  font-size: 0.95rem;
  letter-spacing: 0;
}

.sidebar-section-label {
  padding: 18px 18px 8px;
  color: rgba(255, 255, 255, 0.62);
  font-size: 0.72rem;
  font-weight: 800;
  text-transform: uppercase;
}

.sidebar-nav {
  padding: 0 10px 18px;
}

.nav-item {
  align-items: center;
  min-height: 44px;
  margin: 4px 0;
  padding: 11px 12px;
  color: rgba(255, 255, 255, 0.82);
  border-radius: 8px;
  font-weight: 600;
}

.nav-item i {
  width: 22px;
  margin-right: 10px;
  margin-top: 0;
  color: rgba(255, 255, 255, 0.72);
  font-size: 16px;
}

.nav-item span {
  font-size: 0.9rem;
  line-height: 1.25;
}

.nav-item:hover {
  color: #ffffff;
  background: rgba(255, 255, 255, 0.12);
}

.nav-item:hover i {
  color: #ffffff;
}

.nav-item.active {
  color: #ffffff;
  background: rgba(255, 255, 255, 0.18);
  font-weight: 700;
  box-shadow: none;
}

.nav-item.active i {
  color: #ffffff;
}

.nav-item.active::before {
  display: none;
}
</style>
