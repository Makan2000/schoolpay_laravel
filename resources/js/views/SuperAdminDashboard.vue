<template>
  <div class="dashboard-shell">
    <div class="dashboard-backdrop"></div>

    <header class="hero-card">
      <div>
        <p class="eyebrow">DASHBOARD ADMINISTRATEUR</p>
        <h1>DASHBOARD</h1>
        <p class="hero-copy">
          Ajoutez des ecoles, des classes, des utilisateurs et des paiements depuis l'interface administrateur.
        </p>
      </div>

      <div class="hero-actions">
        <div class="identity-chip">
          <span class="identity-dot"></span>
          Super admin connecte
        </div>
        <button type="button" class="secondary-button" @click="logout">Deconnexion</button>
      </div>
    </header>

    <p v-if="feedback.message" :class="['feedback-banner', feedback.type]">{{ feedback.message }}</p>

    <section class="stats-grid">
      <article class="stat-card teal">
        <span class="stat-label">Ecoles</span>
        <strong>{{ stats.total_ecoles || 0 }}</strong>
        <p>Nombre total des écoles</p>
      </article>

      <article class="stat-card blue">
        <span class="stat-label">Classes</span>
        <strong>{{ stats.total_classes || 0 }}</strong>
        <p>Nombre total des classes</p>
      </article>

      <article class="stat-card gold">
        <span class="stat-label">Parents</span>
        <strong>{{ stats.total_parents || 0 }}</strong>
        <p>Nombre de compte parents</p>
      </article>

      <article class="stat-card dark">
        <span class="stat-label">Montant collecte</span>
        <strong>{{ formatCurrency(stats.montant_total || 0) }}</strong>
        <p>Montant total de paiements réalisé</p>
      </article>
    </section>

    <section class="content-grid">
      <article class="panel panel-form">
        <div class="panel-head">
          <div>
            <p class="mini-label">LISTE DES ECOLES</p>
            <h2>Ecoles</h2>
          </div>
          <button type="button" class="primary-button" @click="toggleEcoleForm">
            {{ showEcoleForm ? 'Fermer le formulaire' : editing.ecoleId ? 'Modifier une ecole' : 'Ajouter une ecole' }}
          </button>
        </div>

        <transition name="fade-slide">
          <form v-if="showEcoleForm" class="stack-form" @submit.prevent="saveEcole">
            <input v-model.trim="ecoleForm.nom" type="text" placeholder="Nom de l'ecole" required />
            <input v-model.trim="ecoleForm.email" type="email" placeholder="Email" required />
            <input v-model.trim="ecoleForm.telephone" type="text" placeholder="Telephone" />
            <textarea v-model.trim="ecoleForm.adresse" rows="3" placeholder="Adresse"></textarea>

            <div class="form-actions">
              <button type="submit" class="primary-button" :disabled="saving.ecole">
                {{ saving.ecole ? 'Traitement...' : editing.ecoleId ? 'Mettre a jour' : 'Ajouter' }}
              </button>
              <button type="button" class="ghost-button" @click="closeEcoleForm">Annuler</button>
            </div>
          </form>
        </transition>

        <p v-if="!showEcoleForm" class="inline-hint">
          Cliquez sur <strong>Ajouter une ecole</strong> pour afficher le formulaire.
        </p>

        <div class="records-list">
          <div v-for="ecole in ecoles" :key="ecole.id" class="record-card">
            <div>
              <strong>{{ ecole.nom }}</strong>
              <p>{{ ecole.email }} · {{ ecole.telephone || 'Sans telephone' }}</p>
            </div>
            <div class="record-actions">
              <button type="button" class="ghost-button" @click="editEcole(ecole)">Modifier</button>
              <button type="button" class="danger-button" @click="deleteEcole(ecole.id)">Supprimer</button>
            </div>
          </div>
        </div>
      </article>

      <article class="panel panel-form">
        <div class="panel-head">
          <div>
            <p class="mini-label">LISTE DES CLASSES</p>
            <h2>Classes</h2>
          </div>
          <button type="button" class="primary-button" @click="toggleClasseForm">
            {{ showClasseForm ? 'Fermer le formulaire' : editing.classeId ? 'Modifier une classe' : 'Ajouter une classe' }}
          </button>
        </div>

        <transition name="fade-slide">
          <form v-if="showClasseForm" class="stack-form" @submit.prevent="saveClasse">
            <input v-model.trim="classeForm.nom" type="text" placeholder="Nom de la classe" required />
            <select v-model="classeForm.ecole_id" required>
              <option value="">Choisir une ecole</option>
              <option v-for="ecole in ecoles" :key="ecole.id" :value="String(ecole.id)">
                {{ ecole.nom }}
              </option>
            </select>

            <div class="form-actions">
              <button type="submit" class="primary-button" :disabled="saving.classe">
                {{ saving.classe ? 'Traitement...' : editing.classeId ? 'Mettre a jour' : 'Ajouter' }}
              </button>
              <button type="button" class="ghost-button" @click="closeClasseForm">Annuler</button>
            </div>
          </form>
        </transition>

        <p v-if="!showClasseForm" class="inline-hint">
          Cliquez sur <strong>Ajouter une classe</strong> pour afficher le formulaire.
        </p>

        <div class="records-list">
          <div v-for="classe in classes" :key="classe.id" class="record-card">
            <div>
              <strong>{{ classe.nom }}</strong>
              <p>{{ classe.ecole?.nom || 'Ecole inconnue' }}</p>
            </div>
            <div class="record-actions">
              <button type="button" class="ghost-button" @click="editClasse(classe)">Modifier</button>
              <button type="button" class="danger-button" @click="deleteClasse(classe.id)">Supprimer</button>
            </div>
          </div>
        </div>
      </article>
    </section>

    <section class="content-grid large-bottom">
      <article class="panel wide-panel">
        <div class="panel-head">
          <div>
            <p class="mini-label">LISTE DES UTILISATEURS</p>
            <h2>Utilisateurs</h2>
          </div>
          <button type="button" class="primary-button" @click="toggleUserForm">
            {{ showUserForm ? 'Fermer le formulaire' : editing.userId ? 'Modifier un utilisateur' : 'Ajouter un utilisateur' }}
          </button>
        </div>

        <transition name="fade-slide">
          <form v-if="showUserForm" class="inline-form" @submit.prevent="saveUser">
            <input v-model.trim="userForm.nom" type="text" placeholder="Nom" required />
            <input v-model.trim="userForm.email" type="email" placeholder="Email" required />
            <input v-model.trim="userForm.telephone" type="text" placeholder="Telephone" />
            <input
              v-model="userForm.password"
              type="password"
              :placeholder="editing.userId ? 'Nouveau mot de passe (optionnel)' : 'Mot de passe'"
              :required="!editing.userId"
            />
            <select v-model="userForm.role" required>
              <option value="parent">Parent</option>
              <option value="superadmin">Super administrateur</option>
            </select>

            <div class="form-actions full-span">
              <button type="submit" class="primary-button" :disabled="saving.user">
                {{ saving.user ? 'Traitement...' : editing.userId ? 'Mettre a jour' : 'Ajouter' }}
              </button>
              <button type="button" class="ghost-button" @click="closeUserForm">Annuler</button>
            </div>
          </form>
        </transition>

        <p v-if="!showUserForm" class="inline-hint">
          Cliquez sur <strong>Ajouter un utilisateur</strong> pour afficher le formulaire.
        </p>

        <div class="table-shell">
          <table>
            <thead>
              <tr>
                <th>Nom</th>
                <th>Email</th>
                <th>Role</th>
                <th>Telephone</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in users" :key="item.id">
                <td>{{ item.nom }}</td>
                <td>{{ item.email }}</td>
                <td>
                  <span class="badge" :class="item.role === 'superadmin' ? 'badge-dark' : 'badge-soft'">
                    {{ item.role }}
                  </span>
                </td>
                <td>{{ item.telephone || '-' }}</td>
                <td>
                  <div class="record-actions">
                    <button type="button" class="ghost-button" @click="editUser(item)">Modifier</button>
                    <button
                      type="button"
                      class="danger-button"
                      @click="deleteUser(item)"
                      :disabled="item.role === 'superadmin'"
                    >
                      Supprimer
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </article>

      <article class="panel payments-panel">
        <div class="panel-head">
          <div>
            <p class="mini-label">LISTE DES PAIEMENTS</p>
            <h2>Paiements recents</h2>
          </div>
          <span class="chip">{{ paiements.length }}</span>
        </div>

        <div class="payments-list">
          <div v-for="paiement in paiements" :key="paiement.id" class="payment-row">
            <div>
              <strong>{{ formatCurrency(paiement.montant) }}</strong>
              <p>
                {{ paiement.parent?.nom || 'Parent' }} · {{ paiement.eleve?.nom || 'Eleve' }} ·
                {{ paiement.ecole?.nom || 'Ecole' }}
              </p>
            </div>
            <select v-model="paiement.statut" @change="updatePaiementStatut(paiement)">
              <option value="en_attente">en_attente</option>
              <option value="valide">valide</option>
              <option value="echoue">echoue</option>
            </select>
          </div>
        </div>
      </article>
    </section>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { api, clearAuthSession } from '../api'

