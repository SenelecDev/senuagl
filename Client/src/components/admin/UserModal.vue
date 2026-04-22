<template>
  <v-dialog v-model="dialog" max-width="600px" persistent>
    <v-card>
      <v-card-title class="modal-title">
        <span class="headline">{{ user && user.id ? 'Modifier l\'utilisateur' : 'Nouvel utilisateur' }}</span>
        <v-spacer></v-spacer>
        <v-btn icon="mdi-close" variant="text" size="small" @click="closeModal"></v-btn>
      </v-card-title>

      <v-card-text>
        <div v-if="user && user.id" class="user-info">
          <v-icon icon="mdi-account" class="user-icon"></v-icon>
          <div>
            <p class="user-name">{{ user.first_name }} {{ user.name }}</p>
            <p class="user-email">{{ user.email }}</p>
          </div>
        </div>

        <v-form ref="formRef" v-model="valid" @submit.prevent="handleSubmit">
          <div class="form-fields">
            <v-text-field
              v-model="form.name"
              label="Nom de famille"
              variant="outlined"
              density="comfortable"
              :rules="nameRules"
              hide-details="auto"
            />
            <v-text-field
              v-model="form.first_name"
              label="Prénom"
              variant="outlined"
              density="comfortable"
              :rules="firstNameRules"
              hide-details="auto"
            />
            <v-text-field
              v-model="form.matricule"
              label="Matricule"
              variant="outlined"
              density="comfortable"
              :rules="matriculeRules"
              hide-details="auto"
            />
            <v-text-field
              v-model="form.email"
              type="email"
              label="Email"
              variant="outlined"
              density="comfortable"
              :rules="emailRules"
              hide-details="auto"
            />
            <v-text-field
              v-model="form.phone"
              label="Téléphone (optionnel)"
              variant="outlined"
              density="comfortable"
              hide-details="auto"
            />
            <v-select
              v-model="form.department_id"
              :items="departments"
              item-title="name"
              item-value="id"
              label="Département"
              variant="outlined"
              density="comfortable"
              :rules="departmentRules"
              :loading="departmentsLoading"
              placeholder="Sélectionnez un département"
              hide-details="auto"
            />
            <v-select
              v-model="form.role_id"
              :items="roles"
              item-title="nom"
              item-value="id"
              label="Rôle"
              variant="outlined"
              density="comfortable"
              :rules="roleRules"
              :loading="rolesLoading"
              placeholder="Sélectionnez un rôle"
              hide-details="auto"
            />
            <v-select
              v-model="form.manager_id"
              :items="managers"
              :item-title="(item) => `${item.first_name} ${item.name}`"
              item-value="id"
              label="Manager / Supérieur (optionnel)"
              variant="outlined"
              density="comfortable"
              clearable
              placeholder="Sélectionnez un manager"
              hide-details="auto"
            />
            <div class="status-switch">
              <v-switch
                v-model="form.is_active"
                label="Compte actif"
                color="primary"
                hide-details
              />
            </div>

            <div v-if="!user || !user.id" class="password-section">
              <p class="password-section-title">
                <v-icon icon="mdi-lock" size="18"></v-icon>
                Configuration du mot de passe
              </p>
              <v-text-field
                v-model="form.password"
                :type="showPassword ? 'text' : 'password'"
                :append-inner-icon="showPassword ? 'mdi-eye' : 'mdi-eye-off'"
                label="Mot de passe"
                variant="outlined"
                density="comfortable"
                :rules="passwordRules"
                hide-details="auto"
                @click:append-inner="showPassword = !showPassword"
              />
              <v-text-field
                v-model="form.password_confirmation"
                :type="showPasswordConfirm ? 'text' : 'password'"
                :append-inner-icon="showPasswordConfirm ? 'mdi-eye' : 'mdi-eye-off'"
                label="Confirmer le mot de passe"
                variant="outlined"
                density="comfortable"
                :rules="passwordConfirmRules"
                hide-details="auto"
                @click:append-inner="showPasswordConfirm = !showPasswordConfirm"
              />
              <div class="password-strength">
                <v-progress-linear
                  :model-value="passwordStrength.score"
                  :color="passwordStrength.color"
                  height="4"
                  rounded
                ></v-progress-linear>
                <p class="strength-text" :style="{ color: passwordStrength.color }">
                  {{ passwordStrength.text }}
                </p>
              </div>
              <div class="password-requirements">
                <p class="requirements-title">Le mot de passe doit contenir :</p>
                <div class="requirement-item" :class="{ met: form.password.length >= 8 }">
                  <v-icon :icon="form.password.length >= 8 ? 'mdi-check' : 'mdi-close'" size="16"></v-icon>
                  Au moins 8 caractères
                </div>
                <div class="requirement-item" :class="{ met: /[A-Z]/.test(form.password) }">
                  <v-icon :icon="/[A-Z]/.test(form.password) ? 'mdi-check' : 'mdi-close'" size="16"></v-icon>
                  Une lettre majuscule
                </div>
                <div class="requirement-item" :class="{ met: /[a-z]/.test(form.password) }">
                  <v-icon :icon="/[a-z]/.test(form.password) ? 'mdi-check' : 'mdi-close'" size="16"></v-icon>
                  Une lettre minuscule
                </div>
                <div class="requirement-item" :class="{ met: /\d/.test(form.password) }">
                  <v-icon :icon="/\d/.test(form.password) ? 'mdi-check' : 'mdi-close'" size="16"></v-icon>
                  Un chiffre
                </div>
              </div>
            </div>
          </div>
        </v-form>
      </v-card-text>

      <v-card-actions class="modal-actions">
        <v-btn variant="outlined" @click="closeModal" :disabled="loading">
          Annuler
        </v-btn>
        <v-btn color="primary" :loading="loading" :disabled="!valid" @click="handleSubmit">
          {{ user && user.id ? 'Modifier' : 'Créer l\'utilisateur' }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script>
export default {
  name: "UserModal",
  props: {
    modelValue: { type: Boolean, default: false },
    user: { type: Object, default: null },
    roles: { type: Array, default: () => [] },
    departments: { type: Array, default: () => [] },
    managers: { type: Array, default: () => [] },
    rolesLoading: { type: Boolean, default: false },
    departmentsLoading: { type: Boolean, default: false },
    loading: { type: Boolean, default: false },
  },
  emits: ['update:modelValue', 'submit'],
  data() {
    return {
      valid: false,
      showPassword: false,
      showPasswordConfirm: false,
      form: this.initializeForm(),
      nameRules: [
        v => !!v || 'Le nom est requis',
        v => (v && v.length >= 2) || 'Le nom doit contenir au moins 2 caractères',
      ],
      firstNameRules: [
        v => !!v || 'Le prénom est requis',
        v => (v && v.length >= 2) || 'Le prénom doit contenir au moins 2 caractères',
      ],
      matriculeRules: [
        v => !!v || 'Le matricule est requis',
        v => (v && v.length >= 3) || 'Le matricule doit contenir au moins 3 caractères',
      ],
      emailRules: [
        v => !!v || 'L\'email est requis',
        v => /.+@.+\..+/.test(v) || 'L\'email doit être valide',
      ],
      departmentRules: [v => !!v || 'Le département est requis'],
      roleRules: [v => !!v || 'Le rôle est requis'],
      passwordRules: [
        v => !!v || 'Le mot de passe est requis',
        v => (v && v.length >= 8) || 'Le mot de passe doit contenir au moins 8 caractères',
        v => /[A-Z]/.test(v) || 'Le mot de passe doit contenir au moins une lettre majuscule',
        v => /[a-z]/.test(v) || 'Le mot de passe doit contenir au moins une lettre minuscule',
        v => /\d/.test(v) || 'Le mot de passe doit contenir au moins un chiffre',
      ],
    };
  },

  computed: {
    dialog: {
      get() { return this.modelValue; },
      set(value) { this.$emit('update:modelValue', value); }
    },
    passwordConfirmRules() {
      return [
        v => !!v || 'La confirmation du mot de passe est requise',
        v => v === this.form.password || 'Les mots de passe ne correspondent pas',
      ];
    },
    passwordStrength() {
      const password = this.form.password;
      if (!password) return { score: 0, color: '#e0e0e0', text: '' };
      let score = 0;
      let feedback = '';
      if (password.length >= 8) score += 25; else feedback = 'Trop court';
      if (/[A-Z]/.test(password)) score += 25; else if (!feedback) feedback = 'Ajouter une majuscule';
      if (/[a-z]/.test(password)) score += 25; else if (!feedback) feedback = 'Ajouter une minuscule';
      if (/\d/.test(password)) score += 25; else if (!feedback) feedback = 'Ajouter un chiffre';
      if (score < 50) return { score, color: '#f44336', text: feedback || 'Faible' };
      if (score < 75) return { score, color: '#ff9800', text: 'Moyen' };
      if (score < 100) return { score, color: '#2196f3', text: 'Bon' };
      return { score, color: '#4caf50', text: 'Excellent' };
    }
  },

  watch: {
    modelValue(newVal) { if (newVal) this.resetForm(); },
    user: { handler() { this.resetForm(); }, deep: true }
  },

  methods: {
    initializeForm() {
      if (this.user && this.user.id) {
        return {
          id: this.user.id,
          name: this.user.name || '',
          first_name: this.user.first_name || '',
          matricule: this.user.matricule || '',
          email: this.user.email || '',
          phone: this.user.phone || '',
          department_id: this.user.department_id || '',
          role_id: this.user.role_id || '',
          manager_id: this.user.manager_id || null,
          is_active: this.user.is_active !== undefined ? this.user.is_active : true,
        };
      }
      return {
        name: '', first_name: '', matricule: '', email: '',
        phone: '', department_id: '', role_id: '',
        manager_id: null, is_active: true,
        password: '', password_confirmation: '',
      };
    },

    async handleSubmit() {
      const { valid } = await this.$refs.formRef.validate();
      if (!valid) return;
      if ((!this.user || !this.user.id) && this.form.password !== this.form.password_confirmation) return;
      this.$emit('submit', this.form);
    },

    closeModal() {
      this.dialog = false;
      this.resetForm();
    },

    resetForm() {
      this.form = this.initializeForm();
      this.$refs.formRef?.resetValidation();
    }
  }
};
</script>

<style scoped>
.modal-title {
  padding: 20px 24px 16px 24px;
  border-bottom: 1px solid #e0e0e0;
}
.user-info {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px;
  background-color: #f5f5f5;
  border-radius: 8px;
  margin-bottom: 24px;
}
.user-icon { color: #008a9b; font-size: 32px; }
.user-name { font-weight: 600; font-size: 16px; margin: 0; color: #1a1a1a; }
.user-email { color: #6b7280; font-size: 14px; margin: 4px 0 0 0; }
.form-fields { display: flex; flex-direction: column; gap: 16px; }
.status-switch {
  background-color: #f5f5f5;
  padding: 12px 16px;
  border-radius: 8px;
  border-left: 4px solid #008a9b;
}
.password-section {
  background-color: #f9f9f9;
  padding: 20px;
  border-radius: 12px;
  border-left: 4px solid #008a9b;
  margin-top: 16px;
}
.password-section-title {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 15px;
  font-weight: 600;
  color: #374151;
  margin-bottom: 16px;
}
.password-strength { margin: 12px 0; }
.strength-text { font-size: 12px; font-weight: 500; margin: 4px 0 0 0; text-align: right; }
.password-requirements { margin-top: 16px; }
.requirements-title { font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 8px; }
.requirement-item {
  display: flex; align-items: center; gap: 8px;
  font-size: 12px; color: #6b7280; margin-bottom: 4px; transition: color 0.2s ease;
}
.requirement-item.met { color: #10b981; }
.requirement-item .v-icon { color: #ef4444; transition: color 0.2s ease; }
.requirement-item.met .v-icon { color: #10b981; }
.modal-actions {
  padding: 16px 24px 20px 24px;
  border-top: 1px solid #e0e0e0;
  gap: 12px;
}
.modal-actions .v-btn { text-transform: none; font-weight: 500; }
:deep(.v-field) { border-radius: 8px; }
:deep(.v-field--focused .v-field__outline) { border-color: #008a9b; border-width: 2px; }
</style>