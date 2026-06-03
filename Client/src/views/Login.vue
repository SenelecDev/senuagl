<template>
  <div class="login-view">
    <div class="login-container">
      <div class="welcome-section">
        <div class="welcome-content">
          <div class="logo-container">
            <img
              src="@/assets/images/logo-senelec.png"
              alt="Logo SENELEC"
              class="company-logo"
            />
          </div>
          <h1>Portail de gestion UAGL DSI</h1>
          <p>
            Bienvenue sur l'espace de gestion des agents. Gagnez en visibilité,
            suivez les postes vacants et accédez aux statistiques en toute
            simplicité.
          </p>
          <ul class="features-list">
            <li>Gestion des agents et de leurs profils</li>
            <li>Suivi des postes et des postes vacants</li>
            <li>Analyse des statistiques</li>
            <li>Planification des avancements et des départs</li>
            <li>Suivi du Budget</li>
          </ul>
        </div>
      </div>

      <div class="form-section">
        <LoginForm />
      </div>
    </div>
  </div>
</template>

<script setup>
import LoginForm from '@/components/LoginForm.vue'
</script>

<style scoped>
.login-view {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px;
  /* background: linear-gradient(135deg, #0c0734 0%, #1d0f56 55%, #2f166f 100%); */
  background: #ffffff;
}

.login-container {
  display: grid;
  grid-template-columns: 1.3fr 1fr;
  width: 100%;
  max-width: 1200px;
  min-height: 660px;
  border-radius: 24px;
  overflow: hidden;
  box-shadow: 0 35px 90px rgba(0, 0, 0, 0.25);
  background-color: rgba(255, 255, 255, 0.05);
  backdrop-filter: blur(10px);
}

.welcome-section {
  position: relative;
  padding: 56px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(145deg, #100644, #261555);
  overflow: hidden;
}

.welcome-section::after {
  content: '';
  position: absolute;
  bottom: -60px;
  left: -30px;
  width: 260px;
  height: 260px;
  background: rgba(255, 255, 255, 0.08);
  border-radius: 50%;
}

.welcome-section::before {
  content: '';
  position: absolute;
  top: -70px;
  right: -40px;
  width: 220px;
  height: 220px;
  background: rgba(255, 255, 255, 0.08);
  border-radius: 50%;
}

.welcome-content {
  position: relative;
  z-index: 1;
  max-width: 500px;
  color: #ffffff;
}

.logo-container {
  margin-bottom: 36px;
  display: flex;
  justify-content: center;
}

.company-logo {
  width: 160px;
  height: auto;
  filter: drop-shadow(0 16px 40px rgba(0, 0, 0, 0.2));
}

.welcome-content h1 {
  font-size: clamp(2rem, 2.5vw, 3rem);
  margin-bottom: 24px;
  line-height: 1.05;
  font-weight: 800;
  color: #ffffff;
}

.welcome-content p {
  font-size: 1rem;
  line-height: 1.8;
  color: rgba(255, 255, 255, 0.9);
  margin-bottom: 28px;
}

.features-list {
  list-style: none;
  padding: 0;
  margin: 0;
  display: grid;
  gap: 14px;
}

.features-list li {
  position: relative;
  padding-left: 32px;
  color: rgba(255, 255, 255, 0.95);
  font-size: 0.99rem;
  line-height: 1.7;
}

.features-list li::before {
  content: '✔';
  position: absolute;
  left: 0;
  top: 0;
  color: #6ee7b7;
  font-weight: 700;
}

.form-section {
  padding: 60px 48px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(255, 255, 255, 0.98);
}

@media (max-width: 960px) {
  .login-container {
    grid-template-columns: 1fr;
    min-height: auto;
  }

  .welcome-section {
    padding: 40px 28px;
  }

  .form-section {
    padding: 40px 28px;
  }
}
</style>