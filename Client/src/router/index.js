import { createRouter, createWebHistory } from 'vue-router'
import Dashboard from '../views/Dashboard.vue'
import AgentsIndex from '../views/Agents/Index.vue'
import AgentDetail from '../views/Agents/Detail.vue'
import PostesIndex from '../views/Postes/Index.vue'
import PostesVacants from '../views/Postes/Vacants.vue'
import Statistiques from '../views/Statistiques.vue'
import Avancements from '../views/Avancements.vue'
import AvancementsListePriorite from '../views/Avancements/ListePriorite.vue'
import Promotions from '../views/Promotions.vue'
import NotesAppreciation from '../views/NotesAppreciation.vue'
import Budget from '../views/Budget/Index.vue'
import Login from '../views/Login.vue'
import { useAuthStore } from '../stores/auth'

const routes = [
    {
        path: '/login',
        name: 'Login',
        component: Login,
        meta: { guest: true }
    },
    {
        path: '/',
        name: 'Dashboard',
        component: Dashboard,
        meta: { requiresAuth: true }
    },
    {
        path: '/agents',
        name: 'Agents',
        component: AgentsIndex,
        meta: { requiresAuth: true }
    },
    {
        path: '/agents/:matricule',
        name: 'AgentDetail',
        component: AgentDetail,
        meta: { requiresAuth: true }
    },
    {
        path: '/postes',
        name: 'Postes',
        component: PostesIndex,
        meta: { requiresAuth: true }
    },
    {
        path: '/postes-vacants',
        name: 'PostesVacants',
        component: PostesVacants,
        meta: { requiresAuth: true }
    },
    {
        path: '/statistiques',
        name: 'Statistiques',
        component: Statistiques,
        meta: { requiresAuth: true }
    },
    {
        path: '/avancements',
        name: 'Avancements',
        component: Avancements,
        meta: { requiresAuth: true }
    },
    {
        path: '/avancements-liste-priorite',
        name: 'AvancementsListePriorite',
        component: AvancementsListePriorite,
        meta: { requiresAuth: true }
    },
    {
        path: '/promotions',
        name: 'Promotions',
        component: Promotions,
        meta: { requiresAuth: true }
    },
    {
        path: '/notes-appreciation',
        name: 'NotesAppreciation',
        component: NotesAppreciation,
        meta: { requiresAuth: true }
    },
    {
        path: '/budget',
        name: 'Budget',
        component: Budget,
        meta: { requiresAuth: true }
    },
    {
        path: '/:pathMatch(.*)*',
        redirect: '/'
    }
]

const router = createRouter({
    history: createWebHistory(),
    routes
})

router.beforeEach(async (to) => {
    const authStore = useAuthStore()
    const requiresAuth = to.matched.some(route => route.meta.requiresAuth)
    const guestOnly = to.matched.some(route => route.meta.guest)

    if (requiresAuth) {
        const authenticated = await authStore.ensureAuthenticated()

        if (!authenticated) {
            return {
                path: '/login',
                query: { redirect: to.fullPath }
            }
        }
    }

    if (guestOnly) {
        const authenticated = await authStore.ensureAuthenticated()

        if (authenticated) {
            return '/'
        }
    }

})

export default router
