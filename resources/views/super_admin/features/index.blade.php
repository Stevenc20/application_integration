@extends('layouts.super_admin')

@section('title', 'Feature Management')

@section('head')
<style>
    /* ── Design Tokens ── */
    :root {
        --fm-primary: #dc2626;
        --fm-primary-light: #ef4444;
        --fm-primary-dark: #b91c1c;
        --fm-accent: #f87171;
        --fm-success: #10b981;
        --fm-surface: rgba(255,255,255,0.72);
        --fm-surface-solid: #ffffff;
        --fm-border: rgba(0,0,0,0.06);
        --fm-text: #1e293b;
        --fm-text-secondary: #64748b;
        --fm-radius: 16px;
    }

    /* ── Hero Banner ── */
    .fm-hero {
        background: linear-gradient(135deg, #991b1b 0%, #dc2626 50%, #e11d48 100%);
        border-radius: var(--fm-radius);
        padding: 2rem 2.5rem;
        color: #fff;
        position: relative;
        overflow: hidden;
        margin-bottom: 1.5rem;
    }
    .fm-hero::before {
        content: '';
        position: absolute;
        top: -40%;
        right: -10%;
        width: 320px;
        height: 320px;
        background: radial-gradient(circle, rgba(255,255,255,0.12) 0%, transparent 70%);
        border-radius: 50%;
    }
    .fm-hero::after {
        content: '';
        position: absolute;
        bottom: -30%;
        left: 20%;
        width: 200px;
        height: 200px;
        background: radial-gradient(circle, rgba(139,92,246,0.25) 0%, transparent 70%);
        border-radius: 50%;
    }
    .fm-hero h1 { font-size: 1.5rem; font-weight: 800; margin: 0 0 0.25rem; position: relative; z-index: 1; }
    .fm-hero p { margin: 0; opacity: 0.85; font-size: 0.875rem; position: relative; z-index: 1; }

    /* ── Stat Pills ── */
    .fm-stats {
        display: flex;
        gap: 0.75rem;
        margin-top: 1.25rem;
        position: relative;
        z-index: 1;
        flex-wrap: wrap;
    }
    .fm-stat {
        background: rgba(255,255,255,0.15);
        backdrop-filter: blur(8px);
        border: 1px solid rgba(255,255,255,0.2);
        border-radius: 12px;
        padding: 0.625rem 1.25rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.8125rem;
        font-weight: 600;
    }
    .fm-stat .num { font-size: 1.25rem; font-weight: 800; }

    /* ── Success Alert ── */
    .fm-alert {
        background: linear-gradient(135deg, #ecfdf5, #d1fae5);
        border: 1px solid #a7f3d0;
        border-radius: 12px;
        padding: 0.875rem 1.25rem;
        display: flex;
        align-items: center;
        gap: 0.625rem;
        margin-bottom: 1.25rem;
        color: #065f46;
        font-size: 0.875rem;
        font-weight: 500;
        animation: fm-slideDown 0.4s ease;
    }
    @keyframes fm-slideDown {
        from { opacity: 0; transform: translateY(-12px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* ── Group Card ── */
    .fm-group {
        background: var(--fm-surface-solid);
        border: 1px solid var(--fm-border);
        border-radius: var(--fm-radius);
        margin-bottom: 1.25rem;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 4px 12px rgba(0,0,0,0.03);
        transition: box-shadow 0.3s ease;
    }
    .fm-group:hover { box-shadow: 0 4px 20px rgba(0,0,0,0.08); }

    .fm-group-header {
        padding: 1rem 1.5rem;
        background: linear-gradient(135deg, #f8fafc, #f1f5f9);
        border-bottom: 1px solid var(--fm-border);
        display: flex;
        align-items: center;
        gap: 0.75rem;
        cursor: pointer;
        user-select: none;
        transition: background 0.2s;
    }
    .fm-group-header:hover { background: linear-gradient(135deg, #f1f5f9, #e2e8f0); }

    .fm-group-icon {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        flex-shrink: 0;
    }
    .fm-group-title {
        font-weight: 700;
        font-size: 0.9375rem;
        color: var(--fm-text);
        flex: 1;
    }
    .fm-group-badge {
        font-size: 0.6875rem;
        font-weight: 600;
        padding: 0.2rem 0.6rem;
        border-radius: 999px;
        background: rgba(220,38,38,0.08);
        color: var(--fm-primary);
    }
    .fm-group-chevron {
        width: 20px;
        height: 20px;
        color: #94a3b8;
        transition: transform 0.3s ease;
    }
    .fm-group.collapsed .fm-group-chevron { transform: rotate(-90deg); }
    .fm-group.collapsed .fm-group-body { display: none; }

    /* ── Table ── */
    .fm-table { width: 100%; border-collapse: collapse; font-size: 0.8125rem; }
    .fm-table thead th {
        padding: 0.625rem 1rem;
        font-weight: 600;
        font-size: 0.6875rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--fm-text-secondary);
        text-align: center;
        white-space: nowrap;
        border-bottom: 1px solid var(--fm-border);
        background: #fafbfc;
        position: sticky;
        top: 0;
        z-index: 2;
    }
    .fm-table thead th:first-child { text-align: left; padding-left: 1.5rem; }
    .fm-table tbody tr { transition: background 0.15s; }
    .fm-table tbody tr:hover { background: #f8fafc; }
    .fm-table tbody td {
        padding: 0.75rem 1rem;
        text-align: center;
        border-bottom: 1px solid rgba(0,0,0,0.03);
    }
    .fm-table tbody td:first-child {
        text-align: left;
        padding-left: 1.5rem;
        font-weight: 600;
        color: var(--fm-text);
    }

    /* ── Toggle Switch ── */
    .fm-toggle {
        position: relative;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .fm-toggle input { position: absolute; opacity: 0; width: 0; height: 0; }
    .fm-toggle-track {
        width: 36px;
        height: 20px;
        background: #d1d5db;
        border-radius: 999px;
        position: relative;
        cursor: pointer;
        transition: background 0.25s ease;
    }
    .fm-toggle-track::after {
        content: '';
        position: absolute;
        top: 2px;
        left: 2px;
        width: 16px;
        height: 16px;
        background: #fff;
        border-radius: 50%;
        box-shadow: 0 1px 3px rgba(0,0,0,0.2);
        transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .fm-toggle input:checked + .fm-toggle-track {
        background: linear-gradient(135deg, var(--fm-primary), var(--fm-accent));
    }
    .fm-toggle input:checked + .fm-toggle-track::after {
        transform: translateX(16px);
    }
    .fm-toggle input:focus-visible + .fm-toggle-track {
        outline: 2px solid var(--fm-primary-light);
        outline-offset: 2px;
    }

    /* ── Save Button ── */
    .fm-save-bar {
        position: sticky;
        bottom: 0;
        background: linear-gradient(to top, rgba(249,250,251,1) 60%, rgba(249,250,251,0));
        padding: 1.5rem 0 0.5rem;
        display: flex;
        justify-content: flex-end;
        gap: 0.75rem;
        z-index: 10;
    }
    .fm-btn-save {
        background: linear-gradient(135deg, var(--fm-primary), var(--fm-primary-dark));
        color: #fff;
        border: none;
        padding: 0.75rem 2rem;
        border-radius: 12px;
        font-weight: 700;
        font-size: 0.875rem;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        box-shadow: 0 4px 14px rgba(220,38,38,0.3);
        transition: all 0.25s ease;
    }
    .fm-btn-save:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 20px rgba(220,38,38,0.4);
    }
    .fm-btn-save:active { transform: translateY(0); }

    /* ── Role Header Colors ── */
    .role-admin { color: #dc2626; }
    .role-supervisor { color: #ef4444; }
    .role-ppc { color: #7c3aed; }
    .role-foreman { color: #0891b2; }
    .role-operator { color: #059669; }
    .role-leader { color: #d97706; }
    .role-quality { color: #e11d48; }
    .role-production { color: #4f46e5; }
    .role-manager { color: #0d9488; }
    .role-kadiv { color: #9333ea; }
    .role-direktur { color: #1d4ed8; }
    .role-presdir { color: #b45309; }

    /* ── Group Icon Colors ── */
    .gicon-dashboard { background: linear-gradient(135deg, #dbeafe, #ede9fe); color: #3b82f6; }
    .gicon-master { background: linear-gradient(135deg, #fef3c7, #fde68a); color: #d97706; }
    .gicon-production { background: linear-gradient(135deg, #d1fae5, #a7f3d0); color: #059669; }
    .gicon-quality { background: linear-gradient(135deg, #fce7f3, #fbcfe8); color: #ec4899; }
    .gicon-report { background: linear-gradient(135deg, #e0e7ff, #c7d2fe); color: #6366f1; }
    .gicon-default { background: linear-gradient(135deg, #f1f5f9, #e2e8f0); color: #64748b; }

    /* ── Responsive ── */
    .fm-scroll-wrapper { overflow-x: auto; }
    @media (max-width: 768px) {
        .fm-hero { padding: 1.5rem; }
        .fm-hero h1 { font-size: 1.25rem; }
        .fm-stats { gap: 0.5rem; }
        .fm-stat { padding: 0.5rem 0.75rem; font-size: 0.75rem; }
        .fm-stat .num { font-size: 1rem; }
    }

    /* ── Animation ── */
    .fm-group { animation: fm-fadeUp 0.4s ease both; }
    @keyframes fm-fadeUp {
        from { opacity: 0; transform: translateY(12px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .fm-group:nth-child(2) { animation-delay: 0.05s; }
    .fm-group:nth-child(3) { animation-delay: 0.1s; }
    .fm-group:nth-child(4) { animation-delay: 0.15s; }
    .fm-group:nth-child(5) { animation-delay: 0.2s; }
    .fm-group:nth-child(6) { animation-delay: 0.25s; }
</style>
@endsection

@section('content')
<div class="p-3 sm:p-4 md:p-6">

    {{-- Success Alert --}}
    @if(session('success'))
    <div class="fm-alert">
        <svg width="20" height="20" fill="none" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        {{ session('success') }}
    </div>
    @endif

    {{-- Hero Banner --}}
    <div class="fm-hero">
        <h1>⚙️ Feature Management</h1>
        <p>Control which roles can access each module across the platform</p>
        <div class="fm-stats">
            <div class="fm-stat">
                <span class="num">{{ $features->count() }}</span> Features
            </div>
            <div class="fm-stat">
                <span class="num">{{ count($roles) }}</span> Roles
            </div>
            <div class="fm-stat">
                <span class="num">{{ $groups->count() }}</span> Groups
            </div>
        </div>
    </div>

    {{-- Permission Form --}}
    <form method="POST" action="{{ route('super-admin.features.update') }}">
        @csrf

        @php
            $groupIcons = [
                'dashboard' => ['class' => 'gicon-dashboard', 'icon' => '📊'],
                'master' => ['class' => 'gicon-master', 'icon' => '🗂️'],
                'production' => ['class' => 'gicon-production', 'icon' => '🏭'],
                'quality' => ['class' => 'gicon-quality', 'icon' => '🔬'],
                'report' => ['class' => 'gicon-report', 'icon' => '📈'],
            ];
        @endphp

        @foreach($groups as $groupName => $groupFeatures)
            @php
                $gKey = strtolower($groupName ?? 'default');
                $gMeta = $groupIcons[$gKey] ?? ['class' => 'gicon-default', 'icon' => '📦'];
                $enabledCount = 0;
                $totalPerms = count($groupFeatures) * count($roles);
                foreach ($groupFeatures as $f) {
                    foreach ($roles as $r) {
                        $k = $r . '_' . $f->id;
                        if (!isset($permissions[$k]) || $permissions[$k]->enabled) $enabledCount++;
                    }
                }
                $pct = $totalPerms > 0 ? round(($enabledCount / $totalPerms) * 100) : 0;
            @endphp
            <div class="fm-group" id="group-{{ Str::slug($groupName) }}">
                <div class="fm-group-header" onclick="this.parentElement.classList.toggle('collapsed')">
                    <div class="fm-group-icon {{ $gMeta['class'] }}">{{ $gMeta['icon'] }}</div>
                    <span class="fm-group-title">{{ $groupName ?: 'Uncategorized' }}</span>
                    <span class="fm-group-badge">{{ $groupFeatures->count() }} features · {{ $pct }}% active</span>
                    <svg class="fm-group-chevron" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </div>
                <div class="fm-group-body">
                    <div class="fm-scroll-wrapper">
                        <table class="fm-table">
                            <thead>
                                <tr>
                                    <th style="min-width:180px;">Feature Name</th>
                                    @foreach($roles as $role)
                                        <th><span class="role-{{ $role }}">{{ ucfirst($role) }}</span></th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($groupFeatures as $feature)
                                <tr>
                                    <td>{{ $feature->feature_name }}</td>
                                    @foreach($roles as $role)
                                        @php
                                            $key = $role . '_' . $feature->id;
                                            $enabled = isset($permissions[$key]) ? $permissions[$key]->enabled : true;
                                        @endphp
                                        <td>
                                            <label class="fm-toggle">
                                                <input type="hidden" name="permissions[{{ $role }}][{{ $feature->id }}]" value="0">
                                                <input type="checkbox" name="permissions[{{ $role }}][{{ $feature->id }}]" value="1" {{ $enabled ? 'checked' : '' }}>
                                                <span class="fm-toggle-track"></span>
                                            </label>
                                        </td>
                                    @endforeach
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endforeach

        {{-- Sticky Save Bar --}}
        <div class="fm-save-bar">
            <button type="submit" class="fm-btn-save">
                <svg width="18" height="18" fill="none" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Save Permissions
            </button>
        </div>
    </form>
</div>
@endsection
