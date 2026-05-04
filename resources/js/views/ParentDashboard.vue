<template>
  <div class="parent-shell">
    <div class="parent-backdrop"></div>

    <header class="hero-card">
      <div>
        <p class="eyebrow">Espace Parent</p>
        <h1>Bonjour {{ user?.nom || 'Parent' }}</h1>
        <p class="hero-copy">
          Suivez vos enfants, reglez les frais de scolarite et gardez une vue claire sur les paiements deja effectues.
        </p>
      </div>

      <div class="hero-actions">
        <div class="identity-chip">
          <span class="identity-dot"></span>
          Compte actif
        </div>
        <button type="button" class="secondary-button" @click="logout">Deconnexion</button>
      </div>
    </header>

    <p v-if="feedback.message" :class="['feedback-banner', feedback.type]">{{ feedback.message }}</p>

    <section class="stats-grid">
      <article class="stat-card teal">
        <span class="stat-label">Mes eleves</span>
        <strong>{{ eleves.length }}</strong>
        <p>Nombre d'eleves rattaches a ce compte</p>
      </article>

      <article class="stat-card blue">
        <span class="stat-label">Paiements</span>
        <strong>{{ paiements.length }}</strong>
        <p>Nombre de paiement effectuer</p>
      </article>

      <article class="stat-card gold">
        <span class="stat-label">Montant verse</span>
        <strong>{{ formatCurrency(totalPaid) }}</strong>
        <p>Somme total de vos paiements valides</p>
      </article>
    </section>

    <section class="content-grid">
      <article class="panel payment-panel">
        <div class="panel-head">
          <div>
            <p class="mini-label">Paiement</p>
            <h2>Regler une scolarite</h2>
          </div>
          <button type="button" class="primary-button" @click="makePaiement" :disabled="savingPaiement">
            {{ savingPaiement ? 'Traitement...' : 'Payer maintenant' }}
          </button>
        </div>

        <form class="stack-form" @submit.prevent="makePaiement">
          <select v-model="paiementForm.eleve_id" required @change="syncPaiementFromEleve">
            <option value="">Choisir un eleve</option>
            <option v-for="eleve in eleves" :key="eleve.id" :value="String(eleve.id)">
              {{ eleve.nom }} {{ eleve.prenom || '' }}
            </option>
          </select>

          <div class="split-fields">
            <select v-model="paiementForm.ecole_id" required @change="onEcoleChangeForPaiement">
              <option value="">Choisir une ecole</option>
              <option v-for="ecole in ecoles" :key="ecole.id" :value="String(ecole.id)">
                {{ ecole.nom }}
              </option>
            </select>

            <select v-model="paiementForm.classe_id" required>
              <option value="">Choisir une classe</option>
              <option v-for="classe in paiementClasses" :key="classe.id" :value="String(classe.id)">
                {{ classe.nom }}
              </option>
            </select>
          </div>

          <input
            v-model.number="paiementForm.montant"
            type="number"
            min="1000"
            step="1000"
            placeholder="Montant en FCFA"
            required
          />
        </form>
      </article>

      <article class="panel side-panel">
        <div class="panel-head compact-head">
          <div>
            <p class="mini-label">Resume</p>
            <h2>Dernieres operations</h2>
          </div>
          <span class="chip">{{ paiements.length }}</span>
        </div>

        <div v-if="paiements.length" class="payments-list">
          <div v-for="paiement in paiements.slice(0, 5)" :key="paiement.id" class="payment-row">
            <div>
              <strong>{{ formatCurrency(paiement.montant) }}</strong>
              <p>{{ paiement.eleve?.nom || 'Eleve' }} · {{ formatDate(paiement.date_paiement || paiement.created_at) }}</p>
            </div>
            <span class="status-chip" :class="paiement.statut">{{ paiement.statut }}</span>
          </div>
        </div>
        <p v-else class="empty-state">Aucun paiement enregistre pour le moment.</p>
      </article>
    </section>

    <section class="content-grid lower-grid">
      <article class="panel add-student-panel">
        <div class="panel-head">
          <div>
            <p class="mini-label">Eleves</p>
            <h2>Ajouter un eleve</h2>
          </div>
          <button type="button" class="primary-button" @click="toggleEleveForm">
            {{ showEleveForm ? 'Fermer le formulaire' : 'Ajouter un eleve' }}
          </button>
        </div>

        <transition name="fade-slide">
          <form v-if="showEleveForm" class="stack-form" @submit.prevent="addEleve">
            <div class="split-fields">
              <input v-model.trim="eleveForm.nom" type="text" placeholder="Nom" required />
              <input v-model.trim="eleveForm.prenom" type="text" placeholder="Prenom" />
            </div>

            <input v-model="eleveForm.date_naissance" type="date" />

            <div class="split-fields">
              <select v-model="eleveForm.ecole_id" required @change="onEcoleChangeForEleve">
                <option value="">Choisir une ecole</option>
                <option v-for="ecole in ecoles" :key="ecole.id" :value="String(ecole.id)">
                  {{ ecole.nom }}
                </option>
              </select>

              <select v-model="eleveForm.classe_id" required>
                <option value="">Choisir une classe</option>
                <option v-for="classe in eleveClasses" :key="classe.id" :value="String(classe.id)">
                  {{ classe.nom }}
                </option>
              </select>
            </div>

            <div class="form-actions">
              <button type="submit" class="primary-button" :disabled="savingEleve">
                {{ savingEleve ? 'Ajout...' : 'Valider l\'ajout' }}
              </button>
              <button type="button" class="ghost-button" @click="closeEleveForm">Annuler</button>
            </div>
          </form>
        </transition>

        <p v-if="!showEleveForm" class="inline-hint">
          Cliquez sur <strong>Ajouter un eleve</strong> pour afficher le formulaire d'inscription.
        </p>
      </article>

      <article class="panel roster-panel">
        <div class="panel-head compact-head">
          <div>
            <p class="mini-label">Suivi</p>
            <h2>Mes enfants</h2>
          </div>
          <span class="chip">{{ eleves.length }}</span>
        </div>

        <div v-if="eleves.length" class="student-list">
          <div v-for="eleve in eleves" :key="eleve.id" class="student-card">
            <div>
              <strong>{{ eleve.nom }} {{ eleve.prenom || '' }}</strong>
              <p>{{ eleve.ecole?.nom || 'Ecole non definie' }}</p>
            </div>
            <div class="student-meta">
              <span>{{ eleve.classe?.nom || 'Classe non definie' }}</span>
              <small>{{ eleve.date_naissance ? formatDateOnly(eleve.date_naissance) : 'Date non renseignee' }}</small>
            </div>
          </div>
        </div>
        <p v-else class="empty-state">Aucun eleve enregistre pour le moment.</p>
      </article>
    </section>

    <section class="panel history-panel">
      <div class="panel-head compact-head">
        <div>
          <p class="mini-label">Historique</p>
          <h2>Tous les paiements</h2>
        </div>
        <span class="chip">{{ paiements.length }}</span>
      </div>

      <div class="table-shell" v-if="paiements.length">
        <table>
          <thead>
            <tr>
              <th>Reference</th>
              <th>Eleve</th>
              <th>Ecole</th>
              <th>Montant</th>
              <th>Date</th>
              <th>Statut</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="paiement in paiements" :key="paiement.id">
              <td>{{ paiement.reference_transaction }}</td>
              <td>{{ paiement.eleve?.nom || '-' }}</td>
              <td>{{ paiement.ecole?.nom || '-' }}</td>
              <td>{{ formatCurrency(paiement.montant) }}</td>
              <td>{{ formatDate(paiement.date_paiement || paiement.created_at) }}</td>
              <td>
                <span class="status-chip" :class="paiement.statut">{{ paiement.statut }}</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <p v-else class="empty-state">Aucun paiement pour le moment.</p>
    </section>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { api, clearAuthSession, getStoredUser, setAuthSession } from '../api'

