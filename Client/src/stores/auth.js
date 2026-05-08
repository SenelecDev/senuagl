import { defineStore } from 'pinia'
import api from '@/api/axios'

const TOKEN_KEY = 'uag_auth_token'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    token: sessionStorage.getItem(TOKEN_KEY),
    user: null,
    loading: false,
    error: null
  }),

  getters: {
    hasToken: (state) => Boolean(state.token),
    isAuthenticated: (state) => Boolean(state.token && state.user)
  },

  actions: {
    syncTokenFromStorage() {
      localStorage.removeItem(TOKEN_KEY)
      const token = sessionStorage.getItem(TOKEN_KEY)
      this.token = token

      if (token) {
        api.defaults.headers.common.Authorization = `Bearer ${token}`
      } else {
        delete api.defaults.headers.common.Authorization
      }
    },

    setSession(token, user) {
      this.token = token
      this.user = user
      localStorage.removeItem(TOKEN_KEY)
      sessionStorage.setItem(TOKEN_KEY, token)
      api.defaults.headers.common.Authorization = `Bearer ${token}`
    },

    clearSession() {
      this.token = null
      this.user = null
      localStorage.removeItem(TOKEN_KEY)
      sessionStorage.removeItem(TOKEN_KEY)
      delete api.defaults.headers.common.Authorization
    },

    async login(credentials) {
      this.loading = true
      this.error = null

      try {
        const response = await api.post('/login', credentials)
        this.setSession(response.data.token, response.data.user)
      } catch (error) {
        this.error = error.response?.data?.message || 'Connexion impossible.'
        throw error
      } finally {
        this.loading = false
      }
    },

    async fetchUser() {
      this.syncTokenFromStorage()

      if (!this.token) {
        return null
      }

      try {
        const response = await api.get('/me')
        this.user = response.data
        return this.user
      } catch (error) {
        this.clearSession()
        throw error
      }
    },

    async ensureAuthenticated() {
      this.syncTokenFromStorage()

      if (!this.token) {
        this.clearSession()
        return false
      }

      if (this.user) {
        return true
      }

      try {
        await this.fetchUser()
        return Boolean(this.user)
      } catch {
        return false
      }
    },

    async logout() {
      try {
        if (this.token) {
          await api.post('/logout')
        }
      } finally {
        this.clearSession()
      }
    }
  }
})
