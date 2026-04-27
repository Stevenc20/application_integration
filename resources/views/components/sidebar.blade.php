@php
$user = auth()->user();
$role = $user->role ?? null;

$dashboardRoute = 'login';

$isAdmin = $role == 'admin';
$isSupervisor = $role == 'supervisor';
$isForeman = $role == 'foreman';
$isOperator = $role == 'operator';
$isPPLC = $role == 'pplc';
$isQuality = $role == 'quality';
$isProduction = $role == 'production';

if($role == "admin"){
    $dashboardRoute = 'admin.dashboard';
}elseif($role == "supervisor"){
    $dashboardRoute = "supervisor.dashboard";
}elseif($role == "operator"){
    $dashboardRoute = "operator.dashboard";
}

$dashboardActive = request()->routeIs('admin.dashboard')
    || request()->routeIs('supervisor.dashboard')
    || request()->routeIs('operator.dashboard');

$monitoringActive = request()->routeIs('monitoring.*');
$master_dataActive = request()->routeIs('production_entry')
    || request()->routeIs('production_recap') || request()->routeIs('job');
$approvalActive = request()->routeIs('production_approval') || request()->routeIs('quality_approval');

$planActive = request()->routeIs('production_line')
    || request()->routeIs('production_plan');

$qualityActive = request()->routeIs('quality_control.*');
$downtimeActive = request()->routeIs('downtime.*') || request()->routeIs('tren_downtime');
$reportActive = request()->routeIs('reports.*');

/* ===============================
DATA OPERASIONAL ACTIVE
=============================== */
$dataOperasionalActive =
    request()->routeIs('operational.dandori')
    || request()->routeIs('operational.idle')
    || request()->routeIs('operational.break')
    || request()->routeIs('operational.handwork')
    || request()->routeIs('operational.qcheck');


/* ===============================
GRAFIK ACTIVE
=============================== */
$grafikActive =
    request()->routeIs('grafik.downtime')
    || request()->routeIs('grafik.output')
    || request()->routeIs('grafik.type')
    || request()->routeIs('grafik.machine')
    || request()->routeIs('grafik.quality');
@endphp


<div>

<!-- Overlay -->
<div id="sidebarOverlay" class="fixed inset-0 hidden z-40 md:hidden"></div>

<aside id="layout-menu"
class="fixed top-0 left-0 z-50 w-64 h-[100dvh] bg-white p-5 pb-2
overflow-y-auto overflow-x-hidden
transform -translate-x-full md:translate-x-0
transition-transform duration-300">

<!-- LOGO -->
<div class="app-brand demo">
<a href="{{ route($dashboardRoute) }}" class="flex items-center pr-1 app-brand-link">
<span class="flex pr-3">
<img class="w-12" src="{{ asset('images/ippi_logo.png') }}">
</span>
<span class="font-semibold text-lg">PT IPPI</span>
</a>

<button id="sidebarClose" class="md:hidden absolute top-4 right-4 text-gray-600 text-xl">X</button>
</div>


<ul class="menu-item group list-none space-y-2 pt-10">

{{-- DASHBOARD --}}
@if($isSupervisor || $isForeman || $isOperator || $isProduction || $isPPLC || $isAdmin)

<li class="menu-item">
<a href="{{ route($dashboardRoute) }}"
class="flex items-center gap-3 px-4 py-2 rounded-lg transition
{{ $dashboardActive
? 'bg-red-600 text-white font-medium'
: 'text-gray-600 hover:bg-gray-100 hover:text-red-600' }}">

<i class="fas fa-home"></i>
<span>Dashboard</span>

</a>
</li>

@endif

@if($role == 'admin')

{{-- USER MANAGEMENT --}}
<li class="menu-item {{ request()->routeIs('users.*') ? 'active' : '' }}">
<a href="{{ route('users.index') }}"
class="flex items-center gap-3 px-4 py-2 rounded-lg transition
{{ request()->routeIs('users.*')
? 'bg-red-600 text-white font-medium'
: 'text-gray-600 hover:bg-gray-100 hover:text-red-600' }}">
<i class="menu-icon fas fa-users"></i>
<span>User Management</span>
</a>
</li>


