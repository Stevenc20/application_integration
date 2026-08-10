<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Print Item Check - {{ $itemCheck->masterTemplate->part_no ?? 'Unknown' }}</title>
    <!-- Include Tailwind if needed, but for print exactness we'll use inline/custom CSS heavily -->
    <!-- Include Tailwind and Alpine -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @page {
            size: A4 landscape;
            margin: 0;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 9px;
            color: #000;
            background: #fff;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
            padding: 1mm 5mm !important;
            margin: 0;
            box-sizing: border-box;
        }
        .print-container {
            width: 100%;
            max-width: 297mm;
            margin: 0 auto;
            border: 2px solid #000;
            box-sizing: border-box;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #000;
            padding: 1px 3px; /* Ultra compact padding */
            vertical-align: middle;
        }
        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .striped-cell {
            background: repeating-linear-gradient(
                -45deg,
                #cbd5e1,
                #cbd5e1 1.5px,
                #ffffff 1.5px,
                #ffffff 5px
            ) !important;
        }
        .no-border { border: none !important; }
        .no-border-left { border-left: none !important; }
        .no-border-right { border-right: none !important; }
        .no-border-top { border-top: none !important; }
        .no-border-bottom { border-bottom: none !important; }
        
        /* Specific widths and heights */
        .header-logo { width: 60px; }
        .sig-box { width: 80px; height: 50px; text-align: center; }
        .sig-img { max-height: 40px; max-width: 70px; }
        
        /* Rotate text for categories */
        .rotate-text {
            writing-mode: vertical-rl;
            transform: rotate(180deg);
            text-align: center;
            white-space: nowrap;
            width: 20px;
        }
        
        /* Utilities */
        .h-full { height: 100%; }
        .w-full { width: 100%; }

        /* Animation Classes */
        .scene {
            width: 100%;
            max-width: 297mm;
            margin: 0 auto;
            perspective: 2500px;
        }
        .flipper {
            width: 100%;
            position: relative;
            transform-style: preserve-3d;
            transition: transform 0.8s cubic-bezier(0.4, 0.2, 0.2, 1);
        }
        .flipper.is-flipped {
            transform: rotateY(180deg);
        }
        .card-face {
            width: 100%;
            backface-visibility: hidden;
            background: #fff;
        }
        .card-back {
            position: absolute;
            top: 0;
            left: 0;
            min-height: 100%;
            transform: rotateY(180deg);
        }
        @media print {
            .scene { perspective: none; }
            .flipper { transition: none; transform: none !important; }
            .flipper.is-flipped { transform: none !important; }
            .card-face { backface-visibility: visible; position: static !important; }
            .card-back { transform: none !important; page-break-before: always; }
            .no-print { display: none !important; }
            body { padding: 0 !important; background: #fff !important; }
        }
    </style>
</head>
<body x-data="{ showBack: false }" class="bg-slate-200 min-h-screen pb-10">
    @php
        $bundleChecks = $itemCheck->bundle_checks ?? $itemCheck->masterTemplate->bundle_checks ?? [];
        if (is_string($bundleChecks)) {
            $bundleChecks = json_decode($bundleChecks, true) ?? [];
        }
        
        // Cek apakah ada data bundle yang benar-benar diisi (bukan hanya template kosong)
        $hasBundleData = false;
        foreach ($bundleChecks as $b) {
            if (!empty(trim($b['bundleName'] ?? '')) || !empty(trim($b['coilNo'] ?? ''))) {
                $hasBundleData = true;
                break;
            }
            if (!empty($b['samples'])) {
                foreach ((array)$b['samples'] as $sampleGroup) {
                    if (is_array($sampleGroup)) {
                        foreach ($sampleGroup as $val) {
                            if (!empty(trim($val))) {
                                $hasBundleData = true;
                                break 3;
                            }
                        }
                    }
                }
            }
        }
    @endphp

    <div class="no-print w-full flex justify-center py-6 sticky top-0 z-50">
        <div class="flex items-center gap-3 bg-white/90 backdrop-blur-sm border border-slate-200 px-6 py-4 rounded-2xl shadow-lg">
            <a href="{{ route('qa.li.index') }}" 
               class="flex items-center gap-2 px-5 py-2.5 rounded-xl bg-white border border-slate-300 text-slate-600 font-bold text-sm hover:bg-slate-50 hover:text-slate-800 transition-all group shadow-sm">
                <svg class="w-4 h-4 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Kembali
            </a>
            
            @if($hasBundleData)
            <button @click="showBack = !showBack" 
                    class="flex items-center gap-2 px-6 py-2.5 rounded-xl bg-indigo-600 text-white font-black text-sm uppercase tracking-wider hover:bg-indigo-700 active:scale-95 transition-all shadow-md shadow-indigo-600/20 group">
                <svg class="w-4 h-4 transition-transform" :class="showBack ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                <span x-text="showBack ? 'Lihat Lembar Inspeksi' : 'Lihat Bundle Check'"></span>
            </button>
            @endif


            <div class="w-px h-6 bg-slate-300 mx-1"></div>
            <button onclick="window.print()" 
                    class="flex items-center gap-2 px-6 py-2.5 rounded-xl bg-red-600 text-white font-black text-sm uppercase tracking-wider hover:bg-red-700 active:scale-95 transition-all shadow-md shadow-red-600/20 group">
                <svg class="w-4 h-4 group-hover:-translate-y-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Cetak PDF
            </button>
        </div>
    </div>

    @php
        $item = $itemCheck->masterTemplate;
        $data = json_decode($item->data, true) ?? [];
        $cols = $data['cols'] ?? [];
        
        // Extract data for results table once
        $dimData = is_string($itemCheck->hasil_dimensi) ? json_decode($itemCheck->hasil_dimensi, true) : ($itemCheck->hasil_dimensi ?? []);
        $appData = is_string($itemCheck->hasil_visual) ? json_decode($itemCheck->hasil_visual, true) : ($itemCheck->hasil_visual ?? []);

        // 1. Tentukan kolom wajib (Base Columns)
        $totalPcs = floatval($itemCheck->total_produksi > 0 ? $itemCheck->total_produksi : ($itemCheck->schedule->actual_qty ?? $item->total_produksi ?? 0));
        
        $baseCols = [];
        if (is_array($item->sampling_cols) && !empty($item->sampling_cols)) {
            $validCols = array_filter($item->sampling_cols, function($c) use ($totalPcs) {
                return $totalPcs > 0 ? $c <= $totalPcs : true;
            });
            if ($totalPcs > 0 && (empty($validCols) || end($validCols) != $totalPcs)) {
                $validCols[] = $totalPcs;
            }
            $baseCols = array_values(array_unique($validCols));
        } else {
            $baseCols = [1];
            if ($totalPcs > 1) $baseCols[] = $totalPcs;
        }

        // 2. Dapatkan kolom tambahan dari data yang sudah disimpan (Custom/Keeper cols)
        $savedCols = [];
        foreach ($dimData as $k => $v) {
            $parts = explode('_', $k);
            if (count($parts) > 1 && trim((string)$v) !== '') {
                $colName = $parts[1];
                if (stripos((string)$colName, 'KEEPER') !== false) {
                    $savedCols[] = $colName;
                }
            }
        }
        foreach ($appData as $k => $v) {
            $parts = explode('_', $k);
            if (count($parts) > 1 && trim((string)$v) !== '') {
                $colName = $parts[1];
                if (stripos((string)$colName, 'KEEPER') !== false) {
                    $savedCols[] = $colName;
                }
            }
        }

        // 3. Gabungkan Base Columns dan Saved (filled) Columns
        $allCols = array_unique(array_merge($baseCols, $savedCols));
        
        usort($allCols, function($a, $b) {
            $aNum = is_numeric($a);
            $bNum = is_numeric($b);
            if ($aNum && $bNum) return $a - $b;
            if ($aNum) return -1;
            if ($bNum) return 1;
            return strcmp($a, $b);
        });
        
        $cols = $allCols;

        $dimStd = $data['dimStd'] ?? [];
        $appStd = $data['appStd'] ?? [];
        $revRecords = $data['revRecords'] ?? [];
        
        $totalStdRows = 12;
        
        $maxCols = 25; // As per layout
        $colChunks = array_chunk($cols, $maxCols);
        if (empty($colChunks)) {
            $colChunks = [[]]; // Ensure at least one page
        }
    @endphp

    <div class="scene">
        <div class="flipper" :class="{ 'is-flipped': showBack }">
            <div class="card-face card-front">
    @foreach($colChunks as $pageIndex => $chunkCols)
        @php
            $displayCols = $chunkCols;
            while(count($displayCols) < $maxCols) {
                $displayCols[] = ''; // pad with empty strings
            }
        @endphp
        <div class="print-container" style="position: relative; {{ $pageIndex > 0 ? 'page-break-before: always; margin-top: 20px;' : '' }}">
            <div style="position: absolute; top: 0; right: 0; font-size: 10px; font-weight: bold; border-left: 2px solid #000; border-bottom: 2px solid #000; padding: 2px 8px; background: white; z-index: 10;">
                FISM PRO-02-08-01
            </div>
        <!-- HEADER -->
        <table style="border-bottom: none;">
            <!-- TITLE ROW -->
            <tr>
                <td colspan="8" style="position: relative; padding: 5px; border-bottom: 1px solid #000;">
                    <div class="text-center">
                        <span style="font-size: 16px; font-weight: bold;">LEMBAR INSPEKSI</span><br>
                        <span style="font-size: 11px;">Lokasi : LINE - A</span>
                    </div>
                </td>
            </tr>
            <!-- INFO ROW 1 -->
            <tr>
                <td rowspan="4" class="text-center" style="width: 10%; border-right: none;">
                    <img src="{{ asset('IPPII.png') }}" style="height: 48px; width: auto; max-width: 100%; object-fit: contain; margin: 0 auto;">
                </td>
                <td rowspan="4" style="width: 24%; font-size: 11px; border-left: none;">
                    PT. INTI PANTJA PRESS INDUSTRI<br>
                    QUALITY ASSURANCE SECTION
                </td>
                <td style="width: 12%; font-size: 10px; border-right: none;">JOB NO.</td>
                <td style="width: 2%; text-align: center; border-left: none; border-right: none;">:</td>
                <td style="width: 28%; font-size: 10px; border-left: none;">{{ $item->job_no ?? '-' }}</td>
                <td class="text-center font-bold" style="width: 8%; font-size: 10px;">APPROVED</td>
                <td class="text-center font-bold" style="width: 8%; font-size: 10px;">CHECKED</td>
                <td class="text-center font-bold" style="width: 8%; font-size: 10px;">PREPARED</td>
            </tr>
            <!-- INFO ROW 2 -->
            <tr>
                <td style="font-size: 10px; border-right: none;">PART NAME</td>
                <td class="text-center" style="border-left: none; border-right: none;">:</td>
                <td style="font-size: 10px; border-left: none;">{{ $item->part_name ?? '-' }}</td>
                <td rowspan="2" class="sig-box">
                    @if($item->paraf_foreman)
                        <img src="{{ $item->paraf_foreman }}" class="sig-img">
                    @endif
                </td>
                <td rowspan="2" class="sig-box">
                    @if($item->paraf_gl)
                        <img src="{{ $item->paraf_gl }}" class="sig-img">
                    @endif
                </td>
                <td rowspan="2" class="sig-box">
                    @if($item->prepared_paraf)
                        <img src="{{ $item->prepared_paraf }}" class="sig-img">
                    @endif
                </td>
            </tr>
            <!-- INFO ROW 3 -->
            <tr>
                <td style="font-size: 10px; border-right: none;">PART NO</td>
                <td class="text-center" style="border-left: none; border-right: none;">:</td>
                <td style="font-size: 10px; border-left: none;">{{ $item->part_no ?? '-' }}</td>
            </tr>
            <!-- INFO ROW 4 -->
            <tr>
                <td style="font-size: 10px; border-right: none;">TYPE</td>
                <td class="text-center" style="border-left: none; border-right: none;">:</td>
                <td style="font-size: 10px; border-left: none;">{{ $item->type ?? 'D40G' }}</td>
                <td class="text-center" style="font-size: 8px;">NOVINA</td>
                <td class="text-center" style="font-size: 8px;">AZRIEL M</td>
                <td class="text-center" style="font-size: 8px;">{{ $data['qgName'] ?? 'TOTOK A' }}</td>
            </tr>
        </table>

        <!-- MIDDLE SECTION (SKETCH AND STANDARD) -->
        @php
            $dimStd = [];
            for($i = 1; $i <= 6; $i++) {
                $itemText = $item->{"dimensi{$i}_item"} ?? '';
                $itemLabel = $item->{"dimensi{$i}"} ?? '';
                
                $finalText = $itemText;
                if ($itemLabel && $itemLabel !== $itemText) {
                    $finalText = $itemText ? ($itemText . ' ' . $itemLabel) : $itemLabel;
                }
                
                // Jika label kosong tapi ada di DB (bisa jadi dim1..dim5 di DB), kita fetch.
                // Jika tidak ada di DB, kita biarkan kosong.
                $dimStd[] = [
                    'item' => $finalText,
                    'method' => $item->{"dimensi{$i}_method"} ?? ''
                ];
            }
            
            $appStd = [];
            for($r = 6; $r <= 14; $r++) {
                $itemText = trim($item->{"appearance{$r}"} ?? '');
                if ($itemText !== '') {
                    $appStd[$r - 6] = [
                        'item' => $itemText,
                        'method' => '' // appearance method is not stored separately in this schema
                    ];
                }
            }
            
            $stdCount = 1;
        @endphp
        <table style="border-top: none; width: 100%; table-layout: fixed;">
            <colgroup>
                <col style="width: 50%;"> <!-- SKETCH -->
                <col style="width: 20px;"> <!-- DIM/APP -->
                <col style="width: 40px;"> <!-- No/Date -->
                <col style="width: auto;"> <!-- Item -->
                <col style="width: 7.5%;"> <!-- Approved -->
                <col style="width: 7.5%;"> <!-- Checked -->
                <col style="width: 7.5%;"> <!-- Prepared -->
            </colgroup>
            <tr style="height: 11px;">
                <td style="padding: 1px 3px; font-weight: bold; border-bottom: 1px solid #000; border-top: none; border-left: none; font-size: 9px;">SKETCH PART</td>
                <td colspan="6" style="padding: 1px 3px; font-weight: bold; border-bottom: 1px solid #000; border-top: none; border-right: none; border-left: none; font-size: 9px; text-align: center;">STANDARD</td>
            </tr>
            @php $stdCount = 1; @endphp
            
            <!-- DIMENSI -->
            @foreach($dimStd as $i => $dim)
                <tr style="height: 11px;">
                    @if($i == 0)
                        <td rowspan="{{ 3 + count($dimStd) + count($appStd) }}" style="vertical-align: middle; padding: 0; border-left: none;">
                            <div style="text-align: center; width: 100%; display: flex; align-items: center; justify-content: center; overflow: hidden; padding: 5px;">
                                @php
                                    $printImagePath = $item->image_path;
                                    if ($printImagePath && Str::contains($printImagePath, 'storage/')) {
                                        $parts = explode('storage/', $printImagePath);
                                        $printImagePath = 'storage/' . end($parts);
                                    }
                                @endphp
                                @if($printImagePath || $item->sketch_url)
                                    <img src="{{ $printImagePath ? (Str::startsWith($printImagePath, 'http') || Str::startsWith($printImagePath, 'data:') ? $printImagePath : asset($printImagePath)) : $item->sketch_url }}" style="max-width: 100%; max-height: 220px; object-fit: contain; box-sizing: border-box;">
                                @else
                                    <div style="color: #ccc;">[ No Sketch ]</div>
                                @endif
                            </div>
                        </td>
                        <td rowspan="{{ count($dimStd) }}" class="rotate-text" style="border-left: none; border-top: none; font-size: 8px;">DIMENSI</td>
                    @endif
                    <td class="text-center" style="{{ $i == 0 ? 'border-top: none;' : '' }}">{{ $stdCount++ }}</td>
                    <td style="{{ $i == 0 ? 'border-top: none;' : '' }}">{{ $dim['item'] ?? '' }}</td>
                    <td colspan="3" style="{{ $i == 0 ? 'border-top: none; border-right: none;' : 'border-right: none;' }}">{{ $dim['method'] ?? '' }}</td>
                </tr>
            @endforeach

            <!-- APPEARANCE -->
            @if(count($appStd) > 0)
                @foreach($appStd as $i => $app)
                    <tr style="height: 11px;">
                        @if($loop->first)
                            <td rowspan="{{ count($appStd) }}" class="rotate-text" style="border-left: none; font-size: 8px;">APPEARANCE</td>
                        @endif
                    <td class="text-center">{{ $stdCount++ }}</td>
                    <td>{{ $app['item'] ?? '' }}</td>
                    <td colspan="3" style="border-right: none;">{{ $app['method'] ?? '' }}</td>
                </tr>
                @endforeach
            @else
                <!-- Keep at least one row so APPEARANCE header shows if it's completely empty? No, skip it entirely -->
            @endif

            <!-- REVISION RECORD -->
            <tr style="height: 11px;">
                <td class="text-center font-bold" style="border-left:none; font-size: 8px;">No</td>
                <td class="text-center font-bold" style="font-size: 8px;">Date</td>
                <td class="text-center font-bold" style="font-size: 8px;">Revision Record</td>
                <td class="text-center font-bold" style="font-size: 8px;">Approved</td>
                <td class="text-center font-bold" style="font-size: 8px;">Checked</td>
                <td class="text-center font-bold" style="font-size: 8px; border-right: none;">Prepared</td>
            </tr>
            @for($r = 0; $r < 2; $r++)
                @php 
                    $rev = $revRecords[$r] ?? []; 
                    $isTri = !empty($rev['record']);
                @endphp
                <tr style="height: 11px;">
                    <td class="text-center" style="border-left:none; font-size: 8px;">
                        @if($isTri) <span style="border: 1px solid #000; border-radius: 50%; padding: 0 3px;">&#9651;</span> @endif
                    </td>
                    <td class="text-center" style="font-size: 8px;">{{ $rev['date'] ?? '' }}</td>
                    <td style="font-size: 8px;">{{ $rev['record'] ?? '' }}</td>
                    <td class="text-center" style="font-size: 8px;">{{ $rev['approved'] ?? '' }}</td>
                    <td class="text-center" style="font-size: 8px;">{{ $rev['checked'] ?? '' }}</td>
                    <td class="text-center" style="font-size: 8px; border-right: none;">{{ $rev['prepared'] ?? '' }}</td>
                </tr>
            @endfor
        </table>

        <!-- RESULTS TABLE -->
        <table style="border-top: none;">
            <tr>
                <td colspan="2" class="font-bold" style="width: 30px; border-top: none;">*) ITEM CHECK</td>
                <td class="font-bold" style="width: 150px; border-top: none; text-align: right; padding-right: 5px;">No. SAMPLE</td>
                @foreach($displayCols as $col)
                    <td class="text-center font-bold" style="border-top: none; width: 25px; background-color: #CCFFCC;">{{ $col }}</td>
                @endforeach
            </tr>
            
            @php $rowCount = 1; @endphp
            
            <!-- DIMENSI RESULTS -->
            @foreach($dimStd as $i => $dim)
                <tr style="height: 12px;">
                    @if($i == 0)
                        <td rowspan="{{ count($dimStd) }}" class="rotate-text" style="width: 10px; font-size: 8px;">* DIMENSI (di isi) *</td>
                    @endif
                    <td class="text-center" style="width: 15px; font-size: 9px;">{{ $rowCount++ }}.</td>
                    <td style="width: 150px; font-size: 9px;">{{ $dim['item'] ?? '' }}</td>
                    @foreach($displayCols as $col)
                        @php 
                            $val = '';
                            $isEmptyRow = empty(trim($dim['item'] ?? '')) && empty(trim($dim['nominal'] ?? ''));
                            $isCellEmpty = $isEmptyRow || empty($col);
                            if (!$isCellEmpty) {
                                if ((string)$col !== (string)$cols[0]) {
                                    $val = '&mdash;';
                                } else {
                                    $val = htmlspecialchars($dimData[($i).'_'.$col] ?? '');
                                }
                            }
                        @endphp
                        <td class="text-center {{ $isCellEmpty ? 'striped-cell' : '' }}" style="font-size: 9px;">{!! $val !!}</td>
                    @endforeach
                </tr>
            @endforeach
            
            <!-- APPEARANCE RESULTS -->
            @if(count($appStd) > 0)
                @foreach($appStd as $i => $app)
                    <tr style="height: 12px;">
                        @if($loop->first)
                            <td rowspan="{{ count($appStd) }}" class="rotate-text" style="width: 10px; font-size: 8px;">* APPEARANCE (√ atau x )*</td>
                        @endif
                    <td class="text-center" style="width: 15px; font-size: 9px;">{{ $rowCount++ }}.</td>
                    <td style="width: 150px; font-size: 9px;">{{ $app['item'] ?? '' }}</td>
                    @foreach($displayCols as $col)
                        @php 
                            $val = '';
                            $isEmptyRow = empty(trim($app['item'] ?? ''));
                            $isCellEmpty = $isEmptyRow || empty($col);
                            if (!$isCellEmpty) {
                                $val = $appData[($i).'_'.$col] ?? '';
                            }
                            $isOk = in_array(strtolower($val), ['ok', '✓']);
                            $isNg = in_array(strtolower($val), ['ng', '✕', '✗', 'x']);
                            $icon = $isOk ? '&#10003;' : ($isNg ? '&#10007;' : $val);
                            if ($isCellEmpty) $icon = '';
                        @endphp
                        <td class="text-center {{ $isCellEmpty ? 'striped-cell' : '' }}" style="font-size: 9px;">{!! $icon !!}</td>
                    @endforeach
                </tr>
                @endforeach
            @endif
                        
            <!-- QG JUDGEMENT -->
            <tr style="height: 13px;">
                <td colspan="3" class="font-bold" style="font-size: 9px; background-color: #fff2cc;">QG JUDGEMENT</td>
                @foreach($displayCols as $col)
                    @php
                        $judgement = '-';
                        if ($col) {
                            $hasInput = false;
                            $isNg = false;
                            
                            // Check Dimensi
                            foreach($dimStd as $i => $dim) {
                                if ($i >= 7) continue; // Only first 7 have samples
                                $val = $dimData[($i).'_'.$col] ?? '';
                                if ($val !== '') {
                                    $hasInput = true;
                                    $nominalStr = $item->{"dimensi".($i+1)."_nominal"} ?? '';
                                    if ($nominalStr !== '') {
                                        $nominal = floatval($nominalStr);
                                        $plus = floatval($item->{"dimensi".($i+1)."_plus"} ?? 0);
                                        $minus = floatval($item->{"dimensi".($i+1)."_minus"} ?? 0);
                                        $v = floatval(str_replace(',', '.', $val));
                                        if ($v < ($nominal - $minus) || $v > ($nominal + $plus)) {
                                            $isNg = true;
                                        }
                                    }
                                }
                            }
                            
                            // Check Appearance
                            $holeStandard = (int)($item->hole_standard ?? 0);
                            if ($holeStandard === 0) {
                                foreach($appStd as $idx => $a) {
                                    if (strpos(strtoupper($a['item'] ?? ''), 'JUMLAH HOLE') !== false) {
                                        preg_match('/\d+/', $a['item'], $matches);
                                        if (isset($matches[0])) $holeStandard = (int)$matches[0];
                                    }
                                }
                            }
                            
                            foreach($appStd as $i => $app) {
                                $val = $appData[($i).'_'.$col] ?? '';
                                if ($val !== '') {
                                    $hasInput = true;
                                    $itemText = strtoupper($app['item'] ?? '');
                                    if (strpos($itemText, 'JUMLAH HOLE') !== false) {
                                        if ($holeStandard > 0 && (int)$val !== $holeStandard) {
                                            $isNg = true;
                                        }
                                    } else {
                                        if (in_array(strtolower($val), ['ng', '✕', '✗', 'x'])) {
                                            $isNg = true;
                                        }
                                    }
                                }
                            }
                            
                            if ($isNg) {
                                $judgement = 'NG';
                            } elseif ($hasInput) {
                                $judgement = 'OK';
                            }
                        }
                    @endphp
                    <td class="text-center font-bold {{ empty($col) ? 'striped-cell' : '' }}" style="font-size: 9px; {!! $judgement == 'NG' ? 'color: #dc2626;' : ($judgement == 'OK' ? 'color: #15803d;' : '') !!}">
                        {{ empty($col) ? '' : $judgement }}
                    </td>
                @endforeach
            </tr>

            <!-- PARAF GL -->
            <tr>
                <td colspan="3" class="font-bold">PARAF GL</td>
                <td colspan="{{ count($displayCols) }}" class="text-center">
                    @if($itemCheck->paraf_foreman)
                        <img src="{{ $itemCheck->paraf_foreman }}" style="height: 25px;">
                    @endif
                </td>
            </tr>
            
            <!-- PARAF FOREMAN -->
            <tr>
                <td colspan="3" class="font-bold">PARAF FOREMAN</td>
                <td colspan="{{ count($displayCols) }}" class="text-center">
                    @if($itemCheck->paraf_leader)
                        <img src="{{ $itemCheck->paraf_leader }}" style="height: 25px;">
                    @endif
                </td>
            </tr>
        </table>
        
        <!-- FOOTER TABLE -->
        <table style="border-top: none;">
            <tr>
                <td style="width: 20%; border-top: none;">QUALITY NAME</td>
                <td style="width: 20%; border-top: none;">QG NAME : <span class="font-bold">{{ $itemCheck->operator->name ?? $data['qgName'] ?? '-' }}</span></td>
                <td style="width: 20%; border-top: none;">GL NAME : <span class="font-bold">{{ $itemCheck->assignedGl->name ?? '-' }}</span></td>
                <td style="width: 20%; border-top: none;">FRM NAME : <span class="font-bold">{{ $itemCheck->assignedForeman->name ?? '-' }}</span></td>
                <td style="width: 20%; border-top: none;">TOTAL PROD : <span class="font-bold">{{ $itemCheck->total_produksi ?? $itemCheck->schedule->actual_qty ?? '-' }}</span></td>
            </tr>
            <tr>
                <td>PRODUCTION INFO</td>
                <td>TGL/BULAN : <span class="font-bold">{{ $itemCheck->tanggal ? \Carbon\Carbon::parse($itemCheck->tanggal)->format('d-m-y') : '-' }}</span></td>
                <td>SHIFT : <span class="font-bold">{{ $itemCheck->shift ?? '-' }}</span></td>
                <td>REPAIR : <span class="font-bold">{{ $itemCheck->repair ?? '-' }}</span></td>
                <td>REJECT : <span class="font-bold">{{ $itemCheck->reject ?? '-' }}</span></td>
            </tr>
        </table>
    </div>
    @endforeach
            </div> <!-- End card-front -->

            <!-- BACK: Bundle Item Check -->
            @php
                $bundleChunks = array_chunk($bundleChecks, 5);
                // Ensure we have exactly 2 chunks (10 bundles) for the layout
                while (count($bundleChunks) < 2) {
                    $bundleChunks[] = [];
                }
                $bundleChunks = array_slice($bundleChunks, 0, 2);

                $appStdBundle = [
                    ['idx' => 0, 'item' => 'Tidak Pecok, Tidak Benjol, Tidak Gelombang'],
                    ['idx' => 1, 'item' => 'Tidak Baret, Tidak Burry'],
                    ['idx' => 2, 'item' => 'Tidak Keriput, Tidak Pecah, Tidak Neck'],
                    ['idx' => 3, 'item' => 'Tidak Karat'],
                    ['idx' => 4, 'item' => 'Tidak Penyok, Flange Tidak Miring'],
                    ['idx' => 5, 'item' => 'Jumlah hole (pcs)'],
                    ['idx' => 6, 'item' => 'ID Mark']
                ];
            @endphp
            
            @if($hasBundleData)
            <div class="card-face card-back">
                <div class="print-container flex flex-col" style="background: white; min-height: 201mm;">
                    <div class="flex-grow">
                        <!-- HEADER -->
                    <table style="border-bottom: none; margin-bottom: 17px;">
                        <tr>
                            <td style="width: 15%; text-align: center; border-right: none;">
                                <img src="{{ asset('IPPII.png') }}" style="height: 45px; max-width: 100%; object-fit: contain; margin: 0 auto;">
                            </td>
                            <td style="width: 85%; border-left: none;">
                                <h2 style="font-size: 16px; font-weight: bold; text-align: center; margin: 0; padding: 5px; letter-spacing: 1px;">PENGECEKAN AWAL PROSES SETELAH PERGANTIAN BUNDLE MATERIAL</h2>
                            </td>
                        </tr>
                    </table>

                    @foreach($bundleChunks as $ci => $chunk)
                        @php
                            // pad chunk to 5 elements
                            while(count($chunk) < 5) {
                                $chunk[] = ['bundleName' => '', 'coilNo' => '', 'judgement' => '', 'samples' => []];
                            }
                        @endphp
                        <table style="{{ $ci > 0 ? 'margin-top: 16px;' : '' }}; table-layout: fixed; width: 100%;">
                            <colgroup>
                                <col style="width: 20%;">
                                @for($i=0; $i<15; $i++)
                                    <col style="width: 4.333333%;">
                                @endfor
                                <col style="width: 15%;">
                            </colgroup>
                            <tr>
                                <td rowspan="2" class="text-center font-bold" style="font-size: 10px;">ITEM CHECK<br>APPEARANCE</td>
                                @foreach($chunk as $bi => $b)
                                    <td colspan="3" class="text-center font-bold" style="font-size: 10px;">BUNDLE ( {{ $b['bundleName'] ?? '' }} )</td>
                                @endforeach
                                <td rowspan="2" class="text-center font-bold" style="font-size: 10px;">TINDAKAN PERBAIKAN APABILA NG</td>
                            </tr>
                            <tr>
                                @foreach($chunk as $bi => $b)
                                    <td class="text-center font-bold">1</td>
                                    <td class="text-center font-bold">2</td>
                                    <td class="text-center font-bold">3</td>
                                @endforeach
                            </tr>
                            
                            <!-- APPEARANCE ROWS -->
                            @foreach($appStdBundle as $app)
                                <tr style="height: 18px;">
                                    <td style="padding-left: 5px;">{{ $app['item'] }}</td>
                                    @foreach($chunk as $b)
                                        @for($s = 1; $s <= 3; $s++)
                                            @php
                                                $val = $b['samples'][$s][$app['idx']] ?? '';
                                                if ($app['idx'] === 5) {
                                                    // "Jumlah hole (pcs)" row -> Show raw number instead of checkmark
                                                    $icon = htmlspecialchars($val);
                                                } else {
                                                    $isOk = in_array(strtolower($val), ['ok', '✓']);
                                                    $isNg = in_array(strtolower($val), ['ng', '✕', '✗', 'x']);
                                                    $icon = $isOk ? '&#10003;' : ($isNg ? '&#10007;' : htmlspecialchars($val));
                                                }
                                            @endphp
                                            <td class="text-center">{!! $icon !!}</td>
                                        @endfor
                                    @endforeach
                                    <td class="text-center" style="font-size: 10px; vertical-align: middle;">
                                        {{ $loop->first && $ci === 0 ? ($item->bundle_tindakan ?? '') : '' }}
                                    </td>
                                </tr>
                            @endforeach

                            <!-- JUDGEMENT -->
                            <tr style="height: 20px;">
                                <td style="padding-left: 5px; font-weight: bold;">JUDGEMENT OK/NG</td>
                                @foreach($chunk as $b)
                                    <td colspan="3" class="text-center font-bold {{ strtolower($b['judgement'] ?? '') === 'ng' ? 'text-red-600' : '' }}">
                                        {{ $b['judgement'] ?? '' }}
                                    </td>
                                @endforeach
                                <td></td>
                            </tr>
                            <!-- COIL NO -->
                            <tr style="height: 20px;">
                                <td style="padding-left: 5px;">COIL NO.</td>
                                @foreach($chunk as $b)
                                    <td colspan="3" class="text-center">{{ $b['coilNo'] ?? '' }}</td>
                                @endforeach
                                <td></td>
                            </tr>
                            <!-- PARAF OPERATOR -->
                            <tr style="height: 20px;">
                                <td style="padding-left: 5px;">PARAF OPERATOR</td>
                                <td colspan="16" class="text-left" style="padding-left: 20px;">
                                    @if($ci === 0 && !empty($itemCheck->paraf_operator))
                                        <img src="{{ $itemCheck->paraf_operator }}" style="height: 20px; object-fit: contain;">
                                    @endif
                                </td>
                            </tr>
                            <!-- PARAF GL -->
                            <tr style="height: 20px;">
                                <td style="padding-left: 5px;">PARAF GL</td>
                                <td colspan="16" class="text-left" style="padding-left: 20px;">
                                    @if($ci === 0 && !empty($itemCheck->paraf_foreman))
                                        <img src="{{ $itemCheck->paraf_foreman }}" style="height: 20px; object-fit: contain;">
                                    @endif
                                </td>
                            </tr>
                            <!-- PARAF FOREMAN -->
                            <tr style="height: 20px;">
                                <td style="padding-left: 5px;">PARAF FOREMAN</td>
                                <td colspan="16" class="text-left" style="padding-left: 20px;">
                                    @if($ci === 0 && !empty($itemCheck->paraf_leader))
                                        <img src="{{ $itemCheck->paraf_leader }}" style="height: 20px; object-fit: contain;">
                                    @endif
                                </td>
                            </tr>
                        </table>
                    @endforeach

                    </div>

                    <!-- FOOTER KESIMPULAN -->
                    <!-- FOOTER KESIMPULAN -->
                    <div style="border-top: 1px solid #000; padding: 5px 10px; font-size: 10px; line-height: 1.4;">
                        <strong>Pernyataan Kesimpulan :</strong><br>
                        Status Produk Setelah Pergantian Bundle Material :<br>
                        OK (Produk sesuai standar/sample)<br>
                        NG (Ada abnormality, kembalikan material ke IRM)<br>
                        Bila terjadi abnormality lakukan <strong>Stop Call Wait (SCW)</strong><br>
                        <br>
                        <strong>Catatan Tambahan :</strong><br>
                        Check sheet ini dirancang untuk memeriksa produk setelah pergantian bundle material, memastikan produk yang dihasilkan kondisi standar sesuai sample. Pemeriksaan dilakukan dengan teliti setiap setelah pergantian bundle material.
                    </div>
                </div>
            </div> <!-- End card-back -->
            @endif
        </div> <!-- End flipper -->
    </div> <!-- End scene -->

    <!-- Small script to trigger print dialog on page load -->
    <script>
        window.onload = function() {
            // Uncomment next line to print immediately
            // window.print();
        };
    </script>
</body>
</html>
