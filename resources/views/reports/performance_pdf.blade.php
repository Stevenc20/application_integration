<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>OEE Performance Report - {{ $month }}</title>
    <style>
        @page { margin: 15mm; }
        body { font-family: sans-serif; font-size: 10pt; color: #333; }
        h1 { font-size: 16pt; text-align: center; margin-bottom: 4px; }
        h2 { font-size: 11pt; text-align: center; color: #666; margin-top: 0; margin-bottom: 16px; font-weight: normal; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        th, td { border: 1px solid #999; padding: 4px 6px; text-align: center; font-size: 8pt; }
        th { background: #e0e0e0; font-weight: bold; }
        .summary { margin-bottom: 16px; }
        .summary td { font-size: 10pt; padding: 6px 10px; }
        .summary .label { text-align: left; font-weight: bold; background: #f5f5f5; }
        .summary .value { font-weight: bold; }
        .oee-total { font-size: 18pt; font-weight: bold; text-align: center; padding: 10px; }
        .footer { margin-top: 24px; font-size: 8pt; text-align: center; color: #999; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .bg-green { background: #d4edda; }
        .bg-amber { background: #fff3cd; }
        .bg-red { background: #f8d7da; }
    </style>
</head>
<body>
    <h1>PT INTI PANTJA PRESS INDUSTRI</h1>
    <h2>OEE Performance Report — {{ \Carbon\Carbon::parse($month . '-01')->format('F Y') }}</h2>

    <table class="summary">
        <tr>
            <td class="label">Availability</td>
            <td class="value">{{ number_format($avgAvailability, 1) }}%</td>
            <td class="label">Performance</td>
            <td class="value">{{ number_format($avgPerformance, 1) }}%</td>
            <td class="label">Quality</td>
            <td class="value">{{ number_format($avgQuality, 1) }}%</td>
            <td class="label">OEE</td>
            <td class="value @if($avgOee >= 85) bg-green @elseif($avgOee >= 65) bg-amber @else bg-red @endif">
                {{ number_format($avgOee, 1) }}%
            </td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th rowspan="2">#</th>
                <th rowspan="2">Date</th>
                <th rowspan="2">Shift</th>
                <th colspan="3">OEE Components</th>
                <th rowspan="2">OEE</th>
                <th rowspan="2">Good Pcs</th>
                <th rowspan="2">Total Stroke</th>
            </tr>
            <tr>
                <th>Availability</th>
                <th>Performance</th>
                <th>Quality</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($dailyRecords as $i => $rec)
            @php
                $oeeVal = $rec['oee'];
                $rowClass = $oeeVal >= 85 ? 'bg-green' : ($oeeVal >= 65 ? 'bg-amber' : 'bg-red');
            @endphp
            <tr>
                <td>{{ $i + 1 }}</td>
                <td class="text-left">{{ \Carbon\Carbon::parse($rec['date'])->format('d M Y') }}</td>
                <td>{{ $rec['shift'] }}</td>
                <td>{{ number_format($rec['availability'], 1) }}%</td>
                <td>{{ number_format($rec['performance'], 1) }}%</td>
                <td>{{ number_format($rec['quality'], 1) }}%</td>
                <td class="{{ $rowClass }}">{{ number_format($oeeVal, 1) }}%</td>
                <td class="text-right">{{ number_format($rec['good']) }}</td>
                <td class="text-right">{{ number_format($rec['stroke']) }}</td>
            </tr>
            @empty
            <tr><td colspan="9" style="padding:16px;color:#999;">No data available</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Generated on {{ now()->format('d F Y H:i:s') }} | Target OEE: {{ $targetOee ?? 80 }}%
    </div>
</body>
</html>
