import { defineStore } from 'pinia'
import { notificationsApi } from '@/services/api'
import { disconnectEcho, getEcho } from '@/services/echo'

export const useNotificationsStore = defineStore('notifications', {
  state: () => ({
    notifications: [],
    maxNotifications: 5,
    globalNotifications: [],
    channelName: null,
    isLoading: false,
    lastFetch: null,
    fetchRequest: null
  }),

  getters: {
    unreadCount: (state) => state.globalNotifications.filter(n => !n.read).length,
    unreadNotifications: (state) => state.globalNotifications.filter(n => !n.read),
    allNotifications: (state) => state.globalNotifications.sort((a, b) => new Date(b.timestamp) - new Date(a.timestamp))
  },

  actions: {
    // Pour les notifications Toast (temporaires)
    addToast(notification) {
      const newNotification = {
        id: Date.now(),
        type: 'info', // success, error, warning, info
        title: '',
        message: '',
        timeout: 5000,
        persistent: false,
        show: true,
        ...notification
      }
      
      this.notifications.unshift(newNotification)
      
      // Limiter le nombre de notifications
      if (this.notifications.length > this.maxNotifications) {
        this.notifications = this.notifications.slice(0, this.maxNotifications)
      }
      
      // Auto-remove si pas persistant
      if (!newNotification.persistent) {
        setTimeout(() => {
          this.removeToast(newNotification.id)
        }, newNotification.timeout)
      }

      return newNotification.id
    },

    removeToast(id) {
      const index = this.notifications.findIndex(n => n.id === id)
      if (index > -1) {
        this.notifications.splice(index, 1)
      }
    },

    markAsShown(id) {
      const notification = this.notifications.find(n => n.id === id)
      if (notification) {
        notification.show = false
      }
    },

    clearAllToasts() {
      this.notifications = []
    },

    // Pour les notifications globales (toolbar/persistantes)
    normalizeNotification(notification) {
      return {
        id: notification.id,
        type: notification.type || 'info',
        title: notification.title || notification.titre || '',
        message: notification.message || '',
        read: Boolean(notification.read ?? notification.lu),
        timestamp: notification.timestamp || notification.created_at || new Date().toISOString(),
        category: notification.category || 'general',
        data: notification.data || {}
      }
    },

    addGlobalNotification(notification) {
      const normalized = this.normalizeNotification(notification)
      const alreadyExists = this.globalNotifications.some((item) => item.id === normalized.id)
      if (!alreadyExists) {
        this.globalNotifications.unshift(normalized)
      }
    },

    async fetchNotifications() {
      const now = Date.now()
      if (this.fetchRequest) return this.fetchRequest
      if (this.lastFetch && now - this.lastFetch < 60000) return

      this.isLoading = true
      this.fetchRequest = notificationsApi.list({ per_page: 20 })
      try {
        const response = await this.fetchRequest
        const rows = response?.data?.data?.data || response?.data?.data || []
        this.globalNotifications = rows.map((row) => this.normalizeNotification(row))
        this.lastFetch = Date.now()
      } catch (error) {
        console.error('Erreur lors du chargement des notifications:', error)
      } finally {
        this.isLoading = false
        this.fetchRequest = null
      }
    },

    async markAsRead(id) {
      const notification = this.globalNotifications.find(n => n.id === id)
      if (notification) {
        notification.read = true
      }
      try {
        await notificationsApi.markAsRead(id)
      } catch (error) {
        console.error('Erreur markAsRead:', error)
      }
    },

    async markAllAsRead() {
      this.globalNotifications.forEach(n => n.read = true)
      try {
        await notificationsApi.markAllAsRead()
      } catch (error) {
        console.error('Erreur markAllAsRead:', error)
      }
    },

    removeGlobalNotification(id) {
      const index = this.globalNotifications.findIndex(n => n.id === id)
      if (index > -1) {
        this.globalNotifications.splice(index, 1)
      }
    },

    // Méthodes utilitaires pour différents types d'actions
    notifySuccess(message, title = 'Succès') {
      this.addToast({
        type: 'success',
        title,
        message,
        timeout: 3000
      })
    },

    notifyError(message, title = 'Erreur') {
      this.addToast({
        type: 'error',
        title,
        message,
        timeout: 5000,
        persistent: false
      })
    },

    notifyWarning(message, title = 'Attention') {
      this.addToast({
        type: 'warning',
        title,
        message,
        timeout: 4000
      })
    },

    notifyInfo(message, title = 'Information') {
      this.addToast({
        type: 'info',
        title,
        message,
        timeout: 4000
      })
    },

    // Notifications spécifiques aux actions métier
    notifyDemandeSubmitted(demandeur) {
      this.addGlobalNotification({
        id: Date.now(),
        type: 'info',
        title: 'Nouvelle demande',
        message: `${demandeur} a soumis une nouvelle demande de congé`,
        category: 'demande'
      })
      
      this.notifySuccess('Demande soumise avec succès!')
    },

    notifyDemandeValidated(action, demandeur) {
      const message = action === 'approve' 
        ? `Demande de ${demandeur} approuvée avec succès`
        : `Demande de ${demandeur} rejetée`
      
      this.addGlobalNotification({
        id: Date.now(),
        type: action === 'approve' ? 'success' : 'warning',
        title: action === 'approve' ? 'Demande approuvée' : 'Demande rejetée',
        message,
        category: 'validation'
      })
      
      if (action === 'approve') {
        this.notifySuccess(message)
      } else {
        this.notifyWarning(message)
      }
    },

    notifyDemandeCancelled(demandeur) {
      this.addGlobalNotification({
        id: Date.now(),
        type: 'info',
        title: 'Demande annulée',
        message: `${demandeur} a annulé sa demande de congé`,
        category: 'demande'
      })
      
      this.notifyInfo('Demande annulée avec succès')
    },

    initRealtimeNotifications() {
      const token = localStorage.getItem('auth_token')
      const userRaw = localStorage.getItem('user')
      if (!token || !userRaw) return

      const user = JSON.parse(userRaw)
      if (!user?.id) return

      const channel = `users.${user.id}`
      if (this.channelName === channel) return

      const echo = getEcho(token)
      this.channelName = channel

      echo.private(channel).listen('.notification.created', (payload) => {
        this.addGlobalNotification(payload)
        this.notifyInfo(payload.message, payload.title || 'Nouvelle notification')
      })
    },

    teardownRealtimeNotifications() {
      if (this.channelName) {
        const echo = getEcho(localStorage.getItem('auth_token'))
        echo.leave(`private-${this.channelName}`)
        this.channelName = null
      }
      disconnectEcho()
    }
  }
})
