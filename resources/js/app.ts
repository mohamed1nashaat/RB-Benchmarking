import { createPinia } from 'pinia'
import { createApp } from 'vue'
import { createI18n } from 'vue-i18n'
import '../css/app.css'
import App from './App.vue'
import './bootstrap'
import router from './router'

// Chart.js register (كما هو)
import {
  BarController,
  BarElement,
  CategoryScale,
  Chart as ChartJS,
  Filler,
  Legend,
  LinearScale,
  LineController,
  LineElement,
  PointElement,
  Title,
  Tooltip,
} from 'chart.js'
ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  LineController,
  BarController,
  Title,
  Tooltip,
  Legend,
  Filler
)

// i18n
import ar from './locales/ar.json'
import en from './locales/en.json'

// Get saved locale from localStorage or use default
const savedLocale = localStorage.getItem('locale') || 'en'
const initialLocale = ['en', 'ar'].includes(savedLocale) ? savedLocale : 'en'

// Set document direction and lang based on locale
document.documentElement.dir = initialLocale === 'ar' ? 'rtl' : 'ltr'
document.documentElement.lang = initialLocale

const i18n = createI18n({
  legacy: false,
  locale: initialLocale,
  fallbackLocale: 'en',
  messages: { en, ar }
})

// 🟢 استدعِ الـ store هنا
import { useAuthStore } from './stores/auth'

// أنشئ التطبيق
const pinia = createPinia()
const app = createApp(App)

app.use(pinia)
app.use(router)
app.use(i18n)
app.mount('#app')

// ✅ Initialize auth store in background without blocking app mounting
const auth = useAuthStore(pinia)
auth.hydrate()
  .catch(() => {
    // تجاهل فشل التهيئة (مثلاً توكن منتهي) — الـrouter guard هيعالج التحويل للّوجين
    console.log('Auth hydration failed - user is not authenticated')
  })