const router = useRouter()
const user = ref(getStoredUser())
const ecoles = ref([])
const eleves = ref([])
const paiements = ref([])
const eleveClasses = ref([])
const paiementClasses = ref([])
const savingEleve = ref(false)
const savingPaiement = ref(false)
const showEleveForm = ref(false)
const feedback = reactive({
  message: '',
  type: 'success',
})

const eleveForm = reactive({
  nom: '',
  prenom: '',
  date_naissance: '',
  ecole_id: '',
  classe_id: '',
})

const paiementForm = reactive({
  eleve_id: '',
  ecole_id: '',
  classe_id: '',
  montant: 1000,
})

const totalPaid = computed(() =>
  paiements.value
    .filter((paiement) => paiement.statut === 'valide')
    .reduce((sum, paiement) => sum + Number(paiement.montant || 0), 0),
)

function setFeedback(message, type = 'success') {
  feedback.message = message
  feedback.type = type
}

function resetEleveForm() {
  eleveForm.nom = ''
  eleveForm.prenom = ''
  eleveForm.date_naissance = ''
  eleveForm.ecole_id = ''
  eleveForm.classe_id = ''
  eleveClasses.value = []
}

function resetPaiementForm() {
  paiementForm.eleve_id = ''
  paiementForm.ecole_id = ''
  paiementForm.classe_id = ''
  paiementForm.montant = 1000
  paiementClasses.value = []
}

