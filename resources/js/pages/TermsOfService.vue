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
            Back to App
          </router-link>
        </div>
      </div>
    </div>

    <!-- Content -->
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
      <div class="bg-white rounded-lg shadow-lg p-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Terms of Service</h1>
        <p class="text-gray-600 mb-8">Last updated: {{ lastUpdated }}</p>

        <div class="prose prose-lg max-w-none">
          <!-- Introduction -->
          <section class="mb-8">
            <h2 class="text-2xl font-semibold text-gray-900 mb-4">1. Acceptance of Terms</h2>
            <p class="text-gray-700 leading-relaxed mb-4">
              Welcome to RB Benchmarks. By accessing or using our advertising intelligence and benchmarking platform ("Service"), you agree to be bound by these Terms of Service ("Terms"). If you do not agree to these Terms, please do not use our Service.
            </p>
            <p class="text-gray-700 leading-relaxed">
              These Terms apply to all users of the Service, including marketing agencies, advertisers, and other business users.
            </p>
          </section>

          <!-- Description of Service -->
          <section class="mb-8">
            <h2 class="text-2xl font-semibold text-gray-900 mb-4">2. Description of Service</h2>
            <p class="text-gray-700 leading-relaxed mb-4">
              RB Benchmarks provides a marketing analytics platform that:
            </p>
            <ul class="list-disc list-inside text-gray-700 mb-4 space-y-1">
              <li>Aggregates advertising performance data from multiple platforms (Facebook Ads, Google Ads, TikTok Ads, Snapchat Ads, LinkedIn Ads)</li>
              <li>Provides industry benchmarking insights and analytics</li>
              <li>Offers campaign performance tracking and reporting</li>
              <li>Generates automated reports and alerts</li>
              <li>Supports multi-tenant agency management</li>
            </ul>
          </section>

          <!-- Account Registration -->
          <section class="mb-8">
            <h2 class="text-2xl font-semibold text-gray-900 mb-4">3. Account Registration</h2>
            <p class="text-gray-700 leading-relaxed mb-4">
              To use certain features of our Service, you must register for an account. When you register, you agree to:
            </p>
            <ul class="list-disc list-inside text-gray-700 mb-4 space-y-1">
              <li>Provide accurate, current, and complete information</li>
              <li>Maintain and update your information to keep it accurate</li>
              <li>Maintain the security of your account credentials</li>
              <li>Accept responsibility for all activities under your account</li>
              <li>Notify us immediately of any unauthorized use of your account</li>
            </ul>
          </section>

          <!-- Platform Integrations -->
          <section class="mb-8">
            <h2 class="text-2xl font-semibold text-gray-900 mb-4">4. Platform Integrations</h2>
            <p class="text-gray-700 leading-relaxed mb-4">
              Our Service integrates with third-party advertising platforms. By connecting your advertising accounts, you:
            </p>
            <ul class="list-disc list-inside text-gray-700 mb-4 space-y-1">
              <li>Authorize us to access your advertising data through official APIs</li>
              <li>Confirm you have the authority to connect these accounts</li>
              <li>Understand that we access data in read-only mode (we do not modify your campaigns)</li>
              <li>Agree to comply with each platform's terms of service</li>
            </ul>
            <p class="text-gray-700 leading-relaxed">
              We are not responsible for changes to third-party APIs or platforms that may affect our Service's functionality.
            </p>
          </section>

          <!-- User Conduct -->
          <section class="mb-8">
            <h2 class="text-2xl font-semibold text-gray-900 mb-4">5. User Conduct</h2>
            <p class="text-gray-700 leading-relaxed mb-4">
              You agree not to:
            </p>
            <ul class="list-disc list-inside text-gray-700 mb-4 space-y-1">
              <li>Use the Service for any unlawful purpose</li>
              <li>Attempt to gain unauthorized access to our systems or other users' accounts</li>
              <li>Interfere with or disrupt the Service or its infrastructure</li>
              <li>Reverse engineer, decompile, or attempt to extract source code</li>
              <li>Use automated means to access the Service without our permission</li>
              <li>Share your account credentials with third parties</li>
              <li>Use the Service to transmit harmful content or malware</li>
            </ul>
          </section>

          <!-- Intellectual Property -->
          <section class="mb-8">
            <h2 class="text-2xl font-semibold text-gray-900 mb-4">6. Intellectual Property</h2>
            <p class="text-gray-700 leading-relaxed mb-4">
              The Service, including its content, features, and functionality, is owned by Red Bananas and is protected by intellectual property laws. You retain ownership of your advertising data, and we retain ownership of:
            </p>
            <ul class="list-disc list-inside text-gray-700 mb-4 space-y-1">
              <li>Our platform, software, and technology</li>
              <li>Aggregated benchmarking data and analytics</li>
              <li>Our trademarks, logos, and branding</li>
              <li>Documentation and other materials</li>
            </ul>
          </section>

          <!-- Data and Privacy -->
          <section class="mb-8">
            <h2 class="text-2xl font-semibold text-gray-900 mb-4">7. Data and Privacy</h2>
            <p class="text-gray-700 leading-relaxed mb-4">
              Your use of the Service is also governed by our <router-link to="/privacy-policy" class="text-primary-600 hover:text-primary-500">Privacy Policy</router-link>. By using the Service, you consent to our data practices as described in the Privacy Policy.
            </p>
            <p class="text-gray-700 leading-relaxed">
              We may use aggregated, anonymized data from all users to generate industry benchmarks. This data cannot be used to identify individual users or their specific advertising performance.
            </p>
          </section>

          <!-- Subscription and Payment -->
          <section class="mb-8">
            <h2 class="text-2xl font-semibold text-gray-900 mb-4">8. Subscription and Payment</h2>
            <p class="text-gray-700 leading-relaxed mb-4">
              Some features of our Service require a paid subscription. By subscribing, you agree to:
            </p>
            <ul class="list-disc list-inside text-gray-700 mb-4 space-y-1">
              <li>Pay all applicable fees as described at the time of purchase</li>
              <li>Provide accurate billing information</li>
              <li>Accept automatic renewal of subscriptions unless cancelled</li>
              <li>Receive invoices and payment notifications electronically</li>
            </ul>
          </section>

          <!-- Disclaimer of Warranties -->
          <section class="mb-8">
            <h2 class="text-2xl font-semibold text-gray-900 mb-4">9. Disclaimer of Warranties</h2>
            <p class="text-gray-700 leading-relaxed mb-4">
              THE SERVICE IS PROVIDED "AS IS" AND "AS AVAILABLE" WITHOUT WARRANTIES OF ANY KIND, EXPRESS OR IMPLIED. WE DO NOT WARRANT THAT:
            </p>
            <ul class="list-disc list-inside text-gray-700 mb-4 space-y-1">
              <li>The Service will be uninterrupted or error-free</li>
              <li>Data from third-party platforms will always be accurate or complete</li>
              <li>The Service will meet your specific requirements</li>
              <li>Any errors or defects will be corrected</li>
            </ul>
          </section>

          <!-- Limitation of Liability -->
          <section class="mb-8">
            <h2 class="text-2xl font-semibold text-gray-900 mb-4">10. Limitation of Liability</h2>
            <p class="text-gray-700 leading-relaxed mb-4">
              TO THE MAXIMUM EXTENT PERMITTED BY LAW, RED BANANAS SHALL NOT BE LIABLE FOR ANY INDIRECT, INCIDENTAL, SPECIAL, CONSEQUENTIAL, OR PUNITIVE DAMAGES, INCLUDING BUT NOT LIMITED TO LOSS OF PROFITS, DATA, OR BUSINESS OPPORTUNITIES.
            </p>
            <p class="text-gray-700 leading-relaxed">
              Our total liability for any claims arising from your use of the Service shall not exceed the amount you paid us in the twelve (12) months preceding the claim.
            </p>
          </section>

          <!-- Termination -->
          <section class="mb-8">
            <h2 class="text-2xl font-semibold text-gray-900 mb-4">11. Termination</h2>
            <p class="text-gray-700 leading-relaxed mb-4">
              Either party may terminate this agreement at any time. We may suspend or terminate your access to the Service if you:
            </p>
            <ul class="list-disc list-inside text-gray-700 mb-4 space-y-1">
              <li>Violate these Terms</li>
              <li>Fail to pay applicable fees</li>
              <li>Engage in fraudulent or illegal activity</li>
              <li>Abuse the Service or its resources</li>
            </ul>
            <p class="text-gray-700 leading-relaxed">
              Upon termination, your right to use the Service will immediately cease. You may request export of your data within 30 days of termination.
            </p>
          </section>

          <!-- Governing Law -->
          <section class="mb-8">
            <h2 class="text-2xl font-semibold text-gray-900 mb-4">12. Governing Law</h2>
            <p class="text-gray-700 leading-relaxed">
              These Terms shall be governed by and construed in accordance with the laws of the Kingdom of Saudi Arabia. Any disputes arising from these Terms shall be subject to the exclusive jurisdiction of the courts in Jeddah, Saudi Arabia.
            </p>
          </section>

          <!-- Changes to Terms -->
          <section class="mb-8">
            <h2 class="text-2xl font-semibold text-gray-900 mb-4">13. Changes to Terms</h2>
            <p class="text-gray-700 leading-relaxed">
              We reserve the right to modify these Terms at any time. We will notify you of significant changes by posting a notice on our website or sending you an email. Your continued use of the Service after such modifications constitutes acceptance of the updated Terms.
            </p>
          </section>

          <!-- Contact Information -->
          <section class="mb-8">
            <h2 class="text-2xl font-semibold text-gray-900 mb-4">14. Contact Information</h2>
            <p class="text-gray-700 leading-relaxed mb-4">
              If you have any questions about these Terms, please contact us:
            </p>
            <div class="bg-gray-50 p-4 rounded-lg">
              <div class="flex items-center mb-2">
                <img :src="logoUrl" alt="RB Benchmarks" class="h-6 w-auto mr-2">
                <span class="font-medium">Red Bananas</span>
              </div>
              <p class="text-gray-700">Email: legal@redbananas.com</p>
              <p class="text-gray-700">Website: https://redbananas.com</p>
            </div>
          </section>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <footer class="bg-white border-t">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="flex justify-center items-center text-gray-500 text-sm">
          <span>&copy; {{ currentYear }}</span>
          <img :src="logoUrl" alt="RB Benchmarks" class="h-4 w-auto mx-2">
          <span>All rights reserved.</span>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

const lastUpdated = 'December 9, 2025'
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
