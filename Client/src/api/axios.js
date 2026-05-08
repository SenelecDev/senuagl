import axios from 'axios'

const TOKEN_KEY = 'uag_auth_token'
localStorage.removeItem(TOKEN_KEY)
const token = sessionStorage.getItem(TOKEN_KEY)

const api = axios.create({
    baseURL: import.meta.env.VITE_API_URL,
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        ...(token ? { Authorization: `Bearer ${token}` } : {})
    }
})

// Intercepteur pour gérer les erreurs
api.interceptors.response.use(
    response => response,
    error => {
        console.error('API Error:', error.response?.data || error.message)
        if (error.response?.status === 401) {
            localStorage.removeItem(TOKEN_KEY)
            sessionStorage.removeItem(TOKEN_KEY)
            delete api.defaults.headers.common.Authorization

            if (window.location.pathname !== '/login') {
                window.location.href = '/login'
            }
        }
        return Promise.reject(error)
    }
)

export default api
