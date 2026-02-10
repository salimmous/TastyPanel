@php
    $navClass = "group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors duration-150";
    $activeClass = "bg-stone-700/80 text-white";
    $inactiveClass = "text-stone-300 hover:bg-stone-800/80 hover:text-white";
@endphp

<a href="{{ route('platform.dashboard') }}" class="{{ $navClass }} {{ request()->routeIs('platform.dashboard') ? $activeClass : $inactiveClass }}">
    <i class="ph ph-squares-four text-lg mr-3 {{ request()->routeIs('platform.dashboard') ? 'text-amber-400' : 'text-stone-400 group-hover:text-white' }}"></i>
    Dashboard
</a>

<a href="{{ route('platform.overview') }}" class="{{ $navClass }} {{ request()->routeIs('platform.overview') ? $activeClass : $inactiveClass }}">
    <i class="ph ph-chart-line-up text-lg mr-3 {{ request()->routeIs('platform.overview') ? 'text-amber-400' : 'text-stone-400 group-hover:text-white' }}"></i>
    Overview
</a>

<a href="{{ route('platform.control') }}" class="{{ $navClass }} {{ request()->routeIs('platform.control*') ? $activeClass : $inactiveClass }}">
    <i class="ph ph-command text-lg mr-3 {{ request()->routeIs('platform.control*') ? 'text-amber-400' : 'text-stone-400 group-hover:text-white' }}"></i>
    Control Center
</a>

<a href="{{ route('platform.deploy') }}" class="{{ $navClass }} {{ request()->routeIs('platform.deploy') ? $activeClass : $inactiveClass }}">
    <i class="ph ph-rocket-launch text-lg mr-3 {{ request()->routeIs('platform.deploy') ? 'text-amber-400' : 'text-stone-400 group-hover:text-white' }}"></i>
    Deploy Center
</a>

<a href="{{ route('platform.tenants') }}" class="{{ $navClass }} {{ request()->routeIs('platform.tenants*') ? $activeClass : $inactiveClass }}">
    <i class="ph ph-globe text-lg mr-3 {{ request()->routeIs('platform.tenants*') ? 'text-amber-400' : 'text-stone-400 group-hover:text-white' }}"></i>
    Sites
</a>

<a href="{{ route('platform.domains') }}" class="{{ $navClass }} {{ request()->routeIs('platform.domains') ? $activeClass : $inactiveClass }}">
    <i class="ph ph-network text-lg mr-3 {{ request()->routeIs('platform.domains') ? 'text-amber-400' : 'text-stone-400 group-hover:text-white' }}"></i>
    Domain Center
</a>

<div class="mt-6 mb-2 px-3 text-xs font-semibold text-stone-500 uppercase tracking-wider">
    Access Control
</div>

<a href="{{ route('platform.users') }}" class="{{ $navClass }} {{ request()->routeIs('platform.users') ? $activeClass : $inactiveClass }}">
    <i class="ph ph-users text-lg mr-3 {{ request()->routeIs('platform.users') ? 'text-amber-400' : 'text-stone-400 group-hover:text-white' }}"></i>
    Users
</a>

<a href="{{ route('platform.roles.index') }}" class="{{ $navClass }} {{ request()->routeIs('platform.roles*') ? $activeClass : $inactiveClass }}">
    <i class="ph ph-shield-check text-lg mr-3 {{ request()->routeIs('platform.roles*') ? 'text-amber-400' : 'text-stone-400 group-hover:text-white' }}"></i>
    Roles & Permissions
</a>

<a href="{{ route('platform.security') }}" class="{{ $navClass }} {{ request()->routeIs('platform.security*') ? $activeClass : $inactiveClass }}">
    <i class="ph ph-lock-key text-lg mr-3 {{ request()->routeIs('platform.security*') ? 'text-amber-400' : 'text-stone-400 group-hover:text-white' }}"></i>
    Security Center
</a>

<div class="mt-6 mb-2 px-3 text-xs font-semibold text-stone-500 uppercase tracking-wider">
    Monitoring
</div>