function toggleEleveForm() {
  showEleveForm.value = !showEleveForm.value
}

function closeEleveForm() {
  showEleveForm.value = false
  resetEleveForm()
}

async function loadClasses(ecoleId) {
  if (!ecoleId) {
    return []
  }

  const { data } = await api.get(`/classes-by-ecole/${ecoleId}`)
  return data
}

async function refreshUser() {
  const { data } = await api.get('/me')
  user.value = data
  setAuthSession({
    token: localStorage.getItem('token'),
    user: data,
    role: data.role,
  })
}

async function refreshData() {
  const [{ data: ecolesData }, { data: elevesData }, { data: paiementsData }] = await Promise.all([
    api.get('/ecoles'),
    api.get('/parent/eleves'),
    api.get('/parent/paiements/historique'),
  ])

  ecoles.value = ecolesData
  eleves.value = elevesData
  paiements.value = paiementsData
}

async function onEcoleChangeForEleve() {
  eleveForm.classe_id = ''
  eleveClasses.value = await loadClasses(eleveForm.ecole_id)
}

async function onEcoleChangeForPaiement() {
  paiementForm.classe_id = ''
  paiementClasses.value = await loadClasses(paiementForm.ecole_id)
}

async function syncPaiementFromEleve() {
  const selectedEleve = eleves.value.find((eleve) => String(eleve.id) === paiementForm.eleve_id)

  if (!selectedEleve) {
    return
  }

  paiementForm.ecole_id = String(selectedEleve.ecole_id)
  paiementClasses.value = await loadClasses(selectedEleve.ecole_id)
  paiementForm.classe_id = String(selectedEleve.classe_id)
}

async function addEleve() {
  savingEleve.value = true
  setFeedback('')

  try {
    await api.post('/parent/eleves', {
      ...eleveForm,
      ecole_id: Number(eleveForm.ecole_id),
      classe_id: Number(eleveForm.classe_id),
    })

    await refreshData()
    closeEleveForm()
    setFeedback('Eleve ajoute avec succes.')
  } catch (err) {
    const validationErrors = err.response?.data?.errors
    setFeedback(
      validationErrors
        ? Object.values(validationErrors).flat().join(' ')
        : "Impossible d'ajouter l'eleve.",
      'error',
    )
  } finally {
    savingEleve.value = false
  }
}

async function makePaiement() {
  savingPaiement.value = true
  setFeedback('')

  try {
    await api.post('/parent/paiements/payer', {
      eleve_id: Number(paiementForm.eleve_id),
      ecole_id: Number(paiementForm.ecole_id),
      classe_id: Number(paiementForm.classe_id),
      montant: Number(paiementForm.montant),
    })

    await refreshData()
    resetPaiementForm()
    setFeedback('Paiement enregistre avec succes.')
  } catch (err) {
    const validationErrors = err.response?.data?.errors
    setFeedback(
      validationErrors
        ? Object.values(validationErrors).flat().join(' ')
        : 'Impossible de traiter le paiement.',
      'error',
    )
  } finally {
    savingPaiement.value = false
  }
}

