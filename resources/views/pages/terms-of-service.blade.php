<x-layouts.guest title="{{ __('Terms of Service') }}">
<div class="min-h-screen bg-gray-50">
    <div class="bg-white shadow">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-6">
                <a href="/" class="flex items-center">
                    <img src="/logo.svg" alt="{{ __('RB Benchmarks') }}" class="h-8 w-auto">
                </a>
                <a href="/login" class="text-primary-600 hover:text-primary-500 font-medium">{{ __("Back to App") }}</a>
            </div>
        </div>
    </div>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">{{ __("Terms of Service") }}</h1>
            <p class="text-gray-600 mb-8">{{ __("Last updated: January 1, 2025") }}</p>

            <div class="prose prose-lg max-w-none">
                <section class="mb-8">
                    <h2 class="text-2xl font-semibold text-gray-900 mb-4">{{ __("1. Acceptance of Terms") }}</h2>
                    <p class="text-gray-700 leading-relaxed mb-4">Welcome to RB Benchmarks. By accessing or using our advertising intelligence and benchmarking platform ("Service"), you agree to be bound by these Terms of Service ("Terms"). If you do not agree to these Terms, please do not use our Service.</p>
                </section>

                <section class="mb-8">
                    <h2 class="text-2xl font-semibold text-gray-900 mb-4">{{ __("2. Description of Service") }}</h2>
                    <p class="text-gray-700 leading-relaxed mb-4">{{ __("RB Benchmarks provides a marketing analytics platform that:") }}</p>
                    <ul class="list-disc list-inside text-gray-700 mb-4 space-y-1">
                        <li>{{ __("Aggregates advertising performance data from multiple platforms") }}</li>
                        <li>{{ __("Provides industry benchmarking insights and analytics") }}</li>
                        <li>{{ __("Offers campaign performance tracking and reporting") }}</li>
                        <li>{{ __("Generates automated reports and alerts") }}</li>
                    </ul>
                </section>

                <section class="mb-8">
                    <h2 class="text-2xl font-semibold text-gray-900 mb-4">{{ __("3. User Accounts") }}</h2>
                    <p class="text-gray-700 leading-relaxed mb-4">You are responsible for maintaining the confidentiality of your account credentials and for all activities that occur under your account. You must notify us immediately of any unauthorized use of your account.</p>
                </section>

                <section class="mb-8">
                    <h2 class="text-2xl font-semibold text-gray-900 mb-4">{{ __("4. Data Usage") }}</h2>
                    <p class="text-gray-700 leading-relaxed mb-4">By connecting your advertising accounts, you grant us permission to access and process your advertising data as described in our Privacy Policy. We use this data to provide benchmarking and analytics services.</p>
                </section>

                <section class="mb-8">
                    <h2 class="text-2xl font-semibold text-gray-900 mb-4">{{ __("5. Limitation of Liability") }}</h2>
                    <p class="text-gray-700 leading-relaxed mb-4">{{ __("The Service is provided \"as is\" without warranties of any kind. We shall not be liable for any indirect, incidental, special, consequential, or punitive damages resulting from your use of the Service.") }}</p>
                </section>

                <section class="mb-8">
                    <h2 class="text-2xl font-semibold text-gray-900 mb-4">{{ __("6. Contact Us") }}</h2>
                    <p class="text-gray-700 leading-relaxed">For questions about these Terms, contact us at legal@redbananas.com</p>
                </section>
            </div>
        </div>
    </div>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6 text-center text-sm text-gray-500">
        &copy; {{ date('Y') }} RB Benchmarks. All rights reserved.
    </div>
</div>
</x-layouts.guest>