const router = useRouter()
const stats = ref({})
const ecoles = ref([])
const classes = ref([])
const users = ref([])
const paiements = ref([])
const feedback = reactive({
  message: '',
  type: 'success',
})
const editing = reactive({
  ecoleId: null,
  classeId: null,
  userId: null,
})
const saving = reactive({
  ecole: false,
  classe: false,
  user: false,
})
const showEcoleForm = ref(false)
const showClasseForm = ref(false)
const showUserForm = ref(false)

const ecoleForm = reactive({
  nom: '',
  email: '',
  telephone: '',
  adresse: '',
})

const classeForm = reactive({
  nom: '',
  ecole_id: '',
})

const userForm = reactive({
  nom: '',
  email: '',
  telephone: '',
  password: '',
  role: 'parent',
})

function setFeedback(message, type = 'success') {
  feedback.message = message
  feedback.type = type
}

function resetEcoleForm() {
  editing.ecoleId = null
  ecoleForm.nom = ''
  ecoleForm.email = ''
  ecoleForm.telephone = ''
  ecoleForm.adresse = ''
}

function resetClasseForm() {
  editing.classeId = null
  classeForm.nom = ''
  classeForm.ecole_id = ''
}

function resetUserForm() {
  editing.userId = null
  userForm.nom = ''
  userForm.email = ''
  userForm.telephone = ''
  userForm.password = ''
  userForm.role = 'parent'
}

