<template>
  <div class="admin-dashboard-layout">
    <AdminSidebar />
    <div class="admin-dashboard-content">
      <AdminToolbar :title="currentTitle" />
      <main class="admin-main-content">
        <div class="admin-main-container">
          <router-view />
        </div>
      </main>
    </div>
  </div>
</template>

<script>
import AdminSidebar from "@/components/admin/AdminSidebar.vue";
import AdminToolbar from "@/components/admin/AdminToolbar.vue";

export default {
  name: "AdminDashboard",
  components: { AdminSidebar, AdminToolbar },
  computed: {
    currentTitle() {
      const routeName = this.$route.meta.title || "Tableau de bord admin";
      return routeName;
    },
  },
};
</script>

<style scoped>
.admin-dashboard-layout {
  display: flex;
  min-height: 100vh;
  background: linear-gradient(180deg, #eff6ff 0%, #f8fafc 100%);
  position: relative;
  overflow-x: hidden;
  margin: 0;
  padding: 0;
}

.admin-dashboard-content {
  flex: 1;
  margin-left: 300px;
  min-height: 100vh;
  transition: margin-left 0.3s ease;
}

.admin-main-content {
  min-height: 100vh;
  padding: 88px 24px 24px;
}

.admin-main-container {
  min-height: calc(100vh - 112px);
}

@media (max-width: 768px) {
  .admin-dashboard-content {
    margin-left: 0;
  }

  .admin-main-content {
    padding: 88px 16px 16px;
  }
}
</style>
