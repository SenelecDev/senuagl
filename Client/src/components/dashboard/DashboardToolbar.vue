<template>
  <v-card height="64" flat class="dashboard-toolbar-card">
    <v-toolbar class="text-white dashboard-toolbar" :style="toolbarStyle">
      <v-btn
        icon="mdi-menu"
        @click="$emit('toggle-sidebar')"
        class="hamburger-btn"
      ></v-btn>
      <v-toolbar-title class="text-center">{{ pageTitle }}</v-toolbar-title>
      <div class="toolbar-actions">
        <v-menu offset-y max-width="400">
          <template #activator="{ props }">
            <v-btn icon v-bind="props">
              <v-badge 
                :content="notificationCount" 
                color="#dc2626" 
                overlap
                :model-value="notificationCount > 0"
              >
                <v-icon>mdi-bell</v-icon>
              </v-badge>
            </v-btn>
          </template>
          <v-list class="notification-dropdown" max-height="400">
            <v-list-item class="notification-header">
              <v-list-item-title class="text-h6">Notifications</v-list-item-title>
              <template v-slot:append>
                <v-btn 
                  size="small" 
                  variant="text" 
                  @click="markAllAsRead"
                  v-if="unreadCount > 0"
                >
                  Tout marquer comme lu
                </v-btn>
              </template>
            </v-list-item>
            <v-divider></v-divider>
            
            <div v-if="notifications.length === 0" class="no-notifications">
              <v-list-item>
                <div class="text-center py-4">
                  <v-icon size="48" color="grey-lighten-2">mdi-bell-outline</v-icon>
                  <p class="text-grey mt-2">Aucune notification</p>
                </div>
              </v-list-item>
            </div>
            
            <v-list-item 
              v-for="notif in notifications.slice(0, 5)" 
              :key="notif.id"
              :class="{ 'notification-unread': !notif.read }"
              @click="markAsRead(notif.id)"
              class="notification-item"
            >
              <template v-slot:prepend>
                <v-avatar size="32" :color="getNotificationColor(notif.type)">
                  <v-icon size="16" color="white">{{ getNotificationIcon(notif.type) }}</v-icon>
                </v-avatar>
              </template>
              
              <v-list-item-title class="text-wrap">{{ notif.title }}</v-list-item-title>
              <v-list-item-subtitle class="text-wrap">{{ notif.message }}</v-list-item-subtitle>
              
              <template v-slot:append>
                <div class="notification-meta">
                  <small class="text-grey">{{ formatTime(notif.timestamp) }}</small>
                  <v-icon v-if="!notif.read" size="8" color="primary" class="ml-2">mdi-circle</v-icon>
                </div>
              </template>
            </v-list-item>
            
            <v-divider v-if="notifications.length > 5"></v-divider>
            <v-list-item v-if="notifications.length > 5" @click="viewAllNotifications">
              <v-list-item-title class="text-center text-primary">
                Voir toutes les notifications ({{ notifications.length }})
              </v-list-item-title>
            </v-list-item>
          </v-list>
        </v-menu>
        <v-btn icon @click="logout" class="logout-btn-toolbar">
          <v-icon>mdi-logout</v-icon>
        </v-btn>
      </div>
    </v-toolbar>
  </v-card>
</template>

<script>
import { useNotificationsStore } from '@/stores/notifications'

export default {
  name: "DashboardToolbar",
  setup() {
    const notificationsStore = useNotificationsStore()
    return { notificationsStore }
  },
  props: {
    pageTitle: {
      type: String,
      default: "Tableau de bord",
    },
    sidebarOpen: {
      type: Boolean,
      default: false,
    },
  },
  computed: {
    notifications() {
      return this.notificationsStore.allNotifications
    },
    notificationCount() {
      return this.notificationsStore.unreadCount
    },
    unreadCount() {
      return this.notificationsStore.unreadCount
    },
    toolbarStyle() {
      let style = {
        background: "linear-gradient(90deg, #008a9b 0%, #261555 100%)",
        height: "64px",
        transition: "margin-left 0.3s ease, width 0.3s ease"
      };
      if (this.sidebarOpen) {
        style["marginLeft"] = "250px";
        style["width"] = "calc(100vw - 250px)";
      } else {
        style["marginLeft"] = "0";
        style["width"] = "100vw";
      }
      return style;
    },
  },
  emits: ["toggle-sidebar"],
  methods: {
    logout() {
      this.notificationsStore.teardownRealtimeNotifications()
      localStorage.removeItem("auth_token");
      localStorage.removeItem("user");
      this.$router.push("/");
    },
    markAsRead(id) {
      this.notificationsStore.markAsRead(id)
    },
    markAllAsRead() {
      this.notificationsStore.markAllAsRead()
    },
    getNotificationColor(type) {
      const colors = {
        success: 'success',
        error: 'error',
        warning: 'warning',
        info: 'info'
      }
      return colors[type] || 'info'
    },
    getNotificationIcon(type) {
      const icons = {
        success: 'mdi-check-circle',
        error: 'mdi-alert-circle',
        warning: 'mdi-alert',
        info: 'mdi-information'
      }
      return icons[type] || 'mdi-information'
    },
    formatTime(timestamp) {
      const now = new Date()
      const notifTime = new Date(timestamp)
      const diffInMinutes = Math.floor((now - notifTime) / (1000 * 60))
      
      if (diffInMinutes < 1) return 'À l\'instant'
      if (diffInMinutes < 60) return `${diffInMinutes}m`
      
      const diffInHours = Math.floor(diffInMinutes / 60)
      if (diffInHours < 24) return `${diffInHours}h`
      
      const diffInDays = Math.floor(diffInHours / 24)
      return `${diffInDays}j`
    },
    viewAllNotifications() {
      // TODO: Navigate to notifications page
      console.log('Voir toutes les notifications')
    }
  },
};
</script>

<style scoped>
.dashboard-toolbar-card {
  border-radius: 0;
  box-shadow: none;
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  width: 100vw;
  z-index: 2000;
}
.dashboard-toolbar {
  min-height: 64px;
  padding-left: 8px;
  padding-right: 8px;
  display: flex;
  align-items: center;
  justify-content: space-between;
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
.hamburger-btn {
  color: #fff;
  margin-right: 8px;
}
.hamburger-btn:hover {
  background-color: rgba(255, 255, 255, 0.1);
}
.v-toolbar-title {
  flex: 1;
  text-align: center;
  font-weight: 600;
  font-size: 20px;
}
body {
  padding-top: 64px;
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
.notification-unread {
  background-color: #f0f9ff;
  border-left: 3px solid #008a9b;
}
.notification-meta {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 4px;
}
.no-notifications {
  padding: 20px;
}
@media (max-width: 768px) {
  .dashboard-toolbar-card {
    left: 0 !important;
    width: 100vw !important;
  }
  .dashboard-toolbar {
    margin-left: 0 !important;
    width: 100vw !important;
  }
}
</style>