function toggleEcoleForm() {
  showEcoleForm.value = !showEcoleForm.value
}

function closeEcoleForm() {
  showEcoleForm.value = false
  resetEcoleForm()
}

function toggleClasseForm() {
  showClasseForm.value = !showClasseForm.value
}

function closeClasseForm() {
  showClasseForm.value = false
  resetClasseForm()
}

function toggleUserForm() {
  showUserForm.value = !showUserForm.value
}

function closeUserForm() {
  showUserForm.value = false
  resetUserForm()
}

async function refreshDashboard() {
  const [
    { data: dashboardData },
    { data: ecolesData },
    { data: classesData },
    { data: usersData },
    { data: paiementsData },
  ] = await Promise.all([
    api.get('/superadmin/dashboard'),
    api.get('/superadmin/ecoles'),
    api.get('/superadmin/classes'),
    api.get('/superadmin/users'),
    api.get('/superadmin/paiements'),
  ])

  stats.value = dashboardData
  ecoles.value = ecolesData
  classes.value = classesData
  users.value = usersData
  paiements.value = paiementsData
}

async function saveEcole() {
  saving.ecole = true
  setFeedback('')

  try {
    const payload = { ...ecoleForm }

    if (editing.ecoleId) {
      await api.put(`/superadmin/ecoles/${editing.ecoleId}`, payload)
      setFeedback('Ecole mise a jour avec succes.')
    } else {
      await api.post('/superadmin/ecoles', payload)
      setFeedback('Ecole ajoutee avec succes.')
    }

    await refreshDashboard()
    closeEcoleForm()
  } catch (err) {
    setFeedback(extractError(err, "Impossible d'enregistrer l'ecole."), 'error')
  } finally {
    saving.ecole = false
  }
}

