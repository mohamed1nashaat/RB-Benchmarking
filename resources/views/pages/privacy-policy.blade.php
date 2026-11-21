<x-layouts.guest title="{{ __('pages.privacy_policy.title') }}">
<div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white shadow">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-6">
                <a href="/" class="flex items-center">
                    <img src="/logo.svg" alt="{{ __('RB Benchmarks') }}" class="h-8 w-auto">
                </a>
                <a href="/login" class="text-primary-600 hover:text-primary-500 font-medium">
                    {{ __('pages.privacy_policy.back_to_app') }}
                </a>
            </div>
        </div>
    </div>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">{{ __('pages.privacy_policy.title') }}</h1>
            <p class="text-gray-600 mb-8">{{ str_replace('{date}', 'January 1, 2025', __('pages.privacy_policy.last_updated')) }}</p>

            <div class="prose prose-lg max-w-none">
                <section class="mb-8">
                    <h2 class="text-2xl font-semibold text-gray-900 mb-4">{{ __('pages.privacy_policy.sections.introduction.title') }}</h2>
                    <p class="text-gray-700 leading-relaxed mb-4">{{ __('pages.privacy_policy.sections.introduction.paragraph1') }}</p>
                    <p class="text-gray-700 leading-relaxed">{{ __('pages.privacy_policy.sections.introduction.paragraph2') }}</p>
                </section>

                <section class="mb-8">
                    <h2 class="text-2xl font-semibold text-gray-900 mb-4">{{ __('pages.privacy_policy.sections.information_collection.title') }}</h2>
                    <h3 class="text-xl font-medium text-gray-900 mb-3">{{ __('pages.privacy_policy.sections.information_collection.personal_info.title') }}</h3>
                    <p class="text-gray-700 leading-relaxed mb-4">{{ __('pages.privacy_policy.sections.information_collection.personal_info.intro') }}</p>
                    <ul class="list-disc list-inside text-gray-700 mb-4 space-y-1">
                        <li>{{ __('pages.privacy_policy.sections.information_collection.personal_info.items.name_email') }}</li>
                        <li>{{ __('pages.privacy_policy.sections.information_collection.personal_info.items.company_info') }}</li>
                        <li>{{ __('pages.privacy_policy.sections.information_collection.personal_info.items.contact_info') }}</li>
                        <li>{{ __('pages.privacy_policy.sections.information_collection.personal_info.items.billing_info') }}</li>
                    </ul>
                </section>

                <section class="mb-8">
                    <h2 class="text-2xl font-semibold text-gray-900 mb-4">{{ __('pages.privacy_policy.sections.how_we_use.title') }}</h2>
                    <p class="text-gray-700 leading-relaxed mb-4">{{ __('pages.privacy_policy.sections.how_we_use.intro') }}</p>
                    <ul class="list-disc list-inside text-gray-700 mb-4 space-y-1">
                        <li>{{ __('pages.privacy_policy.sections.how_we_use.items.provide_service') }}</li>
                        <li>{{ __('pages.privacy_policy.sections.how_we_use.items.manage_account') }}</li>
                        <li>{{ __('pages.privacy_policy.sections.how_we_use.items.generate_benchmarks') }}</li>
                        <li>{{ __('pages.privacy_policy.sections.how_we_use.items.improve_service') }}</li>
                    </ul>
                </section>

                <section class="mb-8">
                    <h2 class="text-2xl font-semibold text-gray-900 mb-4">{{ __('pages.privacy_policy.sections.data_security.title') }}</h2>
                    <p class="text-gray-700 leading-relaxed mb-4">{{ __('pages.privacy_policy.sections.data_security.intro') }}</p>
                    <ul class="list-disc list-inside text-gray-700 mb-4 space-y-1">
                        <li>{{ __('pages.privacy_policy.sections.data_security.items.encryption') }}</li>
                        <li>{{ __('pages.privacy_policy.sections.data_security.items.isolation') }}</li>
                        <li>{{ __('pages.privacy_policy.sections.data_security.items.access_control') }}</li>
                    </ul>
                    <p class="text-gray-700 leading-relaxed">{{ __('pages.privacy_policy.sections.data_security.disclaimer') }}</p>
                </section>

                <section class="mb-8">
                    <h2 class="text-2xl font-semibold text-gray-900 mb-4">{{ __('pages.privacy_policy.sections.your_rights.title') }}</h2>
                    <p class="text-gray-700 leading-relaxed mb-4">{{ __('pages.privacy_policy.sections.your_rights.intro') }}</p>
                    <ul class="list-disc list-inside text-gray-700 mb-4 space-y-1">
                        <li>{{ __('pages.privacy_policy.sections.your_rights.items.access') }}</li>
                        <li>{{ __('pages.privacy_policy.sections.your_rights.items.correction') }}</li>
                        <li>{{ __('pages.privacy_policy.sections.your_rights.items.deletion') }}</li>
                        <li>{{ __('pages.privacy_policy.sections.your_rights.items.portability') }}</li>
                    </ul>
                </section>

                <section class="mb-8">
                    <h2 class="text-2xl font-semibold text-gray-900 mb-4">{{ __('pages.privacy_policy.sections.contact.title') }}</h2>
                    <p class="text-gray-700 leading-relaxed mb-4">{{ __('pages.privacy_policy.sections.contact.intro') }}</p>
                    <p class="text-gray-700">{{ __('pages.privacy_policy.sections.contact.email') }}</p>
                    <p class="text-gray-700">{{ __('pages.privacy_policy.sections.contact.website') }}</p>
                </section>
            </div>
        </div>
    </div>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6 text-center text-sm text-gray-500">
        &copy; {{ date('Y') }} RB Benchmarks. {{ __('pages.privacy_policy.all_rights_reserved') }}
    </div>
</div>
</x-layouts.guest>