{{-- ================= MASTER DATA ================= --}}
<li class="menu-item group {{ request()->routeIs('master.*') ? 'active' : '' }}">

<a href="javascript:void(0);"
class="menu-link menu-toggle flex items-center gap-3 px-4 py-2 rounded-lg transition
{{ request()->routeIs('master.*')
? 'bg-gray-100 text-red-600 font-semibold'
: 'text-gray-600 hover:bg-gray-100 hover:text-red-600' }}">

<span class="flex items-center gap-3">
<i class="menu-icon fas fa-database"></i>
<span>Master Data</span>
</span>

<svg xmlns="http://www.w3.org/2000/svg"
class="w-4 h-4 ml-auto transition-transform duration-300 arrow {{ request()->routeIs('master.*') ? 'rotate-90' : '' }}"
fill="none" viewBox="0 0 24 24" stroke="currentColor">
<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
</svg>

</a>

<ul class="menu-sub list-none ml-8 mt-1 space-y-1 {{ request()->routeIs('master.*') ? '' : 'hidden' }}">

<li>
<a href="{{ route('master.job') }}"
class="block px-3 py-2 rounded-lg transition
{{ request()->routeIs('master.job')
? 'bg-red-600 text-white font-medium'
: 'text-gray-600 hover:bg-gray-100 hover:text-red-600' }}">
Job Master
</a>
</li>

{{-- <li>
<a href="{{ route('master.line') }}"
class="block px-3 py-2 rounded-lg transition
{{ request()->routeIs('master.line')
? 'bg-red-600 text-white font-medium'
: 'text-gray-600 hover:bg-gray-100 hover:text-red-600' }}">
Production Line
</a>
</li>

<li>
<a href="{{ route('master.machine') }}"
class="block px-3 py-2 rounded-lg transition
{{ request()->routeIs('master.machine')
? 'bg-red-600 text-white font-medium'
: 'text-gray-600 hover:bg-gray-100 hover:text-red-600' }}">
Machine Data
</a>
</li> --}}

</ul>
</li>


{{-- ================= SYSTEM SETTINGS ================= --}}
<li class="menu-item group {{ request()->routeIs('settings.*') ? 'active' : '' }}">

<a href="javascript:void(0);"
class="menu-link menu-toggle flex items-center gap-3 px-4 py-2 rounded-lg transition
{{ request()->routeIs('settings.*')
? 'bg-gray-100 text-red-600 font-semibold'
: 'text-gray-600 hover:bg-gray-100 hover:text-red-600' }}">

<span class="flex items-center gap-3">
<i class="menu-icon fas fa-cogs"></i>
<span>System Settings</span>
</span>

<svg xmlns="http://www.w3.org/2000/svg"
class="w-4 h-4 ml-auto transition-transform duration-300 arrow {{ request()->routeIs('settings.*') ? 'rotate-90' : '' }}"
fill="none" viewBox="0 0 24 24" stroke="currentColor">
<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
</svg>

</a>

<ul class="menu-sub list-none ml-8 mt-1 space-y-1 {{ request()->routeIs('settings.*') ? '' : 'hidden' }}">

{{-- <li>
<a href="{{ route('settings.general') }}"
class="block px-3 py-2 rounded-lg transition">
General Settings
</a>
</li>

<li>
<a href="{{ route('settings.shift') }}"
class="block px-3 py-2 rounded-lg transition">
Shift Settings
</a>
</li> --}}

</ul>

</li>

@endif

{{-- supervisor | foreman | admin --}}

@if($isSupervisor || $isForeman || $isAdmin)

@php
$dataOperasionalActive =
    request()->routeIs('operational.input_harian') ||
    request()->routeIs('operational.dandori') ||
    request()->routeIs('operational.idle') ||
    request()->routeIs('operational.break') ||
    request()->routeIs('operational.handwork') ||
    request()->routeIs('operational.qcheck');
@endphp

<li class="menu-item group {{ $dataOperasionalActive ? 'active' : '' }}">