function editEcole(ecole) {
  editing.ecoleId = ecole.id
  ecoleForm.nom = ecole.nom
  ecoleForm.email = ecole.email
  ecoleForm.telephone = ecole.telephone || ''
  ecoleForm.adresse = ecole.adresse || ''
  showEcoleForm.value = true
}

async function deleteEcole(id) {
  try {
    await api.delete(`/superadmin/ecoles/${id}`)
    await refreshDashboard()
    if (editing.ecoleId === id) {
      closeEcoleForm()
    }
    setFeedback('Ecole supprimee avec succes.')
  } catch (err) {
    setFeedback(extractError(err, "Impossible de supprimer l'ecole."), 'error')
  }
}

async function saveClasse() {
  saving.classe = true
  setFeedback('')

  try {
    const payload = {
      nom: classeForm.nom,
      ecole_id: Number(classeForm.ecole_id),
    }

    if (editing.classeId) {
      await api.put(`/superadmin/classes/${editing.classeId}`, payload)
      setFeedback('Classe mise a jour avec succes.')
    } else {
      await api.post('/superadmin/classes', payload)
      setFeedback('Classe ajoutee avec succes.')
    }

    await refreshDashboard()
    closeClasseForm()
  } catch (err) {
    setFeedback(extractError(err, "Impossible d'enregistrer la classe."), 'error')
  } finally {
    saving.classe = false
  }
}

function editClasse(classe) {
  editing.classeId = classe.id
  classeForm.nom = classe.nom
  classeForm.ecole_id = String(classe.ecole_id)
  showClasseForm.value = true
}

async function deleteClasse(id) {
  try {
    await api.delete(`/superadmin/classes/${id}`)
    await refreshDashboard()
    if (editing.classeId === id) {
      closeClasseForm()
    }
    setFeedback('Classe supprimee avec succes.')
  } catch (err) {
    setFeedback(extractError(err, 'Impossible de supprimer la classe.'), 'error')
  }
}

async function saveUser() {
  saving.user = true
  setFeedback('')

  try {
    const payload = {
      nom: userForm.nom,
      email: userForm.email,
      telephone: userForm.telephone,
      role: userForm.role,
    }

    if (userForm.password) {
      payload.password = userForm.password
    }

    if (editing.userId) {
      await api.put(`/superadmin/users/${editing.userId}`, payload)
      setFeedback('Utilisateur mis a jour avec succes.')
    } else {
      await api.post('/superadmin/users', payload)
      setFeedback('Utilisateur ajoute avec succes.')
    }

    await refreshDashboard()
    closeUserForm()
  } catch (err) {
    setFeedback(extractError(err, "Impossible d'enregistrer l'utilisateur."), 'error')
  } finally {
    saving.user = false
  }
}

function editUser(item) {
  editing.userId = item.id
  userForm.nom = item.nom
  userForm.email = item.email
  userForm.telephone = item.telephone || ''
  userForm.password = ''
  userForm.role = item.role
  showUserForm.value = true
}

async function deleteUser(item) {
  if (item.role === 'superadmin') {
    setFeedback('Impossible de supprimer un super administrateur.', 'error')
    return
  }

  const confirmed = window.confirm(`Voulez-vous vraiment supprimer ${item.nom} ?`)
  if (!confirmed) {
    return
  }

  try {
    await api.delete(`/superadmin/users/${item.id}`)
    await refreshDashboard()
    if (editing.userId === item.id) {
      closeUserForm()
    }
    setFeedback('Utilisateur supprime avec succes.')
  } catch (err) {
    setFeedback(extractError(err, "Impossible de supprimer l'utilisateur."), 'error')
  }
}

