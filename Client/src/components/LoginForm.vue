<template>
  <div class="login-form">
    <div class="form-header">
      <h1>Connexion</h1>
    </div>

    <div v-if="authStore.error" class="error-alert">
      <span class="error-icon">!</span>
      <span class="error-text">{{ authStore.error }}</span>
    </div>

    <form @submit.prevent="submit" autocomplete="off">
      <div class="form-group">
        <label for="email">Adresse e-mail</label>
        <input
          id="email"
          type="email"
          v-model="form.email"
          required
          placeholder="Entrez votre adresse e-mail"
          :disabled="authStore.loading"
          autocomplete="off"
        />
      </div>

      <div class="form-group">
        <label for="password">Mot de passe</label>
        <input
          id="password"
          type="password"
          v-model="form.password"
          required
          placeholder="Entrez votre mot de passe"
          :disabled="authStore.loading"
          autocomplete="off"
        />
      </div>

      <div class="form-actions">
        <button type="submit" class="login-btn" :disabled="authStore.loading">
          <span v-if="authStore.loading" class="loading-spinner"></span>
          {{ authStore.loading ? 'Connexion...' : 'Se connecter' }}
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { reactive } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useRouter, useRoute } from 'vue-router'

const authStore = useAuthStore()
const router = useRouter()
const route = useRoute()

const form = reactive({
  email: '',
  password: '',
})

const submit = async () => {
  try {
    await authStore.login(form)
    router.push(route.query.redirect || '/')
  } catch {
    // L'erreur est gérée dans le store
  }
}
</script>

<style scoped>
.login-form {
  background-color: rgba(255, 255, 255, 0.95);
  border-radius: 18px;
  padding: 36px;
  width: 100%;
  max-width: 420px;
  box-shadow: 0 20px 50px rgba(16, 6, 68, 0.15);
  backdrop-filter: blur(12px);
}

.form-header {
  text-align: center;
  margin-bottom: 32px;
}

.form-header h1 {
  font-size: 2rem;
  font-weight: 700;
  color: #100644;
  margin: 0;
}

.error-alert {
  display: flex;
  align-items: center;
  gap: 10px;
  background: #fee2e2;
  border: 1px solid #fca5a5;
  color: #991b1b;
  padding: 14px 16px;
  border-radius: 12px;
  margin-bottom: 24px;
}

.error-icon {
  font-weight: 700;
}

.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  font-weight: 600;
  color: #374151;
  margin-bottom: 10px;
}

.form-group input {
  width: 100%;
  padding: 14px 16px;
  border: 1px solid #d1d5db;
  border-radius: 14px;
  font-size: 1rem;
  color: #111827;
  background: #f9fafb;
  transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.form-group input:focus {
  outline: none;
  border-color: #008a9b;
  box-shadow: 0 0 0 4px rgba(0, 138, 155, 0.12);
}

.form-actions {
  margin-top: 8px;
}

.login-btn {
  width: 100%;
  padding: 14px 18px;
  border: none;
  border-radius: 14px;
  background: #008a9b;
  color: #ffffff;
  font-weight: 700;
  font-size: 1rem;
  cursor: pointer;
  transition: background 0.2s ease;
}

.login-btn:hover:not(:disabled) {
  background: #006d7a;
}

.login-btn:disabled {
  opacity: 0.75;
  cursor: not-allowed;
}

.loading-spinner {
  display: inline-block;
  width: 16px;
  height: 16px;
  border: 2px solid rgba(255, 255, 255, 0.4);
  border-top-color: #ffffff;
  border-radius: 50%;
  margin-right: 10px;
  animation: spinner 0.8s linear infinite;
}

@keyframes spinner {
  to {
    transform: rotate(360deg);
  }
}
</style>