<a href="javascript:void(0);"
class="menu-link menu-toggle flex items-center gap-3 px-4 py-2 rounded-lg transition
{{ $dataOperasionalActive
? 'bg-gray-100 text-red-600 font-semibold'
: 'text-gray-600 hover:bg-gray-100 hover:text-red-600' }}">

<span class="flex items-center gap-3">
<i class="menu-icon fas fa-cogs"></i>
<span>Operational</span>
</span>

<svg xmlns="http://www.w3.org/2000/svg"
class="w-4 h-4 ml-auto transition-transform duration-300 arrow
{{ $dataOperasionalActive ? 'rotate-90' : '' }}"
fill="none"
viewBox="0 0 24 24"
stroke="currentColor">

<path stroke-linecap="round"
stroke-linejoin="round"
stroke-width="2"
d="M9 5l7 7-7 7"/>

</svg>

</a>

{{-- ========================================================= --}}
{{-- SUB MENU --}}
{{-- ========================================================= --}}

<ul class="menu-sub ml-8 mt-1 space-y-1
{{ $dataOperasionalActive ? '' : 'hidden' }}">

{{-- INPUT HARIAN --}}
<li>
<a href="{{ route('operational.input_harian') }}"
class="block px-3 py-2 rounded-lg transition
{{ request()->routeIs('operational.input_harian')
? 'bg-red-600 text-white font-medium'
: 'text-gray-600 hover:bg-gray-100 hover:text-red-600' }}">
Input Harian
</a>
</li>

{{-- DATA DANDORI --}}
<li>
<a href="{{ route('operational.dandori') }}"
class="block px-3 py-2 rounded-lg transition
{{ request()->routeIs('operational.dandori')
? 'bg-red-600 text-white font-medium'
: 'text-gray-600 hover:bg-gray-100 hover:text-red-600' }}">
Data Dandori
</a>
</li>

{{-- DATA IDLE --}}
<li>
<a href="{{ route('operational.idle') }}"
class="block px-3 py-2 rounded-lg transition
{{ request()->routeIs('operational.idle')
? 'bg-red-600 text-white font-medium'
: 'text-gray-600 hover:bg-gray-100 hover:text-red-600' }}">
Data Idle Time
</a>
</li>

{{-- DATA BREAK --}}
<li>
<a href="{{ route('operational.break') }}"
class="block px-3 py-2 rounded-lg transition
{{ request()->routeIs('operational.break')
? 'bg-red-600 text-white font-medium'
: 'text-gray-600 hover:bg-gray-100 hover:text-red-600' }}">
Data Break Time
</a>
</li>

{{-- DATA HANDWORK --}}
<li>
<a href="{{ route('operational.handwork') }}"
class="block px-3 py-2 rounded-lg transition
{{ request()->routeIs('operational.handwork')
? 'bg-red-600 text-white font-medium'
: 'text-gray-600 hover:bg-gray-100 hover:text-red-600' }}">
Data Handwork
</a>
</li>

{{-- DATA QCHECK --}}
<li>
<a href="{{ route('operational.qcheck') }}"
class="block px-3 py-2 rounded-lg transition
{{ request()->routeIs('operational.qcheck')
? 'bg-red-600 text-white font-medium'
: 'text-gray-600 hover:bg-gray-100 hover:text-red-600' }}">
Data Q-Check
</a>
</li>

</ul>

</li>

@endif



{{-- grafik --}}
@if($isSupervisor || $isForeman || $isAdmin)


<li class="menu-item group {{ $grafikActive ? 'active' : '' }}">

<a href="javascript:void(0);"
class="menu-link menu-toggle flex items-center gap-3 px-4 py-2 rounded-lg transition
{{ $grafikActive
? 'bg-gray-100 text-red-600 font-semibold'
: 'text-gray-600 hover:bg-gray-100 hover:text-red-600' }}">

<span class="flex items-center gap-3">
<i class="menu-icon fas fa-chart-bar"></i>
<span>Grafik</span>
</span>

