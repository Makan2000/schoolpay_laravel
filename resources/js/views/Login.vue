<template>
  <div class="auth-page">
    <div class="auth-card">
      <div>
        <p class="eyebrow">SchoolPay</p>
        <h1>Connexion</h1>
        <p class="description">Accédez à votre espace parent ou super administrateur.</p>
      </div>

      <form class="auth-form" @submit.prevent="handleLogin">
        <label>
          <span>Email</span>
          <input v-model.trim="form.email" type="email" placeholder="nom@exemple.com" required />
        </label>

        <label>
          <span>Mot de passe</span>
          <input v-model="form.password" type="password" placeholder="Votre mot de passe" required />
        </label>

        <p v-if="error" class="feedback error">{{ error }}</p>

        <button class="primary-button" type="submit" :disabled="loading">
          {{ loading ? 'Connexion en cours...' : 'Se connecter' }}
        </button>
      </form>

      <p class="helper">
        Vous n'avez pas encore de compte ?
        <RouterLink to="/register">Créer un compte</RouterLink>
      </p>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { api, setAuthSession } from '../api'

const router = useRouter()
const loading = ref(false)
const error = ref('')
const form = reactive({
  email: '',
  password: '',
})

async function handleLogin() {
  loading.value = true
  error.value = ''

  try {
    const { data } = await api.post('/login', form)

    setAuthSession({
      token: data.token,
      user: data.user,
      role: data.role,
    })

    router.push(data.role === 'superadmin' ? '/superadmin' : '/parent')
  } catch (err) {
    error.value = err.response?.data?.message || 'Email ou mot de passe incorrect.'
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.auth-page {
  min-height: 100vh;
  display: grid;
  place-items: center;
  padding: 24px;
}

.auth-card {
  width: min(100%, 420px);
  padding: 32px;
  border-radius: 24px;
  background: rgba(255, 255, 255, 0.96);
  box-shadow: 0 18px 48px rgba(7, 26, 43, 0.18);
}

.eyebrow {
  margin: 0 0 8px;
  text-transform: uppercase;
  letter-spacing: 0.12em;
  font-size: 0.78rem;
  color: #0f766e;
  font-weight: 700;
}

h1 {
  margin: 0 0 8px;
  color: #0f172a;
}

.description,
.helper {
  color: #475569;
}

.auth-form {
  display: grid;
  gap: 16px;
  margin-top: 24px;
}

label {
  display: grid;
  gap: 8px;
  color: #0f172a;
  font-weight: 600;
}

input {
  border: 1px solid #cbd5e1;
  border-radius: 14px;
  padding: 14px 16px;
  font-size: 1rem;
}

input:focus {
  outline: 2px solid #14b8a6;
  border-color: #14b8a6;
}

.primary-button {
  border: none;
  border-radius: 14px;
  padding: 14px 18px;
  background: linear-gradient(135deg, #0f766e, #0ea5e9);
  color: white;
  font-size: 1rem;
  font-weight: 700;
  cursor: pointer;
}

.primary-button:disabled {
  opacity: 0.7;
  cursor: progress;
}

.feedback {
  margin: 0;
  padding: 12px 14px;
  border-radius: 14px;
}

.error {
  background: #fee2e2;
  color: #b91c1c;
}

.helper {
  margin: 20px 0 0;
}

.helper a {
  color: #0f766e;
  font-weight: 700;
}
</style>
