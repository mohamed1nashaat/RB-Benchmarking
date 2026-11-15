<p class="air-src">{{ $type === 'obs' ? 'Observed' : 'Simulated' }} · {{ $stage }}@if(!empty($src)) · {{ $src }}@endif @if(!empty($flag))· <span class="flag">{{ $flag }}</span>@endif</p>