<svg xmlns="http://www.w3.org/2000/svg"
class="w-4 h-4 ml-auto transition-transform duration-300 arrow
{{ $grafikActive ? 'rotate-90' : '' }}"
fill="none" viewBox="0 0 24 24" stroke="currentColor">

<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
d="M9 5l7 7-7 7"/>

</svg>

</a>

<ul class="menu-sub ml-8 mt-1 space-y-1 {{ $grafikActive ? '' : 'hidden' }}">

<li>
<a href="{{ route('grafik.output') }}"
class="block px-3 py-2 rounded-lg transition
{{ request()->routeIs('grafik.output')
? 'bg-red-600 text-white font-medium'
: 'text-gray-600 hover:bg-gray-100 hover:text-red-600' }}">
Tren Output
</a>
</li>

<li>
<a href="{{ route('grafik.quality') }}"
class="block px-3 py-2 rounded-lg transition
{{ request()->routeIs('grafik.quality')
? 'bg-red-600 text-white font-medium'
: 'text-gray-600 hover:bg-gray-100 hover:text-red-600' }}">
Pencapaian Kualitas
</a>
</li>


</ul>

</li>

@endif

{{-- quality --}}
@if($isQuality)

<li class="menu-item group">

<a href="javascript:void(0);" class="menu-link menu-toggle">
<i class="fas fa-search"></i>
<span>Quality Control</span>
</a>

<ul class="menu-sub hidden ml-6">

<li><a href="{{ route('quality_control.defect') }}">Defect Monitoring</a></li>
<li><a href="{{ route('quality_control.reject') }}">Reject Analysis</a></li>

</ul>

</li>

@endif

{{-- PPLC --}}
@if($isPPLC)

<li class="menu-item group">

<a href="javascript:void(0);" class="menu-link menu-toggle">
<i class="fas fa-tasks"></i>
<span>Planning</span>
</a>

<ul class="menu-sub hidden ml-6">

<li><a href="{{ route('production_line') }}">Production Line</a></li>
<li><a href="{{ route('production_plan') }}">Production Plan</a></li>

</ul>

</li>

@endif

{{-- ================= OPERATOR ================= --}}
@if($role == 'operator')

{{-- PRODUCTION --}}
<li class="menu-item group {{ request()->routeIs('production_entry') ? 'active' : '' }}">

<a href="{{ route('production_entry') }}"
class="flex items-center gap-3 px-4 py-2 rounded-lg transition
{{ request()->routeIs('production_entry')
? 'bg-red-600 text-white font-medium'
: 'text-gray-600 hover:bg-gray-100 hover:text-red-600' }}">

<i class="menu-icon fas fa-edit"></i>
<span>Production Input</span>

</a>
</li>

{{-- HISTORY --}}
<li class="menu-item group {{ request()->routeIs('production.history') ? 'active' : '' }}">

<a href="{{ route('production.history') }}"
class="flex items-center gap-3 px-4 py-2 rounded-lg transition
{{ request()->routeIs('production.history')
? 'bg-red-600 text-white font-medium'
: 'text-gray-600 hover:bg-gray-100 hover:text-red-600' }}">

<i class="menu-icon fas fa-history"></i>
<span>My Production</span>

</a>
</li>

@endif


{{-- ================= SUPERVISOR ================= --}}
@if($role == 'supervisor')

{{-- MONITORING --}}
<li class="menu-item group {{ $monitoringActive ? 'active' : '' }}">

<a href="javascript:void(0);"
class="menu-link menu-toggle flex items-center gap-3 px-4 py-2 rounded-lg transition
{{ $monitoringActive
? 'bg-gray-100 text-red-600 font-semibold'
: 'text-gray-600 hover:bg-gray-100 hover:text-red-600' }}">

<span class="flex items-center gap-3">
<i class="menu-icon fas fa-desktop"></i>
<span>Monitoring</span>
</span>

<svg xmlns="http://www.w3.org/2000/svg"
class="w-4 h-4 ml-auto transition-transform duration-300 arrow {{ $monitoringActive ? 'rotate-90' : '' }}"
fill="none" viewBox="0 0 24 24" stroke="currentColor">
<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
</svg>

