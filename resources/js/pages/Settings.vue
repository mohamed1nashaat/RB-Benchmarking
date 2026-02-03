<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="md:flex md:items-center md:justify-between">
      <div class="flex-1 min-w-0">
        <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
          {{ $t('pages.settings.title') }}
        </h2>
        <p class="mt-1 text-sm text-gray-500">
          {{ $t('pages.settings.description') }}
        </p>
      </div>
    </div>

    <!-- Settings Tabs -->
    <div class="bg-white shadow">
      <div class="sm:hidden">
        <label for="tabs" class="sr-only">{{ $t('pages.settings.select_tab') }}</label>
        <select
          v-model="activeTab"
          id="tabs"
          name="tabs"
          class="block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md"
        >
          <option v-for="tab in tabs" :key="tab.key" :value="tab.key">
            {{ tab.name }}
          </option>
        </select>
      </div>
      <div class="hidden sm:block">
        <div class="border-b border-gray-200">
          <nav class="-mb-px flex space-x-8" aria-label="Tabs">
            <button
              v-for="tab in tabs"
              :key="tab.key"
              @click="activeTab = tab.key"
              :class="[
                'whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm',
                activeTab === tab.key
                  ? 'border-primary-500 text-primary-600'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
              ]"
            >
              {{ tab.name }}
            </button>
          </nav>
        </div>
      </div>
    </div>

    <!-- Tab Content -->
    <div class="mt-6">
      <!-- General Settings Tab -->
      <div v-if="activeTab === 'general'" class="space-y-6">
        <!-- Profile Settings -->
        <div class="bg-white shadow rounded-lg divide-y divide-gray-200">
          <div class="px-4 py-5 sm:px-6">
            <h3 class="text-lg font-medium text-gray-900">{{ $t('pages.settings.profile.title') }}</h3>
            <p class="mt-1 text-sm text-gray-500">{{ $t('pages.settings.profile.description') }}</p>
          </div>

          <div class="px-4 py-5 sm:p-6 space-y-6">
            <!-- Avatar with upload -->
            <div class="flex items-center space-x-6 rtl:space-x-reverse">
              <div class="relative group">
                <!-- Avatar Image or Initials -->
                <div
                  v-if="authStore.user?.avatar_url"
                  class="w-20 h-20 rounded-full overflow-hidden ring-2 ring-gray-200"
                >
                  <img
                    :src="authStore.user.avatar_url"
                    alt="Profile"
                    class="w-full h-full object-cover"
                  />
                </div>
                <div
                  v-else
                  class="w-20 h-20 bg-primary-100 rounded-full flex items-center justify-center ring-2 ring-gray-200"
                >
                  <span class="text-2xl font-semibold text-primary-700">{{ userInitials }}</span>
                </div>

                <!-- Upload Overlay -->
                <label
                  class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-50 rounded-full opacity-0 group-hover:opacity-100 cursor-pointer transition-opacity"
                >
                  <CameraIcon class="w-6 h-6 text-white" />
                  <input
                    type="file"
                    accept="image/*"
                    class="hidden"
                    @change="handleAvatarUpload"
                    :disabled="uploadingAvatar"
                  />
                </label>

                <!-- Loading spinner -->
                <div
                  v-if="uploadingAvatar"
                  class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-50 rounded-full"
                >
                  <div class="animate-spin rounded-full h-6 w-6 border-2 border-white border-t-transparent"></div>
                </div>
              </div>

              <div class="flex-1">
                <p class="text-sm font-medium text-gray-900">{{ authStore.user?.name }}</p>
                <p class="text-sm text-gray-500">{{ authStore.user?.email }}</p>
                <div class="mt-2 flex items-center space-x-3 rtl:space-x-reverse">
                  <label class="text-sm text-primary-600 hover:text-primary-700 cursor-pointer font-medium">
                    {{ $t('pages.settings.profile.change_photo') }}
                    <input
                      type="file"
                      accept="image/*"
                      class="hidden"
                      @change="handleAvatarUpload"
                      :disabled="uploadingAvatar"
                    />
                  </label>
                  <button
                    v-if="authStore.user?.avatar_url"
                    @click="removeAvatar"
                    :disabled="uploadingAvatar"
                    class="text-sm text-red-600 hover:text-red-700 font-medium"
                  >
                    {{ $t('pages.settings.profile.remove_photo') }}
                  </button>
                </div>
                <p class="mt-1 text-xs text-gray-400">{{ $t('pages.settings.profile.photo_hint') }}</p>
              </div>
            </div>

            <!-- Avatar Success/Error messages -->
            <div v-if="avatarSuccess" class="bg-green-50 border border-green-200 rounded-md p-3">
              <p class="text-sm text-green-800">{{ $t('pages.settings.profile.avatar_success') }}</p>
            </div>
            <div v-if="avatarError" class="bg-red-50 border border-red-200 rounded-md p-3">
              <p class="text-sm text-red-800">{{ avatarError }}</p>
            </div>

            <!-- Success/Error messages -->
            <div v-if="profileSuccess" class="bg-green-50 border border-green-200 rounded-md p-3">
              <p class="text-sm text-green-800">{{ $t('pages.settings.profile.update_success') }}</p>
            </div>
            <div v-if="profileError" class="bg-red-50 border border-red-200 rounded-md p-3">
              <p class="text-sm text-red-800">{{ profileError }}</p>
            </div>

            <!-- Name field -->
            <div>
              <label class="block text-sm font-medium text-gray-700">{{ $t('pages.settings.profile.name') }}</label>
              <input
                v-model="profileForm.name"
                type="text"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
              />
            </div>

            <!-- Email field (read-only) -->
            <div>
              <label class="block text-sm font-medium text-gray-700">{{ $t('pages.settings.profile.email') }}</label>
              <input
                :value="authStore.user?.email"
                type="email"
                disabled
                class="mt-1 block w-full rounded-md border-gray-300 bg-gray-50 shadow-sm sm:text-sm cursor-not-allowed"
              />
              <p class="mt-1 text-xs text-gray-500">{{ $t('pages.settings.profile.email_hint') }}</p>
            </div>

            <!-- Save button -->
            <div class="flex justify-end">
              <button
                @click="saveProfile"
                :disabled="savingProfile"
                class="px-4 py-2 bg-primary-600 text-white rounded-md hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
              >
                {{ savingProfile ? $t('common.saving') : $t('common.save_changes') }}
              </button>
            </div>
          </div>
        </div>

        <!-- Change Password -->
        <div class="bg-white shadow rounded-lg divide-y divide-gray-200">
          <div class="px-4 py-5 sm:px-6">
            <h3 class="text-lg font-medium text-gray-900">{{ $t('pages.settings.password.title') }}</h3>
            <p class="mt-1 text-sm text-gray-500">{{ $t('pages.settings.password.description') }}</p>
          </div>

          <div class="px-4 py-5 sm:p-6 space-y-4">
            <!-- Success/Error messages -->
            <div v-if="passwordSuccess" class="bg-green-50 border border-green-200 rounded-md p-3">
              <p class="text-sm text-green-800">{{ $t('pages.settings.password.update_success') }}</p>
            </div>
            <div v-if="passwordError" class="bg-red-50 border border-red-200 rounded-md p-3">
              <p class="text-sm text-red-800">{{ passwordError }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700">{{ $t('pages.settings.password.current') }}</label>
              <input
                v-model="passwordForm.current_password"
                type="password"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">{{ $t('pages.settings.password.new') }}</label>
              <input
                v-model="passwordForm.new_password"
                type="password"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">{{ $t('pages.settings.password.confirm') }}</label>
              <input
                v-model="passwordForm.new_password_confirmation"
                type="password"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
              />
            </div>
            <div class="flex justify-end">
              <button
                @click="changePassword"
                :disabled="changingPassword || !passwordForm.current_password || !passwordForm.new_password"
                class="px-4 py-2 bg-primary-600 text-white rounded-md hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
              >
                {{ changingPassword ? $t('common.updating') : $t('pages.settings.password.update_button') }}
              </button>
            </div>
          </div>
        </div>

        <!-- Notification Settings -->
        <div class="bg-white shadow rounded-lg divide-y divide-gray-200">
          <div class="px-4 py-5 sm:px-6">
            <h3 class="text-lg font-medium text-gray-900">{{ $t('pages.settings.notifications.title') }}</h3>
            <p class="mt-1 text-sm text-gray-500">{{ $t('pages.settings.notifications.description') }}</p>
          </div>

          <div class="px-4 py-5 sm:p-6 space-y-6">
            <!-- Email Alerts -->
            <div class="flex items-center justify-between">
              <div class="flex-1">
                <p class="text-sm font-medium text-gray-900">{{ $t('pages.settings.notifications.email_alerts') }}</p>
                <p class="text-sm text-gray-500">{{ $t('pages.settings.notifications.email_alerts_desc') }}</p>
              </div>
              <Switch
                v-model="notifications.emailAlerts"
                :class="notifications.emailAlerts ? 'bg-primary-600' : 'bg-gray-200'"
                class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
              >
                <span
                  :class="notifications.emailAlerts ? 'translate-x-5 rtl:-translate-x-5' : 'translate-x-0'"
                  class="pointer-events-none relative inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                />
              </Switch>
            </div>

            <!-- In-App Notifications -->
            <div class="flex items-center justify-between">
              <div class="flex-1">
                <p class="text-sm font-medium text-gray-900">{{ $t('pages.settings.notifications.in_app') }}</p>
                <p class="text-sm text-gray-500">{{ $t('pages.settings.notifications.in_app_desc') }}</p>
              </div>
              <Switch
                v-model="notifications.inApp"
                :class="notifications.inApp ? 'bg-primary-600' : 'bg-gray-200'"
                class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
              >
                <span
                  :class="notifications.inApp ? 'translate-x-5 rtl:-translate-x-5' : 'translate-x-0'"
                  class="pointer-events-none relative inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                />
              </Switch>
            </div>

            <!-- Weekly Summary -->
            <div class="flex items-center justify-between">
              <div class="flex-1">
                <p class="text-sm font-medium text-gray-900">{{ $t('pages.settings.notifications.weekly_summary') }}</p>
                <p class="text-sm text-gray-500">{{ $t('pages.settings.notifications.weekly_summary_desc') }}</p>
              </div>
              <Switch
                v-model="notifications.weeklySummary"
                :class="notifications.weeklySummary ? 'bg-primary-600' : 'bg-gray-200'"
                class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
              >
                <span
                  :class="notifications.weeklySummary ? 'translate-x-5 rtl:-translate-x-5' : 'translate-x-0'"
                  class="pointer-events-none relative inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                />
              </Switch>
            </div>
          </div>
        </div>

        <!-- Display Preferences -->
        <div class="bg-white shadow rounded-lg divide-y divide-gray-200">
          <div class="px-4 py-5 sm:px-6">
            <h3 class="text-lg font-medium text-gray-900">{{ $t('pages.settings.display.title') }}</h3>
            <p class="mt-1 text-sm text-gray-500">{{ $t('pages.settings.display.description') }}</p>
          </div>

          <div class="px-4 py-5 sm:p-6 space-y-6">
            <!-- Default Date Range -->
            <div>
              <label class="block text-sm font-medium text-gray-700">{{ $t('pages.settings.display.default_date_range') }}</label>
              <select
                v-model="displayPrefs.defaultDateRange"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
              >
                <option value="7d">{{ $t('pages.settings.display.date_range_7d') }}</option>
                <option value="30d">{{ $t('pages.settings.display.date_range_30d') }}</option>
                <option value="90d">{{ $t('pages.settings.display.date_range_90d') }}</option>
                <option value="1y">{{ $t('pages.settings.display.date_range_1y') }}</option>
                <option value="all">{{ $t('pages.settings.display.date_range_all') }}</option>
              </select>
            </div>

            <!-- Currency Display (read-only info) -->
            <div>
              <label class="block text-sm font-medium text-gray-700">{{ $t('pages.settings.display.currency') }}</label>
              <div class="mt-1 flex items-center text-sm text-gray-600">
                <span class="bg-gray-100 px-3 py-2 rounded-md font-medium">SAR ({{ $t('pages.settings.display.saudi_riyal') }})</span>
                <span class="ml-2 rtl:ml-0 rtl:mr-2 text-xs text-gray-500">{{ $t('pages.settings.display.currency_hint') }}</span>
              </div>
            </div>

            <!-- Theme (Coming Soon) -->
            <div>
              <div class="flex items-center justify-between">
                <div>
                  <label class="block text-sm font-medium text-gray-700">{{ $t('pages.settings.display.theme') }}</label>
                  <p class="text-sm text-gray-500">{{ $t('pages.settings.display.theme_light') }}</p>
                </div>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                  {{ $t('common.coming_soon') }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Add Industry Modal -->
    <div v-if="showAddIndustryModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 w-96 max-w-lg mx-4">
        <h3 class="text-lg font-medium text-gray-900 mb-4">{{ $t('pages.settings.add_new_industry') }}</h3>

        <form @submit.prevent="addIndustry">
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">{{ $t('pages.settings.display_name') }}</label>
              <input
                v-model="newIndustry.display_name"
                type="text"
                required
                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                :placeholder="$t('placeholders.industry_name')"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">{{ $t('pages.settings.code_name') }}</label>
              <input
                v-model="newIndustry.name"
                type="text"
                required
                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                :placeholder="$t('placeholders.code_name')"
              />
              <p class="mt-1 text-xs text-gray-500">{{ $t('placeholders.code_name_help') }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">{{ $t('pages.settings.description_optional') }}</label>
              <textarea
                v-model="newIndustry.description"
                rows="3"
                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                :placeholder="$t('placeholders.description_optional')"
              ></textarea>
            </div>
          </div>

          <div class="flex justify-end space-x-3 mt-6">
            <button
              type="button"
              @click="showAddIndustryModal = false; resetNewIndustry()"
              class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800"
            >
              {{ $t('common.cancel') }}
            </button>
            <button
              type="submit"
              :disabled="!newIndustry.display_name || !newIndustry.name || submitting"
              class="px-4 py-2 bg-primary-500 text-white text-sm rounded-md hover:bg-primary-600 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {{ submitting ? $t('pages.settings.adding') : $t('pages.settings.add_industry') }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Add Sub-Industry Modal -->
    <div v-if="showSubIndustryModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 w-96 max-w-lg mx-4">
        <h3 class="text-lg font-medium text-gray-900 mb-4">
          {{ $t('pages.settings.add_sub_industry_to', { industry: selectedIndustry?.display_name }) }}
        </h3>

        <form @submit.prevent="addSubIndustry">
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">{{ $t('pages.settings.display_name') }}</label>
              <input
                v-model="newSubIndustry.display_name"
                type="text"
                required
                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                :placeholder="$t('placeholders.category_name')"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">{{ $t('pages.settings.code_name') }}</label>
              <input
                v-model="newSubIndustry.name"
                type="text"
                required
                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                :placeholder="$t('placeholders.code_name')"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">{{ $t('pages.settings.description_optional') }}</label>
              <textarea
                v-model="newSubIndustry.description"
                rows="3"
                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                :placeholder="$t('placeholders.description_optional')"
              ></textarea>
            </div>
          </div>

          <div class="flex justify-end space-x-3 mt-6">
            <button
              type="button"
              @click="showSubIndustryModal = false; resetNewSubIndustry()"
              class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800"
            >
              {{ $t('common.cancel') }}
            </button>
            <button
              type="submit"
              :disabled="!newSubIndustry.display_name || !newSubIndustry.name || submitting"
              class="px-4 py-2 bg-primary-500 text-white text-sm rounded-md hover:bg-primary-600 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {{ submitting ? $t('pages.settings.adding') : $t('pages.settings.add_sub_industry') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { PlusIcon, PencilIcon, TrashIcon, CameraIcon } from '@heroicons/vue/24/outline'
import { Switch } from '@headlessui/vue'
import { getUniqueIndustries, type AdAccount } from '@/utils/industryAggregator'
import { getCategoriesForIndustry } from '@/utils/categoryMapper'
import { useAuthStore } from '@/stores/auth'

const { t } = useI18n()
const authStore = useAuthStore()

// Tab management
const activeTab = ref('general')
const tabs = computed(() => [
  { key: 'general', name: t('pages.settings.tabs.general') },
])

// User initials
const userInitials = computed(() => {
  if (!authStore.user?.name) return ''
  return authStore.user.name
    .split(' ')
    .map(name => name.charAt(0))
    .join('')
    .toUpperCase()
    .slice(0, 2)
})

// Profile form
const profileForm = ref({
  name: authStore.user?.name || ''
})
const savingProfile = ref(false)
const profileSuccess = ref(false)
const profileError = ref('')

// Password form
const passwordForm = ref({
  current_password: '',
  new_password: '',
  new_password_confirmation: ''
})
const changingPassword = ref(false)
const passwordSuccess = ref(false)
const passwordError = ref('')

// Avatar upload
const uploadingAvatar = ref(false)
const avatarSuccess = ref(false)
const avatarError = ref('')

// Handle avatar upload
const handleAvatarUpload = async (event: Event) => {
  const target = event.target as HTMLInputElement
  const file = target.files?.[0]

  if (!file) return

  // Validate file size (max 2MB)
  if (file.size > 2 * 1024 * 1024) {
    avatarError.value = 'File size must be less than 2MB'
    return
  }

  uploadingAvatar.value = true
  avatarSuccess.value = false
  avatarError.value = ''

  try {
    const formData = new FormData()
    formData.append('avatar', file)

    const response = await window.axios.post('/api/me/avatar', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })

    // Update auth store with new user data
    if (response.data.user) {
      authStore.user = response.data.user
    }

    avatarSuccess.value = true
    setTimeout(() => {
      avatarSuccess.value = false
    }, 3000)
  } catch (error: any) {
    avatarError.value = error.response?.data?.message || 'Failed to upload avatar'
  } finally {
    uploadingAvatar.value = false
    // Reset file input
    target.value = ''
  }
}

// Remove avatar
const removeAvatar = async () => {
  uploadingAvatar.value = true
  avatarSuccess.value = false
  avatarError.value = ''

  try {
    const response = await window.axios.delete('/api/me/avatar')

    // Update auth store with new user data
    if (response.data.user) {
      authStore.user = response.data.user
    }

    avatarSuccess.value = true
    setTimeout(() => {
      avatarSuccess.value = false
    }, 3000)
  } catch (error: any) {
    avatarError.value = error.response?.data?.message || 'Failed to remove avatar'
  } finally {
    uploadingAvatar.value = false
  }
}

// Notification settings (stored in localStorage)
const notifications = ref({
  emailAlerts: localStorage.getItem('pref_emailAlerts') !== 'false',
  inApp: localStorage.getItem('pref_inApp') !== 'false',
  weeklySummary: localStorage.getItem('pref_weeklySummary') === 'true'
})

// Display preferences (stored in localStorage)
const displayPrefs = ref({
  defaultDateRange: localStorage.getItem('pref_defaultDateRange') || '30d'
})

// Watch and save notification preferences
watch(notifications, (val) => {
  localStorage.setItem('pref_emailAlerts', val.emailAlerts.toString())
  localStorage.setItem('pref_inApp', val.inApp.toString())
  localStorage.setItem('pref_weeklySummary', val.weeklySummary.toString())
}, { deep: true })

// Watch and save display preferences
watch(displayPrefs, (val) => {
  localStorage.setItem('pref_defaultDateRange', val.defaultDateRange)
}, { deep: true })

// Save profile
const saveProfile = async () => {
  savingProfile.value = true
  profileSuccess.value = false
  profileError.value = ''

  try {
    const response = await window.axios.put('/api/me/profile', {
      name: profileForm.value.name
    })

    // Update auth store
    if (authStore.user) {
      authStore.user.name = profileForm.value.name
    }

    profileSuccess.value = true
    setTimeout(() => {
      profileSuccess.value = false
    }, 3000)
  } catch (error: any) {
    profileError.value = error.response?.data?.message || 'Failed to update profile'
  } finally {
    savingProfile.value = false
  }
}

// Change password
const changePassword = async () => {
  changingPassword.value = true
  passwordSuccess.value = false
  passwordError.value = ''

  try {
    await window.axios.put('/api/me/password', passwordForm.value)

    // Clear form
    passwordForm.value = {
      current_password: '',
      new_password: '',
      new_password_confirmation: ''
    }

    passwordSuccess.value = true
    setTimeout(() => {
      passwordSuccess.value = false
    }, 3000)
  } catch (error: any) {
    passwordError.value = error.response?.data?.message || error.response?.data?.errors?.current_password?.[0] || 'Failed to update password'
  } finally {
    changingPassword.value = false
  }
}

// State
const loadingIndustries = ref(true)
const submitting = ref(false)
const authError = ref(false)
const industries = ref<any[]>([])

// Modals
const showAddIndustryModal = ref(false)
const showSubIndustryModal = ref(false)
const selectedIndustry = ref(null)

// Form data
const newIndustry = ref({
  display_name: '',
  name: '',
  description: ''
})

const newSubIndustry = ref({
  display_name: '',
  name: '',
  description: ''
})

// Helper function to get industry display name
const getIndustryDisplayName = (industry: string): string => {
  const displayNames: Record<string, string> = {
    'automotive': 'Automotive',
    'beauty_fitness': 'Beauty & Fitness',
    'education': 'Education',
    'entertainment_media': 'Entertainment & Media',
    'finance_insurance': 'Finance & Insurance',
    'food_beverage': 'Food & Beverage',
    'health_medicine': 'Health & Medicine',
    'healthcare': 'Healthcare',
    'home_garden': 'Home & Garden',
    'hospitality': 'Hospitality',
    'media_publishing': 'Media & Publishing',
    'nonprofit': 'Non-Profit',
    'real_estate': 'Real Estate',
    'retail_ecommerce': 'Retail & E-commerce',
    'retail': 'Retail',
    'technology': 'Technology',
    'travel_tourism': 'Travel & Tourism',
    'transportation_logistics': 'Transportation & Logistics',
    'construction_manufacturing': 'Construction & Manufacturing',
    'energy_utilities': 'Energy & Utilities',
    'fashion_luxury': 'Fashion & Luxury',
    'telecommunications': 'Telecommunications',
    'agriculture': 'Agriculture',
    'professional_services': 'Professional Services',
    'other': 'Other'
  }

  return displayNames[industry] || industry.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
}

// Load industries from ad accounts data
const loadIndustries = async () => {
  loadingIndustries.value = true
  authError.value = false

  try {

    // Check authentication state first
    const token = localStorage.getItem('auth_token') || sessionStorage.getItem('auth_token')
    const tenantId = localStorage.getItem('current_tenant_id') || sessionStorage.getItem('current_tenant_id')


    if (!token) {
      authError.value = true
      return
    }

    // Fetch ad accounts data
    const response = await window.axios.get('/api/ad-accounts')
    const accounts: AdAccount[] = response.data.data || []


    // Extract unique industries
    const uniqueIndustryNames = getUniqueIndustries(accounts)

    // Build industry objects with categories from categoryMapper
    industries.value = uniqueIndustryNames.map((industryName, index) => {
      // Count accounts for this industry
      const accountsCount = accounts.filter(acc => acc.industry === industryName).length

      // Get categories from categoryMapper
      const categories = getCategoriesForIndustry(industryName)

      // Convert categories to sub_industries format for display
      const subIndustries = categories.map((category, catIndex) => ({
        id: `${industryName}_${catIndex}`,
        display_name: category,
        name: category.toLowerCase().replace(/[^a-z0-9]+/g, '_'),
        description: `Category under ${getIndustryDisplayName(industryName)}`
      }))

      return {
        id: index + 1,
        name: industryName,
        display_name: getIndustryDisplayName(industryName),
        description: `${accountsCount} ad account(s) using this industry`,
        is_active: true,
        accounts_count: accountsCount,
        sub_industries: subIndustries
      }
    })

    // Sort by accounts count descending
    industries.value.sort((a, b) => b.accounts_count - a.accounts_count)


    if (industries.value.length === 0) {
    }

  } catch (error: any) {
    console.error('Error loading industries:', error)
    console.error('Error response:', error.response)
    console.error('Error status:', error.response?.status)
    console.error('Error data:', error.response?.data)

    if (error.response?.status === 401) {
      authError.value = true
    } else if (error.response?.status === 403) {
      authError.value = true
    } else {
      alert(`Failed to load industries: ${error.response?.data?.message || error.message}`)
    }
    industries.value = []
  } finally {
    loadingIndustries.value = false
  }
}

// Handle authentication errors
const handleAuthError = () => {
  // Clear stored authentication data
  localStorage.removeItem('auth_token')
  localStorage.removeItem('auth_token_expires')
  localStorage.removeItem('current_tenant_id')
  sessionStorage.removeItem('auth_token')
  sessionStorage.removeItem('auth_token_expires')
  sessionStorage.removeItem('current_tenant_id')
  
  // Redirect to login
  window.location.href = '/login'
}

// Add new industry
const addIndustry = async () => {
  submitting.value = true
  try {
    const response = await window.axios.post('/api/industries', newIndustry.value)
    industries.value.push(response.data.data)
    showAddIndustryModal.value = false
    resetNewIndustry()
  } catch (error) {
    console.error('Error adding industry:', error)
    alert('Failed to add industry. Please try again.')
  } finally {
    submitting.value = false
  }
}

// Add sub-industry
const showAddSubIndustryModal = (industry) => {
  selectedIndustry.value = industry
  showSubIndustryModal.value = true
}

const addSubIndustry = async () => {
  if (!selectedIndustry.value) return
  
  submitting.value = true
  try {
    const response = await window.axios.post(
      `/api/industries/${selectedIndustry.value.id}/sub-industries`,
      newSubIndustry.value
    )
    
    // Add to the industry's sub_industries array
    if (!selectedIndustry.value.sub_industries) {
      selectedIndustry.value.sub_industries = []
    }
    selectedIndustry.value.sub_industries.push(response.data.data)
    
    showSubIndustryModal.value = false
    resetNewSubIndustry()
  } catch (error) {
    console.error('Error adding sub-industry:', error)
    alert('Failed to add sub-industry. Please try again.')
  } finally {
    submitting.value = false
  }
}

// Edit functions (placeholder)
const editIndustry = (industry) => {
  // TODO: Implement edit modal
}

const editSubIndustry = (subIndustry) => {
  // TODO: Implement edit modal
}

// Delete functions
const deleteIndustry = async (industry) => {
  if (!confirm(`Are you sure you want to delete "${industry.display_name}"? This action cannot be undone.`)) {
    return
  }
  
  try {
    await window.axios.delete(`/api/industries/${industry.id}`)
    industries.value = industries.value.filter(i => i.id !== industry.id)
  } catch (error) {
    console.error('Error deleting industry:', error)
    alert('Failed to delete industry. It may have associated ad accounts.')
  }
}

const deleteSubIndustry = async (subIndustry) => {
  if (!confirm(`Are you sure you want to delete "${subIndustry.display_name}"?`)) {
    return
  }
  
  try {
    await window.axios.delete(`/api/industries/sub-industries/${subIndustry.id}`)
    
    // Remove from the UI
    const industry = industries.value.find(i => i.sub_industries?.some(s => s.id === subIndustry.id))
    if (industry) {
      industry.sub_industries = industry.sub_industries.filter(s => s.id !== subIndustry.id)
    }
  } catch (error) {
    console.error('Error deleting sub-industry:', error)
    alert('Failed to delete sub-industry. It may have associated ad accounts.')
  }
}

// Reset forms
const resetNewIndustry = () => {
  newIndustry.value = {
    display_name: '',
    name: '',
    description: ''
  }
}

const resetNewSubIndustry = () => {
  newSubIndustry.value = {
    display_name: '',
    name: '',
    description: ''
  }
  selectedIndustry.value = null
}

// Auto-generate code name from display name
const generateCodeName = (displayName) => {
  return displayName.toLowerCase()
    .replace(/[^a-z0-9\s]/g, '')
    .replace(/\s+/g, '_')
    .trim()
}

// Watch for display name changes to auto-generate code name
const watchDisplayName = () => {
  const displayNameInput = document.querySelector('input[placeholder="e.g., Technology"]')
  if (displayNameInput) {
    displayNameInput.addEventListener('input', (e) => {
      if (!newIndustry.value.name) {
        newIndustry.value.name = generateCodeName(e.target.value)
      }
    })
  }
}

// Load data on mount
onMounted(() => {
  loadIndustries()
})
</script>