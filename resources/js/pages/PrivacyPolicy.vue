<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white shadow">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center py-6">
          <div class="flex items-center">
            <router-link to="/" class="flex items-center">
              <img :src="logoUrl" alt="RB Benchmarks" class="h-8 w-auto">
            </router-link>
          </div>
          <router-link
            to="/login"
            class="text-primary-600 hover:text-primary-500 font-medium"
          >
            {{ $t('pages.privacy_policy.back_to_app') }}
          </router-link>
        </div>
      </div>
    </div>

    <!-- Content -->
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
      <div class="bg-white rounded-lg shadow-lg p-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">{{ $t('pages.privacy_policy.title') }}</h1>
        <p class="text-gray-600 mb-8">{{ $t('pages.privacy_policy.last_updated', { date: lastUpdated }) }}</p>

        <div class="prose prose-lg max-w-none">
          <!-- Introduction -->
          <section class="mb-8">
            <h2 class="text-2xl font-semibold text-gray-900 mb-4">{{ $t('pages.privacy_policy.sections.introduction.title') }}</h2>
            <p class="text-gray-700 leading-relaxed mb-4">
              <img :src="logoUrl" alt="RB Benchmarks" class="inline h-4 w-auto mr-1">
              {{ $t('pages.privacy_policy.sections.introduction.paragraph1') }}
            </p>
            <p class="text-gray-700 leading-relaxed">
              {{ $t('pages.privacy_policy.sections.introduction.paragraph2') }}
            </p>
          </section>

          <!-- Information We Collect -->
          <section class="mb-8">
            <h2 class="text-2xl font-semibold text-gray-900 mb-4">{{ $t('pages.privacy_policy.sections.information_collection.title') }}</h2>

            <h3 class="text-xl font-medium text-gray-900 mb-3">{{ $t('pages.privacy_policy.sections.information_collection.personal_info.title') }}</h3>
            <p class="text-gray-700 leading-relaxed mb-4">
              {{ $t('pages.privacy_policy.sections.information_collection.personal_info.intro') }}
            </p>
            <ul class="list-disc list-inside text-gray-700 mb-4 space-y-1">
              <li>{{ $t('pages.privacy_policy.sections.information_collection.personal_info.items.name_email') }}</li>
              <li>{{ $t('pages.privacy_policy.sections.information_collection.personal_info.items.company_info') }}</li>
              <li>{{ $t('pages.privacy_policy.sections.information_collection.personal_info.items.contact_info') }}</li>
              <li>{{ $t('pages.privacy_policy.sections.information_collection.personal_info.items.billing_info') }}</li>
            </ul>

            <h3 class="text-xl font-medium text-gray-900 mb-3">{{ $t('pages.privacy_policy.sections.information_collection.advertising_data.title') }}</h3>
            <p class="text-gray-700 leading-relaxed mb-4">
              {{ $t('pages.privacy_policy.sections.information_collection.advertising_data.intro') }}
            </p>
            <ul class="list-disc list-inside text-gray-700 mb-4 space-y-1">
              <li>{{ $t('pages.privacy_policy.sections.information_collection.advertising_data.items.metrics') }}</li>
              <li>{{ $t('pages.privacy_policy.sections.information_collection.advertising_data.items.campaigns') }}</li>
              <li>{{ $t('pages.privacy_policy.sections.information_collection.advertising_data.items.accounts') }}</li>
              <li>{{ $t('pages.privacy_policy.sections.information_collection.advertising_data.items.aggregated') }}</li>
            </ul>

            <h3 class="text-xl font-medium text-gray-900 mb-3">{{ $t('pages.privacy_policy.sections.information_collection.automatic_info.title') }}</h3>
            <p class="text-gray-700 leading-relaxed mb-4">
              {{ $t('pages.privacy_policy.sections.information_collection.automatic_info.intro') }}
            </p>
            <ul class="list-disc list-inside text-gray-700 mb-4 space-y-1">
              <li>{{ $t('pages.privacy_policy.sections.information_collection.automatic_info.items.logs') }}</li>
              <li>{{ $t('pages.privacy_policy.sections.information_collection.automatic_info.items.usage') }}</li>
              <li>{{ $t('pages.privacy_policy.sections.information_collection.automatic_info.items.device') }}</li>
              <li>{{ $t('pages.privacy_policy.sections.information_collection.automatic_info.items.cookies') }}</li>
            </ul>
          </section>

          <!-- How We Use Information -->
          <section class="mb-8">
            <h2 class="text-2xl font-semibold text-gray-900 mb-4">{{ $t('pages.privacy_policy.sections.how_we_use.title') }}</h2>
            <p class="text-gray-700 leading-relaxed mb-4">
              {{ $t('pages.privacy_policy.sections.how_we_use.intro') }}
            </p>
            <ul class="list-disc list-inside text-gray-700 mb-4 space-y-1">
              <li>{{ $t('pages.privacy_policy.sections.how_we_use.items.provide_service') }}</li>
              <li>{{ $t('pages.privacy_policy.sections.how_we_use.items.manage_account') }}</li>
              <li>{{ $t('pages.privacy_policy.sections.how_we_use.items.process_transactions') }}</li>
              <li>{{ $t('pages.privacy_policy.sections.how_we_use.items.generate_benchmarks') }}</li>
              <li>{{ $t('pages.privacy_policy.sections.how_we_use.items.send_updates') }}</li>
              <li>{{ $t('pages.privacy_policy.sections.how_we_use.items.respond_support') }}</li>
              <li>{{ $t('pages.privacy_policy.sections.how_we_use.items.improve_service') }}</li>
              <li>{{ $t('pages.privacy_policy.sections.how_we_use.items.detect_issues') }}</li>
              <li>{{ $t('pages.privacy_policy.sections.how_we_use.items.legal_compliance') }}</li>
            </ul>
          </section>

          <!-- Information Sharing -->
          <section class="mb-8">
            <h2 class="text-2xl font-semibold text-gray-900 mb-4">{{ $t('pages.privacy_policy.sections.information_sharing.title') }}</h2>
            <p class="text-gray-700 leading-relaxed mb-4">
              {{ $t('pages.privacy_policy.sections.information_sharing.intro') }}
            </p>

            <h3 class="text-xl font-medium text-gray-900 mb-3">{{ $t('pages.privacy_policy.sections.information_sharing.service_providers.title') }}</h3>
            <p class="text-gray-700 leading-relaxed mb-4">
              {{ $t('pages.privacy_policy.sections.information_sharing.service_providers.intro') }}
            </p>
            <ul class="list-disc list-inside text-gray-700 mb-4 space-y-1">
              <li>{{ $t('pages.privacy_policy.sections.information_sharing.service_providers.items.hosting') }}</li>
              <li>{{ $t('pages.privacy_policy.sections.information_sharing.service_providers.items.analytics') }}</li>
              <li>{{ $t('pages.privacy_policy.sections.information_sharing.service_providers.items.payment') }}</li>
              <li>{{ $t('pages.privacy_policy.sections.information_sharing.service_providers.items.email') }}</li>
            </ul>

            <h3 class="text-xl font-medium text-gray-900 mb-3">{{ $t('pages.privacy_policy.sections.information_sharing.aggregated_data.title') }}</h3>
            <p class="text-gray-700 leading-relaxed mb-4">
              {{ $t('pages.privacy_policy.sections.information_sharing.aggregated_data.description') }}
            </p>

            <h3 class="text-xl font-medium text-gray-900 mb-3">{{ $t('pages.privacy_policy.sections.information_sharing.legal.title') }}</h3>
            <p class="text-gray-700 leading-relaxed mb-4">
              {{ $t('pages.privacy_policy.sections.information_sharing.legal.description') }}
            </p>
          </section>

          <!-- Data Security -->
          <section class="mb-8">
            <h2 class="text-2xl font-semibold text-gray-900 mb-4">{{ $t('pages.privacy_policy.sections.data_security.title') }}</h2>
            <p class="text-gray-700 leading-relaxed mb-4">
              {{ $t('pages.privacy_policy.sections.data_security.intro') }}
            </p>
            <ul class="list-disc list-inside text-gray-700 mb-4 space-y-1">
              <li>{{ $t('pages.privacy_policy.sections.data_security.items.encryption') }}</li>
              <li>{{ $t('pages.privacy_policy.sections.data_security.items.isolation') }}</li>
              <li>{{ $t('pages.privacy_policy.sections.data_security.items.assessments') }}</li>
              <li>{{ $t('pages.privacy_policy.sections.data_security.items.access_control') }}</li>
              <li>{{ $t('pages.privacy_policy.sections.data_security.items.monitoring') }}</li>
            </ul>
            <p class="text-gray-700 leading-relaxed">
              {{ $t('pages.privacy_policy.sections.data_security.disclaimer') }}
            </p>
          </section>

          <!-- Data Retention -->
          <section class="mb-8">
            <h2 class="text-2xl font-semibold text-gray-900 mb-4">{{ $t('pages.privacy_policy.sections.data_retention.title') }}</h2>
            <p class="text-gray-700 leading-relaxed mb-4">
              {{ $t('pages.privacy_policy.sections.data_retention.intro') }}
            </p>
            <ul class="list-disc list-inside text-gray-700 mb-4 space-y-1">
              <li>{{ $t('pages.privacy_policy.sections.data_retention.items.account') }}</li>
              <li>{{ $t('pages.privacy_policy.sections.data_retention.items.advertising') }}</li>
              <li>{{ $t('pages.privacy_policy.sections.data_retention.items.usage') }}</li>
              <li>{{ $t('pages.privacy_policy.sections.data_retention.items.legal') }}</li>
            </ul>
          </section>

          <!-- Your Rights -->
          <section class="mb-8">
            <h2 class="text-2xl font-semibold text-gray-900 mb-4">{{ $t('pages.privacy_policy.sections.your_rights.title') }}</h2>
            <p class="text-gray-700 leading-relaxed mb-4">
              {{ $t('pages.privacy_policy.sections.your_rights.intro') }}
            </p>
            <ul class="list-disc list-inside text-gray-700 mb-4 space-y-1">
              <li>{{ $t('pages.privacy_policy.sections.your_rights.items.access') }}</li>
              <li>{{ $t('pages.privacy_policy.sections.your_rights.items.correction') }}</li>
              <li>{{ $t('pages.privacy_policy.sections.your_rights.items.deletion') }}</li>
              <li>{{ $t('pages.privacy_policy.sections.your_rights.items.portability') }}</li>
              <li>{{ $t('pages.privacy_policy.sections.your_rights.items.restriction') }}</li>
              <li>{{ $t('pages.privacy_policy.sections.your_rights.items.objection') }}</li>
              <li>{{ $t('pages.privacy_policy.sections.your_rights.items.withdraw') }}</li>
            </ul>
            <p class="text-gray-700 leading-relaxed">
              {{ $t('pages.privacy_policy.sections.your_rights.contact') }}
            </p>
          </section>

          <!-- International Transfers -->
          <section class="mb-8">
            <h2 class="text-2xl font-semibold text-gray-900 mb-4">{{ $t('pages.privacy_policy.sections.international_transfers.title') }}</h2>
            <p class="text-gray-700 leading-relaxed">
              {{ $t('pages.privacy_policy.sections.international_transfers.description') }}
            </p>
          </section>

          <!-- Third-Party Links -->
          <section class="mb-8">
            <h2 class="text-2xl font-semibold text-gray-900 mb-4">{{ $t('pages.privacy_policy.sections.third_party_links.title') }}</h2>
            <p class="text-gray-700 leading-relaxed">
              {{ $t('pages.privacy_policy.sections.third_party_links.description') }}
            </p>
          </section>

          <!-- Children's Privacy -->
          <section class="mb-8">
            <h2 class="text-2xl font-semibold text-gray-900 mb-4">{{ $t('pages.privacy_policy.sections.children_privacy.title') }}</h2>
            <p class="text-gray-700 leading-relaxed">
              {{ $t('pages.privacy_policy.sections.children_privacy.description') }}
            </p>
          </section>

          <!-- Changes to Policy -->
          <section class="mb-8">
            <h2 class="text-2xl font-semibold text-gray-900 mb-4">{{ $t('pages.privacy_policy.sections.changes_policy.title') }}</h2>
            <p class="text-gray-700 leading-relaxed">
              {{ $t('pages.privacy_policy.sections.changes_policy.description') }}
            </p>
          </section>

          <!-- Contact Information -->
          <section class="mb-8">
            <h2 class="text-2xl font-semibold text-gray-900 mb-4">{{ $t('pages.privacy_policy.sections.contact.title') }}</h2>
            <p class="text-gray-700 leading-relaxed mb-4">
              {{ $t('pages.privacy_policy.sections.contact.intro') }}
            </p>
            <div class="bg-gray-50 p-4 rounded-lg">
              <div class="flex items-center mb-2">
                <img :src="logoUrl" alt="RB Benchmarks" class="h-6 w-auto mr-2">
              </div>
              <p class="text-gray-700">{{ $t('pages.privacy_policy.sections.contact.email') }}</p>
              <p class="text-gray-700">{{ $t('pages.privacy_policy.sections.contact.website') }}</p>
            </div>
          </section>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <footer class="bg-white border-t">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="flex justify-center items-center text-gray-500 text-sm">
          <span>© {{ currentYear }}</span>
          <img :src="logoUrl" alt="RB Benchmarks" class="h-4 w-auto mx-2">
          <span>{{ $t('pages.privacy_policy.all_rights_reserved') }}</span>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
const lastUpdated = 'September 24, 2025'
const currentYear = computed(() => new Date().getFullYear())
const logoUrl = '/logo.svg'
</script>

<style scoped>
.prose {
  max-width: none;
}

.prose h2 {
  @apply border-b border-gray-200 pb-2 mb-4;
}

.prose h3 {
  @apply mb-3;
}

.prose p {
  @apply mb-4;
}

.prose ul {
  @apply mb-4;
}

.prose li {
  @apply mb-1;
}
</style>