</a>

<ul class="menu-sub list-none ml-8 mt-1 space-y-1 {{ $monitoringActive ? '' : 'hidden' }}">

<li>
<a href="{{ route('monitoring.line') }}"
class="block px-3 py-2 rounded-lg transition
{{ request()->routeIs('monitoring.line')
? 'bg-red-600 text-white font-medium'
: 'text-gray-600 hover:bg-gray-100 hover:text-red-600' }}">
Line Monitoring
</a>
</li>

<li>
<a href="{{ route('monitoring.machine_status') }}"
class="block px-3 py-2 rounded-lg transition
{{ request()->routeIs('monitoring.machine_status')
? 'bg-red-600 text-white font-medium'
: 'text-gray-600 hover:bg-gray-100 hover:text-red-600' }}">
Machine Status
</a>
</li>

</ul>
</li>


{{-- ================= PLANNING (SUPERVISOR) ================= --}}
<li class="menu-item group {{ $planActive ? 'active' : '' }}">

<a href="javascript:void(0);"
class="menu-link menu-toggle flex items-center gap-3 px-4 py-2 rounded-lg transition
{{ $planActive
? 'bg-gray-100 text-red-600 font-semibold'
: 'text-gray-600 hover:bg-gray-100 hover:text-red-600' }}">

<span class="flex items-center gap-3">
<i class="menu-icon fas fa-save"></i>
<span>Planning</span>
</span>

<svg xmlns="http://www.w3.org/2000/svg"
class="w-4 h-4 ml-auto transition-transform duration-300 arrow {{ $planActive ? 'rotate-90' : '' }}"
fill="none" viewBox="0 0 24 24" stroke="currentColor">
<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
</svg>

</a>

<ul class="menu-sub list-none ml-8 mt-1 space-y-1 {{ $planActive ? '' : 'hidden' }}">

<li>
<a href="{{ route('production_line') }}"
class="block px-3 py-2 rounded-lg transition
{{ request()->routeIs('production_line')
? 'bg-red-600 text-white font-medium'
: 'text-gray-600 hover:bg-gray-100 hover:text-red-600' }}">
Production Line
</a>
</li>

<li>
<a href="{{ route('production_plan') }}"
class="block px-3 py-2 rounded-lg transition
{{ request()->routeIs('production_plan')
? 'bg-red-600 text-white font-medium'
: 'text-gray-600 hover:bg-gray-100 hover:text-red-600' }}">
Production Plan
</a>
</li>

</ul>
</li>


{{-- PRODUCTION --}}
<li class="menu-item group {{ $master_dataActive ? 'active' : '' }}">

<a href="javascript:void(0);"
class="menu-link menu-toggle flex items-center gap-3 px-4 py-2 rounded-lg transition
{{ $master_dataActive
? 'bg-gray-100 text-red-600 font-semibold'
: 'text-gray-600 hover:bg-gray-100 hover:text-red-600' }}">

<span class="flex items-center gap-3">
<i class="menu-icon fas fa-box"></i>
<span>Production</span>
</span>

<svg xmlns="http://www.w3.org/2000/svg"
class="w-4 h-4 ml-auto transition-transform duration-300 arrow {{ $master_dataActive ? 'rotate-90' : '' }}"
fill="none" viewBox="0 0 24 24" stroke="currentColor">
<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
</svg>

</a>

<ul class="menu-sub list-none ml-8 mt-1 space-y-1 {{ $master_dataActive ? '' : 'hidden' }}">

@if($role == 'operator')
<li>
<a href="{{ route('production_entry') }}"
class="block px-3 py-2 rounded-lg transition
{{ request()->routeIs('production_entry')
? 'bg-red-600 text-white font-medium'
: 'text-gray-600 hover:bg-gray-100 hover:text-red-600' }}">
Production Input
</a>
</li>
@endif

<li>
<a href="{{ route('production_entry') }}"
class="block px-3 py-2 rounded-lg transition
{{ request()->routeIs('production_entry')
? 'bg-red-600 text-white font-medium'
: 'text-gray-600 hover:bg-gray-100 hover:text-red-600' }}">
Production Data (View)
</a>
</li>

