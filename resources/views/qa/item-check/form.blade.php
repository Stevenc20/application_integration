@php
    $itemCheck = $itemCheck ?? null;
    $template = $template ?? ($itemCheck ? $itemCheck->masterTemplate : null);
    $schedule = $schedule ?? ($itemCheck ? $itemCheck->schedule : null);
    $user   = auth()->user();
    $pageTitle = ($itemCheck ? 'Inspeksi Harian - ' : 'Preview Inspeksi - ') . ($template->part_name ?? 'Unknown');
@endphp

@extends('layouts.app')
@section('content')

<style>
    /* CSS Khusus untuk Print / Export PDF */
    @media print {
        @page { size: A4 landscape; margin: 10mm; }
        
        /* Sembunyikan elemen-elemen UI web */
        .li-action-bar, header, nav, aside, #sidebar, .header, 
        .toast-container, .print-hidden, [x-show="showConfirmMain"], 
        #sig-modal, button:not(.print-visible), a:not(.print-visible),
        .toast-wrapper, [role="alert"], #global-intercom-overlay {
            display: none !important;
        }

        /* Reset body dan container untuk cetak penuh */
        body, html {
            background-color: white !important;
            padding: 0 !important;
            margin: 0 !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        #app, .min-h-screen, main, .container, .max-w-7xl, .li-wrap, .p-6, .lg\:p-8 {
            width: 100% !important;
            max-width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
            background: white !important;
            box-shadow: none !important;
        }

        /* Atur tabel agar terlihat rapi */
        .table-container {
            overflow: visible !important;
            max-height: none !important;
        }

        table {
            page-break-inside: auto !important;
            width: 100% !important;
        }

        tr {
            page-break-inside: avoid !important;
            page-break-after: auto !important;
        }

        thead {
            display: table-header-group !important;
        }

        /* Hilangkan scrollbars */
        ::-webkit-scrollbar {
            display: none !important;
        }

        /* Pastikan background color tercetak */
        * {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        /* Ubah input text menjadi teks biasa untuk cetak */
        input[type="text"], input[type="number"], textarea, select {
            border: 1px solid #e2e8f0 !important;
            background: white !important;
            color: #0f172a !important;
        }
        
        /* Hapus bayangan/shadow */
        .shadow-sm, .shadow-md, .shadow-lg, .shadow-xl, .shadow-2xl, .shadow-inner {
            box-shadow: none !important;
        }
        
        /* Hapus sudut melengkung berlebihan pada kontainer utama */
        .rounded-\[2\.5rem\], .rounded-3xl, .rounded-2xl {
            border-radius: 8px !important;
            border: 1px solid #e2e8f0 !important;
        }
    }
</style>

<div class="li-wrap font-['Plus_Jakarta_Sans'] pb-[14rem] sm:pb-36 lg:pb-40"
     x-data='itemCheckForm({ 
        editId: {{ $itemCheck ? $itemCheck->id : "null" }}, 
        previewData: @json($itemCheck ? null : array_merge($template ? $template->toArray() : [], ["total_produksi" => $actualQty ?? 0, "status" => "draft"])),
        role: @json($user->role), 
        userName: @json($user->name), 
        userId: @json($user->id),
        tandemUnfinishedId: {{ (isset($tandemCheck) && !in_array($tandemCheck->status, ["finished", "waiting_qc_approval", "approved"])) ? $tandemCheck->id : "null" }}
     })'
     x-init="init()">

{{-- TOAST --}}
<template x-if="toast">
  <div class="fixed top-5 right-5 z-[10002] flex items-center gap-3 px-5 py-3 rounded-2xl shadow-2xl text-sm font-bold transition-all"
       :class="toast.type==='success' ? 'bg-emerald-600 text-white' : 'bg-red-600 text-white'">
    <span x-text="toast.type==='success' ? '✓' : '✕'"></span>
    <span x-text="toast.msg"></span>
  </div>
</template>

{{-- SKELETON LOADER GLOBAL DIPAKAI (diatur dari app-layout.blade.php) --}}

