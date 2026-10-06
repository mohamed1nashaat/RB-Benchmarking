<footer class="border-t" style="background-color: #0d0f16; border-color: rgba(255,255,255,0.08);">
    <div class="px-4 sm:px-6 lg:px-8 py-4">
        <div class="flex justify-between items-center text-sm text-gray-500">
            <div class="hidden sm:block">
                &copy; {{ date('Y') }} {{ __('app.name') }}. {{ __('footer.copyright') }}
            </div>
            <div class="flex gap-4">
                <a href="{{ route('privacy-policy') }}" class="hover:text-gray-200 transition-colors">
                    {{ __('common.privacy_policy') }}
                </a>
                <a href="{{ route('terms-of-service') }}" class="hover:text-gray-200 transition-colors">
                    {{ __('common.terms_of_service') }}
                </a>
            </div>
        </div>
    </div>
</footer>