async function updatePaiementStatut(paiement) {
  try {
    await api.put(`/superadmin/paiements/${paiement.id}/statut`, {
      statut: paiement.statut,
    })
    await refreshDashboard()
    setFeedback('Statut du paiement mis a jour.')
  } catch (err) {
    setFeedback(extractError(err, 'Impossible de mettre a jour le paiement.'), 'error')
  }
}

function extractError(err, fallback) {
  const validationErrors = err.response?.data?.errors
  return validationErrors
    ? Object.values(validationErrors).flat().join(' ')
    : err.response?.data?.message || fallback
}

function formatCurrency(value) {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'XOF',
    maximumFractionDigits: 0,
  }).format(Number(value || 0))
}

async function logout() {
  try {
    await api.post('/logout')
  } catch {
    // Session deja expiree.
  } finally {
    clearAuthSession()
    router.push('/login')
  }
}

onMounted(async () => {
  try {
    await refreshDashboard()
  } catch {
    clearAuthSession()
    router.push('/login')
  }
})
</script>

<style scoped>
.dashboard-shell {
  position: relative;
  min-height: 100vh;
  padding: 32px;
  background:
    radial-gradient(circle at top left, rgba(14, 165, 233, 0.18), transparent 28%),
    radial-gradient(circle at top right, rgba(15, 118, 110, 0.14), transparent 24%),
    linear-gradient(180deg, #f4fbfb 0%, #f8fafc 48%, #eef6fb 100%);
}

.dashboard-backdrop {
  position: absolute;
  inset: 0;
  background-image: linear-gradient(rgba(15, 23, 42, 0.03) 1px, transparent 1px),
    linear-gradient(90deg, rgba(15, 23, 42, 0.03) 1px, transparent 1px);
  background-size: 36px 36px;
  mask-image: linear-gradient(180deg, rgba(0, 0, 0, 0.38), transparent 80%);
  pointer-events: none;
}

.dashboard-shell > * {
  position: relative;
  z-index: 1;
}

.hero-card,
.panel,
.stat-card,
.record-card,
.payment-row,
.table-shell {
  background: rgba(255, 255, 255, 0.9);
  border: 1px solid rgba(148, 163, 184, 0.16);
  box-shadow: 0 22px 60px rgba(15, 23, 42, 0.08);
  backdrop-filter: blur(16px);
}

.hero-card {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 24px;
  padding: 30px 34px;
  border-radius: 32px;
}

.eyebrow,
.mini-label,
.stat-label {
  margin: 0 0 10px;
  text-transform: uppercase;
  letter-spacing: 0.14em;
  font-size: 0.76rem;
  font-weight: 700;
}

.eyebrow,
.mini-label {
  color: #0f766e;
}

.hero-card h1,
.panel h2 {
  margin: 0;
  color: #0f172a;
}

.hero-card h1 {
  font-size: clamp(2rem, 3vw, 3rem);
}

.hero-copy {
  max-width: 640px;
  margin: 12px 0 0;
  color: #475569;
  line-height: 1.6;
}

.hero-actions {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 14px;
}

.identity-chip,
.chip,
.badge {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  border-radius: 999px;
  padding: 10px 14px;
  font-weight: 700;
}

.identity-chip,
.chip,
.badge-soft {
  background: #dff8f3;
  color: #115e59;
}

.badge-dark {
  background: #dbeafe;
  color: #1d4ed8;
}

.identity-dot {
  width: 9px;
  height: 9px;
  border-radius: 50%;
  background: #14b8a6;
}

.feedback-banner {
  margin: 20px 0 0;
  border-radius: 18px;
  padding: 14px 18px;
  font-weight: 700;
}

.feedback-banner.success {
  background: #dcfce7;
  color: #166534;
}

.feedback-banner.error {
  background: #fee2e2;
  color: #b91c1c;
}

.stats-grid,
.content-grid {
  display: grid;
  gap: 24px;
  margin-top: 24px;
}

.stats-grid {
  grid-template-columns: repeat(4, minmax(0, 1fr));
}

.content-grid {
  grid-template-columns: repeat(2, minmax(0, 1fr));
}

.large-bottom {
  align-items: start;
}

.stat-card {
  padding: 22px;
  border-radius: 28px;
}

.stat-card strong {
  display: block;
  margin-top: 8px;
  font-size: 1.8rem;
  color: #0f172a;
}

.stat-card p {
  margin: 10px 0 0;
  color: #64748b;
}

.stat-card.teal {
  background: linear-gradient(180deg, rgba(240, 253, 250, 0.98), rgba(255, 255, 255, 0.92));
}

.stat-card.blue {
  background: linear-gradient(180deg, rgba(239, 246, 255, 0.98), rgba(255, 255, 255, 0.92));
}

.stat-card.gold {
  background: linear-gradient(180deg, rgba(255, 251, 235, 0.98), rgba(255, 255, 255, 0.92));
}

.stat-card.dark {
  background: linear-gradient(135deg, #0f172a, #1e293b);
}

.stat-card.dark,
.stat-card.dark strong,
.stat-card.dark p,
.stat-card.dark .stat-label {
  color: #f8fafc;
}

.panel {
  padding: 24px;
  border-radius: 28px;
}

.panel-form {
  min-height: 100%;
}

.panel-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
  margin-bottom: 18px;
}

.stack-form,
.inline-form,
.records-list,
.payments-list {
  display: grid;
  gap: 14px;
}

.inline-form {
  grid-template-columns: repeat(5, minmax(0, 1fr));
}

.records-list,
.table-shell,
.payments-list {
  margin-top: 22px;
}

.record-card,
.payment-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
  padding: 16px 18px;
  border-radius: 22px;
}

