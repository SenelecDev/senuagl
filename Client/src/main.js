import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'

// Import de Vuetify
import 'vuetify/styles'
import { createVuetify } from 'vuetify'
import * as components from 'vuetify/components'
import * as directives from 'vuetify/directives'
import { aliases, mdi } from 'vuetify/iconsets/mdi'
import '@mdi/font/css/materialdesignicons.css'

// Import de Font Awesome pour les icônes
import '@fortawesome/fontawesome-free/css/all.css'

// Import PrimeVue pour la version 4
import PrimeVue from 'primevue/config'
import ToastService from 'primevue/toastservice'

// Import du thème officiel PrimeVue 4 - Aura
import Aura from '@primevue/themes/aura'

const vuetify = createVuetify({
  components,
  directives,
  icons: {
    defaultSet: 'mdi',
    aliases,
    sets: {
      mdi,
    },
  },
  theme: {
    defaultTheme: 'light',
    themes: {
      light: {
        colors: {
          primary: '#008a9b',
          secondary: '#261555',
          accent: '#82B1FF',
          error: '#FF5252',
          info: '#2196F3',
          success: '#4CAF50',
          warning: '#FFC107',
        },
      },
    },
  },
})

const app = createApp(App)

app.use(createPinia())
app.use(router)
app.use(vuetify)
app.use(PrimeVue, {
  theme: {
    preset: Aura,
  },
})
app.use(ToastService)

app.mount('#app')