{{-- CSS FIXES TO MATCH REACT --}}
<style>
  .li-info-ttd-row { display: flex; flex-wrap: wrap; border-bottom: 1px solid #94a3b8; }
  .li-info-part { flex: 1 1 360px; min-width: 0; padding: 16px 20px; border-right: 1px solid #94a3b8; }
  .li-ttd-cols { display: flex; flex-shrink: 0; }
  .li-ttd-col { width: 120px; border-left: 1px solid #94a3b8; display: flex; flex-direction: column; flex-grow: 1; }
  .li-ttd-col:first-child { border-left: none; }
  @media (max-width: 1150px) {
    .li-info-part { border-right: none; border-bottom: 1px solid #94a3b8; flex: 1 1 100%; }
    .li-ttd-cols { width: 100%; border-top: 1px solid #94a3b8; margin-top: -1px; }
  }
  .li-sketch-std { display: grid; grid-template-columns: minmax(300px, 500px) 1fr; border-bottom: 1px solid #94a3b8; }
  @media (max-width: 800px) { .li-sketch-std { grid-template-columns: 1fr; } }

  /* STICKY TABLE STYLES */
  .li-table-scroll { overflow-x: auto; max-width: 100%; border-bottom: 1.5px solid #cbd5e1; }
  .li-table-main { width: max-content; min-width: 100%; border-collapse: collapse; table-layout: fixed; }
  .li-top-left-sticky { 
      position: sticky; 
      left: 0; 
      top: 0; 
      z-index: 20; 
      background: #f1f5f9; 
      border-right: 2px solid #cbd5e1; 
      border-bottom: 2px solid #cbd5e1; 
      min-width: 140px !important; 
      max-width: 140px !important; 
      width: 140px !important; 
  }
  .li-head-sticky { position: sticky; top: 0; z-index: 10; background: #f1f5f9; border-bottom: 2px solid #cbd5e1; }
  .li-col-sticky { 
      position: sticky; 
      left: 0; 
      z-index: 5; 
      background: #fff; 
      border-right: 2px solid #cbd5e1; 
      min-width: 140px !important; 
      max-width: 140px !important; 
      width: 140px !important; 
      word-wrap: break-word !important;
      white-space: normal !important;
  }
  .li-row-hover:hover .li-col-sticky { background: #f8fafc; }

  /* Action bar — tidak menutupi sidebar desktop, rapi di HP */
  .li-action-bar {
    padding-bottom: env(safe-area-inset-bottom, 0px);
  }
  @media (min-width: 1024px) {
    .li-action-bar { left: 16rem; }
  }
  .li-action-bar-inner {
    max-width: 1400px;
    margin: 0 auto;
  }

  /* HP/Tablet: kontrol yang dulu cuma hover  selalu tampil + area tap besar */
  .li-reveal-btn {
    opacity: 1 !important;
    pointer-events: auto !important;
  }
  @media (hover: hover) and (pointer: fine) and (min-width: 1024px) {
    .li-reveal-btn {
      opacity: 0 !important;
    }
    .group:hover .li-reveal-btn,
    .group\/cell:hover .li-reveal-btn {
      opacity: 1 !important;
    }
  }
  .li-sketch-actions {
    opacity: 1 !important;
  }
  @media (hover: hover) and (pointer: fine) and (min-width: 1024px) {
    .li-sketch-actions {
      opacity: 0 !important;
    }
    .group:hover .li-sketch-actions {
      opacity: 1 !important;
    }
  }
  @media (max-width: 1023px), (hover: none) {
    .li-touch-cell {
      min-height: 52px;
      padding-top: 8px;
      padding-bottom: 8px;
    }
    .li-touch-input-btn {
      min-width: 56px !important;
      min-height: 48px !important;
      width: auto !important;
      padding: 8px 12px !important;
      font-size: 12px !important;
    }
    .li-touch-link {
      min-height: 44px;
      padding: 10px 12px;
      display: inline-flex;
      align-items: center;
      border-radius: 10px;
      background: rgba(255,255,255,0.9);
      border: 1px solid #e2e8f0;
    }
    .li-touch-setok {
      min-height: 40px;
      min-width: 72px;
      padding: 8px 12px !important;
    }
  }
</style>

{{-- TANDEM FLIP BUTTON --}}
@if(isset($tandemCheck))
<div class="max-w-[1400px] mx-auto my-4 flex items-center justify-between bg-indigo-50 border border-indigo-200 rounded-2xl p-4 shadow-sm relative overflow-hidden">
    <!-- Dekorasi background -->
    <div class="absolute right-0 top-0 w-64 h-64 bg-indigo-100 rounded-full blur-3xl opacity-50 -mr-20 -mt-20 pointer-events-none"></div>
    <div class="relative flex items-center gap-4">
        <div class="w-12 h-12 bg-white text-indigo-600 rounded-xl flex items-center justify-center shadow-sm border border-indigo-100 shrink-0">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
        </div>
        <div>
            <span class="block text-[10px] font-black text-indigo-400 uppercase tracking-widest mb-0.5">Mode Inspeksi Tandem (Kiri/Kanan)</span>
            <span class="block text-sm sm:text-base font-black text-indigo-900">Part Saat Ini: <span class="text-indigo-600">{{ $template->part_name ?? $itemCheck->masterTemplate->part_name ?? 'Tidak diketahui' }}</span></span>
        </div>
    </div>
    <button @click="flipTandem('{{ route('item-check.form', $tandemCheck->id) }}')" class="relative px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs sm:text-sm font-black transition-all shadow-lg shadow-indigo-600/30 active:scale-95 flex items-center gap-2 shrink-0">
        <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
        Flip ke {{ $tandemCheck->masterTemplate->part_name ?? 'Part Pasangan' }}
    </button>
</div>
@endif

{{-- FORM CARD --}}
<div class="bg-white border border-slate-300 rounded-xl sm:rounded-2xl shadow-xl overflow-hidden max-w-[1400px] mx-auto my-2 sm:my-5">

  {{-- HEADER 3-COL --}}
  <div class="li-form-header-grid grid border-b border-slate-200 bg-white" style="grid-template-columns: minmax(180px, 240px) 1fr minmax(120px, 150px);">
    <div class="flex items-center gap-3 p-4">
      <img src="{{ asset('IPPII.png') }}" alt="IPPI Logo" class="w-16 h-16 object-contain shrink-0">
      <div>
        <p class="font-black text-slate-800 text-[14px] leading-tight uppercase">PT. INTI PANTJA<br>PRESS INDUSTRI</p>
      </div>
    </div>
    <div class="flex flex-col items-center justify-center p-4 text-center">
      <div class="flex items-center gap-2 mb-2 justify-center">
        <h1 class="font-black text-lg text-slate-800 uppercase tracking-[2px] leading-none">Lembar Inspeksi</h1>
        @if(!$itemCheck)
        <span class="px-2 py-1 bg-amber-100 text-amber-700 text-[10px] font-black rounded border border-amber-200">PREVIEW MODE</span>
        @endif
      </div>
      <div class="flex items-center gap-4 text-[10px] font-bold text-slate-500">
        <div class="flex items-center gap-2">
            <span>LOKASI:</span>
            <select x-model="lokasi" :disabled="!canEditStandardSection" @change="onLeaderFieldChange('lokasi', 'Lokasi')" class="bg-white/50 border border-slate-200 rounded px-1.5 py-0.5 text-slate-800 outline-none focus:border-red-500 transition-all">
                <option value="">— Pilih Line —</option>
                <option value="PRESS A">PRESS A</option>
                <option value="PRESS B">PRESS B</option>
                <option value="PRESS C">PRESS C</option>
                <option value="PRESS D">PRESS D</option>
            </select>
        </div>
      </div>

      {{-- OPERATOR CLAIM BUTTON --}}
      <template x-if="status === 'locked' && !operatorClaimedAt && isOperator">
          <div class="mt-4">
              <button @click="claimTask()" 
                      class="px-8 py-3 bg-emerald-600 text-white rounded-[1.5rem] text-[11px] font-black hover:bg-emerald-700 hover:scale-105 transition-all shadow-xl shadow-emerald-600/20 flex items-center gap-3">
                  <span class="text-lg">>></span>
                  KLAIM TUGAS INSPEKSI SEKARANG
              </button>
          </div>
      </template>

      {{-- CLAIMED BADGE --}}
      <template x-if="operatorClaimedAt || waktuMulai">
          <div class="mt-4 flex items-center gap-2 bg-emerald-50 border border-emerald-200 px-3 py-1.5 rounded-full shadow-sm">
              <span class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></span>
              <span class="text-[9px] font-black text-emerald-700 uppercase tracking-widest">
                  Diklaim: <span x-text="formatDate(operatorClaimedAt || waktuMulai) + ' ' + new Date(operatorClaimedAt || waktuMulai).toLocaleTimeString()"></span>
              </span>
          </div>
      </template>
    </div>
    <div class="flex flex-col items-end justify-center p-4 gap-2">
      <div class="flex items-center gap-2">
        {{-- BADGE REVISI (HOVER MESSAGE) --}}
        <template x-if="status === 'revision'">
            <div class="relative group" x-data="{ showMsg: false }">
                <div @mouseenter="showMsg = true" @mouseleave="showMsg = false" @click="showMsg = !showMsg"
                     class="flex items-center gap-1 px-3 py-1 bg-red-600 text-white rounded-md text-[10px] font-black cursor-pointer animate-pulse shadow-sm">
                    <span class="w-1.5 h-1.5 bg-white rounded-full"></span>
                    REVISI
                </div>
                
                {{-- POPOVER MESSAGE --}}
                <div x-show="showMsg" x-cloak
                     class="absolute top-8 right-0 w-64 bg-slate-900 text-white p-3 rounded-xl shadow-2xl z-[100] border border-slate-700 transform transition-all scale-100 origin-top-right">
                    <p class="text-[9px] font-black text-red-400 uppercase tracking-widest mb-1.5 flex items-center gap-2">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
                        Pesan Revisi:
                    </p>
                    <p class="text-[11px] font-bold leading-relaxed text-slate-100" x-text="revisiCatatan || 'Harap periksa kembali bagian yang ditandai.'"></p>
                    <div class="absolute -top-1.5 right-6 w-3 h-3 bg-slate-900 transform rotate-45 border-t border-l border-slate-700"></div>
                </div>
            </div>
        </template>
        
        <div class="border-2 border-slate-700 px-3 py-1.5 text-[10px] font-black text-slate-800 whitespace-nowrap bg-white w-full text-center">
          FISM PRO-02-08-01
        </div>
      </div>
      {{-- STATUS INDICATOR (Moved from bottom) --}}
      <span class="px-4 py-1.5 rounded text-[10px] font-black transition-all uppercase tracking-wider w-full text-center border"
            :style="status === 'draft' ? 'background: #dc2626; color: white; border-color: #b91c1c;' : 'background:' + getStatusStyle(status).bg + 
                    '; color:' + getStatusStyle(status).color + 
                    '; border-color:' + getStatusStyle(status).border"
            x-text="getStatusStyle(status).label">
      </span>
    </div>
  </div>

  {{-- TABS --}}
  @if(true) {{-- SHOW TABS FOR ITEM CHECK --}}
  <div class="flex border-b border-slate-200 bg-white px-4 gap-1">

    {{-- Tab: Standard --}}
    <button @click="activeTab='main'"
      class="flex items-center gap-2 px-5 py-3 text-xs font-bold transition-all border-b-2 -mb-px"
      :class="activeTab==='main'
        ? 'border-slate-800 text-slate-800'
        : 'border-transparent text-slate-400 hover:text-slate-600'">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
      </svg>
      Standard
    </button>

    {{-- Tab: Bundle Check --}}
    <button @click="activeTab='bundle'"
      class="flex items-center gap-2 px-5 py-3 text-xs font-bold transition-all border-b-2 -mb-px"
      :class="activeTab==='bundle'
        ? 'border-slate-800 text-slate-800'
        : 'border-transparent text-slate-400 hover:text-slate-600'">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
      </svg>
      Bundle Check
    </button>

  </div>
  @endif

  {{-- REVISI PANEL — tampil hanya saat status === revision --}}
  <div x-show="status === 'revision'" x-cloak
       class="mx-5 my-6 relative overflow-hidden bg-gradient-to-r from-orange-50 via-amber-50 to-yellow-50 border-2 border-orange-400 rounded-[1.25rem] shadow-[0_4px_20px_-4px_rgba(251,146,60,0.3)]">
    
    <div class="absolute top-0 left-0 w-1.5 h-full bg-gradient-to-b from-orange-500 to-amber-400"></div>

    <div class="relative p-5 flex items-start gap-4">
      <div class="shrink-0 w-12 h-12 bg-gradient-to-br from-orange-400 to-amber-500 shadow-md shadow-orange-500/30 rounded-xl flex items-center justify-center relative">
        <div class="absolute inset-0 bg-white/20 rounded-xl animate-ping opacity-20"></div>
        <svg class="w-6 h-6 text-white drop-shadow-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" 
                d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
        </svg>
      </div>
      <div class="flex-1 min-w-0">
        <div class="flex items-center gap-2 mb-1.5">
          <p class="text-sm font-black text-orange-950 uppercase tracking-wide">Dokumen Perlu Direvisi</p>
          <span class="px-2 py-0.5 rounded-md text-[9px] font-black bg-red-100 text-red-600 uppercase tracking-widest border border-red-200 animate-pulse">Action Required</span>
        </div>
        <p class="text-[13px] font-bold text-orange-800 leading-relaxed bg-white/40 px-3 py-2 rounded-lg inline-block border border-orange-200/50" 
           x-text="revisiCatatan || 'Foreman telah meminta revisi pada dokumen ini.'"></p>
        <div class="mt-3 h-px w-full bg-gradient-to-r from-orange-300/60 to-transparent"></div>
        <p class="text-[10px] font-black text-orange-600 uppercase tracking-[0.15em] mt-2 flex items-center gap-1.5">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
          Silakan perbaiki dan ajukan verifikasi ulang
        </p>
      </div>
    </div>
  </div>

  {{-- REVISI LAINNYA PANEL (Khusus untuk revisi "Lainnya") --}}
  <template x-if="fieldRevisions['other'] && !['waiting_supervisor', 'finished'].includes(status) && !['supervisor', 'operator'].includes(role.toLowerCase())">
    <div class="mx-5 mb-4 p-4 rounded-2xl flex items-center justify-between border-2 transition-all shadow-sm"
         :class="!fieldRevisions['other'].resolved ? 'bg-orange-50 border-orange-400' : 'bg-emerald-50 border-emerald-400'">
      <div class="flex items-start gap-4">
        <div class="shrink-0 w-10 h-10 rounded-xl flex items-center justify-center"
             :class="!fieldRevisions['other'].resolved ? 'bg-orange-400' : 'bg-emerald-400'">
          <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
        </div>
        <div class="flex-1 min-w-0">
          <p class="text-sm font-black mb-1" :class="!fieldRevisions['other'].resolved ? 'text-orange-900' : 'text-emerald-900'">Catatan Revisi Lainnya (Foreman)</p>
          <p class="text-xs font-bold leading-relaxed" 
             :class="!fieldRevisions['other'].resolved ? 'text-orange-700' : 'text-emerald-700'"
             x-text="typeof fieldRevisions['other'] === 'object' ? fieldRevisions['other'].catatan : fieldRevisions['other']"></p>
        </div>
      </div>
      
      <template x-if="!fieldRevisions['other'].resolved && isLeader">
        <button @click="confirmResolveRevision('other')" 
                class="px-4 py-2 bg-white text-orange-600 border-2 border-orange-200 hover:border-orange-500 hover:bg-orange-50 font-black text-xs rounded-xl transition-all shadow-sm shrink-0">
            ✓ Selesaikan Revisi
        </button>
      </template>
      <template x-if="fieldRevisions['other'].resolved">
        <div class="px-4 py-2 bg-emerald-100 text-emerald-700 font-black text-xs rounded-xl flex items-center gap-2 shrink-0 border border-emerald-200">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
            Telah Diperbaiki
        </div>
      </template>
    </div>
  </template>

  {{-- PP TAB: STANDARD PP --}}
  <div x-show="activeTab==='main'" x-cloak class="flex flex-col">

    <div x-show="canLeaderEditStandard && shouldLogLeaderRevision" x-cloak
         class="mx-4 mt-3 px-4 py-2.5 rounded-xl bg-amber-50 border border-amber-200 text-[10px] font-bold text-amber-900">
      Mode koreksi Leader QA: edit bagian atas  setelah berhenti mengetik 1 detik, perubahan digabung <strong>1 baris</strong> di Revision Record (bisa tambah manual).
    </div>

    {{-- SECTION 1: INFO & TTD ROW --}}
    <div class="li-info-ttd-row bg-white border-b border-slate-300">
      {{-- Info Part (left) --}}
      <div class="li-info-part">
        <div class="grid grid-cols-2 gap-x-8 gap-y-4">
          @foreach([
            ['jobNo','Job No.','jobNo'],['partName','Part Name','partName'],['partNo','Part No.','partNo'],
            ['partType','Type','partType'],['specMat','Spec Material','specMat'],['typePallet','Type Pallet','pallet'],
          ] as [$key, $label, $presetKey])
          <div class="flex flex-col gap-1.5 relative">
            <div class="flex items-center justify-between">
              <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest">{{ $label }}</label>
              
              <div class="flex items-center gap-1.5">
                {{-- Fitur Canggih: Load History/Template --}}
                @if($key === 'jobNo')
                    <button x-show="isLeader" @click="loadFromHistory()" type="button" class="li-touch-link text-[8px] sm:text-[8px] font-black text-red-500 hover:text-red-700 uppercase">Cari Histori</button>
                @endif
                @if($key === 'partNo')
                    <button x-show="isLeader" @click="loadTemplateByPartNo()" type="button" class="li-touch-link text-[8px] font-black text-red-500 hover:text-red-700 uppercase">Load Master</button>
                @endif


                {{-- Revision Indicator (Top Right) - Touch Friendly & SPV Hidden --}}
                <template x-if="fieldRevisions['{{ $presetKey }}'] && !['waiting_supervisor', 'finished'].includes(status)">
                    <div class="relative ml-2" x-data="{ showTooltip: false }" @click.away="showTooltip = false">
                        
                        {{-- BELUM DIPERBAIKI (MERAH) --}}
                        <template x-if="!fieldRevisions['{{ $presetKey }}'].resolved">
                            <div @click="showTooltip = !showTooltip" @mouseenter="showTooltip = true" @mouseleave="showTooltip = false"
                                 class="flex items-center gap-1 px-2 py-0.5 bg-red-600 text-white rounded-full cursor-pointer hover:bg-red-700 transition-all shadow-md animate-pulse">
                                <span class="text-[8px] font-black uppercase tracking-tighter">REVISI</span>
                            </div>
                        </template>

                        {{-- SUDAH DIPERBAIKI (HIJAU) --}}
                        <template x-if="fieldRevisions['{{ $presetKey }}'].resolved">
                            <div @click="showTooltip = !showTooltip" @mouseenter="showTooltip = true" @mouseleave="showTooltip = false"
                                 class="flex items-center gap-1 px-1.5 py-0.5 bg-emerald-500 text-white rounded-full cursor-pointer hover:bg-emerald-600 transition-all shadow-sm">
                                <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                <span class="text-[8px] font-black uppercase tracking-tighter">DIPERBAIKI</span>
                            </div>
                        </template>

                        {{-- Hover/Click Popover --}}
                        <div x-show="showTooltip" x-cloak
                             x-transition.opacity
                             class="absolute z-[100] bottom-full mb-2 right-0 w-64 bg-slate-900 text-white p-3 rounded-2xl shadow-2xl border border-slate-700 text-[10px] font-bold leading-relaxed">
                            <div class="flex items-center gap-2 mb-1.5 text-slate-400 font-black uppercase text-[8px] tracking-widest"
                                 :class="fieldRevisions['{{ $presetKey }}'].resolved ? 'text-emerald-400' : 'text-red-400'">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
                                Instruksi Foreman:
                            </div>
                            <p class="text-slate-200" x-text="typeof fieldRevisions['{{ $presetKey }}'] === 'object' ? fieldRevisions['{{ $presetKey }}'].catatan : fieldRevisions['{{ $presetKey }}']"></p>
                            <div class="absolute -bottom-1.5 right-4 w-3 h-3 bg-slate-900 transform rotate-45 border-r border-b border-slate-700"></div>
                        </div>
                    </div>
                </template>
              </div>
            </div>
            <div class="relative group">
                <input x-model="{{ $key }}" placeholder="-" 
                       :disabled="!canEditStandardSection"
                       @input.debounce.800ms="onLeaderFieldChange('{{ $key }}', '{{ $label }}')"
                       @change="if('{{ $key }}' === 'jobNo' || '{{ $key }}' === 'partNo') triggerSearch('{{ $key }}')"
                       class="w-full bg-slate-50 border-2 rounded-xl px-4 py-2 text-xs font-bold text-slate-800 outline-none focus:bg-white focus:border-red-500 transition-all placeholder:text-slate-200"
                       :class="{
                         'border-slate-100': !fieldRevisions['{{ $presetKey }}'] || fieldRevisions['{{ $presetKey }}'].resolved,
                         'border-red-500 bg-red-50/50 shadow-inner ring-2 ring-red-100': fieldRevisions['{{ $presetKey }}'] && !fieldRevisions['{{ $presetKey }}'].resolved && !pendingFieldRevisions['{{ $presetKey }}'],
                         'border-orange-500 bg-orange-100/50 border-dashed animate-pulse': pendingFieldRevisions['{{ $presetKey }}']
                       }">
                
                {{-- ACTION BUTTON: SELESAI REVISI --}}
                <template x-if="fieldRevisions['{{ $presetKey }}'] && !fieldRevisions['{{ $presetKey }}'].resolved && isLeader">
                    <div class="mt-2 flex justify-end">
                        <button type="button" @click="confirmResolveRevision('{{ $presetKey }}')"
                                class="px-3 py-1.5 bg-emerald-600 text-white rounded-lg text-[9px] font-black uppercase tracking-widest hover:bg-emerald-700 transition-all shadow-md shadow-emerald-600/20 flex items-center gap-1.5 hover:scale-105 active:scale-95">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            Selesaikan Revisi
                        </button>
                    </div>
                </template>
            </div>
          </div>
          @endforeach
        </div>

        {{-- SAMPLING FORMULA PANEL (Leader / Supervisor only) --}}
        <template x-if="isLeader || isSupervisor">
          <div class="mt-5 rounded-2xl border-2 overflow-hidden"
               :class="canLeaderEditStandard && shouldLogLeaderRevision ? 'border-amber-300 bg-amber-50/40' : 'border-slate-200 bg-slate-50/50'">
            <div class="flex items-center justify-between px-4 py-2.5 bg-slate-100/60 border-b border-slate-200">
              <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span class="text-[9px] font-black text-slate-600 uppercase tracking-widest">Sampling Formula</span>
                <span class="text-[8px] text-slate-400 font-bold">(Leader only)</span>
              </div>
              {{-- Live preview --}}
              <div class="flex items-center gap-3 flex-wrap justify-end">
                <div class="flex items-center gap-1.5" x-show="samplingCalc">
                  <span class="px-2 py-0.5 bg-amber-500 text-white text-[8px] font-black rounded-full uppercase"
                        x-text="samplingCalc?.modeLabel || '-'"></span>
                </div>
                <div class="flex items-center gap-1.5">
                  <span class="text-[8px] font-bold text-slate-400 uppercase">:</span>
                  <span class="px-2 py-0.5 bg-slate-600 text-white text-[9px] font-black rounded-full"
                        x-text="samplingCalc ? samplingCalc.divisor : '-'"></span>
                </div>
                <div class="flex items-center gap-1.5">
                  <span class="text-[8px] font-bold text-slate-400 uppercase">Int1:</span>
                  <span class="px-2 py-0.5 bg-slate-600 text-white text-[9px] font-black rounded-full"
                        x-text="samplingCalc ? samplingCalc.interval1 : '-'"></span>
                </div>
                <div class="flex items-center gap-1.5">
                  <span class="text-[8px] font-bold text-slate-400 uppercase">Int2:</span>
                  <span class="px-2 py-0.5 bg-slate-600 text-white text-[9px] font-black rounded-full"
                        x-text="samplingCalc ? samplingCalc.interval2 : '-'"></span>
                </div>
                <div class="flex items-center gap-1.5">
                  <span class="text-[8px] font-bold text-slate-400 uppercase">Kolom:</span>
                  <span class="px-2 py-0.5 bg-slate-800 text-white text-[9px] font-black rounded-full"
                        x-text="cols.length"></span>
                </div>
              </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 px-3 py-3">
              <!-- <div class="flex flex-col gap-1.5 sm:col-span-1">
                <label class="text-[8px] font-black text-slate-500 uppercase tracking-widest">Metode rumus</label>
                <select x-model="samplingFormulaMode"
                        @change="onSamplingFieldChange('samplingFormulaMode')"
                        :disabled="!canEditSampling"
                        class="w-full bg-white border-2 border-slate-200 rounded-xl px-3 py-2 text-xs font-black text-slate-800 outline-none focus:border-slate-400 disabled:opacity-50">
                  <option value="auto">Otomatis (sesuai CT)</option>
                  <option value="direct">Langsung CTTT (210/192)</option>
                  <option value="pembagi">Pembagi TT+CTmin (282/252)</option>
                </select>
              </div> -->
              <div class="flex flex-col gap-1.5">
                <label class="text-[8px] font-black text-slate-500 uppercase tracking-widest">CT per pcs (detik)</label>
                <input type="number" step="0.1" min="0"
                       x-model.number="tactTime"
                       @change="onSamplingFieldChange('tactTime')"
                       :disabled="!canEditSampling"
                       placeholder="0"
                       class="w-full bg-white border-2 border-slate-200 rounded-xl px-3 py-2 text-xs font-black text-slate-800 outline-none focus:border-slate-400 transition-all placeholder:text-slate-300 disabled:opacity-50">
              </div>
              <div class="flex flex-col gap-1.5">
                <label class="text-[8px] font-black text-slate-500 uppercase tracking-widest">CT Check Dimensi (dt)</label>
                <input type="number" step="0.1" min="0"
                       x-model.number="ctDimensi"
                       @change="onSamplingFieldChange('ctDimensi')"
                       :disabled="!canEditSampling"
                       placeholder="0"
                       class="w-full bg-white border-2 border-slate-200 rounded-xl px-3 py-2 text-xs font-black text-slate-800 outline-none focus:border-slate-400 transition-all placeholder:text-slate-300 disabled:opacity-50">
              </div>
              <div class="flex flex-col gap-1.5">
                <label class="text-[8px] font-black text-slate-500 uppercase tracking-widest">CT Tanpa Dimensi (dt)</label>
                <input type="number" step="0.1" min="0"
                       x-model.number="ctTanpaDimensi"
                       @change="onSamplingFieldChange('ctTanpaDimensi')"
                       :disabled="!canEditSampling"
                       placeholder="0"
                       class="w-full bg-white border-2 border-slate-200 rounded-xl px-3 py-2 text-xs font-black text-slate-800 outline-none focus:border-slate-400 transition-all placeholder:text-slate-300 disabled:opacity-50">
              </div>
            </div>
            <div class="px-4 pb-3">
              <!-- <p class="text-[8px] text-slate-400 font-bold leading-relaxed">
                <strong>Langsung CTTT</strong> (gambar 2): TT=6,5, CT=210/192  Int1=32, Int2=29  kolom 1,33,62&845 (30 kolom).<br>
                <strong>Pembagi</strong> (GT-5154): TT=6,5, CT=282/252  pembagi 11  Int1=26, Int2=23  1,27,50&<br>
                <span class="text-amber-600">Otomatis: CT dimensi e270 detik  pembagi, di bawah  langsung. Ubah manual jika part beda.</span>
              </p> -->
            </div>
          </div>
        </template>

      </div>

      {{-- TTD Part (right) --}}
      <div class="li-ttd-cols">
        @foreach([
          ['APPROVED','Novina','Supervisor','spv'],
          ['CHECKED','Azriel','Foreman','fm'],
          ['PREPARED','','Leader QA','prep'],
        ] as [$role,$nama,$divisi,$padKey])
        <div class="li-ttd-col w-[130px] border-l border-slate-200 flex flex-col">
          <div class="bg-slate-100 border-b border-slate-200 py-2 text-center text-[8px] font-black text-slate-500 uppercase tracking-widest">{{ $role }}</div>
          <div class="flex-1 p-3 flex items-center justify-center border-b border-slate-100 min-h-[90px] bg-white relative">
            @if($padKey === 'prep')
            <template x-if="prepSig">
              <div class="relative group">
                <img :src="prepSig" class="h-14 object-contain mx-auto transition-transform group-hover:scale-105">
                <button x-show="isLeader && !isQASectionFixed" @click="prepSig=null" class="absolute -top-3 -right-3 w-6 h-6 bg-red-600 text-white rounded-full flex items-center justify-center shadow-xl border-2 border-white transition-transform hover:scale-110">×</button>
              </div>
            </template>
            <template x-if="!prepSig">
              <button @click="isLeader && openSignaturePad('prep')" 
                class="text-[10px] px-4 py-2 border-2 border-red-50 rounded-xl font-black text-red-600 hover:bg-red-50 transition-all disabled:opacity-30" 
                :disabled="!isLeader">
 SIGN</button>
            </template>
            @elseif($padKey === 'fm')
            <template x-if="masterGlSig">
              <div class="relative group">
                <img :src="masterGlSig" class="h-14 object-contain mx-auto transition-transform group-hover:scale-105">
              </div>
            </template>
            <template x-if="!masterGlSig">
              <div class="flex flex-col items-center gap-2">
                  <span class="text-rose-100 font-medium text-[10px]">SIGN</span>
              </div>
            </template>
            @elseif($padKey === 'spv')
            <template x-if="masterFmSig">
              <div class="relative group">
                <img :src="masterFmSig" class="h-14 object-contain mx-auto transition-transform group-hover:scale-105">
              </div>
            </template>
            <template x-if="!masterFmSig">
              <div class="flex flex-col items-center gap-2">
                  <span class="text-rose-100 font-medium text-[10px]">SIGN</span>
              </div>
            </template>
            @endif
          </div>
          <div class="p-3 text-center bg-slate-50/50 flex flex-col gap-1">
            @if($padKey==='prep')
                <span class="text-[10px] font-black text-slate-800 truncate" x-text="qgName || '—'"></span>
            @elseif($padKey==='fm')
                <span class="text-[10px] font-black text-slate-800 truncate" x-text="masterGlName || '—'"></span>
            @else
                <span class="text-[10px] font-black text-slate-800 truncate" x-text="masterFmName || '—'"></span>
            @endif
            <p class="text-[7px] text-slate-400 font-bold uppercase tracking-[1px]">{{ $divisi }}</p>
          </div>
        </div>
        @endforeach
      </div>
    </div>
 
    {{-- SECTION 2: SKETCH & STANDARD (REFERENCE) --}}
    <div class="li-sketch-std border-b border-slate-300">
        {{-- SKETCH PART (Left) --}}
        <div class="flex flex-col border-r border-slate-300 bg-white">
            <div class="bg-red-600 px-4 py-2 flex items-center justify-between relative">
                <div class="flex items-center gap-2">
                    <span class="text-[10px] font-black text-white uppercase tracking-widest">SKETCH PART</span>
                    
                    {{-- Revision Indicator Sketch - Touch Friendly & SPV Hidden --}}
                    <template x-if="fieldRevisions['sketch'] && !['waiting_supervisor', 'finished'].includes(status)">
                        <div class="relative" x-data="{ showTooltip: false }" @click.away="showTooltip = false">
                            
                            {{-- BELUM DIPERBAIKI (MERAH) --}}
                            <template x-if="!fieldRevisions['sketch'].resolved">
                                <div @click.stop="showTooltip = !showTooltip" @mouseenter="showTooltip = true" @mouseleave="showTooltip = false"
                                     class="flex items-center gap-1 px-1.5 py-0.5 bg-white text-orange-600 rounded-full cursor-pointer hover:bg-orange-600 hover:text-white transition-all shadow-sm">
                                    <span class="text-[8px] font-black">REVISI</span>
                                </div>
                            </template>

                            {{-- SUDAH DIPERBAIKI (HIJAU) --}}
                            <template x-if="fieldRevisions['sketch'].resolved">
                                <div @click.stop="showTooltip = !showTooltip" @mouseenter="showTooltip = true" @mouseleave="showTooltip = false"
                                     class="flex items-center gap-1 px-1.5 py-0.5 bg-emerald-500 text-white rounded-full cursor-pointer hover:bg-emerald-600 transition-all shadow-sm">
                                    <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                    <span class="text-[8px] font-black">DIPERBAIKI</span>
                                </div>
                            </template>
                            
                            {{-- Hover/Click Popover --}}
                            <div x-show="showTooltip" x-cloak
                                 x-transition.opacity
                                 class="absolute z-[100] top-10 left-4 w-48 bg-slate-900 text-white p-2.5 rounded-xl shadow-2xl text-[9px] font-bold leading-relaxed border border-slate-700 normal-case tracking-normal">
                                <p class="text-orange-400 mb-1 font-black" :class="fieldRevisions['sketch'].resolved ? 'text-emerald-400' : 'text-orange-400'">CATATAN FOREMAN:</p>
                                <p x-text="typeof fieldRevisions['sketch'] === 'object' ? fieldRevisions['sketch'].catatan : fieldRevisions['sketch']"></p>
                            </div>
                        </div>
                    </template>
                </div>
                <!-- <span class="text-[8px] text-red-100 font-bold uppercase opacity-80">Reference</span> -->
            </div>
            <div class="flex-1 flex items-center justify-center p-6 relative min-h-[220px] cursor-pointer group hover:bg-slate-50 transition-all" @click="isLeader && (showSketchChoiceModal = true)">
                <template x-if="sketchUrl">
                    <div class="relative group">
                        <img :src="sketchUrl" class="max-w-full max-h-[400px] object-contain rounded-xl shadow-xl transition-transform group-hover:scale-[1.02]">
                        <div class="li-sketch-actions absolute top-2 right-2 flex flex-wrap gap-2 transition-opacity">
                            <button x-show="isLeader && canEditStandardSection && sketchSource === 'blank'" @click.stop="openSketchEditor()" class="min-h-[44px] px-4 py-2.5 bg-blue-600 text-white text-[10px] font-black rounded-xl shadow-xl hover:bg-blue-700 active:scale-95 flex items-center gap-1">
                                ✏ Edit & Tandai Zona
                            </button>
                            <button x-show="isLeader && canEditStandardSection" @click.stop="clearSketchWithRevision()" class="min-w-[44px] min-h-[44px] bg-red-600 text-white rounded-xl shadow-lg flex items-center justify-center hover:bg-red-700 active:scale-95 border-2 border-white text-sm font-black">×</button>
                        </div>
                    </div>
                </template>
                <template x-if="!sketchUrl">
                    <div class="flex flex-col items-center gap-3 text-slate-300">
                        <div class="w-12 h-12 rounded-full border-2 border-dashed border-slate-200 flex items-center justify-center">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </div>
                        <p class="text-[9px] font-black uppercase tracking-[1px]">Upload Sketch Part</p>
                    </div>
                </template>
                <input type="file" x-ref="sketchInput" class="hidden" accept="image/*" @change="handleSketch($event)">
            </div>

            {{-- ACTION BUTTON SKETCH: SELESAI REVISI --}}
            <template x-if="fieldRevisions['sketch'] && !fieldRevisions['sketch'].resolved && isLeader">
                <div class="p-3 bg-red-50 border-t border-red-200 flex justify-end">
                    <button type="button" @click.stop="confirmResolveRevision('sketch')"
                            class="px-4 py-2 bg-emerald-600 text-white rounded-lg text-[9px] font-black uppercase tracking-widest hover:bg-emerald-700 transition-all shadow-md shadow-emerald-600/20 flex items-center gap-1.5 hover:scale-105 active:scale-95">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        Selesaikan Revisi Sketch
                    </button>
                </div>
            </template>

            <div class="p-3 bg-white border-t border-slate-100 flex items-center justify-center gap-4 flex-wrap">
                <div class="flex items-center gap-2"><span class="text-emerald-500 font-black">✓</span> <span class="text-[8px] font-black text-slate-400">OK</span></div>
                <div class="flex items-center gap-2"><span class="text-rose-500 font-black">×</span> <span class="text-[8px] font-black text-slate-400">NG</span></div>
            </div>
        </div>

        {{-- STANDARD (Right) --}}
        <div class="flex flex-col bg-white">
            <div class="bg-red-600 px-4 py-2 flex items-center justify-between shadow-sm">
                <span class="text-[10px] font-black text-white uppercase tracking-widest">STANDARD</span>
                <!-- <span class="text-[8px] text-red-100 font-bold uppercase opacity-80">Reference</span> -->
            </div>
            <div class="bg-white">
                <table class="w-full border-collapse text-[10px]">
                    <thead class="sticky top-0 bg-slate-50 border-b border-slate-200 z-10 shadow-sm">
                        <tr>
                            <th class="px-3 py-3 text-center font-black text-slate-400 w-10 border-r border-slate-100">No</th>
                            <th class="px-3 py-3 text-left font-black text-slate-500 border-r border-slate-100">Item Check</th>
                            <th class="px-3 py-3 text-left font-black text-slate-500">Metode</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        {{-- DIMENSI SUBHEADER --}}
                        <tr class="bg-red-50/50">
                            <td colspan="3" class="px-3 py-3 text-[9px] font-black text-red-600 uppercase tracking-widest">DIMENSI</td>
                        </tr>
                        <template x-for="(dim, ri) in dimStd" :key="'ref_dim_'+ri">
                            <tr class="hover:bg-slate-50/50 transition-colors group">
                                <td class="px-3 py-3 text-center font-black text-slate-800 border-r border-slate-100" x-text="ri+1"></td>
                                <td class="px-3 py-3 border-r border-slate-100">
                                    <div class="flex items-center justify-between gap-2 group/cell li-touch-cell min-h-[48px]">
                                        <div class="flex flex-col gap-0.5 flex-1 min-w-0"
                                             :class="isLeader && canEditStandardSection ? 'cursor-pointer' : ''"
                                             @click="isLeader && canEditStandardSection && openDimSettings(ri)">
                                            <span class="text-[10px] font-black text-slate-700 uppercase" x-show="dim.item" x-text="dim.item"></span>
                                            <span class="text-[9px] font-black text-red-600 uppercase tracking-tight" x-text="getDimStandardText(ri)"></span>
                                        </div>
                                        <button type="button" x-show="isLeader && canEditStandardSection"
                                                @click.stop="openDimSettings(ri)"
                                                class="li-reveal-btn shrink-0 flex flex-col items-center justify-center gap-0.5 min-w-[48px] min-h-[48px] p-2 bg-red-50 text-red-600 border-2 border-red-200 rounded-xl hover:bg-red-600 hover:text-white active:scale-95 shadow-sm"
                                                title="Atur standar dimensi">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                            <span class="text-[8px] font-black leading-none lg:hidden">Atur</span>
                                        </button>
                                    </div>
                                </td>
                                <td class="px-3 py-3">
                                    <input x-model="dim.method" placeholder="Metode"
                                           :disabled="!canEditStandardSection"
                                           class="w-full min-h-[40px] px-2 bg-slate-50 sm:bg-transparent border border-slate-200 sm:border-0 rounded-lg sm:rounded-none text-slate-700 font-bold outline-none disabled:opacity-50 text-[11px] sm:text-[10px] focus:border-red-400">
                                </td>
                            </tr>
                        </template>

                        {{-- APPEARANCE SUBHEADER --}}
                        <tr class="bg-red-50/50">
                            <td colspan="3" class="px-3 py-3 text-[9px] font-black text-red-600 uppercase tracking-widest">APPEARANCE</td>
                        </tr>
                        <template x-for="(app, ri) in appItems" :key="'ref_app_'+ri">
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-3 py-3 text-center font-black text-slate-800 border-r border-slate-100" x-text="ri+8"></td>
                                <td class="px-3 py-3" colspan="2">
                                    {{-- Jumlah Hole: input standar pcs di kolom Standard (kanan) --}}
                                    <template x-if="app && app.toUpperCase().includes('JUMLAH HOLE')">
                                        <div class="flex flex-col gap-1">
                                            <span class="text-[10px] font-black text-slate-700" x-text="app"></span>
                                            <div class="flex items-center gap-1.5 mt-0.5">
                                                <span class="text-[9px] text-slate-400 font-bold">Std:</span>
                                                <input type="number" x-model="holeStandard"
                                                       :disabled="!canEditStandardSection"
                                                       @change="onLeaderFieldChange('holeStandard', 'Jumlah Hole standar')"
                                                       class="w-16 text-[10px] font-black text-center rounded px-1.5 py-0.5 outline-none transition-all disabled:opacity-50"
                                                       :class="(!holeStandard || parseInt(holeStandard) <= 0) ? 'border-2 border-red-500 bg-red-50 text-red-600 focus:border-red-600' : 'bg-slate-100 border border-slate-200 text-slate-800 focus:border-red-400'"
                                                       placeholder="pcs">
                                                <span class="text-[9px] text-slate-400 font-bold">pcs</span>
                                            </div>
                                        </div>
                                    </template>
                                    {{-- Baris lainnya: normal, bisa edit via modal --}}
                                    <template x-if="!app || !app.toUpperCase().includes('JUMLAH HOLE')">
                                        <div class="flex items-center gap-2 group li-touch-cell min-h-[48px] cursor-pointer rounded-lg active:bg-red-50/50"
                                             @click="canEditStandardSection && isLeader && openAppStandardModal(ri)">
                                            <input x-model="appItems[ri]"
                                                   readonly
                                                   :disabled="!canEditStandardSection"
                                                   placeholder="Ketuk untuk isi standar..."
                                                   class="w-full min-h-[40px] px-2 bg-slate-50 sm:bg-transparent border border-slate-200 sm:border-0 rounded-lg sm:rounded-none font-bold text-slate-700 outline-none cursor-pointer disabled:cursor-not-allowed truncate text-[11px] sm:text-[10px] pointer-events-none">
                                            <button type="button" x-show="canEditStandardSection && isLeader" 
                                                    @click.stop="openAppStandardModal(ri)" 
                                                    class="li-reveal-btn shrink-0 flex flex-col items-center justify-center gap-0.5 min-w-[48px] min-h-[48px] p-2 bg-red-50 text-red-600 border-2 border-red-200 rounded-xl hover:bg-red-100 active:scale-95 shadow-sm">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                                <span class="text-[8px] font-black leading-none lg:hidden">Edit</span>
                                            </button>
                                        </div>
                                    </template>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            {{-- REVISION RECORD (di dalam kolom STANDARD, bawah Appearance) --}}
            <div id="revision-record-section" class="bg-slate-50">
                <div class="flex items-center justify-between px-4 py-3 border-y border-slate-200">
                    <div class="flex items-center gap-2">
                        <span class="text-[9px] font-black text-slate-800 uppercase tracking-widest">Revision Record</span>
                    </div>
                    <button type="button" x-show="isLeader || !isQASectionFixed" @click="addRevRecord()" 
                            class="text-[8px] font-black text-slate-500 hover:text-slate-800 transition-colors">+ Tambah Baris (manual)</button>
                </div>
                <table class="w-full border-collapse text-[9px] border-b border-slate-200">
                    <thead>
                        <tr class="bg-slate-100/50">
                            <th class="px-3 py-3 text-center font-black text-slate-600 uppercase w-12 border-r border-slate-200">No</th>
                            <th class="px-3 py-3 text-center font-black text-slate-600 uppercase w-32 border-r border-slate-200">Date</th>
                            <th class="px-4 py-3 text-left font-black text-slate-600 uppercase border-r border-slate-200">Revision Record</th>
                            <th class="px-3 py-3 text-center font-black text-slate-600 uppercase w-24 border-r border-slate-200">Approved</th>
                            <th class="px-3 py-3 text-center font-black text-slate-600 uppercase w-24 border-r border-slate-200">Checked</th>
                            <th class="px-3 py-3 text-center font-black text-slate-600 uppercase w-32 border-r border-slate-200">Prepared</th>
                            <th class="px-2 py-3 text-center font-black text-slate-600 uppercase w-12" x-show="isLeader && canEditStandardSection"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(rec, ri) in revRecords" :key="'rr_'+ri">
                            <tr class="border-t border-slate-200 hover:bg-slate-50 transition-colors bg-white group/rr">
                                <td class="px-3 py-3 border-r border-slate-200"><div class="w-full text-center text-[10px] font-black text-slate-500" x-text="ri + 1"></div></td>
                                <td class="px-3 py-3 border-r border-slate-200"><input type="date" x-model="revRecords[ri].date" :disabled="!(isLeader && canEditStandardSection)" class="w-full bg-transparent font-bold text-slate-700 outline-none text-center text-[10px] focus:ring-0 disabled:opacity-60"></td>
                                <td class="px-4 py-3 border-r border-slate-200"><input x-model="revRecords[ri].record" :disabled="!(isLeader && canEditStandardSection)" class="w-full bg-transparent font-bold text-slate-700 outline-none text-[10px] placeholder:text-slate-300 disabled:opacity-60" placeholder="Ketik catatan revisi di sini..."></td>
                                <td class="px-3 py-3 text-center border-r border-slate-200"><input x-model="revRecords[ri].approved" :disabled="!(isLeader && canEditStandardSection)" class="w-full bg-transparent font-bold text-slate-700 outline-none text-center text-[10px] disabled:opacity-60" placeholder="-"></td>
                                <td class="px-3 py-3 text-center border-r border-slate-200"><input x-model="revRecords[ri].checked" :disabled="!(isLeader && canEditStandardSection)" class="w-full bg-transparent font-bold text-slate-700 outline-none text-center text-[10px] disabled:opacity-60" placeholder="-"></td>
                                <td class="px-3 py-3 text-center border-r border-slate-200"><input x-model="revRecords[ri].prepared" :disabled="!(isLeader && canEditStandardSection)" class="w-full bg-transparent font-bold text-slate-700 outline-none text-center text-[10px] disabled:opacity-60" placeholder="-"></td>
                                <td class="px-2 py-3 text-center" x-show="isLeader && canEditStandardSection">
                                    <button type="button" @click="removeRevRecord(ri)"
                                            class="min-w-[36px] min-h-[36px] inline-flex items-center justify-center rounded-lg text-red-500 hover:bg-red-50 hover:text-red-700 border border-transparent hover:border-red-200 transition-colors"
                                            title="Hapus baris">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="revRecords.length === 0" x-cloak>
                            <td :colspan="(isLeader && canEditStandardSection) ? 7 : 6" class="px-4 py-6 text-center text-[10px] font-bold text-slate-400">Belum ada catatan revisi.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>


    @if(true) {{-- SHOW TABEL INSPEKSI FOR ITEM CHECK --}}
    {{-- SECTION 3: TABEL INSPEKSI (THE CORE) --}}
    <div class="bg-white">

        <div class="bg-slate-900 px-6 py-2.5 flex items-center justify-between border-b border-slate-700 shadow-lg">
            <div class="flex items-center gap-4">
                <div class="w-2 h-5 bg-red-600 rounded-full"></div>
                <h2 class="text-[11px] font-black text-white uppercase tracking-[3px]">TABEL INSPEKSI (ITEM CHECK)</h2>
                
                {{-- DURASI WAKTU --}}
                <div class="ml-4 flex items-center bg-slate-800/80 rounded border border-slate-600 overflow-hidden shadow-inner">
                    <div class="px-2.5 py-1 bg-slate-700/80 border-r border-slate-600 flex items-center gap-1.5" title="Waktu Standar / Target Pengerjaan">
                        <span class="text-[9px] font-black text-slate-300 uppercase tracking-widest">Target:</span>
                        <span class="text-[10px] font-black text-emerald-400" x-text="waktuStandarText"></span>
                    </div>
                    <template x-if="!waktuMulai">
                        <button type="button" @click="startTask()" class="px-3 py-1 bg-emerald-600 hover:bg-emerald-500 text-white text-[9px] font-black uppercase tracking-widest transition-colors flex items-center gap-1.5" title="Mulai pencatatan waktu">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg> MULAI
                        </button>
                    </template>
                    <template x-if="waktuMulai">
                        <div class="px-2.5 py-1 flex items-center gap-1.5" title="Waktu Aktual Berjalan">
                            <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Aktual:</span>
                            <span class="text-[10px] font-black text-blue-400" x-text="waktuAktualText"></span>
                        </div>
                    </template>
                </div>
            </div>
            <div class="flex items-center gap-6">
                {{-- NEW KEEPER CHECK BUTTON --}}
                <button type="button" x-show="isOperator && !['locked'].includes(status) && status !== 'draft'" 
                        @click="addKeeperCheckCol()"
                        class="px-3 py-1.5 bg-rose-600 text-white rounded-[0.5rem] text-[9px] font-black hover:bg-rose-700 transition-all shadow-md shadow-rose-600/30 flex items-center gap-1.5 animate-pulse">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"/></svg>
                    INPUT TEMUAN KEEPER
                </button>

                <div class="flex items-center gap-2">
                    <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Kolom:</span>
                    <span class="px-2 py-0.5 bg-slate-800 text-red-500 rounded text-[10px] font-black" x-text="cols.length"></span>
                </div>
                <template x-if="Object.values(appData).some(v => v === 'ng')">
                    <div class="px-3 py-1 bg-red-600/10 border border-red-600/20 rounded-full flex items-center gap-2 animate-pulse">
                        <span class="w-1.5 h-1.5 bg-red-600 rounded-full"></span>
                        <span class="text-[9px] font-black text-red-500 uppercase">Warning: Terdapat NG</span>
                    </div>
                </template>
            </div>
        </div>

        <div class="li-table-scroll scroll-hint bg-slate-50/30">
            <table class="li-table-main border-collapse">
                <thead>
                    <tr class="bg-slate-100 border-b-2 border-slate-300">
                        <th class="li-top-left-sticky min-w-[140px] text-left px-4 py-2 text-[10px] font-black text-slate-600 uppercase tracking-[2px]">(*) ITEM CHECK</th>
                        <template x-for="c in cols" :key="'th_'+c">
                            <th class="li-head-sticky min-w-[100px] text-center py-2 text-[9px] font-black border-r transition-all group"
                                :class="String(c).startsWith('KEEPER') ? 'bg-rose-50 text-rose-700 border-rose-200 shadow-inner' : 'text-slate-500 border-slate-200'">
                                <div class="relative w-full h-full flex items-center justify-center">
                                    <span x-text="c"></span>
                                    <template x-if="String(c).startsWith('KEEPER') && isOperator && isQCSectionOpen">
                                        <button @click="removeKeeperCol(c)" title="Hapus Kolom Keeper"
                                                class="absolute right-1 opacity-0 group-hover:opacity-100 text-rose-400 hover:text-white bg-transparent hover:bg-rose-500 rounded-full w-4 h-4 flex items-center justify-center transition-all z-10">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
                                        </button>
                                    </template>
                                </div>
                            </th>
                        </template>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    {{-- DIMENSI SECTION --}}
                    <tr class="bg-red-600/5">
                        <td class="li-col-sticky bg-red-50/90 px-4 py-4 text-[9px] font-black text-red-700 uppercase tracking-widest border-r-2 border-red-200">DIMENSI (dl las)</td>
                        <template x-for="c in cols" :key="'dim_head_'+c">
                            <td class="border-r py-1 text-center transition-all"
                                :class="String(c).startsWith('KEEPER') ? 'bg-rose-100 border-rose-200 shadow-inner' : 'bg-red-50/30 border-slate-200'">
                                <!-- Tombol ALL OK telah dihapus -->
                            </td>
                        </template>
                    </tr>
                    <template x-for="(dim, ri) in dimStd" :key="'dim_row_'+ri">
                        <tr class="li-row-hover group bg-white hover:bg-slate-50 transition-colors">
                            <td class="li-col-sticky px-4 py-2 text-[10px] font-bold text-slate-800 bg-inherit">
                                <div class="flex items-center gap-1.5">
                                    <span x-text="(ri+1) + '. '"></span>
                                    <span class="text-[9px] font-black text-red-600 uppercase tracking-tight" x-text="getDimStandardText(ri)"></span>
                                </div>
                            </td>
                            <template x-for="c in cols" :key="'dim_cell_'+ri+'_'+c">
                                <td class="text-center py-1.5 border-r transition-all"
                                    :class="String(c).startsWith('KEEPER') ? 'bg-rose-50/50 border-rose-100' : (isDimOut(ri, c) ? 'bg-red-50 border-slate-200' : (isColUnlocked(c) ? 'bg-inherit border-slate-200' : 'bg-slate-50/30 border-slate-200'))">
                                    <template x-if="c === cols[0]">
                                        <button type="button" @click="openDimInput(ri, c)" :disabled="!isQCSectionOpen || !isOperator || !isColUnlocked(c)"
                                                class="li-touch-input-btn w-16 h-10 mx-auto rounded-xl text-[11px] font-black transition-all flex flex-col items-center justify-center border shadow-none active:scale-95 disabled:opacity-30 disabled:cursor-not-allowed"
                                                :class="{
                                                    'bg-red-500 text-white border-red-600 shadow-md shadow-red-200 disabled:opacity-100': isDimOut(ri, c),
                                                    'bg-emerald-500 text-white border-emerald-600 shadow-md shadow-emerald-200 disabled:opacity-100': dimData[ri+'_'+c] && !isDimOut(ri, c),
                                                    'bg-transparent text-slate-300 border border-dashed border-slate-300 hover:bg-white hover:border-solid hover:border-slate-400 hover:text-slate-600 hover:shadow-sm': !dimData[ri+'_'+c] && isColUnlocked(c)
                                                }">
                                            <span x-text="dimData[ri+'_'+c] || ''"></span>
                                        </button>
                                    </template>
                                    <template x-if="c !== cols[0]">
                                        <div class="w-16 h-10 mx-auto flex items-center justify-center">
                                            <span class="text-slate-300 text-lg font-black">&mdash;</span>
                                        </div>
                                    </template>
                                </td>
                            </template>
                        </tr>
                    </template>

                    {{-- APPEARANCE SECTION --}}
                    <tr class="bg-red-600/5">
                        <td class="li-col-sticky bg-red-50/90 px-4 py-4 text-[9px] font-black text-red-700 uppercase tracking-widest border-r-2 border-red-200">APPEARANCE ( atau ×)</td>
                        <template x-for="c in cols" :key="'app_head_'+c">
                            <td class="border-r py-1 text-center transition-all"
                                :class="String(c).startsWith('KEEPER') ? 'bg-rose-100 border-rose-200 shadow-inner' : 'bg-red-50/30 border-slate-200'">
                                <!-- Tombol ALL OK telah dihapus -->
                            </td>
                        </template>
                    </tr>
                    
                    <template x-for="(app, ri) in appItems" :key="'app_row_'+ri">
                        <tr class="li-row-hover group bg-white hover:bg-slate-50 transition-colors">
                            <td class="li-col-sticky px-4 py-2 text-[10px] font-bold text-slate-800 bg-inherit">
                                {{-- Label: Jumlah Hole di Item Check tampil teks & standard pcs secara dinamis --}}
                                <template x-if="app && app.toUpperCase().includes('JUMLAH HOLE')">
                                    <div class="flex flex-col gap-0.5">
                                        <span x-text="(ri+8) + '. ' + app"></span>
                                        <span class="text-[9px] text-amber-600 font-black" x-text="holeStandard ? '(' + holeStandard + ' pcs)' : '(Belum diset)'"></span>
                                    </div>
                                </template>
                                <template x-if="!app || !app.toUpperCase().includes('JUMLAH HOLE')">
                                    <span x-text="(ri+8) + '. ' + (app || '...')"></span>
                                </template>
                            </td>
                            
                            {{-- CASE A: Type Pallet (Input Teks Penuh) --}}
                            <template x-if="app && app.toLowerCase().includes('type pallet')">
                                <td :colspan="cols.length" class="text-center py-0 border-r border-slate-200">
                                    <input x-model="appData[ri + '_all']" :disabled="!isQCSectionOpen || !isOperator || ['finished', 'approved', 'locked'].includes(status)"
                                           class="w-full h-8 px-4 text-[10px] font-bold text-slate-600 bg-transparent outline-none border-none focus:bg-slate-50 transition-colors placeholder:text-slate-300 disabled:opacity-50"
                                           placeholder="...">
                                </td>
                            </template>

                            {{-- CASE B: Jumlah Hole (Input Angka per Kolom) --}}
                            <template x-if="app && app.toUpperCase().includes('JUMLAH HOLE')">
                                <template x-for="c in cols" :key="'hole_cell_'+ri+'_'+c">
                                    <td class="text-center py-2 border-r transition-all"
                                        :class="String(c).startsWith('KEEPER') ? 'bg-rose-50/50 border-rose-100' : (isColUnlocked(c) ? 'border-slate-200' : 'bg-slate-50/30 border-slate-200 opacity-40')">
                                        <div class="flex flex-col items-center gap-0.5">
                                            <input type="number"
                                                   x-model="appData[`${ri}_${c}`]"
                                                   @change="checkHoleCount(c)"
                                                   :disabled="!isQCSectionOpen || !isOperator || !isColUnlocked(c)"
                                                   class="w-14 sm:w-12 min-h-[44px] text-sm sm:text-[10px] font-black text-center bg-white border-2 border-slate-200 rounded-xl py-2 outline-none focus:border-amber-400 focus:bg-amber-50 transition-all disabled:opacity-30 disabled:cursor-not-allowed"
                                                   :class="appData[`${ri}_${c}`] && parseInt(appData[`${ri}_${c}`]) !== (parseInt(holeStandard)||0) ? 'border-rose-400 text-rose-600 bg-rose-50 disabled:opacity-100' : (appData[`${ri}_${c}`] ? 'border-emerald-300 text-emerald-700 bg-emerald-50 disabled:opacity-100' : '')"
                                                   :placeholder="holeStandard || '?'">
                                            <template x-if="appData[`${ri}_${c}`] && parseInt(appData[`${ri}_${c}`]) !== (parseInt(holeStandard)||0)">
                                                <button type="button" @click="openNgReasonModal(ri, c)" 
                                                        class="flex items-center justify-center gap-1 px-3 py-2 min-h-[40px] rounded-xl text-[10px] font-black transition-all mt-1 shadow-sm active:scale-95 border"
                                                        :class="hasNgNote(ri, c) ? 'bg-amber-50 text-amber-800 border-amber-300 hover:bg-amber-100' : 'bg-blue-50 text-blue-600 border-blue-200 hover:bg-blue-100'"
                                                        :title="isNgModalReadOnly ? 'Lihat catatan NG' : 'Edit catatan NG'">
                                                    <span x-text="isNgModalReadOnly ? 'Lihat Catatan' : 'Catatan NG'"></span>
                                                </button>
                                            </template>
                                            <template x-if="appData[`${ri}_${c}`] && parseInt(appData[`${ri}_${c}`]) === (parseInt(holeStandard)||0)">
                                                <span class="text-[9px] font-black text-emerald-600 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-lg mt-1">✓ OK</span>
                                            </template>
                                        </div>
                                    </td>
                                </template>
                            </template>

                            {{-- CASE C: Baris Penampilan Biasa (OK/NG Buttons) --}}
                            <template x-if="!app || (!app.toLowerCase().includes('type pallet') && !app.toUpperCase().includes('JUMLAH HOLE'))">
                                <template x-for="c in cols" :key="'app_cell_'+ri+'_'+c">
                                    <td class="text-center py-2 border-r transition-all"
                                        :class="String(c).startsWith('KEEPER') ? 'bg-rose-50/50 border-rose-100' : (isColUnlocked(c) ? 'border-slate-200' : 'bg-slate-50/30 border-slate-200 opacity-40')">
                                        
                                        <template x-if="app && app !== ''">
                                            <div class="flex flex-col items-center gap-1.5 py-1">
                                                <div class="flex items-center justify-center gap-2">
                                                    <!-- Tombol OK -->
                                                    <button type="button" @click="setAppVal(ri, c, 'ok')" :disabled="!isOperator || !isColUnlocked(c)"
                                                            class="li-app-btn w-11 h-11 sm:w-10 sm:h-10 rounded-xl flex items-center justify-center text-sm font-black transition-all border shadow-none active:scale-90 disabled:opacity-30 disabled:cursor-not-allowed"
                                                            :class="getAppVal(ri, c) === 'ok' 
                                                                ? 'bg-emerald-500 text-white border-emerald-600 shadow-md shadow-emerald-200 disabled:opacity-100' 
                                                                : 'bg-transparent text-slate-300 border border-dashed border-slate-300 hover:bg-emerald-50 hover:border-solid hover:border-emerald-300 hover:text-emerald-600'">
                                                        ✓
                                                    </button>
                                                    <!-- Tombol NG -->
                                                    <button type="button" @click="setAppVal(ri, c, 'ng')" :disabled="!isOperator || !isColUnlocked(c)"
                                                            class="li-app-btn w-11 h-11 sm:w-10 sm:h-10 rounded-xl flex items-center justify-center text-sm font-black transition-all border shadow-none active:scale-90 disabled:opacity-30 disabled:cursor-not-allowed"
                                                            :class="getAppVal(ri, c) === 'ng' 
                                                                ? 'bg-rose-500 text-white border-rose-600 shadow-md shadow-rose-200 disabled:opacity-100' 
                                                                : 'bg-transparent text-slate-300 border border-dashed border-slate-300 hover:bg-rose-50 hover:border-solid hover:border-rose-300 hover:text-rose-600'">
                                                        <span x-text="(ngDetails[`${ri}_${c}`] && ngDetails[`${ri}_${c}`].qty > 1) ? ngDetails[`${ri}_${c}`].qty + ' ×' : '×'"></span>
                                                    </button>
                                                </div>

                                                <template x-if="getAppVal(ri, c) === 'ng'">
                                                    <button type="button" @click="openNgReasonModal(ri, c)" 
                                                            class="flex items-center justify-center gap-1 px-3 py-2 min-h-[40px] rounded-xl text-[10px] font-black transition-all shadow-sm active:scale-95 border"
                                                            :class="hasNgNote(ri, c) ? 'bg-amber-50 text-amber-800 border-amber-300 hover:bg-amber-100' : 'bg-blue-50 text-blue-600 border-blue-200 hover:bg-blue-100'"
                                                            :title="isNgModalReadOnly ? 'Lihat catatan NG' : 'Edit catatan NG'">
                                                        <span x-text="isNgModalReadOnly ? 'Lihat Catatan' : 'Catatan NG'"></span>
                                                    </button>
                                                </template>
                                            </div>
                                        </template>
                                    </td>
                                </template>
                            </template>
                        </tr>
                    </template>
                    
                    
                                        {{-- Q.G JUDGEMENT --}}
                                        <tr class="bg-slate-100/80 border-t-2 border-slate-300">
                                            <td class="li-col-sticky bg-slate-200/80 px-4 py-3 text-[10px] font-black text-slate-800 uppercase tracking-[2px] border-r-2 border-slate-300 shadow-sm">QG JUDGEMENT</td>
                                            <template x-for="c in cols" :key="'qgj_'+c">
                                                <td class="text-center py-3 border-r border-slate-300 transition-colors"
                                                    :class="getColJudgement(c) === 'OK' ? 'bg-emerald-100 text-emerald-700' : (getColJudgement(c) === 'NG' ? 'bg-rose-100 text-rose-700' : 'bg-slate-50 text-slate-400')">
                                                    <span class="text-[11px] font-black tracking-[1px] uppercase" x-text="getColJudgement(c) || '-'"></span>
                                                </td>
                                            </template>
                                        </tr>

                    {{-- Ringkasan catatan NG (mudah dicek setelah status Selesai) --}}
                    <tr x-show="ngSummaryList.length > 0" x-cloak class="bg-rose-50">
                        <td class="li-col-sticky bg-rose-50 px-4 py-4 text-[9px] font-black text-rose-900 uppercase tracking-widest border-r-2 border-rose-200 align-top">
                            CATATAN NG
                            <div class="text-[8px] font-bold text-rose-700 normal-case mt-1.5" x-show="isNgModalReadOnly">Klik kartu untuk detail</div>
                        </td>
                        <td :colspan="cols.length" class="p-0 border-r border-slate-200">
                            <div class="sticky left-[140px] w-max py-4 px-4">
                                <div class="flex gap-3 max-w-[80vw] overflow-x-auto pb-4 custom-scrollbar">
                                <template x-for="item in ngSummaryList" :key="item.key">
                                    <button type="button" @click="openNgReasonModal(item.row, item.col)"
                                            class="shrink-0 w-[320px] text-left rounded-2xl border border-rose-200 bg-white hover:bg-rose-50 hover:border-rose-300 shadow-sm hover:shadow-md transition-all relative group block">
                                        
                                        <div class="p-3.5 flex flex-col h-full">
                                            <div class="flex justify-between items-start gap-3 mb-2">
                                                <div class="text-[10px] font-black text-slate-800 uppercase tracking-wider leading-tight" x-text="item.appearance + '  ' + item.sampleLabel + (ngDetails[`${item.row}_${item.col}`] && ngDetails[`${item.row}_${item.col}`].qty > 1 ? ' (' + ngDetails[`${item.row}_${item.col}`].qty + ' pcs)' : '')"></div>
                                                <span class="text-[8px] font-black text-rose-600 bg-rose-100 px-2 py-1 rounded-md whitespace-nowrap group-hover:bg-rose-200 transition-colors" x-text="isNgModalReadOnly ? 'DETAIL' : 'EDIT'"></span>
                                            </div>
                                            
                                            <div class="text-[10px] font-bold text-slate-600 mb-3 p-2 bg-slate-50 border border-slate-100 rounded-xl italic flex-1" x-show="item.catatan !== '—'" x-text="'💬 ' + item.catatan"></div>
                                            
                                            <div class="flex flex-wrap gap-1.5 mt-auto">
                                                <span x-show="item.problems !== '—'" class="inline-flex items-center px-2 py-1 rounded-lg bg-red-50 border border-red-100 text-[8px] font-black text-red-700 uppercase tracking-wide">
                                                    <span class="mr-1 opacity-50">Problem:</span> <span x-text="item.problems"></span>
                                                </span>
                                                <span x-show="item.proses !== '—'" class="inline-flex items-center px-2 py-1 rounded-lg bg-indigo-50 border border-indigo-100 text-[8px] font-black text-indigo-700 uppercase tracking-wide">
                                                    <span class="mr-1 opacity-50">Proses:</span> <span x-text="item.proses"></span>
                                                </span>
                                                <span x-show="item.areas !== '—'" class="inline-flex items-center px-2 py-1 rounded-lg bg-blue-50 border border-blue-100 text-[8px] font-black text-blue-700 uppercase tracking-wide">
                                                    <span class="mr-1 opacity-50">Area:</span> <span x-text="item.areas"></span>
                                                </span>
                                            </div>
                                        </div>
                                    </button>
                                </template>
                                </div>
                            </div>
                        </td>
                    </tr>

                    {{-- CATATAN REVISI GL --}}
                    <template x-if="fieldRevisions['item_check']">
                        <tr :class="fieldRevisions['item_check'].resolved ? 'bg-emerald-50 border-t-2 border-emerald-200' : 'bg-orange-50 border-t-2 border-orange-200'">
                            <td class="li-col-sticky px-4 py-3 text-[9px] font-black uppercase tracking-[2px] border-r-2 align-middle"
                                :class="fieldRevisions['item_check'].resolved ? 'bg-emerald-100/50 text-emerald-800 border-emerald-200' : 'bg-orange-100/50 text-orange-800 border-orange-200'">CATATAN REVISI</td>
                            <td :colspan="cols.length" class="p-0 border-r" :class="fieldRevisions['item_check'].resolved ? 'border-emerald-200' : 'border-orange-200'">
                                <div class="sticky left-[140px] w-max py-2 px-4 flex items-center gap-6">
                                    <div class="px-4 py-2 bg-white border rounded-xl text-[11px] font-black shadow-sm flex items-center gap-2"
                                         :class="fieldRevisions['item_check'].resolved ? 'border-emerald-300 text-emerald-700' : 'border-orange-300 text-orange-700 animate-pulse'">
                                        <template x-if="!fieldRevisions['item_check'].resolved">
                                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                        </template>
                                        <template x-if="fieldRevisions['item_check'].resolved">
                                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                                        </template>
                                        <span x-text="fieldRevisions['item_check'].catatan"></span>
                                        <span x-show="fieldRevisions['item_check'].resolved" class="ml-2 text-[9px] bg-emerald-100 px-2 py-0.5 rounded-full">(Telah Diperbaiki)</span>
                                    </div>
                                    <div x-show="isOperator && !fieldRevisions['item_check'].resolved" class="flex items-center gap-2.5 bg-white px-4 py-2 rounded-xl border-2 border-orange-200 shadow-sm transition-all hover:bg-orange-50 cursor-pointer" @click="itemCheckRevisionChecked = !itemCheckRevisionChecked">
                                        <input type="checkbox" id="revConfirmStd" x-model="itemCheckRevisionChecked" class="w-4 h-4 text-orange-600 rounded border-orange-300 focus:ring-orange-500 cursor-pointer">
                                        <label for="revConfirmStd" class="text-[10px] font-black text-orange-800 uppercase tracking-widest cursor-pointer select-none">SAYA SUDAH MEMPERBAIKI REVISI INI</label>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </template>

                    {{-- PARAF OPERATOR (Prepared) --}}
                    <tr class="bg-slate-100/50 border-t border-slate-200">
                        <td class="li-col-sticky bg-slate-50 px-4 py-2 text-[9px] font-black text-slate-500 uppercase tracking-widest border-r-2 border-slate-200">PARAF OPERATOR (Prepared)</td>
                        <td :colspan="cols.length" class="p-0 border-r border-slate-200">
                            <div class="sticky left-[140px] w-max py-2 px-4 flex items-center justify-start gap-6">
                                <template x-if="!bundlePrepSig">
                                    <div class="flex items-center gap-3">
                                        <div class="flex flex-col items-start gap-0.5">
                                            <span class="text-[7px] font-black text-slate-400 uppercase tracking-widest">Operator QC</span>
                                            <div class="px-4 py-1.5 bg-white border border-slate-200 rounded-lg text-[10px] font-black text-slate-800 min-w-[180px] text-center" 
                                                 x-text="operatorUsers.find(u => String(u.id) === String(assignedOperatorId))?.nama || '-'"></div>
                                        </div>
                                        <button type="button" @click="autoSignOperator('operator_ttd')"
                                                :disabled="!assignedOperatorId"
                                                :class="!assignedOperatorId ? 'opacity-50 cursor-not-allowed border-slate-200 text-slate-300' : 'border-slate-200 text-slate-500 hover:border-red-500 hover:text-red-600 shadow-sm'"
                                                class="mt-3 ml-2 px-3 py-1 bg-white border rounded-lg text-[9px] font-black transition-all"> TTD</button>
                                    </div>
                                </template>
                                <template x-if="bundlePrepSig">
                                    <div class="flex items-center gap-4 bg-red-600 px-4 py-1.5 rounded-xl shadow-md transform transition-transform hover:scale-105">
                                        <div class="text-left border-r border-red-500 pr-4">
                                            <div class="text-[7px] text-red-200 font-black uppercase tracking-widest">Prepared By:</div>
                                            <div class="text-[10px] font-black text-white" x-text="operatorUsers.find(u => String(u.id) === String(assignedOperatorId))?.nama || '-'"></div>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <div class="bg-white px-2 py-1 rounded-lg shadow-inner flex items-center justify-center">
                                                <img :src="bundlePrepSig" class="h-6 object-contain">
                                            </div>
                                            <button type="button" x-show="isOperator && !['locked', 'finished', 'approved'].includes(status)" @click="initiateUpdateMaster('operator_ttd', assignedOperatorId)" class="w-4 h-4 bg-white/20 hover:bg-white/40 rounded-full text-white flex items-center justify-center transition-all" title="Perbarui Master TTD">
                                                <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                            </button>
                                            <button type="button" x-show="isOperator && !['locked', 'finished', 'approved'].includes(status)" @click="bundlePrepSig=null" class="w-4 h-4 bg-white/20 hover:bg-white/40 rounded-full text-white text-[8px] flex items-center justify-center transition-all" title="Hapus dari dokumen ini">×</button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </td>
                    </tr>

                    {{-- PARAF ROWS (Checked / Approved) --}}
                    <tr class="bg-slate-100/50">
                        <td class="li-col-sticky bg-slate-50 px-4 py-2 text-[9px] font-black text-slate-500 uppercase tracking-widest border-r-2 border-slate-200">PARAF GL (Checked)</td>
                        <td :colspan="cols.length" class="p-0 border-r border-slate-200">
                            <div class="sticky left-[140px] w-max py-2 px-4 flex items-center justify-start gap-6">
                                <template x-if="!bundleGlSig">
                                    <div class="flex items-center gap-3 flex-wrap">
                                        <div class="flex flex-col items-start gap-0.5">
                                            <span class="text-[7px] font-black text-slate-400 uppercase tracking-widest">Select GL</span>
                                            <template x-if="isOperator">
                                                <select x-model="assignedGlId" class="px-3 py-1.5 text-[9px] font-black bg-white border border-slate-200 rounded-lg outline-none focus:border-red-500 shadow-sm transition-all">
                                                    <option value="">— Pilih GL —</option>
                                                    <template x-for="u in glUsers" :key="u.id">
                                                        <option :value="u.id" x-text="u.nama"></option>
                                                    </template>
                                                </select>
                                            </template>
                                            <template x-if="!isOperator">
                                                <div class="px-4 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-[10px] font-black text-slate-800 min-w-[150px]" 
                                                     x-text="glUsers.find(u => String(u.id) === String(assignedGlId))?.nama || '-'"></div>
                                            </template>
                                        </div>
                                        {{-- Tombol TTD Fisik (untuk GL yang hadir di jalur) --}}
                                        <button type="button" @click="openPinPad('glParaf', assignedGlId)"
                                                :disabled="!assignedGlId"
                                                :class="!assignedGlId ? 'opacity-50 cursor-not-allowed border-slate-200 text-slate-300' : 'border-slate-200 text-slate-500 hover:border-red-500 hover:text-red-600 shadow-sm'"
                                                class="mt-3 px-4 py-1.5 bg-white border rounded-lg text-[9px] font-black transition-all"> TTD</button>
                                        
                                        {{-- Tombol Tolak Fisik (Jika GL minta revisi langsung di tempat) --}}
                                        <button type="button" x-show="status === 'waiting_qc_approval'" @click="promptItemCheckRevision()"
                                                class="mt-3 px-4 py-1.5 bg-orange-50 border border-orange-200 text-orange-600 rounded-lg text-[9px] font-black hover:bg-orange-100 transition-all shadow-sm flex items-center gap-1.5">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                            Tolak
                                        </button>

                                        {{-- Tombol Interkom GL --}}
                                        <button type="button" x-show="isOperator" @click="startIntercomCall('gl')"
                                                title="Panggil GL via Interkom"
                                                class="mt-3 px-3 py-1.5 bg-red-600 hover:bg-red-750 text-white rounded-lg text-[9px] font-black transition-all shadow-sm shadow-red-600/20 flex items-center gap-1.5">
                                            📞 Interkom GL
                                        </button>


                                    </div>
                                </template>
                                <template x-if="bundleGlSig">
                                    <div class="flex items-center gap-4 bg-red-600 px-4 py-1.5 rounded-xl shadow-md transform transition-transform hover:scale-105">
                                        <div class="text-left border-r border-red-500 pr-4">
                                            <div class="text-[7px] text-red-200 font-black uppercase tracking-widest">Checked By:</div>
                                            <div class="text-[10px] font-black text-white" x-text="glUsers.find(u => String(u.id) === String(assignedGlId))?.nama || ' '"></div>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <div class="bg-white px-2 py-1 rounded-lg shadow-inner flex items-center justify-center">
                                                <img :src="bundleGlSig" class="h-6 object-contain">
                                            </div>
                                            <button type="button" x-show="!['locked', 'finished', 'approved'].includes(status)" @click="initiateUpdateMaster('glParaf', assignedGlId)" class="w-4 h-4 bg-white/20 hover:bg-white/40 rounded-full text-white flex items-center justify-center transition-all" title="Perbarui Master TTD">
                                                <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                            </button>
                                            <button type="button" x-show="!['locked', 'finished', 'approved'].includes(status)" @click="bundleGlSig=null" class="w-4 h-4 bg-white/20 hover:bg-white/40 rounded-full text-white text-[8px] flex items-center justify-center transition-all" title="Hapus dari dokumen ini">×</button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </td>
                    </tr>
                    <tr class="bg-slate-100/50">
                        <td class="li-col-sticky bg-slate-50 px-4 py-2 text-[9px] font-black text-slate-500 uppercase tracking-widest border-r-2 border-slate-200">PARAF FOREMAN (Approved)</td>
                        <td :colspan="cols.length" class="p-0 border-r border-slate-200">
                            <div class="sticky left-[140px] w-max py-2 px-4 flex items-center justify-start gap-6">
                                <template x-if="!bundleFmSig">
                                    <div class="flex items-center gap-3 flex-wrap">
                                        <div class="flex flex-col items-start gap-0.5">
                                            <span class="text-[7px] font-black text-slate-400 uppercase tracking-widest">Select Foreman</span>
                                            <template x-if="isOperator">
                                                <select x-model="assignedForemanId" class="px-3 py-1.5 text-[9px] font-black bg-white border border-slate-200 rounded-lg outline-none focus:border-red-500 shadow-sm transition-all">
                                                    <option value="">— Pilih Foreman —</option>
                                                    <template x-for="u in fmUsers" :key="u.id">
                                                        <option :value="u.id" x-text="u.nama"></option>
                                                    </template>
                                                </select>
                                            </template>
                                            <template x-if="!isOperator">
                                                <div class="px-4 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-[10px] font-black text-slate-800 min-w-[150px]" 
                                                     x-text="fmUsers.find(u => String(u.id) === String(assignedForemanId))?.nama || '-'"></div>
                                            </template>
                                        </div>
                                        {{-- Tombol TTD Fisik (jika Foreman turun ke jalur untuk kondisi NG Kritis) --}}
                                        <button type="button" @click="openPinPad('fmParaf', assignedForemanId)"
                                                :disabled="!assignedForemanId"
                                                :class="!assignedForemanId ? 'opacity-50 cursor-not-allowed border-slate-200 text-slate-300' : 'border-slate-200 text-slate-500 hover:border-red-500 hover:text-red-600 shadow-sm'"
                                                class="mt-3 px-4 py-1.5 bg-white border rounded-lg text-[9px] font-black transition-all"> TTD</button>
                                        {{-- Tombol Interkom Foreman --}}
                                        <button type="button" x-show="isOperator" @click="startIntercomCall('foreman')"
                                                title="Panggil Foreman via Interkom"
                                                class="mt-3 px-3 py-1.5 bg-red-600 hover:bg-red-750 text-white rounded-lg text-[9px] font-black transition-all shadow-sm shadow-red-600/20 flex items-center gap-1.5 animate-pulse">
                                            📞 Interkom Foreman
                                        </button>


                                    </div>
                                </template>
                                <template x-if="bundleFmSig">
                                    <div class="flex items-center gap-4 bg-red-600 px-4 py-1.5 rounded-xl shadow-md transform transition-transform hover:scale-105">
                                        <div class="text-left border-r border-red-500 pr-4">
                                            <div class="text-[7px] text-red-200 font-black uppercase tracking-widest">Approved By:</div>
                                            <div class="text-[10px] font-black text-white" x-text="fmUsers.find(u => String(u.id) === String(assignedForemanId))?.nama || '-'"></div>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <div class="bg-white px-2 py-1 rounded-lg shadow-inner flex items-center justify-center">
                                                <img :src="bundleFmSig" class="h-6 object-contain">
                                            </div>
                                            <button type="button" x-show="!['locked', 'finished', 'approved'].includes(status)" @click="initiateUpdateMaster('fmParaf', assignedForemanId)" class="w-4 h-4 bg-white/20 hover:bg-white/40 rounded-full text-white flex items-center justify-center transition-all" title="Perbarui Master TTD">
                                                <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                            </button>
                                            <button type="button" x-show="!['locked', 'finished', 'approved'].includes(status)" @click="bundleFmSig=null" class="w-4 h-4 bg-white/20 hover:bg-white/40 rounded-full text-white text-[8px] flex items-center justify-center transition-all" title="Hapus dari dokumen ini">×</button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>


    {{-- FOOTER / STATS SECTION HAS BEEN MOVED TO THE BOTTOM --}}

  </div>{{-- END TAB STANDARD --}}

  {{-- PP TAB: BUNDLE CHECK PP --}}
  <div x-show="activeTab==='bundle'" x-cloak class="p-8 bg-slate-50/50 min-h-screen">
    
    <div class="max-w-[1200px] mx-auto">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h3 class="text-lg font-black text-slate-800 uppercase tracking-[2px]">BAGIAN 2: ITEM CHECK (BUNDLE)</h3>
                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-1">Inspection records for each production bundle</p>
            </div>
            <div class="flex items-center gap-3">
                <span class="px-4 py-1.5 bg-white border border-slate-200 rounded-full text-[10px] font-black text-slate-500 shadow-sm" x-text="bundleChecks.length + ' Bundles Loaded'"></span>
            </div>
        </div>

        <template x-for="(chunk, ci) in bundleChunks" :key="'chunk_'+ci">
          <div class="mb-12 bg-white border border-slate-200 overflow-hidden shadow-xl shadow-slate-200/50 transition-all hover:shadow-2xl hover:shadow-slate-200/60">
            {{-- Chunk Header --}}
            <div class="bg-slate-900 px-8 py-4 flex justify-between items-center border-b border-slate-800">
              <div class="flex items-center gap-3">
                <div class="w-2 h-5 bg-red-600 rounded-full"></div>
                <span class="text-[11px] font-black text-white uppercase tracking-[3px]" x-text="'BUNDLE GROUP ' + (ci+1)"></span>
              </div>
              <div class="flex items-center gap-2">
                <span class="text-[9px] font-black text-slate-500 uppercase">Status:</span>
                <span class="text-[9px] font-black text-emerald-400 uppercase tracking-widest">Active</span>
              </div>
            </div>
    
            <div class="overflow-x-auto overflow-y-hidden custom-scrollbar pb-2">
              <table class="w-max min-w-full border-collapse">
                <thead>
                  <tr class="bg-slate-50">
                    <th class="border-b border-r border-slate-200 px-3 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest sticky left-0 bg-slate-50 z-30 min-w-[120px] max-w-[120px] text-left">ITEM BUNDLE</th>
                    <template x-for="(bundle, bi) in chunk" :key="'bh_'+bundle.id">
                      <th class="border-b border-r border-slate-200 px-4 py-5 text-center min-w-[150px] bg-slate-50/50" colspan="3">
                        <div class="flex flex-col gap-2">
                          <input x-model="bundle.bundleName" :placeholder="'BUNDLE KE-' + ((ci * 5) + bi + 1)" 
                                 :disabled="!isQCSectionOpen || !isOperator"
                                 class="w-full text-center text-[12px] font-black uppercase bg-white border border-slate-300 py-1.5 focus:border-red-500 focus:ring-2 focus:ring-red-500/20 rounded-xl transition-all placeholder:text-slate-300 disabled:opacity-50 disabled:bg-transparent">
                          <div class="flex items-center justify-center gap-2 w-full mt-1.5">
                             <span class="text-[11px] font-black text-slate-500">COIL:</span>
                             <input x-model="bundle.coilNo" placeholder="NO. COIL" 
                                    :disabled="!isQCSectionOpen || !isOperator"
                                    class="flex-1 text-center text-[12px] font-black text-slate-700 bg-white border-2 border-slate-200 py-2 focus:border-red-500 focus:ring-2 focus:ring-red-500/20 rounded-xl transition-all outline-none disabled:opacity-50 disabled:bg-transparent placeholder:text-slate-300 shadow-sm hover:border-slate-300">
                          </div>
                        </div>
                      </th>
                    </template>
                  </tr>
                  <tr class="bg-white">
                    <th class="border-b border-r border-slate-200 px-6 py-2 text-[9px] font-black text-slate-300 sticky left-0 bg-white z-30 text-left">SAMPLE NO.</th>
                    <template x-for="bundle in chunk" :key="'sh_'+bundle.id">
                      <template x-for="s in [1,2,3]" :key="'sno_'+bundle.id+'_'+s">
                        <th class="border-b border-r border-slate-100 px-1 py-2 text-[9px] font-black text-slate-400 text-center min-w-[100px] bg-slate-50/30" x-text="'SAMPLE ' + s"></th>
                      </template>
                    </template>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                  @foreach([
                    ['A', 'Tidak Pecok, Tidak Benjol, Tidak Gelombang'],
                    ['B', 'Tidak Baret, Tidak Burry'],
                    ['C', 'Tidak Keriput, Tiak Pecah, Tidak Neck'],
                    ['D', 'Tidak Karat'],
                    ['E', 'Tidak Penyok, Flange Tidak Miring'],
                    ['F', 'Jumlah hole (pcs)'],
                    ['G', 'ID Mark']
                  ] as $idx => $row)
                  <tr class="hover:bg-slate-50/80 transition-colors group">
                    <td class="border-r border-slate-200 px-3 py-3 text-[10px] font-bold text-slate-700 sticky left-0 bg-white group-hover:bg-slate-50 z-20 transition-colors">
                        <span class="text-slate-300 font-black mr-2">{{ $row[0] }}.</span> {{ $row[1] }}
                    </td>
                    <template x-for="bundle in chunk" :key="'br_'+bundle.id+'_{{ $idx }}'">
                      <template x-for="s in [1,2,3]" :key="'bc_'+bundle.id+'_{{ $idx }}_'+s">
                        <td class="border-r border-slate-100 p-1 text-center transition-colors">
                          @if($idx === 5)
                            {{-- F. Jumlah hole (pcs) --}}
                            <input x-model="bundle.samples[s]['{{ $idx }}']" type="number" placeholder="-"
                                   :disabled="!isQCSectionOpen || !isOperator"
                                   class="w-12 h-10 mx-auto block text-center text-[12px] font-black bg-white border-2 border-slate-200 rounded-xl focus:border-amber-400 focus:bg-amber-50 outline-none transition-all disabled:opacity-50 disabled:bg-slate-50 disabled:cursor-not-allowed placeholder:text-slate-300 shadow-sm hover:border-slate-300">
                          @else
                            {{-- Status Button: OK and NG (Side by side) --}}
                            <div class="flex gap-2 justify-center items-center w-full h-full relative z-0">
                                <button type="button" @click="bundle.samples[s]['{{ $idx }}'] = (bundle.samples[s]['{{ $idx }}'] === 'ok' ? '' : 'ok')" 
                                        :disabled="!isQCSectionOpen || !isOperator"
                                        class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl flex items-center justify-center text-sm font-black transition-all border shadow-none active:scale-90 disabled:opacity-30 disabled:cursor-not-allowed"
                                        :class="bundle.samples[s]['{{ $idx }}'] === 'ok' ? 'bg-emerald-500 text-white border-emerald-600 shadow-md shadow-emerald-200' : 'bg-transparent text-slate-300 border-dashed border-slate-300 hover:bg-emerald-50 hover:border-solid hover:border-emerald-300 hover:text-emerald-600'">
                                    ✓
                                </button>
                                <button type="button" @click="bundle.samples[s]['{{ $idx }}'] = (bundle.samples[s]['{{ $idx }}'] === 'ng' ? '' : 'ng')" 
                                        :disabled="!isQCSectionOpen || !isOperator"
                                        class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl flex items-center justify-center text-sm font-black transition-all border shadow-none active:scale-90 disabled:opacity-30 disabled:cursor-not-allowed"
                                        :class="bundle.samples[s]['{{ $idx }}'] === 'ng' ? 'bg-rose-500 text-white border-rose-600 shadow-md shadow-rose-200' : 'bg-transparent text-slate-300 border-dashed border-slate-300 hover:bg-rose-50 hover:border-solid hover:border-rose-300 hover:text-rose-600'">
                                    ×
                                </button>
                            </div>
                          @endif
                        </td>
                      </template>
                    </template>
                  </tr>
                  @endforeach
    

                  {{-- CATATAN REVISI GL --}}
                  <template x-if="fieldRevisions['item_check']">
                      <tr :class="fieldRevisions['item_check'].resolved ? 'bg-emerald-50 border-y-2 border-emerald-200' : 'bg-orange-50 border-y-2 border-orange-200'">
                          <td class="border-r px-3 py-3 text-[10px] font-black sticky left-0 z-20 uppercase tracking-widest align-middle"
                              :class="fieldRevisions['item_check'].resolved ? 'border-emerald-200 text-emerald-800 bg-emerald-100/50' : 'border-orange-200 text-orange-800 bg-orange-100/50'">CATATAN REVISI</td>
                          <td :colspan="chunk.length * 3" class="p-0 border-r" :class="fieldRevisions['item_check'].resolved ? 'border-emerald-200' : 'border-orange-200'">
                              <div class="sticky left-[120px] w-max py-2 px-6 flex items-center gap-6">
                                  <div class="px-4 py-2 bg-white border rounded-xl text-[11px] font-black shadow-sm flex items-center gap-2"
                                       :class="fieldRevisions['item_check'].resolved ? 'border-emerald-300 text-emerald-700' : 'border-orange-300 text-orange-700 animate-pulse'">
                                      <template x-if="!fieldRevisions['item_check'].resolved">
                                          <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                      </template>
                                      <template x-if="fieldRevisions['item_check'].resolved">
                                          <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                                      </template>
                                      <span x-text="fieldRevisions['item_check'].catatan"></span>
                                      <span x-show="fieldRevisions['item_check'].resolved" class="ml-2 text-[9px] bg-emerald-100 px-2 py-0.5 rounded-full">(Telah Diperbaiki)</span>
                                  </div>
                                  <div x-show="isOperator && !fieldRevisions['item_check'].resolved" class="flex items-center gap-2.5 bg-white px-4 py-2 rounded-xl border-2 border-orange-200 shadow-sm transition-all hover:bg-orange-50 cursor-pointer" @click="itemCheckRevisionChecked = !itemCheckRevisionChecked">
                                      <input type="checkbox" id="revConfirmBnd" x-model="itemCheckRevisionChecked" class="w-4 h-4 text-orange-600 rounded border-orange-300 focus:ring-orange-500 cursor-pointer">
                                      <label for="revConfirmBnd" class="text-[10px] font-black text-orange-800 uppercase tracking-widest cursor-pointer select-none">SAYA SUDAH MEMPERBAIKI REVISI INI</label>
                                  </div>
                              </div>
                          </td>
                      </tr>
                  </template>

                  {{-- PARAF OPERATOR (Prepared) --}}
                  <tr class="bg-white">
                    <td class="border-r border-slate-200 px-3 py-4 text-[10px] font-black text-slate-800 sticky left-0 bg-white z-20 uppercase tracking-widest">PARAF OPERATOR (Prepared)</td>
                    <td :colspan="chunk.length * 3" class="p-0 border-r border-slate-200">
                        <div class="sticky left-[120px] w-max py-3 px-6 flex items-center justify-start gap-4">
                            <div class="flex flex-col items-start gap-0.5">
                                <span class="text-[7px] font-black text-slate-400 uppercase tracking-widest">Operator QC</span>
                                <div class="px-4 py-2 bg-slate-50 border-2 border-slate-100 rounded-xl text-[10px] font-black text-slate-800 min-w-[200px] text-center" 
                                     x-text="operatorUsers.find(u => String(u.id) === String(assignedOperatorId))?.nama || '-'"></div>
                            </div>
                            <button @click="autoSignOperator('operator_ttd')" 
                                    class="mt-3 px-4 py-2 border border-slate-300 rounded-xl text-[9px] font-bold text-slate-500 hover:bg-slate-50 transition-all flex items-center gap-2 shadow-sm disabled:opacity-50 disabled:bg-slate-50 disabled:cursor-not-allowed">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                
 TTD
                            </button>
                            
                            <template x-if="bundlePrepSig">
                                <img :src="bundlePrepSig" class="h-10 object-contain ml-4 border-l pl-4 border-slate-100 transition-all hover:scale-110">
                            </template>
                        </div>
                    </td>
                  </tr>

                  {{-- PARAF GL (Checked) --}}
                  <tr class="bg-white">
                    <td class="border-r border-slate-200 px-3 py-4 text-[10px] font-black text-slate-800 sticky left-0 bg-white z-20 uppercase tracking-widest">PARAF GL (Checked)</td>
                    <td :colspan="chunk.length * 3" class="p-0 border-r border-slate-200">
                        <div class="sticky left-[120px] w-max py-3 px-6 flex items-center justify-start gap-4">
                            <div class="flex flex-col items-start gap-0.5">
                                <span class="text-[7px] font-black text-slate-400 uppercase tracking-widest">Select GL</span>
                                <template x-if="isOperator">
                                    <select x-model="assignedGlId"
                                            @change="const selected = glUsers.find(u => String(u.id) === String(assignedGlId)); bundleGlName = selected ? selected.nama : ''"
                                            class="min-w-[200px] px-4 py-2 bg-slate-50 border-2 border-slate-100 rounded-xl text-[10px] font-black outline-none focus:border-red-500 transition-all shadow-sm">
                                        <option value="">— PILIH GL —</option>
                                        <template x-for="u in glUsers" :key="'glf_'+u.id">
                                            <option :value="u.id" x-text="u.nama"></option>
                                        </template>
                                    </select>
                                </template>
                                <template x-if="!isOperator">
                                    <div class="px-4 py-2 bg-slate-50 border-2 border-slate-100 rounded-xl text-[10px] font-black text-slate-800 min-w-[200px] text-center" 
                                         x-text="glUsers.find(u => String(u.id) === String(assignedGlId))?.nama || bundleGlName || '-'"></div>
                                </template>
                            </div>
                            <button @click="openPinPad('gl_global', assignedGlId)" 
                                    :disabled="!assignedGlId"
                                    class="mt-3 px-4 py-2 border border-slate-300 rounded-xl text-[9px] font-bold text-slate-500 hover:bg-slate-50 transition-all flex items-center gap-2 shadow-sm disabled:opacity-50 disabled:bg-slate-50 disabled:cursor-not-allowed">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                
 TTD
                            </button>
                            
                            {{-- Tombol Tolak Fisik / Mandiri --}}
                            <button type="button" x-show="status === 'waiting_qc_approval' || (isGroupLeader && String(userId) === String(assignedGlId))" @click="promptItemCheckRevision()"
                                    class="mt-3 px-4 py-2 bg-orange-50 border border-orange-200 text-orange-600 rounded-xl text-[9px] font-black hover:bg-orange-100 transition-all shadow-sm flex items-center gap-1.5 h-full">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                Tolak / Revisi
                            </button>
                            
                            <template x-if="bundleGlSig">
                                <img :src="bundleGlSig" class="h-10 object-contain ml-4 border-l pl-4 border-slate-100 transition-all hover:scale-110">
                            </template>
                        </div>
                    </td>
                  </tr>

                  {{-- PARAF FOREMAN (Approved) --}}
                  <tr class="bg-white">
                    <td class="border-r border-slate-200 px-3 py-4 text-[10px] font-black text-slate-800 sticky left-0 bg-white z-20 uppercase tracking-widest">PARAF FOREMAN (Approved)</td>
                    <td :colspan="chunk.length * 3" class="p-0 border-t border-slate-100 border-r border-slate-200">
                        <div class="sticky left-[120px] w-max py-3 px-6 flex items-center justify-start gap-4">
                            <div class="flex flex-col items-start gap-0.5">
                                <span class="text-[7px] font-black text-slate-400 uppercase tracking-widest">Select Foreman</span>
                                <template x-if="isOperator">
                                    <select x-model="assignedForemanId"
                                            @change="const selected = fmUsers.find(u => String(u.id) === String(assignedForemanId)); bundleFmName = selected ? selected.nama : ''"
                                            class="min-w-[200px] px-4 py-2 bg-slate-50 border-2 border-slate-100 rounded-xl text-[10px] font-black outline-none focus:border-red-500 transition-all shadow-sm">
                                        <option value="">— PILIH FOREMAN —</option>
                                        <template x-for="u in fmUsers" :key="'fmf_'+u.id">
                                            <option :value="u.id" x-text="u.nama"></option>
                                        </template>
                                    </select>
                                </template>
                                <template x-if="!isOperator">
                                    <div class="px-4 py-2 bg-slate-50 border-2 border-slate-100 rounded-xl text-[10px] font-black text-slate-800 min-w-[200px] text-center" 
                                         x-text="fmUsers.find(u => String(u.id) === String(assignedForemanId))?.nama || bundleFmName || '-'"></div>
                                </template>
                            </div>
                            <button @click="openPinPad('fm_global', assignedForemanId)" 
                                    :disabled="!assignedForemanId"
                                    class="mt-3 px-4 py-2 border border-slate-300 rounded-xl text-[9px] font-bold text-slate-500 hover:bg-slate-50 transition-all flex items-center gap-2 shadow-sm disabled:opacity-50 disabled:bg-slate-50 disabled:cursor-not-allowed">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                
 TTD
                            </button>
                            <template x-if="bundleFmSig">
                                <img :src="bundleFmSig" class="h-10 object-contain ml-4 border-l pl-4 border-slate-100 transition-all hover:scale-110">
                            </template>
                        </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </template>
    
        {{-- Bundle Actions --}}
        <div class="flex justify-center gap-6 mb-16">
            <button @click="addMoreBundles()" class="px-8 py-4 bg-emerald-600 text-white rounded-[1.5rem] text-[11px] font-black hover:bg-emerald-700 hover:scale-105 transition-all shadow-xl shadow-emerald-600/20 flex items-center gap-3">
                <div class="w-6 h-6 bg-white/20 rounded-lg flex items-center justify-center font-black text-lg">+</div>
                TAMBAH 5 BUNDLE BARU
            </button>
            <button @click="removeLast5Bundles()" x-show="bundleChecks.length > 5" class="px-8 py-4 bg-white border-2 border-rose-500 text-rose-600 rounded-[1.5rem] text-[11px] font-black hover:bg-rose-50 hover:scale-105 transition-all shadow-xl shadow-rose-500/10 flex items-center gap-3">
                <div class="w-6 h-6 bg-rose-500/10 rounded-lg flex items-center justify-center font-black text-lg"></div>
                HAPUS 5 BUNDLE TERAKHIR
            </button>
        </div>
    
        {{-- Tindakan Section --}}
        <div class="bg-white border border-slate-200 rounded-[2.5rem] p-10 shadow-2xl shadow-slate-200/50 relative overflow-hidden group transition-all hover:shadow-slate-200/70">
            <!-- <div class="absolute top-0 right-0 p-10 opacity-[0.03] transition-opacity group-hover:opacity-[0.06]">
                <svg class="w-40 h-40 text-slate-900" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>
            </div> -->
            <div class="flex items-center gap-4 mb-6">
                <div class="w-1.5 h-6 bg-red-600 rounded-full"></div>
                <label class="block text-[11px] font-black text-slate-800 uppercase tracking-[2px]">Tindakan Perbaikan Apabila NG</label>
            </div>
            <textarea x-model="bundleTindakan" rows="5" placeholder="Tuliskan langkah-langkah perbaikan secara detail di sini..."
                      class="w-full px-8 py-6 bg-slate-50 border-2 border-slate-100 rounded-[2rem] text-[13px] font-bold text-slate-700 focus:bg-white focus:ring-[12px] focus:ring-red-500/5 focus:border-red-500 outline-none transition-all resize-none shadow-inner leading-relaxed placeholder:text-slate-300"></textarea>
            <div class="mt-4 flex justify-end">
                <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest italic">* Wajib diisi jika terdapat status NG pada bundle</p>
            </div>
        </div>
    </div>

        {{-- BUNDLE HINT (di paling bawah tab Bundle) --}}
        <div class="mt-8 mx-2 mb-8 bg-slate-50 border border-slate-200 rounded-2xl p-5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 bg-slate-200 rounded-xl flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-[10px] font-black text-slate-700 uppercase tracking-widest">Catatan: Bundle Item Check</p>
                    <p class="text-[9px] text-slate-500 font-bold mt-0.5">Pengisian bundle bersifat <strong>Opsional</strong>. Isi hanya jika ada inspeksi per-bundle yang diperlukan.</p>
                </div>
            </div>
            <button type="button" @click="activeTab = 'main'; window.scrollTo({top: 0, behavior: 'smooth'})"
                    class="w-full sm:w-auto px-4 py-2 bg-white border border-red-200 text-red-600 rounded-xl text-[9px] font-black uppercase tracking-widest hover:bg-red-600 hover:text-white transition-all shadow-sm shrink-0 text-center">
                 Kembali ke LI
            </button>
        </div>
    </div>


    {{-- FOOTER INFO SECTION --}}
    <div class="p-8 bg-slate-50 border-t border-slate-200 pb-32 lg:pb-40" x-show="activeTab === 'main'" x-cloak>
        
    
        {{-- Row 1: Production Stats (Auto-pulled) & Shift --}}
        <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-4 mb-8">
            
            {{-- 1. TANGGAL PRODUKSI --}}
            <div class="bg-slate-100/80 border border-slate-200 rounded-2xl p-4 shadow-inner relative overflow-hidden group">
                <div class="absolute top-0 right-0 p-2 opacity-10 transition-transform group-hover:scale-110">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
                <label class="block text-[9px] font-black text-slate-500 uppercase tracking-[2px] mb-2">TGL PRODUKSI</label>
                <div class="text-[13px] font-black text-slate-800" x-text="tanggal || '-'"></div>
            </div>

            {{-- 2. TOTAL ACTUAL (PRODUKSI) --}}
            <div class="bg-slate-100/80 border border-slate-200 rounded-2xl p-4 shadow-inner relative overflow-hidden group">
                <div class="absolute top-0 right-0 p-2 opacity-10 transition-transform group-hover:scale-110">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                </div>
                <label class="block text-[9px] font-black text-slate-500 uppercase tracking-[2px] mb-2">TOTAL ACTUAL</label>
                <div class="flex items-baseline gap-1">
                    <template x-if="isOperator && !['finished','locked'].includes(status)">
                        <input x-model.number="totalPcs" type="number" class="w-16 text-[14px] font-black text-slate-800 bg-white/50 border border-slate-300 rounded-md focus:border-red-500 focus:ring-1 focus:ring-red-500 px-2 py-0 text-center transition-all shadow-sm" placeholder="0">
                    </template>
                    <template x-if="!isOperator || ['finished','locked'].includes(status)">
                        <div class="text-[14px] font-black text-slate-800 px-2" x-text="totalPcs || '0'"></div>
                    </template>
                    <span class="text-[9px] text-slate-500 font-bold uppercase">PCS</span>
                </div>
            </div>

            {{-- 3. TOTAL OK --}}
            <div class="bg-emerald-50/50 border border-emerald-100 rounded-2xl p-4 shadow-inner relative overflow-hidden group">
                <div class="absolute top-0 right-0 p-2 opacity-10 transition-transform group-hover:scale-110">
                    <svg class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </div>
                <label class="block text-[9px] font-black text-emerald-600/70 uppercase tracking-[2px] mb-2">TOTAL OK</label>
                <div class="flex items-baseline gap-1">
                    <div class="text-[14px] font-black text-emerald-700" x-text="computedOkCount() || '0'"></div>
                    <span class="text-[9px] text-emerald-600 font-bold uppercase">PCS</span>
                </div>
            </div>

            {{-- 4. TOTAL NG (Reject + Repair) --}}
            <div class="bg-rose-50/50 border border-rose-100 rounded-2xl p-4 shadow-inner relative overflow-hidden group">
                <div class="absolute top-0 right-0 p-2 opacity-10 transition-transform group-hover:scale-110">
                    <svg class="w-8 h-8 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </div>
                <label class="block text-[9px] font-black text-rose-600/70 uppercase tracking-[2px] mb-2">TOTAL NG</label>
                <div class="flex items-baseline gap-1">
                    <div class="text-[14px] font-black text-rose-700" x-text="computedNgCount() || '0'"></div>
                    <span class="text-[9px] text-rose-600 font-bold uppercase">PCS</span>
                </div>
            </div>

            {{-- 5. SHIFT: hanya QC (Operator) yang bisa isi --}}
            <div class="bg-white border-2 border-slate-200 rounded-2xl p-4 shadow-sm focus-within:border-red-400 focus-within:ring-2 focus-within:ring-red-100 transition-all z-10 relative">
                <label class="block text-[9px] font-black text-slate-500 uppercase tracking-[2px] mb-2">SHIFT</label>
                <template x-if="isOperator && !['finished','locked'].includes(status)">
                    <select :value="shift" @change="shift = $event.target.value" class="w-full text-[13px] font-black text-slate-800 bg-transparent border-0 p-0 focus:ring-0 cursor-pointer outline-none z-20">
                        <option value="">- Pilih -</option>
                        <option value="Shift Pagi">Shift Pagi</option>
                        <option value="Shift Malam">Shift Malam</option>
                    </select>
                </template>
                <template x-if="!isOperator || ['finished','locked'].includes(status)">
                    <p class="text-[13px] font-black text-slate-800" x-text="shift || '-'"></p>
                </template>
            </div>
            
            {{-- 6. TOTAL WAKTU INSPEKSI --}}
            <div class="bg-blue-50/50 border border-blue-100 rounded-2xl p-4 shadow-inner relative overflow-hidden group">
                <div class="absolute top-0 right-0 p-2 opacity-10 transition-transform group-hover:scale-110">
                    <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <label class="block text-[9px] font-black text-blue-600/70 uppercase tracking-[2px] mb-2">WAKTU AKTUAL</label>
                <div class="flex items-baseline gap-1">
                    <div class="text-[14px] font-black text-blue-700" x-text="waktuAktualText || '-'"></div>
                </div>
            </div>
            
        </div>

        {{-- Row 2: Verification Panels --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Panel 1: QA PREPARED & PENUGASAN --}}
            <div class="bg-white border border-slate-200 rounded-[2rem] p-8 shadow-xl shadow-slate-200/40 flex flex-col justify-between min-h-[220px]">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-1 h-5 bg-red-600 rounded-full"></div>
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">QA PREPARED (STANDAR)</label>
                </div>
                <div class="flex-1 flex flex-col items-center justify-center gap-4">
                    <div class="w-16 h-16 bg-slate-50 border border-slate-100 rounded-full flex items-center justify-center mb-2">
                        <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    </div>

                    <div class="text-xl font-black text-slate-800 text-center tracking-tight" x-text="qgName || '—'"></div>
                    <p class="text-[9px] text-slate-500 font-bold uppercase">QA DEPARTMENT</p>
                    
                    {{-- Penugasan Foreman & GL untuk Leader --}}
                    <template x-if="isLeader && ['draft','revision'].includes(status)">
                        <div class="w-full space-y-3 mt-4 pt-4 border-t border-slate-100">
                            <div class="flex items-center gap-2">
                                <label class="w-20 text-[9px] font-black text-slate-400 uppercase text-left">Supervisor:</label>
                                <div class="flex-1 bg-slate-50 border border-slate-100 rounded-xl px-3 py-1.5 text-[10px] font-black text-slate-800 text-center">Novina</div>
                            </div>
                            <div class="flex items-center gap-2">
                                <label class="w-20 text-[9px] font-black text-slate-400 uppercase text-left">Foreman:</label>
                                <div class="flex-1 bg-slate-50 border border-slate-100 rounded-xl px-3 py-1.5 text-[10px] font-black text-slate-800 text-center">Azriel</div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Panel 2: QC INSPECTOR --}}
            <div class="bg-white border border-slate-200 rounded-[2rem] p-8 shadow-xl shadow-slate-200/40 flex flex-col justify-between min-h-[220px] relative overflow-hidden">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-1 h-5 bg-red-600 rounded-full"></div>
                    <label class="text-[10px] font-black text-red-400 uppercase tracking-widest">QC INSPECTOR (VERIFICATION)</label>
                </div>
                <div class="flex-1 flex flex-col items-center justify-center gap-4">
                    <div class="w-16 h-16 bg-red-50 rounded-full flex items-center justify-center mb-2">
                        <svg class="w-8 h-8 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    </div>
                    
                    <template x-if="isLeader && ['draft','revision'].includes(status)">
                        <select x-model="assignedOperatorId" class="w-full max-w-[200px] bg-slate-50 border-2 border-slate-100 rounded-xl px-4 py-2 text-xs font-black text-slate-800 outline-none focus:border-red-500 transition-all">
                            <option value="">— Pilih Operator QC —</option>
                            <template x-for="u in operatorUsers" :key="u.id">
                                <option :value="u.id" x-text="u.nama"></option>
                            </template>
                        </select>
                    </template>
                    <template x-if="!isLeader || !['draft','revision'].includes(status)">
                        <div class="text-xl font-black text-slate-800 text-center" 
                             x-text="operatorUsers.find(u => String(u.id) === String(assignedOperatorId))?.nama || 'Belum Di-assign'"></div>
                    </template>

                    <p class="text-[9px] text-red-500 font-bold uppercase">QC DEPARTMENT / OPERATOR</p>
                </div>
            </div>

            {{-- Panel 3: CATATAN --}}
            <div class="bg-white border border-slate-200 rounded-[2rem] p-8 shadow-xl shadow-slate-200/40 flex flex-col min-h-[220px]">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-1 h-5 bg-slate-800 rounded-full"></div>
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">CATATAN / REMARKS</label>
                    </div>
                    <template x-if="samplingProgressData?.isOverdue">
                        <span class="text-[9px] font-bold text-red-500 bg-red-50 border border-red-100 px-2 py-1 rounded-md animate-pulse">* Wajib diisi (Overtime)</span>
                    </template>
                </div>
                <textarea id="remarks" x-model="catatan" class="flex-1 w-full text-xs font-bold text-slate-700 bg-slate-50/50 border rounded-2xl p-4 resize-none outline-none transition-all"
                          :class="samplingProgressData?.isOverdue && !(catatan || '').trim() ? 'border-red-300 focus:border-red-500 focus:bg-white' : 'border-slate-100 focus:bg-white focus:border-red-500'"
                          :placeholder="samplingProgressData?.isOverdue ? 'Tuliskan alasan keterlambatan inspeksi di sini...' : 'Tambahkan catatan jika ada...'"></textarea>
            </div>
        </div>

        {{-- PPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPP --}}
        {{-- BUNDLE CHECK PROMPT — hanya muncul untuk Operator/QC   --}}
        {{-- PPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPP --}}
        <template x-if="activeTab === 'main' && isOperator && isQCSectionOpen && status !== 'finished' && status !== 'locked'">
            <div class="mt-6">

                {{-- PINTASAN MENU: Bundle Check (Selalu Tampil) --}}
                <div class="bg-slate-50/50 border border-slate-200 rounded-3xl p-5 shadow-sm relative overflow-hidden flex flex-col sm:flex-row items-start sm:items-center justify-between gap-5">
                    <div class="flex items-center gap-4 flex-1">
                        <div class="w-12 h-12 bg-red-50 rounded-2xl flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-black text-slate-800">Perlu mengisi data <span class="text-red-600">Bundle Check</span>?</p>
                            <p class="text-[10px] text-slate-500 font-bold mt-0.5 leading-snug">Opsional — klik tombol di samping untuk masuk ke tab Bundle Check.</p>
                        </div>
                    </div>
                    <button type="button" @click="activeTab = 'bundle'; setTimeout(() => { const main = document.querySelector('main'); if(main) main.scrollTop = 0; window.scrollTo(0,0); }, 50)"
                            class="w-full sm:w-auto px-6 py-3 bg-red-600 text-white rounded-2xl text-[11px] font-black hover:bg-red-700 shadow-lg shadow-red-600/25 transition-all active:scale-95 shrink-0">
                        Buka Tab Bundle Check
                    </button>
                </div>

            </div>
        </template>

    </div>

    @endif

  {{-- ACTION BAR (Isle Style) — disembunyikan saat modal TTD aktif --}}
  <div x-show="!activePad" x-cloak
       class="li-action-bar fixed bottom-6 left-0 right-0 z-50 pointer-events-none px-4 lg:px-6 flex justify-center md:justify-end">
    <div class="pointer-events-auto bg-white/80 backdrop-blur-xl border border-slate-200/80 rounded-3xl shadow-[0_8px_32px_rgba(15,23,42,0.15)] w-full md:w-max transition-all">
      <div class="p-3 sm:p-4 relative">
        
        {{-- Warning Validation (Floating Tooltip) --}}
        <template x-if="isOperator && isQCSectionOpen && !isFormComplete && !['finished', 'locked'].includes(status)">
           <div class="absolute -top-12 left-1/2 -translate-x-1/2 w-max px-4 py-2 bg-red-600 text-white text-[10px] font-black uppercase tracking-widest rounded-full shadow-lg shadow-red-600/30 animate-bounce flex items-center gap-2">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
              Lengkapi Form Inspeksi
           </div>
        </template>

        <div class="flex items-center gap-2 sm:gap-4">

          {{-- 1. Kembali ke Daftar (Icon Only on Mobile) --}}
          <a href="{{ url('/item-check') }}"
             class="flex items-center justify-center min-h-[50px] sm:min-h-[56px] w-[50px] sm:w-auto sm:px-5 rounded-2xl border-2 border-slate-200 bg-white text-slate-500 hover:bg-slate-50 hover:text-slate-700 hover:border-slate-300 transition-all shrink-0 active:scale-95 shadow-sm group"
             title="Kembali">
            <svg class="w-5 h-5 shrink-0 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            <span class="text-[13px] font-black hidden sm:block ml-2 uppercase tracking-wide">Kembali</span>
          </a>

          <div class="flex-1 flex items-center justify-end gap-2 sm:gap-3">

            @if(!$itemCheck && isset($scheduleId))
                <form action="{{ route('item-check.start', $scheduleId) }}" method="POST" class="m-0">
                    @csrf
                    <button type="submit" class="w-full sm:w-auto min-h-[50px] sm:min-h-[56px] flex items-center justify-center gap-2 px-6 sm:px-8 bg-blue-600 text-white text-[11px] sm:text-[13px] font-black rounded-2xl hover:bg-blue-700 hover:-translate-y-0.5 transition-all shadow-xl shadow-blue-600/30 active:scale-95">
                        <span class="uppercase tracking-wide">Mulai Inspeksi Sekarang</span>
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </button>
                </form>
            @else

            {{-- 3. Simpan Draft --}}
            <button x-show="['draft', 'in_progress', 'revision', 'ready_for_qc'].includes(status)"
              @click="handleSaveDraft()"
              :disabled="saving || savingDraft"
              class="flex-1 max-w-[180px] min-h-[50px] sm:min-h-[56px] flex items-center justify-center gap-2 px-2 sm:px-5 bg-slate-800 text-white border-2 border-slate-800 text-[11px] sm:text-[13px] font-black rounded-2xl hover:bg-slate-900 transition-all shadow-md disabled:opacity-50 active:scale-95">
              <template x-if="savingDraft">
                <svg class="animate-spin w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
              </template>
              <template x-if="!savingDraft">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
              </template>
              <span class="truncate uppercase tracking-wide" x-text="savingDraft ? 'Menyimpan...' : (editId ? 'Simpan Edit' : 'Draft')"></span>
            </button>

            {{-- 4. Export PDF --}}
            <a href="{{ $itemCheck ? route('item-check.print', $itemCheck->id) : '#' }}" 
               x-show="['finished', 'locked'].includes(status)"
               target="_blank"
               class="flex-1 max-w-[200px] min-h-[50px] sm:min-h-[56px] flex items-center justify-center gap-2 px-4 bg-slate-800 text-white border-2 border-slate-800 text-[11px] sm:text-[13px] font-black rounded-2xl hover:bg-slate-900 transition-all shadow-md active:scale-95 print-hidden">
              <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
              <span class="truncate uppercase tracking-wide">Export PDF</span>
            </a>

            {{-- 5. Primary Action (Kirim) --}}
            <button @click="handleSave()"
              :disabled="saving || savingDraft || (isLeader && hasUnresolvedRevisions) || (isOperator && isQCSectionOpen && !isFormComplete)"
              x-show="
                (isLeader && ['draft','revision'].includes(status)) ||
                (isForeman && status === 'waiting_foreman') ||
                (isSupervisor && status === 'waiting_supervisor') ||
                (isOperator && ['draft', 'in_progress', 'revision', 'ready_for_qc'].includes(status)) ||
                ((isGroupLeader || isForeman) && status === 'waiting_qc_approval')
              "
              class="relative group flex-[1.5] lg:flex-none w-auto min-w-[140px] sm:min-w-[180px] min-h-[50px] sm:min-h-[56px] flex items-center justify-center gap-2 px-3 sm:px-6 text-[11px] sm:text-[14px] font-black rounded-2xl transition-all overflow-hidden"
              :class="((isLeader && hasUnresolvedRevisions) || (isOperator && isQCSectionOpen && !isFormComplete)) 
                ? 'bg-slate-100 text-slate-400 cursor-not-allowed border-2 border-slate-200' 
                : 'bg-red-600 text-white hover:bg-red-700 shadow-xl shadow-red-600/30 hover:shadow-red-600/40 border-2 border-red-600 hover:-translate-y-0.5 active:scale-95 active:translate-y-0'">
              
              {{-- Animated Background Glow (Only when active) --}}
              <div x-show="!((isLeader && hasUnresolvedRevisions) || (isOperator && isQCSectionOpen && !isFormComplete))" class="absolute inset-0 bg-gradient-to-r from-red-500/0 via-white/20 to-red-500/0 opacity-0 group-hover:opacity-100 group-hover:translate-x-full transition-all duration-700 -translate-x-full"></div>
              
              <template x-if="saving">
                <svg class="animate-spin w-4 h-4 shrink-0 relative z-10" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
              </template>
              <span class="whitespace-nowrap uppercase tracking-wider relative z-10" x-text="actionBarLabel"></span>
            </button>
            @endif

          </div>
        </div>
      </div>
    </div>
  </div>

</div>{{-- END FORM CARD --}}

{{-- SIGNATURE MODAL --}}
<div id="sig-modal" x-show="activePad" x-cloak
     class="fixed inset-0 z-[200] bg-black/60 backdrop-blur-sm flex items-center justify-center p-4"
     @keydown.escape.window="closeSignaturePad()">
  <div class="bg-white rounded-3xl p-8 w-full max-w-sm shadow-2xl" @click.outside="closeSignaturePad()">
    <h3 class="text-lg font-black text-slate-800 mb-1 text-center">Tanda Tangan</h3>
    <p class="text-xs text-slate-400 text-center mb-5 font-semibold uppercase tracking-widest" x-text="activePad?.toUpperCase()"></p>
    <canvas id="sig-canvas" width="600" height="240"
      class="w-full border-2 border-slate-200 rounded-2xl cursor-crosshair bg-white touch-none block"
      style="height:120px"></canvas>
    <div class="flex gap-3 mt-4">
      <button @click="clearCanvas()" class="flex-1 py-3 border-2 border-slate-200 rounded-2xl text-sm font-bold text-slate-500 hover:bg-slate-50 transition-all">Hapus</button>
      <button @click="saveSignature()" class="flex-2 flex-grow py-3 bg-red-600 text-white rounded-2xl text-sm font-black hover:bg-red-700 transition-all shadow-lg shadow-red-600/20">✓ Simpan TTD</button>
    </div>
    <button @click="closeSignaturePad()" class="w-full mt-3 py-2.5 text-sm font-bold text-slate-400 hover:text-slate-600 transition-colors">Batal</button>
  </div>
</div>

{{-- PIN MODAL FOR SIGNATURE --}}
<div x-show="showPinModal" x-cloak
     class="fixed inset-0 z-[10001] bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-[2.5rem] p-8 w-full max-w-sm shadow-2xl relative text-center">
        <h3 class="text-xl font-black text-slate-800 uppercase tracking-widest mb-1">Otentikasi PIN</h3>
        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-6">Masukkan 6 Digit PIN Akun Anda</p>
        
        <div class="flex justify-center gap-3 mb-8">
            <template x-for="i in 6">
                <div class="w-4 h-4 rounded-full transition-colors"
                     :class="pinInput.length >= i ? 'bg-red-500' : 'bg-slate-200'"></div>
            </template>
        </div>

        <div class="grid grid-cols-3 gap-3 mb-6">
            <template x-for="n in [1, 2, 3, 4, 5, 6, 7, 8, 9]" :key="n">
                <button type="button" @click="if(pinInput.length < 6) pinInput += n"
                        class="py-4 bg-slate-50 border-2 border-slate-100 rounded-2xl text-2xl font-black text-slate-700 hover:bg-slate-100 active:scale-95 transition-all">
                    <span x-text="n"></span>
                </button>
            </template>
            <button type="button" @click="closePinModal()"
                    class="py-4 bg-white border-2 border-slate-100 rounded-2xl text-xs font-black text-slate-400 hover:bg-slate-50 active:scale-95 transition-all">
                BATAL
            </button>
            <button type="button" @click="if(pinInput.length < 6) pinInput += '0'"
                    class="py-4 bg-slate-50 border-2 border-slate-100 rounded-2xl text-2xl font-black text-slate-700 hover:bg-slate-100 active:scale-95 transition-all">
                0
            </button>
            <button type="button" @click="pinInput = pinInput.slice(0, -1)"
                    class="py-4 bg-rose-50 border-2 border-rose-100 rounded-2xl text-sm font-black text-rose-600 hover:bg-rose-100 active:scale-95 transition-all">
                DEL
            </button>
        </div>

        <button type="button" @click="submitPinSignature()"
                :disabled="pinInput.length !== 6 || verifyingPin"
                class="w-full py-4 rounded-2xl text-sm font-black text-white uppercase tracking-widest shadow-lg transition-all active:scale-95 flex justify-center items-center gap-2"
                :class="pinInput.length === 6 ? 'bg-emerald-600 hover:bg-emerald-700 shadow-emerald-600/30' : 'bg-slate-300 cursor-not-allowed'">
            <template x-if="verifyingPin">
                <svg class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
            </template>
            <span x-text="verifyingPin ? 'Memeriksa...' : 'Verifikasi TTD'"></span>
        </button>
    </div>
</div>

    {{-- GLOBAL CONFIRM MODAL (UPGRADED) --}}
    <div x-show="showConfirmMain" x-cloak 
         class="fixed inset-0 z-[9999] flex items-center justify-center p-4 overflow-x-hidden overflow-y-auto"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        
        {{-- Overlay dengan Blur --}}
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-[4px]" @click="showConfirmMain = false"></div>

        {{-- Modal Content (Sharp) --}}
        <div class="relative bg-white rounded-[2.5rem] text-left overflow-hidden shadow-2xl transform transition-all sm:max-w-md sm:w-full border border-slate-100 z-10"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 scale-95">
            
            <div class="bg-white px-8 pt-10 pb-8">
                <div class="flex flex-col items-center text-center">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-20 w-20 rounded-full bg-slate-50 mb-6 group transition-colors border-2 border-slate-50">
                        <svg class="h-10 w-10 text-slate-400 group-hover:text-red-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="mt-3 text-center w-full">
                        <h3 class="text-xl font-black text-slate-800 uppercase tracking-widest mb-3" x-text="confirmTitle"></h3>
                        <div class="mt-2">
                            <p class="text-sm font-bold text-slate-500 leading-relaxed px-4" x-text="confirmMessage"></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-slate-50/50 px-8 py-6 flex flex-col gap-3">
                <button type="button" @click="confirmAction()"
                        :class="confirmBtnColor"
                        class="w-full inline-flex justify-center rounded-2xl border border-transparent shadow-lg px-6 py-4 text-sm font-black text-white hover:opacity-90 focus:outline-none transition-all active:scale-95 uppercase tracking-widest" 
                        x-text="confirmBtnText">
                </button>
                <button type="button" @click="showConfirmMain = false"
                        class="w-full inline-flex justify-center rounded-2xl border-2 border-slate-200 px-6 py-3.5 text-sm font-bold text-slate-400 hover:bg-white hover:text-slate-600 focus:outline-none transition-all uppercase tracking-widest">
                    Batalkan
                </button>
            </div>
        </div>
    </div>

    {{-- QPR URGENT PROMPT MODAL --}}
    {{-- Muncul setelah LI disimpan dengan status NG, mendesak operator segera isi QPR --}}
    <div x-show="showQprPrompt" x-cloak
         class="fixed inset-0 z-[10000] flex items-center justify-center p-4"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-90"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-90">

        {{-- Blur overlay --}}
        <div class="fixed inset-0 bg-red-950/70 backdrop-blur-[6px]"></div>

        {{-- Modal card --}}
        <div class="relative bg-white rounded-[2.5rem] overflow-hidden shadow-2xl max-w-md w-full z-10 border-4 border-red-500">

            {{-- Red urgency header --}}
            <div class="bg-gradient-to-br from-red-600 to-red-700 px-8 pt-10 pb-8 text-center">
                {{-- Animated warning icon --}}
                <div class="w-20 h-20 bg-white/15 rounded-full flex items-center justify-center mx-auto mb-5 animate-pulse">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div class="inline-flex items-center gap-2 bg-white/20 text-white text-[10px] font-black uppercase tracking-widest px-4 py-1.5 rounded-full mb-4">
                    <span class="w-2 h-2 bg-yellow-300 rounded-full animate-ping inline-block"></span>
                    Tindakan Diperlukan
                </div>
                <h2 class="text-2xl font-black text-white mb-2 leading-tight">Ditemukan NG!</h2>
                <p class="text-red-100 text-sm font-semibold leading-relaxed">
                    Anda <span class="font-black text-white underline">wajib</span> segera mengisi formulir
                    <span class="font-black text-yellow-300">Quality Problem Report (QPR)</span>
                    untuk temuan NG ini sekarang.
                </p>
            </div>

            {{-- Body info --}}
            <div class="px-8 py-6 bg-red-50 border-b border-red-100">
                <div class="flex items-start gap-3">
                    <div class="w-8 h-8 bg-red-100 rounded-xl flex items-center justify-center shrink-0 mt-0.5">
                        <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-black text-red-800 mb-1">Mengapa ini penting?</p>
                        <p class="text-[11px] text-red-600 font-medium leading-relaxed">
                            Data dari Lembar Inspeksi Anda sudah <strong>otomatis terisi</strong> di form QPR.
                            Anda tinggal melengkapi analisa penyebab dan tindakan perbaikannya saja.
                        </p>
                    </div>
                </div>
            </div>

            {{-- Action buttons --}}
            <div class="px-8 py-6 flex flex-col gap-3 bg-white">
                <button type="button"
                        @click="window.location.href = tandemUnfinishedId ? `/qpr/${pendingQprId}/edit?next=${encodeURIComponent('/item-check/' + tandemUnfinishedId + '/form')}` : `/qpr/${pendingQprId}/edit`"
                        class="w-full py-4 bg-red-600 text-white rounded-2xl font-black text-sm uppercase tracking-widest hover:bg-red-700 transition-all active:scale-95 shadow-lg shadow-red-600/25 flex items-center justify-center gap-3">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    Isi QPR Sekarang
                </button>
                <button type="button"
                        @click="showQprPrompt = false; showToast('success', 'Disimpan! Jangan lupa isi QPR segera.'); setTimeout(() => window.location.href = tandemUnfinishedId ? '/item-check/' + tandemUnfinishedId + '/form' : '/item-check', 2000)"
                        class="w-full py-3 border-2 border-red-200 bg-white text-red-500 rounded-2xl font-bold text-sm hover:bg-red-50 transition-all uppercase tracking-widest">
                    Nanti (Kembali)
                </button>
            </div>
        </div>
    </div>

    {{-- SEARCH RESULT MODAL --}}
    <div x-show="showSearchModal" 
         class="fixed inset-0 z-[110] bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         style="display: none;">
        
        <div class="bg-white w-full max-w-2xl rounded-[2.5rem] shadow-2xl overflow-hidden flex flex-col max-h-[80vh]" @click.away="showSearchModal = false">
            <div class="px-8 py-6 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-black text-slate-800 flex items-center gap-2">
                        <template x-if="searchType === 'master'">
                            <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/></svg>
                        </template>
                        <template x-if="searchType === 'history'">
                            <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </template>
                        <span x-text="searchType === 'master' ? 'Pilih Data Master' : 'Pilih Histori Laporan'"></span>
                    </h3>
                    <p class="text-xs text-slate-400 font-bold mt-1">
                        Hasil pencarian untuk: <span class="text-red-500" x-text="searchQuery"></span>
                    </p>
                </div>
                <button @click="showSearchModal = false" class="w-10 h-10 flex items-center justify-center bg-white border border-slate-200 rounded-full text-slate-400 hover:text-red-500 hover:border-red-100 transition-all shadow-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto p-6 space-y-3">
                <template x-for="(item, idx) in searchResults" :key="idx">
                    <div class="group p-5 bg-slate-50 border-2 rounded-[1.5rem] transition-all flex flex-col gap-4 shadow-sm"
                         :class="searchType === 'master' ? 'border-emerald-100 hover:border-emerald-300' : 'border-blue-100 hover:border-blue-300'">
                        
                        <div class="flex items-center gap-4">
                            <div class="w-14 h-14 rounded-2xl flex flex-col items-center justify-center shadow-sm"
                                 :class="searchType === 'master' ? 'bg-emerald-50 text-emerald-600' : 'bg-blue-50 text-blue-600'">
                                <template x-if="searchType === 'master'">
                                    <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/></svg>
                                </template>
                                <template x-if="searchType === 'history'">
                                    <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </template>
                                <span class="text-[7px] font-black uppercase" x-text="searchType === 'master' ? 'DATA' : 'HISTORI'"></span>
                            </div>
                            <div class="flex-1 min-w-0">
                                {{-- Primary Identifier --}}
                                <div class="flex items-center gap-2 mb-1">
                                    <h4 class="text-base font-black tracking-tight" 
                                        :class="searchType === 'history' ? 'text-blue-700' : 'text-emerald-700'"
                                        x-text="searchType === 'history' ? (item.job_no || '—') : (item.part_no || '—')">
                                    </h4>
                                    <span class="px-2 py-0.5 bg-slate-200 text-slate-600 text-[8px] font-black rounded-full uppercase" x-text="item.type || item.part_type || '—'"></span>
                                </div>
                                
                                {{-- Secondary Details --}}
                                <div class="space-y-0.5">
                                    <p class="text-[11px] font-bold text-slate-600" x-text="item.part_name || 'Tanpa Nama Part'"></p>
                                    <div class="flex items-center gap-4 text-[9px] font-bold text-slate-400">
                                        <div class="flex items-center gap-4">
                                            <template x-if="searchType === 'history'">
                                                <span class="flex items-center gap-1 font-black text-emerald-600/80">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg> 
                                                    <span x-text="item.part_no"></span>
                                                </span>
                                            </template>
                                            <template x-if="searchType === 'master'">
                                                <span class="flex items-center gap-1 font-black text-blue-600/80">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                                    <span x-text="item.job_no || '—'"></span>
                                                </span>
                                            </template>
                                            
                                            <template x-if="item.created_at">
                                                <span class="flex items-center gap-1 text-slate-500">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                    <span x-text="formatDate(item.created_at)"></span>
                                                </span>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- ACTION BUTTONS --}}
                        <div class="flex items-center gap-2 pt-2 mt-1 border-t border-slate-200">
                            {{-- Autofill Action --}}
                            <button type="button" @click="selectSearchResult(item)" 
                                    class="w-full py-2.5 px-4 text-white text-[10px] font-black uppercase tracking-widest rounded-xl transition-all shadow-md flex items-center justify-center gap-2"
                                    :class="searchType === 'master' ? 'bg-emerald-600 hover:bg-emerald-700 shadow-emerald-600/30' : 'bg-blue-600 hover:bg-blue-700 shadow-blue-600/30'">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                                Pilih & Autofill Form
                            </button>
                        </div>
                    </div>
                </template>
            </div>

            <div class="p-6 bg-slate-50 border-t border-slate-100 flex justify-center">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest italic text-center">Pilih salah satu data untuk autofill otomatis secara instan</p>
            </div>
        </div>
    </div>

    {{-- CUSTOM MODAL: KONFIRMASI REVISI SELESAI --}}
    <div x-show="showConfirmResolve" x-cloak class="fixed inset-0 z-[9999] flex items-center justify-center">
        {{-- Backdrop --}}
        <div x-show="showConfirmResolve" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="showConfirmResolve = false"
             class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"></div>

        {{-- Modal Box --}}
        <div x-show="showConfirmResolve"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95 translate-y-4"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100 translate-y-0"
             x-transition:leave-end="opacity-0 scale-95 translate-y-4"
             class="relative bg-white rounded-3xl shadow-2xl w-full max-w-sm mx-4 overflow-hidden border border-slate-100">
            
            {{-- Header --}}
            <div class="bg-emerald-50 px-6 py-5 border-b border-emerald-100 flex items-center gap-4">
                <div class="w-12 h-12 bg-emerald-100 rounded-2xl flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <h3 class="text-base font-black text-emerald-900">Verifikasi Perbaikan</h3>
                    <p class="text-[10px] font-bold text-emerald-600 uppercase tracking-widest mt-0.5">Selesaikan Revisi</p>
                </div>
            </div>

            {{-- Body --}}
            <div class="p-6">
                <p class="text-xs font-bold text-slate-600 leading-relaxed text-center">
                    Apakah Anda yakin bagian yang direvisi ini sudah diperbaiki dengan benar?
                </p>
            </div>

            {{-- Actions --}}
            <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex items-center gap-3">
                <button type="button" @click="showConfirmResolve = false"
                        class="px-5 py-2.5 bg-slate-200/70 text-slate-700 text-[10px] font-black uppercase tracking-widest hover:bg-slate-300 rounded-xl transition-all">
                    Batal
                </button>
                <button type="button" @click="executeResolveRevision()"
                        class="flex-1 px-6 py-2.5 bg-emerald-600 text-white rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-emerald-700 hover:scale-105 active:scale-95 transition-all shadow-lg shadow-emerald-600/30 flex items-center justify-center gap-2">
                    Ya, Sudah Selesai
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </button>
            </div>
        </div>
    </div>



    {{-- MODAL KONFIRMASI BUNDLE CHECK (double-verify) --}}
    <div x-show="showBundleCheckConfirm" x-cloak
         class="fixed inset-0 z-[9998] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showBundleCheckConfirm = false"></div>
        <div x-show="showBundleCheckConfirm"
             x-transition:enter="transition ease-out duration-250"
             x-transition:enter-start="opacity-0 scale-90"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-90"
             class="relative bg-white rounded-3xl shadow-2xl w-full max-w-md p-8 flex flex-col items-center text-center gap-4">

            {{-- Icon --}}
            <div class="w-16 h-16 bg-indigo-100 rounded-2xl flex items-center justify-center mb-2">
                <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                </svg>
            </div>

            <h3 class="text-xl font-black text-slate-900">Konfirmasi Bundle Check</h3>
            <p class="text-sm text-slate-500 font-medium leading-relaxed">
                Anda akan membuka formulir <strong class="text-slate-800">Pengecekan Awal Proses Setelah Pergantian Bundle Material</strong>.<br><br>
                Pastikan Anda telah <strong class="text-slate-800">mempersiapkan GL dan Foreman</strong> untuk memberikan tanda tangan pada sesi bundle check ini.
            </p>

            {{-- Warning box --}}
            <div class="w-full bg-amber-50 border border-amber-200 rounded-2xl p-4 text-left">
                <p class="text-[10px] font-black text-amber-700 uppercase tracking-widest mb-1"> Perhatian</p>
                <p class="text-[11px] text-amber-600 font-bold leading-relaxed">Bundle Check ini berbeda dari LI utama. Diperlukan tanda tangan GL (Checked) dan Foreman (Approved) yang baru.</p>
            </div>

            <div class="flex gap-3 w-full mt-2">
                <button type="button" @click="showBundleCheckConfirm = false"
                        class="flex-1 py-3 bg-slate-100 text-slate-600 rounded-2xl text-sm font-black hover:bg-slate-200 transition-all">
                    Batal
                </button>
                <button type="button" @click="confirmBundleCheck()"
                        class="flex-[2] py-3 bg-indigo-600 text-white rounded-2xl text-sm font-black hover:bg-indigo-700 shadow-lg shadow-indigo-600/25 transition-all active:scale-95">
                    ✓ Ya, Lanjutkan Bundle Check
                </button>
            </div>
        </div>
    </div>

    {{-- MODAL ALASAN APPEARANCE NG --}}
    <div x-show="showNgModal" x-cloak class="fixed inset-0 z-[9999] flex items-center justify-center">
        <div x-show="showNgModal" class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="cancelNgReason()"></div>
        <div x-show="showNgModal" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-8 scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 scale-100"
             x-transition:leave-end="opacity-0 translate-y-8 scale-95"
             class="relative bg-white rounded-none sm:rounded-3xl p-4 sm:p-6 w-full max-w-lg sm:max-h-[90vh] max-sm:fixed max-sm:inset-0 max-sm:max-h-none max-sm:h-full max-sm:rounded-none shadow-2xl flex flex-col z-10">
            
            <div class="flex justify-between items-center mb-5 pb-4 border-b border-slate-100">
                <h3 class="text-xl font-black text-slate-800" x-text="(isNgModalReadOnly ? 'Lihat Catatan: ' : 'Edit Alasan: ') + (appItems[ngTargetRow] || 'Appearance') + (ngTargetCol ? ' (Sample ' + ngTargetCol + ')' : '')"></h3>
                <button @click="cancelNgReason()" class="w-8 h-8 flex items-center justify-center rounded-full bg-slate-100 text-slate-500 hover:bg-slate-200 hover:text-slate-700 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto pr-2 -mr-2 space-y-5">
                <div x-show="!(appItems[ngTargetRow] && appItems[ngTargetRow].toUpperCase().includes('HOLE'))" class="space-y-5">
                    {{-- Proses --}}
                    <div>
                        <label class="block text-xs font-black text-slate-700 mb-2">Proses <span class="text-slate-400 font-normal">(bisa pilih lebih dari satu)</span></label>
                        <div class="grid grid-cols-3 gap-2">
                            <template x-for="op in Object.keys(ngCurrentProcesses)" :key="op">
                                <label class="flex items-center gap-2.5 p-2.5 bg-slate-50 border border-slate-200 rounded-xl cursor-pointer hover:border-indigo-300 transition-colors"
                                       :class="[ngCurrentProcesses[op] ? 'border-indigo-500 bg-indigo-50/60' : '', isNgModalReadOnly ? 'pointer-events-none' : '']">
                                    <input type="checkbox" x-model="ngCurrentProcesses[op]" :disabled="isNgModalReadOnly"
                                           class="w-4 h-4 text-indigo-600 border-2 border-slate-300 rounded focus:ring-indigo-500 disabled:opacity-100 disabled:cursor-default">
                                    <span class="text-xs font-bold text-slate-700" x-text="op"></span>
                                </label>
                            </template>
                        </div>
                    </div>

                    {{-- Area --}}
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-xs font-black text-slate-700">Area Cacat <span class="text-slate-400 font-normal">(pilih area yang cacat, 1–16)</span></label>
                        </div>
                        
                        {{-- Referensi Gambar --}}
                        <template x-if="sketchUrl">
                            <div class="mb-3 rounded-xl border border-slate-200 overflow-hidden bg-slate-50 flex justify-center items-center p-2 shadow-inner">
                                <img :src="sketchUrl" alt="Referensi Area Part" class="max-w-full max-h-[580px] object-contain rounded-lg cursor-zoom-in" @click="window.open(sketchUrl, '_blank')">
                            </div>
                        </template>
                        <div class="grid grid-cols-8 gap-1.5">
                            <template x-for="n in [1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16]" :key="n">
                                <label class="flex flex-col items-center justify-center gap-1 p-2 bg-slate-50 border border-slate-200 rounded-lg cursor-pointer hover:border-blue-300 transition-colors aspect-square"
                                       :class="[ngCurrentAreas[n] ? 'border-blue-500 bg-blue-50 text-blue-700' : 'text-slate-500', isNgModalReadOnly ? 'pointer-events-none' : '']">
                                    <input type="checkbox" x-model="ngCurrentAreas[n]" :disabled="isNgModalReadOnly"
                                           class="hidden disabled:opacity-100 disabled:cursor-default">
                                    <span class="text-[11px] font-black leading-none" x-text="n"></span>
                                    <div class="w-3.5 h-3.5 rounded border-2 flex items-center justify-center transition-all"
                                         :class="ngCurrentAreas[n] ? 'border-blue-500 bg-blue-500' : 'border-slate-300'">
                                        <svg x-show="ngCurrentAreas[n]" class="w-2 h-2 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3.5" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </div>
                                </label>
                            </template>
                        </div>
                    </div>

                    {{-- Problem --}}
                    <div>
                        <label class="block text-xs font-black text-slate-700 mb-2">Problem (Pilih setidaknya satu)</label>
                        <div class="grid grid-cols-2 gap-3">
                            <template x-for="prob in Object.keys(ngCurrentProblems)" :key="prob">
                                <label class="flex flex-col justify-center p-3 bg-slate-50 border border-slate-200 rounded-xl cursor-pointer hover:border-red-300 transition-colors" 
                                       :class="[ngCurrentProblems[prob] ? 'border-red-500 bg-red-50/50' : '', isNgModalReadOnly ? 'pointer-events-none' : '']">
                                    <div class="flex items-center gap-3 w-full">
                                        <input type="checkbox" x-model="ngCurrentProblems[prob]" :disabled="isNgModalReadOnly" class="w-5 h-5 text-red-600 border-2 border-slate-300 rounded focus:ring-red-500 disabled:opacity-100 disabled:cursor-default shrink-0">
                                        <span class="text-sm font-bold text-slate-700" x-text="prob"></span>
                                    </div>
                                    <template x-if="prob === 'LAINNYA' && ngCurrentProblems['LAINNYA']">
                                        <input type="text" x-model="ngOtherProblemText" :readonly="isNgModalReadOnly" placeholder="Ketik problem di sini..." class="mt-3 w-full px-3 py-2 text-sm font-bold text-slate-700 bg-white border-2 border-red-200 rounded-lg focus:outline-none focus:border-red-500 transition-all" @click.stop="">
                                    </template>
                                </label>
                            </template>
                        </div>
                    </div>

                    {{-- Penyebab --}}
                    <div>
                        <label class="block text-xs font-black text-slate-700 mb-2">Penyebab (Pilih setidaknya satu)</label>
                        <div class="grid grid-cols-3 gap-3">
                            <template x-for="cause in Object.keys(ngCurrentCauses)" :key="cause">
                                <label class="flex items-center gap-3 p-3 bg-slate-50 border border-slate-200 rounded-xl cursor-pointer hover:border-red-300 transition-colors" 
                                       :class="[ngCurrentCauses[cause] ? 'border-red-500 bg-red-50/50' : '', isNgModalReadOnly ? 'pointer-events-none' : '']">
                                    <input type="checkbox" x-model="ngCurrentCauses[cause]" :disabled="isNgModalReadOnly" class="w-5 h-5 text-red-600 border-2 border-slate-300 rounded focus:ring-red-500 disabled:opacity-100 disabled:cursor-default">
                                    <span class="text-sm font-bold text-slate-700" x-text="cause"></span>
                                </label>
                            </template>
                        </div>
                    </div>

                    {{-- Disposisi untuk KEEPER (bisa multi-disposisi) --}}
                    <template x-if="String(ngTargetCol).toUpperCase().startsWith('KEEPER')">
                        <div>
                            <label class="block text-xs font-black text-slate-700 mb-2">Tindakan / Disposisi (Khusus Keeper)</label>
                            <div class="grid grid-cols-2 gap-3">
                                <div class="p-3 bg-indigo-50 border-2 border-indigo-200 rounded-xl">
                                    <label class="block text-[10px] font-black text-indigo-800 mb-1">REPAIR (Pcs)</label>
                                    <input type="number" min="0" x-model.number="ngCurrentQtyRepair" :readonly="isNgModalReadOnly" class="w-full px-3 py-2 bg-white border-2 border-indigo-300 rounded-lg text-sm font-black text-indigo-700 text-center focus:border-indigo-500 outline-none">
                                </div>
                                <div class="p-3 bg-rose-50 border-2 border-rose-200 rounded-xl">
                                    <label class="block text-[10px] font-black text-rose-800 mb-1">REJECT (Pcs)</label>
                                    <input type="number" min="0" x-model.number="ngCurrentQtyReject" :readonly="isNgModalReadOnly" class="w-full px-3 py-2 bg-white border-2 border-rose-300 rounded-lg text-sm font-black text-rose-700 text-center focus:border-rose-500 outline-none">
                                </div>
                            </div>
                        </div>
                    </template>
                    
                    {{-- Disposisi untuk NON-KEEPER --}}
                    <template x-if="!String(ngTargetCol).toUpperCase().startsWith('KEEPER')">
                        <div>
                            <label class="block text-xs font-black text-slate-700 mb-2">Tindakan / Disposisi NG <span class="text-red-500">*</span></label>
                            <div class="grid grid-cols-2 gap-3">
                                <label class="flex items-center gap-3 p-3 bg-slate-50 border-2 rounded-xl cursor-pointer hover:border-indigo-300 transition-all" 
                                       :class="[ngCurrentDisposition === 'repair' ? 'border-indigo-500 bg-indigo-50/50 text-indigo-900' : 'border-slate-200 text-slate-700', isNgModalReadOnly ? 'pointer-events-none' : '']">
                                    <input type="radio" name="ngDisposition" value="repair" x-model="ngCurrentDisposition" :disabled="isNgModalReadOnly" class="w-5 h-5 text-indigo-600 border-2 border-slate-300 rounded-full focus:ring-indigo-500 disabled:opacity-100 disabled:cursor-default">
                                    <span class="text-sm font-black">REPAIR</span>
                                </label>
                                <label class="flex items-center gap-3 p-3 bg-slate-50 border-2 rounded-xl cursor-pointer hover:border-rose-300 transition-all" 
                                       :class="[ngCurrentDisposition === 'reject' ? 'border-rose-500 bg-rose-50/50 text-rose-900' : 'border-slate-200 text-slate-700', isNgModalReadOnly ? 'pointer-events-none' : '']">
                                    <input type="radio" name="ngDisposition" value="reject" x-model="ngCurrentDisposition" :disabled="isNgModalReadOnly" class="w-5 h-5 text-rose-600 border-2 border-slate-300 rounded-full focus:ring-rose-500 disabled:opacity-100 disabled:cursor-default">
                                    <span class="text-sm font-black">REJECT</span>
                                </label>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Upload Bukti Foto --}}
                <div class="mt-4 border-t border-slate-100 pt-4">
                    <label class="block text-xs font-black text-slate-700 mb-2">Bukti Foto Cacat / NG (Opsional)</label>
                    <div class="flex items-start gap-4">
                        <div class="flex-1">
                            <template x-if="!ngCurrentPhoto">
                                <label class="flex flex-col items-center justify-center w-full h-32 border-2 border-slate-300 border-dashed rounded-xl cursor-pointer bg-slate-50 hover:bg-slate-100 hover:border-slate-400 transition-all" :class="isNgModalReadOnly ? 'pointer-events-none opacity-50' : ''">
                                    <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                        <svg class="w-8 h-8 mb-2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest"><span class="font-black text-red-500">Kamera</span> / Pilih File</p>
                                    </div>
                                    <input type="file" class="hidden" accept="image/*" capture="environment" @change="handleNgPhotoUpload($event)" :disabled="isNgModalReadOnly" />
                                </label>
                            </template>
                            <template x-if="ngCurrentPhoto">
                                <div class="relative group rounded-xl overflow-hidden border-2 border-slate-200">
                                    <img :src="ngCurrentPhoto.startsWith('http') ? ngCurrentPhoto : '/' + ngCurrentPhoto" class="w-full h-40 object-cover bg-slate-100" />
                                    <template x-if="!isNgModalReadOnly">
                                        <button type="button" @click.stop="ngCurrentPhoto = ''" class="absolute top-2 right-2 w-8 h-8 flex items-center justify-center bg-red-600 text-white rounded-full shadow-lg hover:bg-red-700 hover:scale-110 active:scale-95 transition-all">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- Catatan --}}
                <div>
                    <label class="block text-xs font-black text-slate-700 mb-2">Catatan / Keterangan NG (Opsional)</label>
                    <textarea x-model="ngCurrentNote" :readonly="isNgModalReadOnly" class="w-full px-4 py-3 bg-slate-50 border-2 border-slate-200 rounded-xl text-sm font-bold text-slate-700 outline-none focus:border-red-500 transition-colors min-h-[100px] resize-none" :placeholder="isNgModalReadOnly ? 'Tidak ada catatan' : 'Tulis catatan alasan di sini...'"></textarea>
                </div>


            </div>

            <div class="mt-6 pt-5 border-t border-slate-100 flex justify-end gap-3">
                <button @click="cancelNgReason()" class="px-6 py-3 bg-slate-100 text-slate-600 rounded-xl font-black hover:bg-slate-200 transition-all" x-text="isNgModalReadOnly ? 'Tutup' : 'Batal'"></button>
                <button x-show="!isNgModalReadOnly" @click="saveNgReason()" class="px-6 py-3 bg-blue-600 text-white rounded-xl font-black shadow-lg shadow-blue-600/20 hover:bg-blue-700 active:scale-95 transition-all flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    Simpan
                </button>
            </div>
        </div>
    </div>
    {{-- MODAL SETTING APPEARANCE --}}
    <div x-show="showAppStandardModal" x-cloak 
         class="fixed inset-0 z-[10000] flex items-center justify-center p-4 overflow-x-hidden overflow-y-auto">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showAppStandardModal = false"></div>
        <div class="relative bg-white rounded-[2rem] w-full max-w-lg shadow-2xl overflow-hidden border border-slate-100 z-10 flex flex-col max-h-[90vh]"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-8 scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 scale-100">
            
            <div class="px-6 py-5 bg-slate-50 border-b border-slate-100 flex items-center justify-between shrink-0">
                <div>
                    <h3 class="text-base font-black text-slate-800 uppercase tracking-wider mb-1">Setting Standar Appearance</h3>
                    <p class="text-[10px] text-slate-500 font-bold">Pilih beberapa standar (opsional) & tambahkan custom</p>
                </div>
                <div class="px-3 py-1 bg-red-600 text-white text-[10px] font-black rounded-full shadow-sm" x-text="'ITEM #' + ((appStandardTargetRi||0) + 1)"></div>
            </div>

            <div class="p-6 space-y-4 overflow-y-auto">
                <div class="bg-slate-50 border border-slate-200 rounded-xl p-4">
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">Tipe Marking (Pilih jika ada)</label>
                    <div class="flex flex-wrap gap-2">
                        <template x-for="m in ['X', 'R', 'L', 'XR', 'XL', 'UR', 'UL', 'JR', 'JL', '2L', '2R', 'MR', 'ML']" :key="m">
                            <label class="cursor-pointer">
                                <input type="radio" name="markingType" :value="m" x-model="appStandardMarking" class="peer hidden">
                                <div class="px-3 py-1.5 text-xs font-bold border-2 border-slate-200 text-slate-500 rounded-lg peer-checked:border-red-500 peer-checked:bg-red-50 peer-checked:text-red-700 transition-all hover:bg-slate-100">
                                    <span x-text="m"></span>
                                </div>
                            </label>
                        </template>
                        <label class="cursor-pointer">
                            <input type="radio" name="markingType" value="" x-model="appStandardMarking" class="peer hidden">
                            <div class="px-3 py-1.5 text-xs font-bold border-2 border-slate-200 text-slate-500 rounded-lg peer-checked:border-slate-500 peer-checked:bg-slate-200 peer-checked:text-slate-800 transition-all hover:bg-slate-100">
                                Tidak Ada
                            </div>
                        </label>
                    </div>
                </div>

                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1 mt-2">Pre-defined Standar (Bisa Pilih > 1)</label>
                <div class="grid grid-cols-1 gap-2">
                    <template x-for="preset in appStandardPresets" :key="preset">
                        <label class="flex items-center gap-3 p-3 bg-slate-50 border border-slate-200 rounded-xl cursor-pointer hover:border-red-300 transition-colors" 
                               :class="appStandardSelected.includes(preset) ? 'border-red-500 bg-red-50/50 shadow-inner' : ''">
                            <input type="checkbox" :value="preset" x-model="appStandardSelected" class="w-4 h-4 text-red-600 border-2 border-slate-300 rounded focus:ring-red-500">
                            <span class="text-xs font-bold text-slate-700" x-text="preset"></span>
                        </label>
                    </template>
                </div>

                <div class="mt-4">
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Standar Kustom / Lainnya</label>
                    <textarea x-model="appStandardCustom" rows="2" 
                              placeholder="Ketik standar tambahan di sini (Opsional)..."
                              class="w-full px-4 py-3 bg-slate-50 border-2 border-slate-100 rounded-xl text-xs font-bold text-slate-800 focus:bg-white focus:border-red-500 transition-all outline-none resize-none"></textarea>
                </div>
                
                {{-- Preview --}}
                <div class="bg-slate-900 rounded-2xl p-4 flex flex-col gap-1 border border-slate-800 shadow-inner mt-4">
                    <span class="text-[8px] font-black text-slate-500 uppercase tracking-widest">Preview Standar:</span>
                    <span class="text-[11px] font-bold text-white leading-relaxed" x-text="[appStandardMarking ? ('Marking ' + appStandardMarking + ' harus jelas / nyata') : '', ...appStandardSelected, appStandardCustom.trim()].filter(Boolean).join(', ') || '(Belum ada yang dipilih)'"></span>
                </div>
            </div>

            <div class="bg-slate-50 p-5 border-t border-slate-100 flex flex-wrap gap-3 shrink-0">
                <button @click="clearAppStandard()" 
                        class="px-5 py-3 bg-rose-50 border-2 border-rose-100 rounded-xl text-[10px] font-black text-rose-600 uppercase tracking-widest hover:bg-rose-100 transition-all flex items-center gap-2">
                    Kosongkan
                </button>
                <div class="flex-1 flex justify-end gap-3">
                    <button @click="showAppStandardModal = false" class="px-5 py-3 bg-white border-2 border-slate-200 text-slate-600 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-slate-50 transition-all">Batal</button>
                    <button @click="saveAppStandardModal()" class="px-6 py-3 bg-red-600 text-white rounded-xl text-[10px] font-black uppercase tracking-widest shadow-lg shadow-red-600/20 hover:bg-red-700 active:scale-95 transition-all">Simpan</button>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL SETTING DIMENSI --}}
    <div x-show="showDimModal" x-cloak 
         class="fixed inset-0 z-[10000] flex items-center justify-center p-4 overflow-x-hidden overflow-y-auto">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showDimModal = false"></div>
        <div class="relative bg-white rounded-[2.5rem] w-full max-w-md shadow-2xl overflow-hidden border border-slate-100 z-10"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-8 scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 scale-100">
            
            <div class="px-8 pt-8 pb-6 bg-slate-50 border-b border-slate-100">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-lg font-black text-slate-800 uppercase tracking-wider">Setting Standar Dimensi</h3>
                    <div class="px-3 py-1 bg-red-600 text-white text-[10px] font-black rounded-full" x-text="'ITEM #' + (targetDimIdx + 1)"></div>
                </div>
                <p class="text-xs text-slate-400 font-bold">Atur nilai nominal dan batas toleransi untuk validasi otomatis.</p>
            </div>

            <div class="p-8 space-y-5">
                {{-- Nominal: Full-width stepper --}}
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Nominal / Nilai</label>
                        {{-- Step size pills --}}
                        <div class="flex gap-1">
                            <template x-for="s in [0.001, 0.01, 0.1, 1]" :key="s">
                                <button type="button"
                                        @click="tempDim._step = s"
                                        :class="tempDim._step == s ? 'bg-red-600 text-white shadow-sm' : 'bg-slate-100 text-slate-500 hover:bg-slate-200'"
                                        class="px-2 py-0.5 rounded-lg text-[9px] font-black uppercase tracking-wider transition-all"
                                        x-text="s"></button>
                            </template>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button"
                                @click="tempDim.nominal = (Math.round((parseFloat(tempDim.nominal||0) - (tempDim._step||0.01)) * 100000) / 100000).toString()"
                                class="w-14 h-14 flex-shrink-0 flex items-center justify-center bg-slate-100 hover:bg-red-50 hover:text-red-600 border-2 border-slate-200 hover:border-red-200 rounded-2xl text-xl font-black text-slate-500 transition-all active:scale-90 select-none">-</button>
                        <input type="number"
                               inputmode="decimal"
                               step="any"
                               x-model="tempDim.nominal"
                               class="flex-1 min-w-0 px-4 py-3.5 bg-slate-50 border-2 border-slate-200 rounded-2xl text-2xl font-black text-slate-800 focus:bg-white focus:border-red-500 transition-all outline-none text-center"
                               placeholder="0.00">
                        <button type="button"
                                @click="tempDim.nominal = (Math.round((parseFloat(tempDim.nominal||0) + (tempDim._step||0.01)) * 100000) / 100000).toString()"
                                class="w-14 h-14 flex-shrink-0 flex items-center justify-center bg-slate-100 hover:bg-emerald-50 hover:text-emerald-600 border-2 border-slate-200 hover:border-emerald-200 rounded-2xl text-xl font-black text-slate-500 transition-all active:scale-90 select-none">+</button>
                    </div>
                </div>

                {{-- Toleransi Row --}}
                <div class="grid grid-cols-2 gap-4">
                    {{-- Plus --}}
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Toleransi (+)</label>
                        <div class="flex items-center gap-1.5">
                            <button type="button"
                                    @click="tempDim.plus = Math.max(0, Math.round((parseFloat(tempDim.plus||0) - (tempDim._step||0.01)) * 100000) / 100000)"
                                    class="w-10 h-10 flex-shrink-0 flex items-center justify-center bg-emerald-50 hover:bg-emerald-100 border-2 border-emerald-200 rounded-xl text-base font-black text-emerald-600 transition-all active:scale-90 select-none">-</button>
                            <input type="number" step="any" inputmode="decimal" x-model="tempDim.plus"
                                   class="flex-1 min-w-0 px-2 py-2.5 bg-emerald-50 border-2 border-emerald-100 rounded-xl text-sm font-black text-emerald-700 focus:bg-white focus:border-emerald-500 transition-all outline-none text-center">
                            <button type="button"
                                    @click="tempDim.plus = (Math.round((parseFloat(tempDim.plus||0) + (tempDim._step||0.01)) * 100000) / 100000)"
                                    class="w-10 h-10 flex-shrink-0 flex items-center justify-center bg-emerald-50 hover:bg-emerald-100 border-2 border-emerald-200 rounded-xl text-base font-black text-emerald-600 transition-all active:scale-90 select-none">+</button>
                        </div>
                    </div>
                    {{-- Minus --}}
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Toleransi (-)</label>
                        <div class="flex items-center gap-1.5">
                            <button type="button"
                                    @click="tempDim.minus = Math.max(0, Math.round((parseFloat(tempDim.minus||0) - (tempDim._step||0.01)) * 100000) / 100000)"
                                    class="w-10 h-10 flex-shrink-0 flex items-center justify-center bg-rose-50 hover:bg-rose-100 border-2 border-rose-200 rounded-xl text-base font-black text-rose-600 transition-all active:scale-90 select-none">-</button>
                            <input type="number" step="any" inputmode="decimal" x-model="tempDim.minus"
                                   class="flex-1 min-w-0 px-2 py-2.5 bg-rose-50 border-2 border-rose-100 rounded-xl text-sm font-black text-rose-700 focus:bg-white focus:border-rose-500 transition-all outline-none text-center">
                            <button type="button"
                                    @click="tempDim.minus = (Math.round((parseFloat(tempDim.minus||0) + (tempDim._step||0.01)) * 100000) / 100000)"
                                    class="w-10 h-10 flex-shrink-0 flex items-center justify-center bg-rose-50 hover:bg-rose-100 border-2 border-rose-200 rounded-xl text-base font-black text-rose-600 transition-all active:scale-90 select-none">+</button>
                        </div>
                    </div>
                </div>

                {{-- Metode --}}
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Metode Pengecekan</label>
                    <select x-model="tempDim.method" 
                            class="w-full px-4 py-3 bg-slate-50 border-2 border-slate-100 rounded-xl text-sm font-bold text-slate-700 focus:bg-white focus:border-red-500 transition-all outline-none">
                        <option value="">Pilih Metode</option>
                        <option value="Vernier Caliper">Vernier Caliper</option>
                        <option value="Check Jig">Check Jig</option>
                        <option value="Visual">Visual</option>
                        <option value="Micro Meter">Micro Meter</option>
                    </select>
                </div>

                {{-- Preview --}}
                <div class="bg-slate-900 rounded-2xl p-4 flex flex-col items-center justify-center gap-1 border border-slate-800 shadow-inner">
                    <span class="text-[8px] font-black text-slate-500 uppercase tracking-widest">Preview Tampilan:</span>
                    <span class="text-sm font-black text-white" x-text="
                        (tempDim.item && tempDim.item.toUpperCase().includes('SPEC MATERIAL')) 
                        ? (tempDim.nominal || '') 
                        : ((tempDim.item && tempDim.item.toUpperCase().includes('JUMLAH HOLE')) 
                            ? ((tempDim.nominal ? tempDim.nominal + ' pcs' : '')) 
                            : ((tempDim.nominal ? 'Ø ' + tempDim.nominal + ' mm' : '') + (tempDim.plus || tempDim.minus ? ' +' + (tempDim.plus||0) + '/-' + (tempDim.minus||0) : ''))
                          )
                    "></span>
                </div>
            </div>

            <div class="bg-slate-50 p-6 flex flex-wrap gap-3">
                <button @click="clearDimSettings()" 
                        class="px-5 py-3.5 bg-rose-50 border-2 border-rose-100 rounded-2xl text-[10px] font-black text-rose-600 uppercase tracking-widest hover:bg-rose-100 transition-all flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    Hapus
                </button>
                <button @click="showDimModal = false" 
                        class="flex-1 py-3.5 bg-white border-2 border-slate-200 rounded-2xl text-[10px] font-black text-slate-400 uppercase tracking-widest hover:bg-slate-100 transition-all">
                    Batal
                </button>
                <button @click="saveDimSettings()" 
                        class="flex-[2] py-3.5 bg-red-600 rounded-2xl text-[10px] font-black text-white uppercase tracking-widest shadow-lg shadow-red-600/30 hover:bg-red-700 transition-all active:scale-95">
                    ✓ Simpan Standar
                </button>
            </div>
        </div>
    </div>
    
    {{-- MODAL PILIH ALASAN JEDA TIMER --}}
    <div x-show="showPauseModal" x-cloak 
         class="fixed inset-0 z-[100] flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showPauseModal = false"></div>
        <div class="bg-white rounded-[2rem] shadow-2xl w-full max-w-sm overflow-hidden relative z-10 animate-[popIn_0.3s_ease-out]">
            <div class="p-6 bg-red-600">
                <h3 class="text-white text-base font-black tracking-widest uppercase flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Jeda Inspeksi
                </h3>
                <p class="text-red-100 text-[10px] font-bold mt-1 uppercase tracking-wider">Silakan pilih alasan Anda menjeda timer</p>
            </div>
            <div class="p-6">
                <div class="flex flex-col gap-3">
                    <label class="flex items-center gap-3 p-3 border-2 rounded-xl cursor-pointer transition-all"
                           :class="pauseReason === 'Istirahat Makan / Sholat' ? 'border-red-500 bg-red-50' : 'border-slate-100 hover:border-slate-300'">
                        <input type="radio" x-model="pauseReason" value="Istirahat Makan / Sholat" class="w-4 h-4 text-red-600 focus:ring-red-500">
                        <span class="text-xs font-black text-slate-700">🍱 Istirahat Makan / Sholat</span>
                    </label>
                    <label class="flex items-center gap-3 p-3 border-2 rounded-xl cursor-pointer transition-all"
                           :class="pauseReason === 'Ke Toilet' ? 'border-red-500 bg-red-50' : 'border-slate-100 hover:border-slate-300'">
                        <input type="radio" x-model="pauseReason" value="Ke Toilet" class="w-4 h-4 text-red-600 focus:ring-red-500">
                        <span class="text-xs font-black text-slate-700">🚽 Ke Toilet</span>
                    </label>
                    <label class="flex items-center gap-3 p-3 border-2 rounded-xl cursor-pointer transition-all"
                           :class="pauseReason === 'Mengambil Peralatan' ? 'border-red-500 bg-red-50' : 'border-slate-100 hover:border-slate-300'">
                        <input type="radio" x-model="pauseReason" value="Mengambil Peralatan" class="w-4 h-4 text-red-600 focus:ring-red-500">
                        <span class="text-xs font-black text-slate-700">🛠️ Mengambil Peralatan / Material</span>
                    </label>
                    <label class="flex items-center gap-3 p-3 border-2 rounded-xl cursor-pointer transition-all"
                           :class="pauseReason === 'Lainnya' ? 'border-red-500 bg-red-50' : 'border-slate-100 hover:border-slate-300'">
                        <input type="radio" x-model="pauseReason" value="Lainnya" class="w-4 h-4 text-red-600 focus:ring-red-500">
                        <span class="text-xs font-black text-slate-700">💬 Alasan Lainnya...</span>
                    </label>
                </div>
                
                <div x-show="pauseReason === 'Lainnya'" x-collapse>
                    <div class="mt-4">
                        <input type="text" x-model="customPauseReason" placeholder="Tuliskan alasan spesifik..."
                               class="w-full text-xs font-bold text-slate-700 bg-slate-50 border-2 border-slate-200 rounded-xl p-3 outline-none focus:bg-white focus:border-red-500 transition-all">
                    </div>
                </div>
            </div>
            <div class="bg-slate-50 p-6 flex gap-3">
                <button @click="showPauseModal = false; pauseReason = ''; customPauseReason = '';" 
                        class="flex-1 py-3.5 bg-white border-2 border-slate-200 rounded-2xl text-[10px] font-black text-slate-400 uppercase tracking-widest hover:bg-slate-100 transition-all">
                    Batal
                </button>
                <button @click="confirmPause()" 
                        class="flex-1 py-3.5 bg-red-600 rounded-2xl text-[10px] font-black text-white uppercase tracking-widest shadow-lg shadow-red-600/30 hover:bg-red-700 transition-all active:scale-95">
                    Mulai Jeda
                </button>
            </div>
        </div>
    </div>
    
    {{-- MODAL INPUT DIMENSI OPERATOR --}}
    <div x-show="showDimInputModal" x-cloak 
         class="fixed inset-0 z-[10001] flex items-center justify-center p-4 overflow-x-hidden overflow-y-auto">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="closeDimInput()"></div>
        <div class="relative bg-white rounded-[2.5rem] w-full max-w-4xl flex flex-col md:flex-row shadow-2xl overflow-hidden border border-slate-100 z-10"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-8 md:translate-y-0 md:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 md:scale-100">
            
            {{-- Bagian Kiri: Informasi, Standar & Aksi --}}
            <div class="w-full md:w-[340px] lg:w-[400px] shrink-0 flex flex-col bg-slate-50/30 md:border-r border-slate-100">
                {{-- Header dengan warna dinamis sesuai status OK/NG --}}
                <div class="px-6 pt-6 pb-4 border-b border-slate-100 transition-colors duration-300"
                     :class="{
                         'bg-red-50/90': getDimInputStatus() === 'ng',
                         'bg-emerald-50/95': getDimInputStatus() === 'ok',
                         'bg-slate-50/90': getDimInputStatus() === 'empty'
                     }">
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <h3 class="text-base font-black text-slate-800 uppercase tracking-wider">Input Dimensi</h3>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-0.5" x-text="'Item #' + (dimInputTargetRi + 1) + '  Sampel Kolom ' + dimInputTargetCol"></p>
                        </div>
                        <div class="px-3 py-1 text-[10px] font-black rounded-full transition-all duration-300"
                             :class="{
                                 'bg-red-600 text-white shadow-md shadow-red-200': getDimInputStatus() === 'ng',
                                 'bg-emerald-600 text-white shadow-md shadow-emerald-200': getDimInputStatus() === 'ok',
                                 'bg-slate-200 text-slate-500': getDimInputStatus() === 'empty'
                             }"
                             x-text="getDimInputStatus() === 'ng' ? 'NG ×' : (getDimInputStatus() === 'ok' ? 'OK ✓' : 'KOSONG')"></div>
                    </div>

                    {{-- Preview Standar Totok --}}
                    <div class="bg-white/80 backdrop-blur-sm rounded-2xl p-4 border border-slate-100/50 shadow-sm mt-4 flex items-center justify-between">
                        <div>
                            <span class="text-[8px] font-black text-slate-400 uppercase tracking-widest block mb-1">Standar Dimensi</span>
                            <span class="text-sm font-black text-slate-800" x-text="dimStd[dimInputTargetRi] ? getDimStandardText(dimInputTargetRi) : ''"></span>
                        </div>
                        <div class="flex gap-2">
                            <div class="px-2 py-1 bg-emerald-50 rounded-lg text-center">
                                <span class="text-[8px] font-black text-emerald-600 block uppercase">Toleransi (+)</span>
                                <span class="text-xs font-black text-emerald-700" x-text="getParsedDimStd(dimInputTargetRi)?.plus ? '+' + getParsedDimStd(dimInputTargetRi).plus : '0'"></span>
                            </div>
                            <div class="px-2 py-1 bg-rose-50 rounded-lg text-center">
                                <span class="text-[8px] font-black text-rose-600 block uppercase">Toleransi (-)</span>
                                <span class="text-xs font-black text-rose-700" x-text="getParsedDimStd(dimInputTargetRi)?.minus ? '-' + getParsedDimStd(dimInputTargetRi).minus : '0'"></span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Rentang Batas Mutlak Min - Max & Autofill --}}
                <div class="p-6 flex-1 flex flex-col justify-center space-y-4">
                    
                    {{-- Notice Kalibrasi / Filler Space --}}
                    <div class="flex-1 flex flex-col justify-center">
                        <div class="bg-blue-50/50 border border-blue-100 rounded-2xl p-4 flex items-start gap-3 shadow-sm">
                            <div class="bg-blue-100 p-2 rounded-xl text-blue-600 shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                            <div>
                                <h4 class="text-[10px] font-black text-blue-800 uppercase tracking-widest mb-0.5">Catatan Inspeksi</h4>
                                <p class="text-[10px] font-medium text-blue-600/80 leading-relaxed">
                                    Jika hasil aktual berada di luar batas toleransi (NG), part harus dipisahkan dan dilaporkan sebagai temuan (Problem Report).
                                </p>
                            </div>
                        </div>
                    </div>

                    <template x-if="getParsedDimStd(dimInputTargetRi) && getParsedDimStd(dimInputTargetRi).nominal !== null">
                        <div class="w-full bg-white border border-slate-100 rounded-2xl p-4 flex items-center justify-between text-xs font-bold text-slate-600 shadow-sm">
                            <div class="text-center">
                                <span class="text-[8px] font-black text-slate-400 uppercase tracking-wider block">BATAS MIN</span>
                                <span class="text-xs font-black text-rose-600" x-text="(parseFloat(getParsedDimStd(dimInputTargetRi).nominal) - (parseFloat(getParsedDimStd(dimInputTargetRi).minus) || 0)).toFixed(3) + ' ' + getDimUnit(dimInputTargetRi)"></span>
                            </div>
                            <div class="h-8 w-px bg-slate-200"></div>
                            <div class="text-center">
                                <span class="text-[8px] font-black text-slate-400 uppercase tracking-wider block">NOMINAL</span>
                                <span class="text-xs font-black text-slate-800" x-text="parseFloat(getParsedDimStd(dimInputTargetRi).nominal).toFixed(3) + ' ' + getDimUnit(dimInputTargetRi)"></span>
                            </div>
                            <div class="h-8 w-px bg-slate-200"></div>
                            <div class="text-center">
                                <span class="text-[8px] font-black text-slate-400 uppercase tracking-wider block">BATAS MAX</span>
                                <span class="text-xs font-black text-emerald-600" x-text="(parseFloat(getParsedDimStd(dimInputTargetRi).nominal) + (parseFloat(getParsedDimStd(dimInputTargetRi).plus) || 0)).toFixed(3) + ' ' + getDimUnit(dimInputTargetRi)"></span>
                            </div>
                        </div>
                    </template>

                    <template x-if="getParsedDimStd(dimInputTargetRi) && getParsedDimStd(dimInputTargetRi).nominal !== null">
                        <button type="button" 
                                @click="dimInputTemp = getParsedDimStd(dimInputTargetRi).nominal.toString()"
                                class="w-full py-3.5 bg-slate-100 border border-slate-200 hover:bg-slate-200 text-slate-600 rounded-2xl text-[10px] font-black uppercase tracking-widest transition-all">
                             Gunakan Nilai Standar (<span x-text="getDimPrefix(dimInputTargetRi)"></span><span x-text="getParsedDimStd(dimInputTargetRi).nominal"></span>)
                        </button>
                    </template>
                </div>

                {{-- Footer / Tombol Aksi --}}
                <div class="bg-slate-100/50 p-4 border-t border-slate-100 flex gap-3 mt-auto">
                    <button type="button" @click="closeDimInput()" 
                            class="flex-[0.5] py-3.5 bg-white border border-slate-200 rounded-xl text-[10px] font-black text-slate-400 uppercase tracking-widest hover:bg-slate-100 hover:text-slate-600 transition-all">
                        Batal
                    </button>
                    <button type="button" @click="saveDimInput()" 
                            class="flex-[1.5] py-3.5 rounded-xl text-[10px] font-black text-white uppercase tracking-widest shadow-lg transition-all active:scale-95"
                            :class="getDimInputStatus() === 'ng' ? 'bg-red-600 shadow-red-600/30 hover:bg-red-700' : 'bg-emerald-600 shadow-emerald-600/30 hover:bg-emerald-700'">
                        ✓ Simpan Hasil
                    </button>
                </div>
            </div>

            {{-- Bagian Kanan: Input Besar & Numpad --}}
            <div class="flex-1 p-8 flex flex-col justify-center bg-white">
                <div class="w-full text-center">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3 block">Ketik Hasil Ukur Aktual</label>
                    <div class="relative w-full flex items-center justify-center gap-3">
                        <button type="button" 
                                @click="let cv = parseFloat(dimInputTemp); if(isNaN(cv)) cv = parseFloat(getParsedDimStd(dimInputTargetRi)?.nominal) || 0; dimInputTemp = (Math.round((cv - 0.01) * 100) / 100).toString()"
                                class="w-16 h-20 flex-shrink-0 flex items-center justify-center bg-slate-100 hover:bg-rose-100 border-4 border-slate-200 hover:border-rose-300 rounded-[1.25rem] text-4xl font-black text-slate-500 hover:text-rose-600 transition-all active:scale-95 select-none shadow-sm pb-2">-</button>
                        
                        <input type="text" 
                               readonly
                               x-model="dimInputTemp" 
                               placeholder="0.00" 
                               class="w-full max-w-[200px] px-4 py-4 text-[42px] leading-none font-black text-center rounded-[1.25rem] border-4 transition-all outline-none min-w-0 shadow-inner cursor-default"
                               :class="{
                                   'bg-red-50 border-red-500 text-red-700 focus:border-red-600': getDimInputStatus() === 'ng',
                                   'bg-emerald-50 border-emerald-500 text-emerald-700 focus:border-emerald-600': getDimInputStatus() === 'ok',
                                   'bg-slate-50 border-slate-200 text-slate-800 focus:bg-white focus:border-red-500': getDimInputStatus() === 'empty'
                               }">

                        <button type="button" 
                                @click="let cv = parseFloat(dimInputTemp); if(isNaN(cv)) cv = parseFloat(getParsedDimStd(dimInputTargetRi)?.nominal) || 0; dimInputTemp = (Math.round((cv + 0.01) * 100) / 100).toString()"
                                class="w-16 h-20 flex-shrink-0 flex items-center justify-center bg-slate-100 hover:bg-emerald-100 border-4 border-slate-200 hover:border-emerald-300 rounded-[1.25rem] text-4xl font-black text-slate-500 hover:text-emerald-600 transition-all active:scale-95 select-none shadow-sm pb-1">+</button>
                    </div>
                </div>

                {{-- Virtual Numpad (Agar Keyboard Bawaan Tablet Tidak Muncul) --}}
                <div class="w-full max-w-[280px] mx-auto grid grid-cols-3 gap-3 mt-8">
                    <template x-for="n in [1, 2, 3, 4, 5, 6, 7, 8, 9]" :key="n">
                        <button type="button" @click="dimInputTemp = (dimInputTemp === '0' ? '' : dimInputTemp || '') + n"
                                class="py-4 bg-white border-2 border-slate-200 rounded-[1.25rem] text-xl font-black text-slate-700 hover:bg-slate-50 hover:border-slate-300 active:scale-95 transition-all shadow-sm">
                            <span x-text="n"></span>
                        </button>
                    </template>
                    <button type="button" @click="if(!(dimInputTemp||'').includes('.')) dimInputTemp = (dimInputTemp || '0') + '.'"
                            class="py-4 bg-slate-100 border-2 border-slate-200 rounded-[1.25rem] text-xl font-black text-slate-700 hover:bg-slate-200 active:scale-95 transition-all shadow-sm">
                        .
                    </button>
                    <button type="button" @click="dimInputTemp = (dimInputTemp === '0' ? '' : dimInputTemp || '') + '0'"
                            class="py-4 bg-white border-2 border-slate-200 rounded-[1.25rem] text-xl font-black text-slate-700 hover:bg-slate-50 hover:border-slate-300 active:scale-95 transition-all shadow-sm">
                        0
                    </button>
                    <button type="button" @click="dimInputTemp = (dimInputTemp || '').slice(0, -1)"
                            class="py-4 bg-rose-50 border-2 border-rose-200 rounded-[1.25rem] text-[13px] font-black text-rose-600 hover:bg-rose-100 active:scale-95 transition-all shadow-sm flex items-center justify-center">
                        DEL
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL PANGGILAN KELUAR (OPERATOR DIALER SCREEN) --}}
    <div x-show="showIntercomModal" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-950/85 backdrop-blur-md"
         style="display: none;">
        
        <div class="bg-slate-900 border border-slate-800 rounded-[24px] w-full max-w-[280px] sm:max-w-xs overflow-hidden shadow-2xl relative p-6 text-center text-white">
            {{-- Glowing Background Effects --}}
            <div class="absolute -top-20 -left-20 w-40 h-40 bg-red-600/10 rounded-full blur-3xl pointer-events-none animate-pulse"></div>
            <div class="absolute -bottom-20 -right-20 w-40 h-40 bg-indigo-600/10 rounded-full blur-3xl pointer-events-none animate-pulse"></div>

            {{-- Avatar & Call Waves --}}
            <div class="relative flex items-center justify-center my-1.5">
                <!-- Pulse rings -->
                <div class="absolute w-16 h-16 border border-red-500/30 rounded-full animate-ping duration-[3000ms]"></div>
                <div class="absolute w-12 h-12 border border-red-500/50 rounded-full animate-ping"></div>
                
                <!-- Avatar circle -->
                <div class="w-10 h-10 bg-gradient-to-tr from-red-600 to-rose-500 rounded-full flex items-center justify-center shadow-lg shadow-red-500/30 relative z-10">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-white animate-bounce" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                    </svg>
                </div>
            </div>

            {{-- Title & Status --}}
            <h3 class="text-[9px] font-black text-red-500 uppercase tracking-[2px] animate-pulse">PANGGILAN INTERKOM</h3>
            <div class="text-[13px] font-black text-slate-100 animate-pulse mt-0.5" x-text="intercomCall?.status === 'calling_gl' ? 'Memanggil GL...' : (intercomCall?.status === 'calling_foreman' ? 'Memanggil Foreman...' : 'Terhubung')"></div>
            <p class="text-[8px] font-bold text-slate-400 uppercase tracking-wider mt-1">
                Line: <span x-text="lokasi || '—'"></span> " Part: <span class="truncate max-w-[120px] inline-block align-bottom" x-text="partName || '—'"></span>
            </p>

            {{-- Call State Content --}}
            <div class="my-3 py-2 border-t border-b border-slate-800 bg-slate-950/40 rounded-xl">
                <!-- Calling / Ringing Status -->
                <template x-if="intercomCall && (intercomCall.status === 'calling_gl' || intercomCall.status === 'calling_foreman')">
                    <div>
                        <div class="text-xs font-black text-rose-400 uppercase tracking-widest flex items-center justify-center gap-1.5 animate-pulse">
                            <span class="w-2 h-2 bg-rose-500 rounded-full animate-ping"></span>
                            <span>Menunggu Jawaban...</span>
                        </div>
                        <p class="text-[9px] text-slate-500 font-bold mt-1">GL/Foreman sedang berdering di kantor</p>
                    </div>
                </template>

                <!-- Answered / Connected Status -->
                <template x-if="intercomCall && intercomCall.status === 'answered'">
                    <div class="px-2">
                        <div class="text-xs font-black text-emerald-500 uppercase tracking-widest flex items-center justify-center gap-1.5 animate-bounce mb-2">
                            <span>✓ PANGGILAN DIJAWAB!</span>
                        </div>
                        
                        <!-- Pager Quick Message bubble -->
                        <div class="relative bg-emerald-500/10 border border-emerald-500/20 rounded-2xl p-4 mt-2 shadow-inner">
                            <div class="absolute -top-2 left-6 w-4 h-4 bg-slate-900 border-t border-l border-emerald-500/20 rotate-45"></div>
                            <span class="text-[8px] font-black text-emerald-500 tracking-wider block text-left mb-1">RESPON GL/FOREMAN:</span>
                            <p class="text-[11px] font-black text-slate-200 text-left italic leading-relaxed" x-text="'' + intercomCall.response_msg + '>'"></p>
                        </div>
                    </div>
                </template>

                <!-- Declined / Busy Status -->
                <template x-if="intercomCall && intercomCall.status === 'declined'">
                    <div>
                        <div class="text-xs font-black text-rose-500 uppercase tracking-widest flex items-center justify-center gap-1.5 mb-1">
                            <span>× GL/FOREMAN SIBUK</span>
                        </div>
                        <p class="text-[10px] text-slate-400 font-bold leading-relaxed px-4">Panggilan ditolak atau ditandai sedang sibuk. Silakan laporkan ulang atau hubungi langsung.</p>
                    </div>
                </template>
            </div>

            {{-- Action Buttons --}}
            <div class="flex flex-col gap-1.5">
                <!-- Cancel Button for active call or declined call -->
                <template x-if="intercomCall && (intercomCall.status === 'calling_gl' || intercomCall.status === 'calling_foreman' || intercomCall.status === 'declined')">
                    <button type="button" @click="cancelIntercomCall()"
                            class="w-full py-2.5 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-[10px] font-black uppercase tracking-widest shadow-sm transition-all active:scale-95 flex items-center justify-center gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 8l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2M5 3a2 2 0 00-2 2v2a2 2 0 00.224.912l1.625 3.25a2 2 0 002.329 1.053l2.871-.957a2 2 0 011.666.307l2.88 1.92a2 2 0 01.912 1.667v2.88a2 2 0 01-1.667 1.912l-2.88-.96a2 2 0 00-2.329 1.053l-1.625 3.25A2 2 0 015 21v-2a2 2 0 00-2-2v-2" />
                        </svg>
                        Tutup & Batal
                    </button>
                </template>

                {{-- "GL SUDAH DI SINI" — Physical check-in button (shown when GL is 'dalam perjalanan') --}}
                {{-- GL/Foreman yang baru tiba menekan tombol ini di tablet operator --}}
                <template x-if="intercomCall && intercomCall.status === 'answered'">
                    <div class="flex flex-col gap-1.5 mt-1 pt-2 border-t border-slate-800">
                        <p class="text-[7px] font-black text-slate-500 uppercase tracking-widest text-center">GL / Foreman yang sudah tiba, tekan tombol ini:</p>
                        <button type="button" @click="arriveAtLine()"
                                class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-500 active:scale-95 text-white rounded-xl text-[11px] font-black uppercase tracking-widest shadow-sm transition-all flex items-center justify-center gap-2 animate-pulse">
                            <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                             Saya Sudah Di Sini
                        </button>
                        <p class="text-[8px] text-slate-600 text-center">Tombol ini akan mematikan alarm di perangkat GL/Foreman.</p>
                        <button type="button" @click="cancelIntercomCall()"
                                class="w-full py-3 bg-slate-800 hover:bg-slate-700 text-slate-400 rounded-xl text-[9px] font-bold transition-all active:scale-95">
                            Tutup Tanpa Check-in
                        </button>
                    </div>
                </template>

                <!-- Close Button for declined calls -->
                <template x-if="intercomCall && intercomCall.status === 'declined'">
                    <button type="button" @click="cancelIntercomCall()"
                            class="w-full py-4 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-lg transition-all active:scale-95 flex items-center justify-center gap-2">
                        × Tutup Layar
                    </button>
                </template>
            </div>
        </div>
    </div>

    {{-- CHOICES MODAL (SELECT INITIAL SKETCH FLOW) --}}
    <div x-show="showSketchChoiceModal" x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="fixed inset-0 z-[250] flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md">
        
        <div class="bg-slate-900 border border-slate-800 rounded-3xl w-full max-w-lg overflow-hidden shadow-2xl relative p-6 font-sans">
            {{-- Header --}}
            <div class="text-center mb-6">
                <h3 class="text-sm font-bold text-white uppercase tracking-wider">Mulai Menggambar Sketch</h3>
                <p class="text-xs text-slate-400 mt-1">Pilih bagaimana Anda ingin memulai lembar gambar kerja</p>
            </div>

            {{-- Options List --}}
            <div class="space-y-3">
                <!-- Option 1: Upload Image (langsung simpan, tanpa editor) -->
                <button type="button" @click="sketchSource = 'upload'; $refs.sketchInput.click(); showSketchChoiceModal = false;"
                        class="w-full p-4 bg-slate-950/50 hover:bg-slate-800 border border-slate-800 hover:border-indigo-500 rounded-2xl text-left transition-all flex items-center gap-4 group active:scale-[0.98]">
                    <div class="w-12 h-12 bg-indigo-500/10 text-indigo-400 rounded-xl flex items-center justify-center shrink-0 group-hover:bg-indigo-600 group-hover:text-white transition-all">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                    <div class="flex-1">
                        <h4 class="text-xs font-bold text-slate-200 group-hover:text-white transition-colors uppercase tracking-wider">Opsi 1: Upload Gambar</h4>
                        <p class="text-[10px] text-slate-400 mt-0.5">Unggah foto / blueprint part (.png, .jpg) langsung sebagai sketch referensi</p>
                    </div>
                </button>

                <!-- Option 2: Blank A4 Landscape (dengan SVG editor) -->
                <button type="button" @click="sketchSource = 'blank'; openBlankSketchEditor('blank_a4_landscape')"
                        class="w-full p-4 bg-slate-950/50 hover:bg-slate-800 border border-slate-800 hover:border-emerald-500 rounded-2xl text-left transition-all flex items-center gap-4 group active:scale-[0.98]">
                    <div class="w-12 h-12 bg-emerald-500/10 text-emerald-400 rounded-xl flex items-center justify-center shrink-0 group-hover:bg-emerald-600 group-hover:text-white transition-all">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                    </div>
                    <div class="flex-1">
                        <h4 class="text-xs font-bold text-slate-200 group-hover:text-white transition-colors uppercase tracking-wider">Opsi 2: Gambar + Tandai Zona (Kanvas A4)</h4>
                        <p class="text-[10px] text-slate-400 mt-0.5">Buka editor untuk menggambar sketsa & menandai zona defect secara bebas</p>
                    </div>
                </button>
            </div>

            {{-- Footer Controls --}}
            <div class="mt-6 pt-4 border-t border-slate-800 flex justify-end">
                <button type="button" @click="showSketchChoiceModal = false"
                        class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-xs font-bold text-slate-300 rounded-xl transition-colors">
                    Batal
                </button>
            </div>
        </div>
    </div>

    {{-- MODAL SKETCH EDITOR (SVG BASED) --}}
    <div x-show="showSketchEditor" x-cloak
         class="fixed inset-0 z-[200] flex items-center justify-center bg-slate-900/90 backdrop-blur-sm p-4 text-slate-100">
        <div class="bg-slate-900 rounded-2xl border border-slate-700 w-full max-w-6xl h-[90vh] flex flex-col overflow-hidden shadow-2xl relative font-sans">
            
            {{-- Header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-800 bg-slate-900/50 backdrop-blur shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-indigo-500/20 text-indigo-400 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                    </div>
                    <div>
                        <h2 class="text-sm font-semibold text-white tracking-wide cursor-default select-none">Visual Defect Mapping</h2>
                        <p class="text-xs text-slate-400 mt-0.5">Edit dan beri penanda (SVG Editor)</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <button type="button" @click="closeSketchEditor()" class="px-5 py-2.5 text-sm font-medium text-slate-300 hover:text-white hover:bg-slate-800 rounded-xl transition-colors">Batal</button>
                    <button type="button" @click="saveSketchAnnotation()" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white shadow-lg shadow-indigo-500/20 rounded-xl text-sm font-medium transition-all flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
                        Simpan Perubahan
                    </button>
                </div>
            </div>

            {{-- Editor Body --}}
            <div class="flex-1 flex min-h-0 relative">
                
                {{-- Canvas Area (Dot Grid) --}}
                <div class="flex-1 relative overflow-hidden" style="background-color: #0f172a; background-image: radial-gradient(#334155 1px, transparent 1px); background-size: 20px 20px;">
                    
                    {{-- SVG Canvas --}}
                    <svg ref="svgCanvas" x-ref="svgCanvas"
                         viewBox="0 0 900 580"
                         preserveAspectRatio="xMidYMid meet"
                         class="w-full h-full cursor-default select-none"
                         @mousedown="handleSvgMouseDown($event)"
                         @mousemove="handleSvgMouseMove($event)"
                         @mouseup="handleSvgMouseUp($event)"
                         @mouseleave="handleSvgMouseUp($event)"
                         @touchstart.prevent="handleSvgMouseDown($event)"
                         @touchmove.prevent="handleSvgMouseMove($event)"
                         @touchend.prevent="handleSvgMouseUp($event)">
                        
                        <g x-html="svgRenderAll()"></g>
                    </svg>

                    {{-- Invisible textarea for multiline text editing --}}
                    <textarea id="svgTextInput"
                           x-show="svgEditingText"
                           x-model="svgTextVal"
                           @input="svgUpdateSelProp('label', svgTextVal)"
                           @keydown.escape="svgEditingText = null"
                           @keydown.enter.stop=""
                           rows="1"
                           class="absolute opacity-0 pointer-events-none resize-none"
                           style="top:0;left:0;width:1px;height:1px;"></textarea>

                </div>

                {{-- Toolbar Right --}}
                <div class="w-64 bg-slate-900 border-l border-slate-800 flex flex-col shrink-0 relative z-10">
                    
                    {{-- Tools Grid --}}
                    <div class="p-4 border-b border-slate-800">
                        <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">Tools</h3>
                        <div class="grid grid-cols-4 gap-2">
                            {{-- Select --}}
                            <button type="button" @click="svgSetTool('select')" title="Pilih"
                                    :class="svgTool === 'select' ? 'bg-indigo-600 text-white' : 'bg-slate-800 text-slate-400 hover:bg-slate-700 hover:text-slate-200'"
                                    class="h-10 rounded-lg flex items-center justify-center transition-all">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5"/></svg>
                            </button>
                            {{-- Rect --}}
                            <button type="button" @click="svgSetTool('rect')" title="Kotak"
                                    :class="svgTool === 'rect' ? 'bg-indigo-600 text-white' : 'bg-slate-800 text-slate-400 hover:bg-slate-700 hover:text-slate-200'"
                                    class="h-10 rounded-lg flex items-center justify-center transition-all">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" stroke-width="2"/></svg>
                            </button>
                            {{-- Circle --}}
                            <button type="button" @click="svgSetTool('circle')" title="Lingkaran"
                                    :class="svgTool === 'circle' ? 'bg-indigo-600 text-white' : 'bg-slate-800 text-slate-400 hover:bg-slate-700 hover:text-slate-200'"
                                    class="h-10 rounded-lg flex items-center justify-center transition-all">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9" stroke-width="2"/></svg>
                            </button>
                            {{-- Zone --}}
                            <button type="button" @click="svgSetTool('zone')" title="Zona"
                                    :class="svgTool === 'zone' ? 'bg-indigo-600 text-white' : 'bg-slate-800 text-slate-400 hover:bg-slate-700 hover:text-slate-200'"
                                    class="h-10 rounded-lg flex items-center justify-center transition-all">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" stroke-width="2" stroke-dasharray="4 2"/><circle cx="7" cy="7" r="2" fill="currentColor"/></svg>
                            </button>
                            {{-- Text --}}
                            <button type="button" @click="svgSetTool('text')" title="Teks"
                                    :class="svgTool === 'text' ? 'bg-indigo-600 text-white' : 'bg-slate-800 text-slate-400 hover:bg-slate-700 hover:text-slate-200'"
                                    class="h-10 rounded-lg flex items-center justify-center transition-all">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 6h10M12 6v12"/></svg>
                            </button>
                            {{-- Line --}}
                            <button type="button" @click="svgSetTool('line')" title="Garis"
                                    :class="svgTool === 'line' ? 'bg-indigo-600 text-white' : 'bg-slate-800 text-slate-400 hover:bg-slate-700 hover:text-slate-200'"
                                    class="h-10 rounded-lg flex items-center justify-center transition-all">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M5 19L19 5"/></svg>
                            </button>
                            {{-- Arrow --}}
                            <button type="button" @click="svgSetTool('arrow')" title="Panah"
                                    :class="svgTool === 'arrow' ? 'bg-indigo-600 text-white' : 'bg-slate-800 text-slate-400 hover:bg-slate-700 hover:text-slate-200'"
                                    class="h-10 rounded-lg flex items-center justify-center transition-all">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                            </button>
                            {{-- Delete --}}
                            <button type="button" @click="svgDeleteSelected()" title="Hapus Terpilih"
                                    class="h-10 rounded-lg flex items-center justify-center transition-all bg-slate-800 text-slate-400 hover:bg-red-900/60 hover:text-red-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </div>
                    </div>
                    
                    {{-- Colors --}}
                    <div class="p-4 border-b border-slate-800">
                        <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">Color</h3>
                        <div class="flex flex-wrap gap-2">
                            <template x-for="c in ['#ef4444', '#f97316', '#eab308', '#22c55e', '#3b82f6', '#a855f7', '#ec4899', '#ffffff', '#000000']">
                                <button type="button" @click="svgColor = c; svgUpdateSelProp('color', c)"
                                        class="w-6 h-6 rounded-full border-2 transition-transform hover:scale-110"
                                        :class="svgColor === c ? 'border-white scale-110' : 'border-transparent'"
                                        :style="'background-color: ' + c"></button>
                            </template>
                        </div>
                    </div>
                    
                    {{-- Selected Properties --}}
                    <div class="p-4 flex-1 overflow-y-auto" x-show="svgSelected">
                        <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">Properties</h3>
                        <div class="space-y-4">
                            <template x-if="svgGetSelShape()?.type === 'text'">
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-2">Edit Teks</label>
                                        <textarea :value="svgGetSelShape()?.label" @input="svgUpdateSelProp('label', $event.target.value)" rows="3" class="w-full bg-slate-950 border border-slate-800 focus:border-indigo-500 rounded-xl px-3 py-2.5 text-sm text-white focus:ring-1 focus:ring-indigo-500 resize-none transition-all placeholder-slate-600" placeholder="Ketik teks di sini..."></textarea>
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-2">Ukuran Font</label>
                                        <div class="flex items-center gap-3 bg-slate-950 p-2.5 rounded-xl border border-slate-800">
                                            <input type="range" min="8" max="72" :value="svgGetSelShape()?.fontSize || 14" @input="svgUpdateSelProp('fontSize', parseInt($event.target.value))" class="flex-1 accent-indigo-500 bg-slate-800 h-1.5 rounded-lg appearance-none cursor-pointer" />
                                            <span class="text-xs font-mono text-slate-300 w-10 text-right shrink-0" x-text="(svgGetSelShape()?.fontSize || 14) + 'px'"></span>
                                        </div>
                                    </div>
                                </div>
                            </template>
                            
                            <template x-if="svgGetSelShape()?.type === 'image' || svgGetSelShape()?.type === 'rect' || svgGetSelShape()?.type === 'zone'">
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="block text-xs text-slate-400 mb-1">Lebar</label>
                                        <input type="number" :value="svgGetSelShape()?.w" @input="svgUpdateSelProp('w', parseInt($event.target.value) || 0)" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-1.5 text-sm text-white" />
                                    </div>
                                    <div>
                                        <label class="block text-xs text-slate-400 mb-1">Tinggi</label>
                                        <input type="number" :value="svgGetSelShape()?.h" @input="svgUpdateSelProp('h', parseInt($event.target.value) || 0)" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-1.5 text-sm text-white" />
                                    </div>
                                </div>
                            </template>

                            <template x-if="svgGetSelShape()?.type === 'image'">
                                <div>
                                    <button type="button" @click="svgRotateImage()" class="w-full flex items-center justify-center gap-2 bg-slate-800 hover:bg-slate-700 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                        Putar Gambar 90
                                    </button>
                                </div>
                            </template>

                            <template x-if="svgGetSelShape()?.type === 'line' || svgGetSelShape()?.type === 'arrow'">
                                <div class="space-y-4">
                                    {{-- Connection Type --}}
                                    <div>
                                        <label class="block text-[11px] font-medium text-slate-400 uppercase tracking-wider mb-2">Tipe Koneksi</label>
                                        <div class="grid grid-cols-3 gap-1 bg-slate-950 p-1 rounded-xl">
                                            <button type="button" @click="svgUpdateSelProp('connectionType', 'straight')"
                                                    class="py-1.5 text-xs font-semibold rounded-lg transition-all flex flex-col items-center justify-center gap-1"
                                                    :class="(svgGetSelShape()?.connectionType || 'straight') === 'straight' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900'">
                                                <span class="text-sm">q</span>
                                                <span class="text-[9px]">Lurus</span>
                                            </button>
                                            <button type="button" @click="svgUpdateSelProp('connectionType', 'orthogonal')"
                                                    class="py-1.5 text-xs font-semibold rounded-lg transition-all flex flex-col items-center justify-center gap-1"
                                                    :class="svgGetSelShape()?.connectionType === 'orthogonal' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900'">
                                                <span class="text-sm"></span>
                                                <span class="text-[9px]">Siku</span>
                                            </button>
                                            <button type="button" @click="svgUpdateSelProp('connectionType', 'curved')"
                                                    class="py-1.5 text-xs font-semibold rounded-lg transition-all flex flex-col items-center justify-center gap-1"
                                                    :class="svgGetSelShape()?.connectionType === 'curved' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900'">
                                                <span class="text-sm">?</span>
                                                <span class="text-[9px]">Lengkung</span>
                                            </button>
                                        </div>
                                    </div>

                                    {{-- Line Style --}}
                                    <div>
                                        <label class="block text-[11px] font-medium text-slate-400 uppercase tracking-wider mb-2">Gaya Garis</label>
                                        <div class="grid grid-cols-3 gap-1 bg-slate-950 p-1 rounded-xl">
                                            <button type="button" @click="svgUpdateSelProp('dashStyle', 'solid')"
                                                    class="py-1.5 text-xs font-semibold rounded-lg transition-all flex flex-col items-center justify-center gap-1"
                                                    :class="(svgGetSelShape()?.dashStyle || 'solid') === 'solid' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900'">
                                                <span class="w-8 h-[3px] bg-current rounded"></span>
                                                <span class="text-[9px]">Solid</span>
                                            </button>
                                            <button type="button" @click="svgUpdateSelProp('dashStyle', 'dashed')"
                                                    class="py-1.5 text-xs font-semibold rounded-lg transition-all flex flex-col items-center justify-center gap-1"
                                                    :class="svgGetSelShape()?.dashStyle === 'dashed' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900'">
                                                <div class="flex gap-0.5">
                                                    <span class="w-2 h-[3px] bg-current rounded-sm"></span>
                                                    <span class="w-2 h-[3px] bg-current rounded-sm"></span>
                                                    <span class="w-2 h-[3px] bg-current rounded-sm"></span>
                                                </div>
                                                <span class="text-[9px]">Putus</span>
                                            </button>
                                            <button type="button" @click="svgUpdateSelProp('dashStyle', 'dotted')"
                                                    class="py-1.5 text-xs font-semibold rounded-lg transition-all flex flex-col items-center justify-center gap-1"
                                                    :class="svgGetSelShape()?.dashStyle === 'dotted' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900'">
                                                <div class="flex gap-0.5">
                                                    <span class="w-[3px] h-[3px] bg-current rounded-full"></span>
                                                    <span class="w-[3px] h-[3px] bg-current rounded-full"></span>
                                                    <span class="w-[3px] h-[3px] bg-current rounded-full"></span>
                                                </div>
                                                <span class="text-[9px]">Titik</span>
                                            </button>
                                        </div>
                                    </div>

                                    {{-- Arrowheads --}}
                                    <div>
                                        <label class="block text-[11px] font-medium text-slate-400 uppercase tracking-wider mb-2">Ujung Panah</label>
                                        <div class="grid grid-cols-2 gap-2">
                                            <button type="button" @click="svgUpdateSelProp('arrowStart', !svgGetSelShape()?.arrowStart)"
                                                    class="py-2 text-xs font-semibold rounded-xl transition-all border flex items-center justify-center gap-2"
                                                    :class="svgGetSelShape()?.arrowStart ? 'bg-indigo-600/10 border-indigo-500 text-indigo-400' : 'border-slate-800 text-slate-400 hover:bg-slate-950'">
                                                <span> Panah Kiri</span>
                                            </button>
                                            <button type="button" @click="svgUpdateSelProp('arrowEnd', !svgGetSelShape()?.arrowEnd)"
                                                    class="py-2 text-xs font-semibold rounded-xl transition-all border flex items-center justify-center gap-2"
                                                    :class="svgGetSelShape()?.arrowEnd ? 'bg-indigo-600/10 border-indigo-500 text-indigo-400' : 'border-slate-800 text-slate-400 hover:bg-slate-950'">
                                                <span>Panah Kanan </span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                    
                    {{-- Uploads --}}
                    <div class="p-4 border-t border-slate-800 space-y-2">
                        <button type="button" @click="$refs.svgBgInput.click()" class="w-full py-2 bg-slate-800 hover:bg-slate-700 text-xs font-medium text-slate-300 rounded-lg transition-colors flex items-center justify-center gap-2">
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            Set Background
                        </button>
                        <input type="file" x-ref="svgBgInput" class="hidden" accept="image/*" @change="handleSvgBgUpload($event)">
                        
                        <button type="button" @click="$refs.svgImgInput.click()" class="w-full py-2 bg-slate-800 hover:bg-slate-700 text-xs font-medium text-slate-300 rounded-lg transition-colors flex items-center justify-center gap-2">
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            Insert Image
                        </button>
                        <input type="file" x-ref="svgImgInput" class="hidden" accept="image/*" @change="handleSvgImageUpload($event)">
                        
                        <button type="button" @click="svgClearAll()" class="w-full py-2 mt-2 bg-red-900/30 hover:bg-red-900/50 text-xs font-medium text-red-400 rounded-lg transition-colors">
                            Hapus Semua Kanvas
                        </button>
                    </div>

                </div>
            </div>
        </div>
    </div>
    
    {{-- PPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPP --}}
    {{-- PANEL GL/FOREMAN TIBA DI TABLET OPERATOR                          --}}
    {{-- Muncul setelah GL datang ke lapangan dan klik "Saya Sudah Di Sini" --}}
    {{-- PPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPP --}}
    <div x-show="glArrivedData" x-cloak style="display:none;"
         class="fixed inset-x-0 top-0 z-[9998] p-3 sm:p-4">
        <div class="max-w-lg mx-auto bg-emerald-600 border border-emerald-500 rounded-2xl p-4 flex items-center gap-4 shadow-2xl shadow-emerald-700/40">
            <div class="w-12 h-12 bg-emerald-500 rounded-xl flex items-center justify-center flex-shrink-0 text-2xl"></div>
            <div class="flex-1 min-w-0">
                <p class="text-xs font-black text-white uppercase tracking-widest">GL / Foreman Telah Tiba</p>
                <p class="text-[11px] text-emerald-100 font-bold mt-0.5">
                    <span class="text-white font-black" x-text="glArrivedData?.arrived_name || '—'"></span>
                    sudah check-in di tablet ini pukul
                    <span class="text-white font-black" x-text="glArrivedData?.arrived_at ? new Date(glArrivedData.arrived_at).toLocaleTimeString('id-ID', {hour:'2-digit', minute:'2-digit'}) : '—'"></span>
                </p>
            </div>
            <button @click="glArrivedData = null" class="text-emerald-200 hover:text-white p-1">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    </div>

    {{-- PPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPP --}}
    {{-- MODAL PANGGILAN MASUK — Di Tablet Operator                         --}}
    {{-- GL/Foreman pakai tablet ini untuk check-in fisik (klik "Di Sini") --}}
    {{-- PPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPP --}}
    <div x-show="showIncomingModal"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-red-950/90 backdrop-blur-md"
         style="display: none;">

        <div class="bg-slate-900 border-2 border-red-500 rounded-[24px] w-full max-w-[280px] sm:max-w-[320px] overflow-hidden shadow-2xl relative p-5 text-center text-white">
            {{-- Siren Blinking Ring Effects --}}
            <div class="absolute inset-0 bg-red-600/5 animate-pulse rounded-[24px] pointer-events-none"></div>

            {{-- Icon --}}
            <div class="relative flex items-center justify-center my-1.5">
                <div class="absolute w-12 h-12 bg-red-500/20 rounded-full animate-ping"></div>
                <div class="w-10 h-10 bg-red-600 rounded-full flex items-center justify-center shadow-lg shadow-red-500/40 relative z-10 animate-bounce">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                </div>
            </div>

            <h3 class="text-[9px] font-black text-red-500 uppercase tracking-[2px] animate-pulse">PANGGILAN DARURAT</h3>
            <div class="text-[13px] font-black text-slate-100 mt-0.5 leading-tight">BUTUH BANTUAN!</div>

            {{-- Document Context --}}
            <div class="my-2 p-2 bg-slate-950/60 border border-slate-800 rounded-xl text-left text-[9px] font-bold space-y-1">
                <div class="flex justify-between border-b border-slate-900 pb-1">
                    <span class="text-slate-500 uppercase tracking-widest text-[8px]">LINE / LOKASI:</span>
                    <span class="text-red-400 uppercase tracking-widest font-black" x-text="incomingCall?.lembar_inspeksi?.lokasi || '—'"></span>
                </div>
                <div class="flex justify-between border-b border-slate-900 pb-1">
                    <span class="text-slate-500 uppercase tracking-widest text-[8px]">PART NAME:</span>
                    <span class="text-slate-200 animate-pulse truncate max-w-[120px] text-right" x-text="incomingCall?.lembar_inspeksi?.part_name || '—'"></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500 uppercase tracking-widest text-[8px]">JOB NO:</span>
                    <span class="text-slate-200" x-text="incomingCall?.lembar_inspeksi?.job_no || '—'"></span>
                </div>
            </div>

            {{-- Status badge: Dalam Perjalanan jika sudah direspons --}}
            <template x-if="incomingCall?.status === 'answered'">
                <div class="mb-2 py-1 px-2 bg-emerald-600/20 border border-emerald-500/40 rounded-lg text-emerald-400 text-[8px] font-black uppercase tracking-widest animate-pulse">
                    < <span x-text="incomingCall?.responder_name || 'GL/Foreman'"></span> Dalam Perjalanan...
                </div>
            </template>

            {{--  TOMBOL UTAMA: "Saya Sudah Di Sini"  --}}
            {{-- GL/Foreman yang memegang tablet ini menekan tombol ini untuk check-in fisik --}}
            <div class="mt-2 pt-2 border-t border-slate-800">
                <p class="text-[7px] text-slate-500 font-bold uppercase tracking-widest mb-1.5">GL / Foreman yang sudah tiba di sini:</p>
                <button type="button" @click="arriveAtLine()"
                        class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-500 active:scale-95 text-white rounded-xl text-[10px] font-black uppercase tracking-widest shadow-sm transition-all flex items-center justify-center gap-1.5">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                     Saya Sudah Di Sini
                </button>
                <p class="text-[8px] text-slate-600 mt-1.5 leading-tight">Menekan tombol ini akan mematikan notifikasi alarm.</p>
            </div>
        </div>
    </div> <!-- END showIncomingModal -->
        
    {{-- FLOATING LIVE TIMER WIDGET (PREMIUM DUAL-TIMER - WHITE THEME) --}}
        <template x-if="waktuMulai && !waktuSelesai && samplingProgressData">
            <div class="fixed bottom-6 z-40 animate-[slideUp_0.5s_ease-out] transition-all duration-300"
                 :class="sidebarOpen ? 'left-4 md:left-72' : 'left-4 lg:left-28'">
                <!-- Outer glow and glassmorphism base -->
                <div class="relative group cursor-default">
                    <div class="absolute -inset-1 bg-gradient-to-r from-blue-400 to-rose-400 rounded-[1.5rem] blur opacity-20 group-hover:opacity-30 transition duration-500"></div>
                    <div class="relative bg-white/95 backdrop-blur-xl border border-slate-200 shadow-xl shadow-slate-200/50 rounded-[1.5rem] p-4 flex flex-col min-w-[220px] overflow-hidden">
                        
                        <!-- Main Total Time -->
                        <div class="text-center mb-3">
                            <p class="text-[8px] font-black text-slate-500 uppercase tracking-[0.2em] mb-0.5">Total Waktu Aktual</p>
                            <div class="text-[28px] font-black text-slate-800 tracking-widest font-mono leading-none" style="font-feature-settings: 'tnum';" x-text="liveTimerDisplay"></div>
                        </div>

                        <!-- Divider -->
                        <div class="h-px w-full bg-slate-100 mb-3"></div>

                        <!-- Current Sample Info -->
                        <div class="flex justify-between items-end mb-1.5 gap-4">
                            <div class="flex flex-col">
                                <span class="text-[9px] font-bold text-slate-500 uppercase tracking-widest" x-text="`SAMPEL ${samplingProgressData.sampelNo}`"></span>
                                <span class="text-[11px] font-black text-blue-600 uppercase tracking-wider" x-text="`KOLOM ${samplingProgressData.kolom}`"></span>
                            </div>
                            <!-- Countdown or Overdue -->
                            <div class="text-right">
                                <template x-if="!samplingProgressData.isOverdue">
                                    <span class="text-[14px] font-black text-blue-600 font-mono" x-text="samplingProgressData.remainingText"></span>
                                </template>
                                <template x-if="samplingProgressData.isOverdue">
                                    <span class="text-[13px] font-black text-rose-600 font-mono animate-pulse whitespace-nowrap" x-text="samplingProgressData.remainingText"></span>
                                </template>
                            </div>
                        </div>

                        <!-- Progress Bar -->
                        <div class="h-1.5 w-full bg-slate-100 rounded-full overflow-hidden relative shadow-inner">
                            <template x-if="!samplingProgressData.isOverdue">
                                <div class="absolute top-0 left-0 h-full bg-gradient-to-r from-blue-600 to-blue-400 rounded-full transition-all duration-1000 ease-linear shadow-[0_0_10px_rgba(37,99,235,0.3)]"
                                     :style="`width: ${samplingProgressData.progressPercent}%`"></div>
                            </template>
                            <template x-if="samplingProgressData.isOverdue">
                                <div class="absolute top-0 left-0 h-full w-full bg-gradient-to-r from-rose-600 to-rose-500 rounded-full animate-pulse shadow-[0_0_10px_rgba(225,29,72,0.4)]"></div>
                            </template>
                        </div>
                        
                        <!-- Pause Button -->
                        <button type="button" @click="togglePause()"
                                class="mt-3 w-full py-2 bg-slate-100 hover:bg-slate-200 active:scale-95 text-slate-500 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Jeda Timer
                        </button>
                    </div>
                </div>
            </div>
        </template>
        
    {{-- FULLSCREEN PAUSE / JEDA OVERLAY --}}
    <div x-show="isPaused" x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-105"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="fixed inset-0 z-[9999] flex flex-col items-center justify-center p-4 bg-slate-900/95 backdrop-blur-md">
        
        <div class="text-center">
            <div class="w-24 h-24 bg-amber-500/20 text-amber-500 rounded-full flex items-center justify-center mx-auto mb-6 animate-pulse">
                <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            
            <h1 class="text-4xl font-black text-white uppercase tracking-widest mb-2">Sedang Jeda</h1>
            <p class="text-slate-400 font-semibold mb-10 max-w-sm mx-auto">Waktu inspeksi diberhentikan sementara. Silakan selesaikan keperluan Anda (Toilet/Istirahat/dll).</p>
            
            <button type="button" @click="togglePause()"
                    class="px-10 py-5 bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-400 hover:to-orange-400 text-white rounded-3xl text-sm font-black uppercase tracking-[0.2em] shadow-2xl shadow-orange-500/30 transition-all active:scale-95 flex items-center gap-3 mx-auto">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Lanjutkan Inspeksi
            </button>
        </div>
    </div>
        
    </div>{{-- END x-data --}}
@endsection