.record-card p,
.payment-row p,
.inline-hint {
  margin: 6px 0 0;
  color: #64748b;
}

.inline-hint {
  padding-top: 6px;
}

.wide-panel {
  min-width: 0;
}

.table-shell {
  overflow: auto;
  border-radius: 24px;
}

.table-shell table {
  width: 100%;
  border-collapse: collapse;
}

.table-shell th,
.table-shell td {
  padding: 16px 18px;
  text-align: left;
  border-bottom: 1px solid #e2e8f0;
}

.table-shell th {
  color: #475569;
  font-size: 0.86rem;
  text-transform: uppercase;
  letter-spacing: 0.08em;
}

.record-actions,
.form-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.full-span {
  grid-column: 1 / -1;
}

input,
select,
textarea,
button {
  font: inherit;
}

input,
select,
textarea {
  width: 100%;
  border: 1px solid #d7e0ea;
  border-radius: 16px;
  padding: 14px 16px;
  background: rgba(255, 255, 255, 0.96);
  color: #0f172a;
}

input:focus,
select:focus,
textarea:focus {
  outline: 2px solid rgba(20, 184, 166, 0.2);
  border-color: #14b8a6;
}

button {
  border: none;
  border-radius: 16px;
  padding: 12px 16px;
  font-weight: 700;
  cursor: pointer;
}

.primary-button {
  background: linear-gradient(135deg, #0f766e, #0ea5e9);
  color: white;
}

.secondary-button {
  background: linear-gradient(135deg, #0f172a, #334155);
  color: white;
}

.ghost-button {
  background: #e2e8f0;
  color: #0f172a;
}

.danger-button {
  background: #fee2e2;
  color: #b91c1c;
}

button:disabled {
  opacity: 0.65;
  cursor: not-allowed;
}

.payments-panel select {
  width: auto;
  min-width: 138px;
}

.fade-slide-enter-active,
.fade-slide-leave-active {
  transition: all 0.25s ease;
}

.fade-slide-enter-from,
.fade-slide-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}

@media (max-width: 1200px) {
  .stats-grid,
  .content-grid,
  .inline-form {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 760px) {
  .dashboard-shell {
    padding: 18px;
  }

  .hero-card,
  .record-card,
  .payment-row,
  .panel-head {
    flex-direction: column;
    align-items: flex-start;
  }

  .hero-actions {
    width: 100%;
    align-items: flex-start;
  }

  .stats-grid,
  .content-grid,
  .inline-form {
    grid-template-columns: 1fr;
  }

  .table-shell th,
  .table-shell td {
    padding: 12px;
  }
}
</style>