async function logout() {
  try {
    await api.post('/logout')
  } catch {
    // Token deja invalide.
  } finally {
    clearAuthSession()
    router.push('/login')
  }
}

function formatCurrency(value) {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'XOF',
    maximumFractionDigits: 0,
  }).format(Number(value || 0))
}

function formatDate(value) {
  if (!value) {
    return '-'
  }

  return new Date(value).toLocaleString('fr-FR')
}

function formatDateOnly(value) {
  if (!value) {
    return '-'
  }

  return new Date(value).toLocaleDateString('fr-FR')
}

onMounted(async () => {
  try {
    await Promise.all([refreshUser(), refreshData()])
  } catch {
    clearAuthSession()
    router.push('/login')
  }
})
</script>

<style scoped>
.parent-shell {
  position: relative;
  min-height: 100vh;
  padding: 32px;
  background:
    radial-gradient(circle at top left, rgba(245, 158, 11, 0.16), transparent 24%),
    radial-gradient(circle at top right, rgba(14, 165, 233, 0.14), transparent 22%),
    linear-gradient(180deg, #fffdf7 0%, #f8fafc 50%, #eef7fb 100%);
}

.parent-backdrop {
  position: absolute;
  inset: 0;
  background-image: linear-gradient(rgba(15, 23, 42, 0.03) 1px, transparent 1px),
    linear-gradient(90deg, rgba(15, 23, 42, 0.03) 1px, transparent 1px);
  background-size: 34px 34px;
  mask-image: linear-gradient(180deg, rgba(0, 0, 0, 0.35), transparent 84%);
  pointer-events: none;
}

.parent-shell > * {
  position: relative;
  z-index: 1;
}

.hero-card,
.panel,
.stat-card,
.payment-row,
.student-card,
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
  max-width: 660px;
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
.status-chip {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  border-radius: 999px;
  padding: 10px 14px;
  font-weight: 700;
}

.identity-chip,
.chip {
  background: #dff8f3;
  color: #115e59;
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
  grid-template-columns: repeat(3, minmax(0, 1fr));
}

.content-grid {
  grid-template-columns: 1.35fr 0.95fr;
}

.lower-grid {
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

.panel {
  padding: 24px;
  border-radius: 28px;
}

.panel-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
  margin-bottom: 18px;
}

.compact-head {
  margin-bottom: 16px;
}

.stack-form,
.payments-list,
.student-list {
  display: grid;
  gap: 14px;
}

.split-fields,
.form-actions {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 14px;
}

.payment-row,
.student-card {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
  padding: 16px 18px;
  border-radius: 22px;
}

.payment-row p,
.student-card p,
.empty-state,
.inline-hint {
  margin: 6px 0 0;
  color: #64748b;
}

.inline-hint {
  padding: 8px 0 0;
}

.student-meta {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 6px;
  color: #0f172a;
}

.history-panel {
  margin-top: 24px;
}

.table-shell {
  margin-top: 20px;
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

input,
select,
button {
  font: inherit;
}

input,
select {
  width: 100%;
  border: 1px solid #d7e0ea;
  border-radius: 16px;
  padding: 14px 16px;
  background: rgba(255, 255, 255, 0.96);
  color: #0f172a;
}

input:focus,
select:focus {
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

button:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.status-chip.valide {
  background: #dcfce7;
  color: #166534;
}

.status-chip.en_attente {
  background: #fef3c7;
  color: #92400e;
}

.status-chip.echoue {
  background: #fee2e2;
  color: #b91c1c;
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
  .content-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 760px) {
  .parent-shell {
    padding: 18px;
  }

  .hero-card,
  .payment-row,
  .student-card,
  .panel-head {
    flex-direction: column;
    align-items: flex-start;
  }

  .hero-actions,
  .student-meta {
    width: 100%;
    align-items: flex-start;
  }

  .split-fields,
  .form-actions {
    grid-template-columns: 1fr;
  }

  .table-shell th,
  .table-shell td {
    padding: 12px;
  }
}
</style>
