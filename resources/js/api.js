import axios from 'axios'

const tokenKey = 'token'
const userKey = 'user'
const roleKey = 'userRole'

export const api = axios.create({
  baseURL: '/api',
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  },
})

export function getToken() {
  return localStorage.getItem(tokenKey)
}

export function getUserRole() {
  return localStorage.getItem(roleKey)
}

export function getStoredUser() {
  const raw = localStorage.getItem(userKey)
  return raw ? JSON.parse(raw) : null
}

export function setAuthSession({ token, user, role }) {
  localStorage.setItem(tokenKey, token)
  localStorage.setItem(userKey, JSON.stringify(user))
  localStorage.setItem(roleKey, role)
}

export function clearAuthSession() {
  localStorage.removeItem(tokenKey)
  localStorage.removeItem(userKey)
  localStorage.removeItem(roleKey)
}

api.interceptors.request.use((config) => {
  const token = getToken()

  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }

  return config
})