<li>
<a href="{{ route('production_recap') }}"
class="block px-3 py-2 rounded-lg transition
{{ request()->routeIs('production_recap')
? 'bg-red-600 text-white font-medium'
: 'text-gray-600 hover:bg-gray-100 hover:text-red-600' }}">
Production Recap
</a>
</li>

</ul>
</li>


{{-- APPROVAL --}}
<li class="menu-item group {{ $approvalActive ? 'active' : '' }}">

<a href="javascript:void(0);"
class="menu-link menu-toggle flex items-center gap-3 px-4 py-2 rounded-lg transition
{{ $approvalActive
? 'bg-gray-100 text-red-600 font-semibold'
: 'text-gray-600 hover:bg-gray-100 hover:text-red-600' }}">

<span class="flex items-center gap-3">
<i class="menu-icon fas fa-check-circle"></i>
<span>Approval</span>
</span>

<svg xmlns="http://www.w3.org/2000/svg"
class="w-4 h-4 ml-auto transition-transform duration-300 arrow {{ $approvalActive ? 'rotate-90' : '' }}"
fill="none" viewBox="0 0 24 24" stroke="currentColor">
<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
</svg>

</a>

<ul class="menu-sub list-none ml-8 mt-1 space-y-1 {{ $approvalActive ? '' : 'hidden' }}">

<li>
<a href="{{ route('production_approval') }}"
class="block px-3 py-2 rounded-lg transition
{{ request()->routeIs('production_approval')
? 'bg-red-600 text-white font-medium'
: 'text-gray-600 hover:bg-gray-100 hover:text-red-600' }}">
Production Approval
</a>
</li>

{{-- QUALITY CONTROL --}}

<li>
<a href="{{ route('quality_approval') }}"
class="block px-3 py-2 rounded-lg transition
{{ request()->routeIs('quality_approval')
? 'bg-red-600 text-white font-medium'
: 'text-gray-600 hover:bg-gray-100 hover:text-red-600' }}">
Quality Approval
</a>
</li>

</ul>
</li>

<li class="menu-item group {{ $qualityActive ? 'active' : '' }}">

<a href="javascript:void(0);"
class="menu-link menu-toggle flex items-center gap-3 px-4 py-2 rounded-lg transition
{{ $qualityActive
? 'bg-gray-100 text-red-600 font-semibold'
: 'text-gray-600 hover:bg-gray-100 hover:text-red-600' }}">

<span class="flex items-center gap-3">
<i class="menu-icon fas fa-search"></i>
<span>Quality Control</span>
</span>

<svg xmlns="http://www.w3.org/2000/svg"
class="w-4 h-4 ml-auto transition-transform duration-300 arrow {{ $qualityActive ? 'rotate-90' : '' }}"
fill="none" viewBox="0 0 24 24" stroke="currentColor">
<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
d="M9 5l7 7-7 7"/>
</svg>

</a>

<ul class="menu-sub ml-8 mt-1 space-y-1 {{ $qualityActive ? '' : 'hidden' }}">

<li>
<a href="{{ route('quality_control.defect') }}"
class="block px-3 py-2 rounded-lg transition
{{ request()->routeIs('quality_control.defect')
? 'bg-red-600 text-white font-medium'
: 'text-gray-600 hover:bg-gray-100 hover:text-red-600' }}">
Defect Monitoring
</a>
</li>

<li>
<a href="{{ route('quality_control.reject') }}"
class="block px-3 py-2 rounded-lg transition
{{ request()->routeIs('quality_control.reject')
? 'bg-red-600 text-white font-medium'
: 'text-gray-600 hover:bg-gray-100 hover:text-red-600' }}">
Reject Analysis
</a>
</li>

</ul>

</li>

{{-- DOWNTIME CONTROL --}}

<li class="menu-item group {{ $downtimeActive ? 'active' : '' }}">

