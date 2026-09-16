<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Print Lembar Inspeksi - {{ $item->part_no }}</title>
    <!-- Include Tailwind if needed, but for print exactness we'll use inline/custom CSS heavily -->
    @vite(['resources/css/app.css'])
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
            padding: 4mm 5mm !important;
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
        
        @media print {
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="no-print w-full flex justify-center mb-6">
        <div class="flex items-center gap-3 bg-slate-100/80 border border-slate-200 px-6 py-4 rounded-2xl shadow-sm">
            <a href="{{ route('qa.li.index') }}" 
               class="flex items-center gap-2 px-5 py-2.5 rounded-xl bg-white border border-slate-300 text-slate-600 font-bold text-sm hover:bg-slate-50 hover:text-slate-800 transition-all group shadow-sm">
                <svg class="w-4 h-4 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Kembali
            </a>
            <div class="w-px h-6 bg-slate-300 mx-1"></div>
            <button onclick="window.print()" 
                    class="flex items-center gap-2 px-6 py-2.5 rounded-xl bg-red-600 text-white font-black text-sm uppercase tracking-wider hover:bg-red-700 active:scale-95 transition-all shadow-md shadow-red-600/20 group">
                <svg class="w-4 h-4 group-hover:-translate-y-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Cetak PDF
            </button>
        </div>
    </div>

    @php
        $data = json_decode($item->data, true) ?? [];
        $cols = $data['cols'] ?? [];
        
        // Dynamically calculate cols if missing (as it is not saved to DB)
        if (empty($cols)) {
            $totalPcs = floatval($item->total_produksi ?? 0);
            $tactTime = floatval($item->tact_time ?? 0);
            $ctDimensi = floatval($item->ct_dimensi ?? 0);
            $ctTanpaDimensi = floatval($item->ct_tanpa_dimensi ?? 0);

            if ($totalPcs > 0) {
                if ($tactTime > 0 && $ctDimensi > 0 && $ctTanpaDimensi > 0) {
                    if ($ctDimensi < 100) {
                        $div = max(1, round($tactTime + $ctDimensi / 60));
                        $interval1 = max(1, round($ctDimensi / $div));
                        $interval2 = max(1, round($ctTanpaDimensi / $div));
                    } else {
                        $interval1 = max(1, round($ctDimensi / $tactTime));
                        $interval2 = max(1, floor($ctTanpaDimensi / $tactTime));
                    }

                    $cols[] = 1;
                    $next = 1 + $interval1;
                    while ($next < $totalPcs) {
                        $cols[] = $next;
                        $next += $interval2;
                    }
                    if (end($cols) !== $totalPcs) {
                        $cols[] = $totalPcs;
                    }
                } else {
                    if ($totalPcs <= 9) {
                        for ($i = 1; $i <= $totalPcs; $i++) $cols[] = $i;
                    } else {
                        $cols = [1, 2, 3];
                        foreach ([10, 20, 40, 60, 80, 100] as $v) {
                            if ($v <= $totalPcs) $cols[] = $v;
                        }
                        for ($v = 125; $v <= 200; $v += 25) {
                            if ($v <= $totalPcs) $cols[] = $v;
                        }
                        for ($v = 250; $v < $totalPcs; $v += 50) {
                            $cols[] = $v;
                        }
                        if (end($cols) !== $totalPcs) {
                            $cols[] = $totalPcs;
                        }
                    }
                }
            }
            // Ensure unique and sorted
            if (!empty($cols)) {
                $cols = array_values(array_unique($cols));
                sort($cols);
            }
        }

        $dimStd = $data['dimStd'] ?? [];
        $appStd = $data['appStd'] ?? [];
        $revRecords = $data['revRecords'] ?? [];
        
        // Prepare exactly 12 standard rows (4 dimensi, 8 appearance) as per image, or use actual
        // The image shows 12 rows total for STANDARD.
        $totalStdRows = 12;
        
        $maxCols = 25; // As per layout
        $colChunks = array_chunk($cols, $maxCols);
        if (empty($colChunks)) {
            $colChunks = [[]]; // Ensure at least one page
        }
        
        // Extract data for results table once
        $dimData = [];
        for($i=1; $i<=7; $i++) {
            $rowIdx = $i - 1;
            $results = $item->{"dimensi{$i}_results"};
            if (is_string($results)) $results = json_decode($results, true);
            
            if (is_array($results) && !empty($results)) {
                foreach($results as $col => $val) {
                    $dimData["{$rowIdx}_{$col}"] = $val;
                }
            } else {
                // Fallback for older format
                $dimData["{$rowIdx}_1"] = $item->{"dimensi{$i}_sample_1"} ?? '';
                $dimData["{$rowIdx}_2"] = $item->{"dimensi{$i}_sample_2"} ?? '';
                $dimData["{$rowIdx}_3"] = $item->{"dimensi{$i}_sample_3"} ?? '';
            }
        }

        $appData = [];
        for($r=6; $r<=14; $r++) {
            $rowIdx = $r - 6;
            $results = $item->{"appearance{$r}_results"};
            if (is_string($results)) $results = json_decode($results, true);
            if (is_array($results)) {
                foreach($results as $col => $val) {
                    $appData["{$rowIdx}_{$col}"] = $val;
                }
            }
        }
    @endphp

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
            <tr style="height: 12px;">
                <td style="width: 50%; padding: 1px 3px; font-weight: bold; border-bottom: 1px solid #000; font-size: 9px; border-top: none;">SKETCH PART</td>
                <td style="width: 50%; padding: 1px 3px; font-weight: bold; border-bottom: 1px solid #000; font-size: 9px; text-align: center; border-left: none; border-top: none;">STANDARD</td>
            </tr>
            <tr>
                <td style="width: 50%; vertical-align: top; padding: 0;">
                    
                    <div style="text-align: center; height: 255px; width: 100%; display: flex; align-items: center; justify-content: center; overflow: hidden; padding-top: 5px;">
                        @php
                            $printImagePath = $item->image_path;
                            if ($printImagePath && Str::contains($printImagePath, 'storage/')) {
                                $parts = explode('storage/', $printImagePath);
                                $printImagePath = 'storage/' . end($parts);
                            }
                        @endphp
                        @if($printImagePath || $item->sketch_url)
                            <img src="{{ $printImagePath ? (Str::startsWith($printImagePath, 'http') || Str::startsWith($printImagePath, 'data:') ? $printImagePath : asset($printImagePath)) : $item->sketch_url }}" style="max-width: 100%; max-height: 100%; object-fit: contain; padding: 5px; box-sizing: border-box;">
                        @else
                            <div style="color: #ccc;">[ No Sketch ]</div>
                        @endif
                    </div>
                </td>
                <td style="width: 50%; vertical-align: top; padding: 0; border: none; height: 100%;">
                    <table style="border:none; table-layout: fixed; width: 100%; height: 100%;">
                        <colgroup>
                            <col style="width: 20px;">
                            <col style="width: 40px;">
                            <col style="width: auto;">
                            <col style="width: 15%;">
                            <col style="width: 15%;">
                            <col style="width: 15%;">
                        </colgroup>
                        @php
                            $stdCount = 1;
                        @endphp
                        
                        <!-- DIMENSI -->
                        @foreach($dimStd as $i => $dim)
                            <tr style="height: 12px;">
                                @if($i == 0)
                                    <td rowspan="{{ count($dimStd) }}" class="rotate-text" style="border-left: none; border-top: none; font-size: 8px;">DIMENSI</td>
                                @endif
                                <td class="text-center" style="{{ $i == 0 ? 'border-top: none;' : '' }}">{{ $stdCount++ }}</td>
                                <td style="{{ $i == 0 ? 'border-top: none;' : '' }}">{{ $dim['item'] ?? '' }}</td>
                                <td colspan="3" style="{{ $i == 0 ? 'border-top: none;' : '' }}">{{ $dim['method'] ?? '' }}</td>
                            </tr>
                        @endforeach
                        
                        <!-- APPEARANCE -->
                        @if(count($appStd) > 0)
                            @foreach($appStd as $i => $app)
                                <tr style="height: 12px;">
                                    @if($loop->first)
                                        <td rowspan="{{ count($appStd) }}" class="rotate-text" style="border-left: none; font-size: 8px;">APPEARANCE</td>
                                    @endif
                                <td class="text-center">{{ $stdCount++ }}</td>
                                <td>{{ $app['item'] ?? '' }}</td>
                                    <td colspan="3">{{ $app['method'] ?? '' }}</td>
                                </tr>
                            @endforeach
                        @endif

                        <!-- REVISION RECORD -->
                        <tr style="height: 12px;">
                            <td class="text-center font-bold" style="border-left:none; font-size: 8px;">No</td>
                            <td class="text-center font-bold" style="font-size: 8px;">Date</td>
                            <td class="text-center font-bold" style="font-size: 8px;">Revision Record</td>
                            <td class="text-center font-bold" style="font-size: 8px;">Approved</td>
                            <td class="text-center font-bold" style="font-size: 8px;">Checked</td>
                            <td class="text-center font-bold" style="font-size: 8px;">Prepared</td>
                        </tr>
                        @for($r = 0; $r < 2; $r++)
                            @php 
                                $rev = $revRecords[$r] ?? []; 
                                $isTri = !empty($rev['record']);
                            @endphp
                            <tr style="height: 12px;">
                                <td class="text-center" style="border-left:none; font-size: 8px;">
                                    @if($isTri) <span style="border: 1px solid #000; border-radius: 50%; padding: 0 3px;">&#9651;</span> @endif
                                </td>
                                <td class="text-center" style="font-size: 8px;">{{ $rev['date'] ?? '' }}</td>
                                <td style="font-size: 8px;">{{ $rev['record'] ?? '' }}</td>
                                <td class="text-center" style="font-size: 8px;">{{ $rev['approved'] ?? '' }}</td>
                                <td class="text-center" style="font-size: 8px;">{{ $rev['checked'] ?? '' }}</td>
                                <td class="text-center" style="font-size: 8px;">{{ $rev['prepared'] ?? '' }}</td>
                            </tr>
                        @endfor
                    </table>
                </td>
            </tr>
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
                <tr style="height: 13px;">
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
                                // $dimData keys are 0_1, 1_1, etc.
                                $val = $dimData[($i).'_'.$col] ?? '';
                            }
                        @endphp
                        <td class="text-center {{ $isCellEmpty ? 'striped-cell' : '' }}" style="font-size: 9px;">{{ $val }}</td>
                    @endforeach
                </tr>
            @endforeach
            
            <!-- APPEARANCE RESULTS -->
            @if(count($appStd) > 0)
                @foreach($appStd as $i => $app)
                    <tr style="height: 13px;">
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
            <tr style="height: 15px;">
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
                    @if($item->paraf_gl_bottom)
                        <img src="{{ $item->paraf_gl_bottom }}" style="height: 25px;">
                    @endif
                </td>
            </tr>
            
            <!-- PARAF FOREMAN -->
            <tr>
                <td colspan="3" class="font-bold">PARAF FOREMAN</td>
                <td colspan="{{ count($displayCols) }}" class="text-center">
                    @if($item->paraf_foreman_bottom)
                        <img src="{{ $item->paraf_foreman_bottom }}" style="height: 25px;">
                    @endif
                </td>
            </tr>
        </table>
        
        <!-- FOOTER TABLE -->
        <table style="border-top: none;">
            <tr>
                <td style="width: 20%; border-top: none;">QUALITY NAME</td>
                <td style="width: 20%; border-top: none;">QG NAME : <span class="font-bold">{{ $item->qg_name ?? $data['qgName'] ?? '-' }}</span></td>
                <td style="width: 20%; border-top: none;">GL NAME : <span class="font-bold">{{ $item->assignedGl->name ?? $item->paraf_gl_bottom_name ?? '-' }}</span></td>
                <td style="width: 20%; border-top: none;">FRM NAME : <span class="font-bold">{{ $item->assignedForeman->name ?? $item->paraf_fm_bottom_name ?? '-' }}</span></td>
                <td style="width: 20%; border-top: none;">TOTAL PROD : <span class="font-bold">{{ $item->total_produksi ?? '-' }}</span></td>
            </tr>
            <tr>
                <td>PRODUCTION INFO</td>
                <td>TGL/BULAN : <span class="font-bold">{{ $item->tgl_bulan ? \Carbon\Carbon::parse($item->tgl_bulan)->format('d-m-y') : '-' }}</span></td>
                <td>SHIFT : <span class="font-bold">{{ $item->shift ?? '-' }}</span></td>
                <td>REPAIR : <span class="font-bold">{{ $item->repair ?? '-' }}</span></td>
                <td>REJECT : <span class="font-bold">{{ $item->reject ?? '-' }}</span></td>
            </tr>
        </table>
    </div>
    @endforeach

    <!-- Small script to trigger print dialog on page load -->
    <script>
        window.onload = function() {
            // Uncomment next line to print immediately
            // window.print();
        };
    </script>
</body>
</html>