<a href="{{ route('platform.monitoring') }}" class="{{ $navClass }} {{ request()->routeIs('platform.monitoring') ? $activeClass : $inactiveClass }}">
    <i class="ph ph-waveform text-lg mr-3 {{ request()->routeIs('platform.monitoring') ? 'text-amber-400' : 'text-stone-400 group-hover:text-white' }}"></i>
    Monitoring Center
</a>

<a href="{{ route('platform.monitoring.rules') }}" class="{{ $navClass }} {{ request()->routeIs('platform.monitoring.rules') ? $activeClass : $inactiveClass }}">
    <i class="ph ph-sliders text-lg mr-3 {{ request()->routeIs('platform.monitoring.rules') ? 'text-amber-400' : 'text-stone-400 group-hover:text-white' }}"></i>
    Monitoring Rules
</a>

<a href="{{ route('platform.analytics') }}" class="{{ $navClass }} {{ request()->routeIs('platform.analytics') ? $activeClass : $inactiveClass }}">
    <i class="ph ph-chart-pie-slice text-lg mr-3 {{ request()->routeIs('platform.analytics') ? 'text-amber-400' : 'text-stone-400 group-hover:text-white' }}"></i>
    Analytics
</a>

<a href="{{ route('platform.system') }}" class="{{ $navClass }} {{ request()->routeIs('platform.system') ? $activeClass : $inactiveClass }}">
    <i class="ph ph-cpu text-lg mr-3 {{ request()->routeIs('platform.system') ? 'text-amber-400' : 'text-stone-400 group-hover:text-white' }}"></i>
    System Status
</a>

<a href="{{ route('platform.backups') }}" class="{{ $navClass }} {{ request()->routeIs('platform.backups*') ? $activeClass : $inactiveClass }}">
    <i class="ph ph-hard-drives text-lg mr-3 {{ request()->routeIs('platform.backups*') ? 'text-amber-400' : 'text-stone-400 group-hover:text-white' }}"></i>
    Backups
</a>

<a href="{{ route('platform.drills') }}" class="{{ $navClass }} {{ request()->routeIs('platform.drills*') ? $activeClass : $inactiveClass }}">
    <i class="ph ph-siren text-lg mr-3 {{ request()->routeIs('platform.drills*') ? 'text-amber-400' : 'text-stone-400 group-hover:text-white' }}"></i>
    DR Drills
</a>

<a href="{{ route('platform.audit_logs') }}" class="{{ $navClass }} {{ request()->routeIs('platform.audit_logs') ? $activeClass : $inactiveClass }}">
    <i class="ph ph-scroll text-lg mr-3 {{ request()->routeIs('platform.audit_logs') ? 'text-amber-400' : 'text-stone-400 group-hover:text-white' }}"></i>
    Audit Logs
</a>

<div class="mt-6 mb-2 px-3 text-xs font-semibold text-stone-500 uppercase tracking-wider">
    Configuration
</div>

<a href="{{ route('platform.themes') }}" class="{{ $navClass }} {{ request()->routeIs('platform.themes*') ? $activeClass : $inactiveClass }}">
    <i class="ph ph-paint-brush text-lg mr-3 {{ request()->routeIs('platform.themes*') ? 'text-amber-400' : 'text-stone-400 group-hover:text-white' }}"></i>
    Themes
</a>

<a href="{{ route('platform.plugins') }}" class="{{ $navClass }} {{ request()->routeIs('platform.plugins*') ? $activeClass : $inactiveClass }}">
    <i class="ph ph-plugs text-lg mr-3 {{ request()->routeIs('platform.plugins*') ? 'text-amber-400' : 'text-stone-400 group-hover:text-white' }}"></i>
    Plugins
</a>

<a href="{{ route('platform.settings') }}" class="{{ $navClass }} {{ request()->routeIs('platform.settings') ? $activeClass : $inactiveClass }}">
    <i class="ph ph-gear text-lg mr-3 {{ request()->routeIs('platform.settings') ? 'text-amber-400' : 'text-stone-400 group-hover:text-white' }}"></i>
    Settings
</a>