<a href="javascript:void(0);"
class="menu-link menu-toggle flex items-center gap-3 px-4 py-2 rounded-lg transition
{{ $downtimeActive
? 'bg-gray-100 text-red-600 font-semibold'
: 'text-gray-600 hover:bg-gray-100 hover:text-red-600' }}">

<span class="flex items-center gap-3">
<i class="menu-icon fas fa-tools"></i>
<span>Downtime Control</span>
</span>

<svg xmlns="http://www.w3.org/2000/svg"
class="w-4 h-4 ml-auto transition-transform duration-300 arrow {{ $downtimeActive ? 'rotate-90' : '' }}"
fill="none" viewBox="0 0 24 24" stroke="currentColor">
<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
d="M9 5l7 7-7 7"/>
</svg>

</a>

<ul class="menu-sub ml-8 mt-1 space-y-1 {{ $downtimeActive ? '' : 'hidden' }}">

<li>
<a href="{{ route('downtime.monitoring') }}"
class="block px-3 py-2 rounded-lg transition
{{ request()->routeIs('downtime.monitoring')
? 'bg-red-600 text-white font-medium'
: 'text-gray-600 hover:bg-gray-100 hover:text-red-600' }}">
Downtime Monitoring
</a>
</li>

<li>
<a href="{{ route('downtime.tren_downtime') }}"
class="block px-3 py-2 rounded-lg transition
{{ request()->routeIs('downtime.tren_downtime')
? 'bg-red-600 text-white font-medium'
: 'text-gray-600 hover:bg-gray-100 hover:text-red-600' }}">
Tren Downtime
</a>
</li>

<li>
<a href="{{ route('downtime.history') }}"
class="block px-3 py-2 rounded-lg transition
{{ request()->routeIs('downtime.history')
? 'bg-red-600 text-white font-medium'
: 'text-gray-600 hover:bg-gray-100 hover:text-red-600' }}">
Trouble History
</a>
</li>

</ul>

</li>

{{-- REPORTS --}}
<li class="menu-item group {{ $reportActive ? 'active' : '' }}">

<a href="javascript:void(0);"
class="menu-link menu-toggle flex items-center gap-3 px-4 py-2 rounded-lg transition
{{ $reportActive
? 'bg-gray-100 text-red-600 font-semibold'
: 'text-gray-600 hover:bg-gray-100 hover:text-red-600' }}">

<span class="flex items-center gap-3">
<i class="menu-icon fas fa-chart-line"></i>
<span>Reports</span>
</span>

<svg xmlns="http://www.w3.org/2000/svg"
class="w-4 h-4 ml-auto transition-transform duration-300 arrow {{ $reportActive ? 'rotate-90' : '' }}"
fill="none" viewBox="0 0 24 24" stroke="currentColor">
<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
d="M9 5l7 7-7 7"/>
</svg>

</a>

<ul class="menu-sub ml-8 mt-1 space-y-1 {{ $reportActive ? '' : 'hidden' }}">

<li>
<a href="{{ route('reports.daily_production') }}"
class="block px-3 py-2 rounded-lg transition
{{ request()->routeIs('reports.daily_production')
? 'bg-red-600 text-white font-medium'
: 'text-gray-600 hover:bg-gray-100 hover:text-red-600' }}">
Daily Production
</a>
</li>

<li>
<a href="{{ route('reports.performance') }}"
class="block px-3 py-2 rounded-lg transition
{{ request()->routeIs('reports.performance')
? 'bg-red-600 text-white font-medium'
: 'text-gray-600 hover:bg-gray-100 hover:text-red-600' }}">
Performance Report
</a>
</li>

</ul>

</li>

@endif


</ul>

</aside>
</div>


<script>
document.querySelectorAll('.menu-toggle').forEach(item => {
    item.addEventListener('click', function () {

        const parent = this.closest('.menu-item');
        const submenu = parent.querySelector('.menu-sub');
        const arrow = this.querySelector('.arrow');

        if (submenu) submenu.classList.toggle('hidden');
        if (arrow) arrow.classList.toggle('rotate-90');

        parent.classList.toggle('active');
    });
});

</script>


