@php
$isActive = request()->routeIs('filament.admin.pages.dashboard');
@endphp

<a href="{{ route('filament.admin.pages.dashboard') }}" class="dashboard-top-nav{{ $isActive ? ' active' : '' }}">
    <x-heroicon-o-presentation-chart-line class="w-5 h-5" />
    <span>仪表板</span>
</a>
