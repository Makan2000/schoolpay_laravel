import './bootstrap';
import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import { api, clearAuthSession } from './api'

api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      clearAuthSession()

      if (router.currentRoute.value.path !== '/login') {
        router.push('/login')
      }
    }

    return Promise.reject(error)
  },
)

createApp(App).use(router).mount('#app')
