import axios from 'axios';

// Récupérer la configuration depuis les variables d'environnement.
// Fallback: utiliser la même origine que l'application (ex: http://localhost:8081/api)
const API_URL = import.meta.env.VITE_API_BASE_URL || `${window.location.origin}/api`;

// Configuration de base d'Axios avec plus de robustesse
const apiClient = axios.create({
  baseURL: API_URL,
  timeout: 30000, // 30 secondes
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
  // Configuration pour éviter les problèmes CORS
  withCredentials: true,
});

// Intercepteur pour ajouter le token d'authentification
apiClient.interceptors.request.use(
  (config) => {
    console.log('🚀 Requête API:', config.method.toUpperCase(), config.url);
    console.log('📦 Données envoyées:', config.data);

    const token = localStorage.getItem('auth_token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => {
    console.error('❌ Erreur de requête:', error);
    return Promise.reject(error);
  }
);

// Intercepteur pour gérer les réponses et les erreurs
apiClient.interceptors.response.use(
  (response) => {
    console.log('✅ Réponse reçue:', response.status, response.config.url);
    console.log('📋 Données reçues:', response.data);
    return response;
  },
  (error) => {
    console.error('❌ Erreur de réponse:', error);

    if (error.code === 'ECONNABORTED') {
      console.error('⏱️ Timeout de connexion');
    }

    if (error.response) {
      console.error('📋 Réponse d\'erreur:', error.response.status, error.response.data);
    } else if (error.request) {
      console.error('📡 Pas de réponse reçue:', error.request);
    }

    // Gestion des erreurs d'authentification
    if (error.response?.status === 401) {
      localStorage.removeItem('auth_token');
      localStorage.removeItem('user');
      window.location.href = '/';
    }

    // Gestion des erreurs de validation (422)
    if (error.response?.status === 422) {
      const validationErrors = error.response.data.errors;
      console.error('🚫 Erreurs de validation:', validationErrors);
      if (validationErrors) {
        const errorMessages = Object.values(validationErrors).flat();
        console.error('📝 Messages d\'erreur:', errorMessages);
        error.message = errorMessages.join(', ');
      }
    }

    // Gestion des erreurs serveur (500)
    if (error.response?.status >= 500) {
      error.message = 'Erreur serveur. Veuillez réessayer plus tard.';
    }

    // Gestion des erreurs réseau
    if (!error.response) {
      error.message = 'Erreur de connexion. Vérifiez votre connexion internet.';
    }

    return Promise.reject(error);
  }
);

// Services API
export const authApi = {
  login: (credentials) => apiClient.post('/login', credentials),
  logout: () => apiClient.post('/logout'),
  user: () => apiClient.get('/user'),
  refresh: () => apiClient.post('/refresh'),
};

export const dashboardApi = {
  stats: () => apiClient.get('/dashboard/stats'),
  recentActivity: () => apiClient.get('/dashboard/recent-activity'),
  statsManager: () => apiClient.get('/dashboard/stats-manager'),
};

export const usersApi = {
  list: (params = {}) => apiClient.get('/users', { params }),
  create: (data) => apiClient.post('/users', data),
  get: (id) => apiClient.get(`/users/${id}`),
  update: (id, data) => apiClient.put(`/users/${id}`, data),
  delete: (id) => apiClient.delete(`/users/${id}`),
  toggleStatus: (id) => apiClient.patch(`/users/${id}/toggle-status`),
  resetPassword: (id) => apiClient.post(`/users/${id}/reset-password`, {}),
  resetPasswordWithData: (id, passwordData) => apiClient.post(`/users/${id}/reset-password`, passwordData),
  managers: () => apiClient.get('/managers'),
};

export const rolesApi = {
  list: () => apiClient.get('/roles'),
  create: (data) => apiClient.post('/roles', data),
  get: (id) => apiClient.get(`/roles/${id}`),
  update: (id, data) => apiClient.put(`/roles/${id}`, data),
  delete: (id) => apiClient.delete(`/roles/${id}`),
};

export const departmentsApi = {
  list: () => apiClient.get('/departments'),
  create: (data) => apiClient.post('/departments', data),
  get: (id) => apiClient.get(`/departments/${id}`),
  update: (id, data) => apiClient.put(`/departments/${id}`, data),
  delete: (id) => apiClient.delete(`/departments/${id}`),
  stats: (id) => apiClient.get(`/departments/${id}/stats`),
};

export const demandesApi = {
  list: (params = {}) => apiClient.get('/demandes-conges', { params }),
  create: (data) => apiClient.post('/demandes-conges', data),
  get: (id) => apiClient.get(`/demandes-conges/${id}`),
  update: (id, data) => apiClient.put(`/demandes-conges/${id}`, data),
  delete: (id) => apiClient.delete(`/demandes-conges/${id}`),
  validate: (id, data) => apiClient.post(`/demandes-conges/${id}/validate`, data),
  demandesAValider: (params = {}) => apiClient.get('/demandes-a-valider', { params }),
};

export const notificationsApi = {
  list: (params = {}) => apiClient.get('/notifications', { params }),
  unread: () => apiClient.get('/notifications/unread'),
  markAsRead: (id) => apiClient.patch(`/notifications/${id}/read`),
  markAllAsRead: () => apiClient.patch('/notifications/read-all'),
};

export const testApi = {
  ping: () => apiClient.get('/test'),
};

export default apiClient;
