import { defineStore } from 'pinia';
import { usersApi, rolesApi, departmentsApi } from '@/services/api';

export const useUsersAdminStore = defineStore('usersAdmin', {
  state: () => ({
    users: [],
    roles: [],
    departments: [],
    loading: false,
    usersLoading: false,
    rolesLoading: false,
    departmentsLoading: false,
    error: null,
    pagination: {
      current_page: 1,
      per_page: 10,
      total: 0,
      last_page: 1,
    },
    cache: {
      users: { data: null, timestamp: null },
      roles: { data: null, timestamp: null },
      departments: { data: null, timestamp: null },
    },
  }),

  getters: {
    totalUsers: (state) => state.pagination.total || state.users.length,
    
    usersByRole: (state) => {
      const roleCount = {};
      state.users.forEach(user => {
        const role = user.role?.nom || 'employe';
        roleCount[role] = (roleCount[role] || 0) + 1;
      });
      return roleCount;
    },

    activeUsers: (state) => state.users.filter(user => user.is_active),
    
    inactiveUsers: (state) => state.users.filter(user => !user.is_active),

    usersByDepartment: (state) => {
      const departmentCount = {};
      state.users.forEach(user => {
        const dept = user.department?.name || 'Non assigné';
        departmentCount[dept] = (departmentCount[dept] || 0) + 1;
      });
      return departmentCount;
    },
  },

  actions: {
    async fetchUsers(page = 1, perPage = 10, search = '', forceRefresh = false) {
      // Gestion du cache
      const cacheKey = `${page}-${perPage}-${search}`;
      const now = Date.now();
      const cacheTimeout = 5 * 60 * 1000; // 5 minutes
      
      if (!forceRefresh && this.cache.users.data && 
          this.cache.users.timestamp && 
          (now - this.cache.users.timestamp) < cacheTimeout) {
        return this.cache.users.data;
      }

      this.usersLoading = true;
      this.error = null;

      try {
        const params = {
          page,
          per_page: perPage,
        };
        
        if (search.trim()) {
          params.search = search.trim();
        }

        const response = await usersApi.list(params);
        console.log('👥 Utilisateurs API response:', response.data);
        
        if (response.data.success) {
          this.users = response.data.data.data || response.data.data || [];
          
          // Gestion de la pagination
          if (response.data.data.current_page) {
            this.pagination = {
              current_page: response.data.data.current_page,
              per_page: response.data.data.per_page,
              total: response.data.data.total,
              last_page: response.data.data.last_page,
            };
          }

          // Mettre en cache
          this.cache.users = {
            data: response.data,
            timestamp: now
          };
        } else {
          this.users = [];
        }
        
        return response.data;
      } catch (error) {
        this.error = error.message;
        console.error('Erreur lors du chargement des utilisateurs:', error);
        this.users = [];
        return { success: false, error: this.error };
      } finally {
        this.usersLoading = false;
      }
    },

    async fetchRoles(forceRefresh = false) {
      const now = Date.now();
      const cacheTimeout = 10 * 60 * 1000; // 10 minutes
      
      if (!forceRefresh && this.cache.roles.data && 
          this.cache.roles.timestamp && 
          (now - this.cache.roles.timestamp) < cacheTimeout) {
        this.roles = this.cache.roles.data;
        return this.cache.roles.data;
      }

      this.rolesLoading = true;
      this.error = null;

      try {
        const response = await rolesApi.list();
        console.log('👤 Rôles API response:', response.data);
        
        if (response.data.success) {
          const raw = response.data.data;
          this.roles = Array.isArray(raw)
            ? raw
            : (raw && Array.isArray(raw.data) ? raw.data : []);
          this.cache.roles = {
            data: this.roles,
            timestamp: now
          };
        } else {
          this.roles = [];
        }
        
        return response.data;
      } catch (error) {
        this.error = error.message;
        console.error('Erreur lors du chargement des rôles:', error);
        this.roles = [];
        return { success: false, error: this.error };
      } finally {
        this.rolesLoading = false;
      }
    },

    async fetchDepartments(forceRefresh = false) {
      const now = Date.now();
      const cacheTimeout = 10 * 60 * 1000; // 10 minutes
      
      if (!forceRefresh && this.cache.departments.data && 
          this.cache.departments.timestamp && 
          (now - this.cache.departments.timestamp) < cacheTimeout) {
        this.departments = this.cache.departments.data;
        return this.cache.departments.data;
      }

      this.departmentsLoading = true;
      this.error = null;

      try {
        const response = await departmentsApi.list();
        console.log('🏢 Départements API response:', response.data);
        
        if (response.data.success) {
          const raw = response.data.data;
          this.departments = Array.isArray(raw)
            ? raw
            : (raw && Array.isArray(raw.data) ? raw.data : []);
          this.cache.departments = {
            data: this.departments,
            timestamp: now
          };
        } else {
          this.departments = [];
        }
        
        return response.data;
      } catch (error) {
        this.error = error.message;
        console.error('Erreur lors du chargement des départements:', error);
        this.departments = [];
        return { success: false, error: this.error };
      } finally {
        this.departmentsLoading = false;
      }
    },

    async addUser(userData) {
      this.loading = true;
      this.error = null;

      try {
        const response = await usersApi.create(userData);
        
        if (response.data.success) {
          // Invalider le cache et recharger
          this.cache.users.timestamp = null;
          await this.fetchUsers(1, 10, '', true);
        }
        
        return response.data;
      } catch (error) {
        this.error = error.message;
        console.error('Erreur lors de la création de l\'utilisateur:', error);
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async updateUser(userId, userData) {
      this.loading = true;
      this.error = null;

      try {
        const response = await usersApi.update(userId, userData);
        
        if (response.data.success) {
          // Mettre à jour dans le state local
          const index = this.users.findIndex(u => u.id === userId);
          if (index !== -1) {
            this.users[index] = response.data.data;
          }
          // Invalider le cache
          this.cache.users.timestamp = null;
        }
        
        return response.data;
      } catch (error) {
        this.error = error.message;
        console.error('Erreur lors de la mise à jour de l\'utilisateur:', error);
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async removeUser(userId) {
      this.loading = true;
      this.error = null;

      try {
        const response = await usersApi.delete(userId);
        
        if (response.data.success) {
          // Supprimer du state local
          this.users = this.users.filter(u => u.id !== userId);
          // Invalider le cache
          this.cache.users.timestamp = null;
        }
        
        return response.data;
      } catch (error) {
        this.error = error.message;
        console.error('Erreur lors de la suppression de l\'utilisateur:', error);
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async toggleUserStatus(userId) {
      this.loading = true;
      this.error = null;

      try {
        const response = await usersApi.toggleStatus(userId);
        
        if (response.data.success) {
          // Mettre à jour dans le state local
          const index = this.users.findIndex(u => u.id === userId);
          if (index !== -1) {
            this.users[index].is_active = !this.users[index].is_active;
          }
        }
        
        return response.data;
      } catch (error) {
        this.error = error.message;
        console.error('Erreur lors du changement de statut:', error);
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async resetUserPasswordWithData(userId, passwordData) {
      this.loading = true;
      this.error = null;

      try {
        const response = await usersApi.resetPasswordWithData(userId, passwordData);
        return response.data;
      } catch (error) {
        this.error = error.message;
        console.error('Erreur lors de la réinitialisation du mot de passe:', error);
        throw error;
      } finally {
        this.loading = false;
      }
    },

    clearError() {
      this.error = null;
    },
  },
});
