<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Production Press Achievement — Monitor</title>
    <style>
        :root {
            --header-row-0-height: 38px;
        }
        *{margin:0;padding:0;box-sizing:border-box}
        body{
            background:#f1f5f9;
            color:#1e293b;
            font-family:Arial,Helvetica,sans-serif;
            overflow:hidden;
            height:100vh;
            width:100vw;
        }
        .monitor-container{
            height:100vh;
            width:100vw;
            display:flex;
            flex-direction:column;
        }
        .header{
            height:60px;
            background:#fff;
            border-bottom:2px solid #e2e8f0;
            display:flex;
            justify-content:space-between;
            align-items:center;
            padding:0 20px;
            flex-shrink:0;
        }
        .header-left{display:flex;flex-direction:column}
        .header-left .title{font-size:18px;font-weight:900;color:#0f172a;letter-spacing:0.04em}
        .header-left .subtitle{font-size:10px;color:#64748b;font-weight:600;letter-spacing:0.06em}
        .header-right{
            display:flex;align-items:center;gap:16px;
            text-align:right;font-size:12px;font-weight:700;color:#334155;line-height:1.4;
        }
        .header-right .clock{font-size:20px;font-weight:900;color:#1d4ed8;font-variant-numeric:tabular-nums}
        .header-right .exit-btn{
            display:inline-flex;align-items:center;gap:4px;
            padding:6px 14px;border-radius:6px;
            font-size:11px;font-weight:700;color:#64748b;
            background:#f1f5f9;border:1px solid #e2e8f0;
            text-decoration:none;transition:all 0.12s;cursor:pointer;
        }
        .header-right .exit-btn:hover{background:#fee2e2;color:#dc2626;border-color:#fecaca}
        .filter-bar{display:flex;gap:6px}
        .filter-bar button{
            padding:4px 14px;border-radius:6px;border:1px solid #e2e8f0;
            font-size:11px;font-weight:700;cursor:pointer;
            background:#fff;color:#64748b;transition:all 0.12s;
        }
        .filter-bar button:hover{background:#f1f5f9;border-color:#94a3b8}
        .filter-bar button.active{background:#1d4ed8;color:#fff;border-color:#1d4ed8}
        .filter-bar button.active:hover{background:#1e40af}
        .filter-bar+.filter-bar{margin-left:8px}

        /* Shift bar buttons match filter bar */
        #shiftBar button{padding:4px 14px;border-radius:6px;border:1px solid #e2e8f0;font-size:11px;font-weight:700;cursor:pointer;background:#fff;color:#64748b;transition:all .12s}
        #shiftBar button:hover{background:#f1f5f9;border-color:#94a3b8}
        #shiftBar button.active{background:#059669;color:#fff;border-color:#059669}
        #shiftBar button.active:hover{background:#047857}

        /* ── NO PLAN badge / notice ── */
        .plan-badge{
            display:inline-block;margin-left:6px;padding:1px 7px;border-radius:99px;
            background:#fef3c7;color:#92400e;border:1px solid #fcd34d;
            font-size:9px;font-weight:900;letter-spacing:0.06em;vertical-align:middle;
            white-space:nowrap;
        }
        .plan-notice{
            background:#fffbeb!important;color:#92400e!important;
            font-size:10px;font-weight:800;letter-spacing:0.04em;
        }
        .plan-notice.alert{background:#fef3c7!important;border-top:2px solid #fcd34d}

        /* ── Shift change toast ── */
        .shift-toast{
            position:fixed;top:76px;left:50%;transform:translateX(-50%);
            z-index:1000;display:flex;align-items:center;gap:10px;
            padding:10px 22px;border-radius:10px;
            background:#1e40af;color:#fff;font-size:14px;font-weight:900;
            letter-spacing:0.05em;box-shadow:0 10px 30px rgba(0,0,0,0.25);
            opacity:0;pointer-events:none;
            transition:opacity .25s ease, transform .25s ease;
        }
        .shift-toast.show{opacity:1;transform:translateX(-50%)}
        .shift-toast svg{width:20px;height:20px;flex-shrink:0;animation:pulse-dot 1.2s ease-in-out infinite}
        .shift-toast.night{background:#6d28d9}
        @media(max-width:480px){.shift-toast{font-size:11px;padding:8px 14px;top:52px}.shift-toast svg{width:16px;height:16px}}
        @media(min-width:3840px){.shift-toast{font-size:30px;padding:22px 48px;top:150px;border-radius:16px}.shift-toast svg{width:44px;height:44px}}
        .live-dot{
            width:7px;height:7px;border-radius:50%;
            background:#22c55e;display:inline-block;
            animation:pulse-dot 1.5s ease-in-out infinite;
            vertical-align:middle;margin-right:4px;
        }
        @keyframes pulse-dot{0%,100%{opacity:1}50%{opacity:0.3}}
        .table-wrap{flex:1;overflow:clip;min-height:0;height:100%}
        .table-scroll{width:100%;height:100%;overflow-y:auto;overflow-x:auto;contain:layout style}
        .table-scroll::-webkit-scrollbar{width:8px;height:8px}
        .table-scroll::-webkit-scrollbar-track{background:#f1f5f9}
        .table-scroll::-webkit-scrollbar-thumb{background:#cbd5e1;border-radius:4px}
        .table-scroll::-webkit-scrollbar-thumb:hover{background:#94a3b8}

        /* ── BASE TABLE ── */
        table{width:100%;min-width:100%;border-collapse:collapse;table-layout:fixed}
        th,td{
            border:1px solid #e2e8f0;
            text-align:center;
            padding:2px 2px;
            font-size:9px;
            white-space:nowrap;
            overflow:hidden;
            text-overflow:ellipsis;
            max-width:0; /* forces table-layout:fixed to clip content, not overflow */
            color: #1e293b;
        }
        thead th{
            background:#f8fafc;font-size:8px;font-weight:800;
            text-transform:uppercase;letter-spacing:0.04em;color:#64748b;
            padding:4px 2px;
            position:sticky;top:0;z-index:2;
            white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:0;
        }
        thead th.line-header{
            background:#1e40af;color:#fff;
            font-size:10px;
            padding:6px 2px;
            overflow:hidden;text-overflow:ellipsis;
        }
        td.desc-cell{
            background:#f8fafc;font-weight:700;text-align:left;
            padding-left:6px;
            color:#334155;font-size:12px;
            position:sticky;left:0;z-index:1;
            overflow:hidden;text-overflow:ellipsis;
            max-width:none; /* sticky cells must NOT use max-width:0 trick */
            min-width:50px; /* ensure it never collapses */
        }
        td.desc-cell.detail{background:#f1f5f9;color:#64748b;font-size:10px;font-weight:600}
        tbody tr:nth-child(even) td{background:#fafafa}
        tbody tr:nth-child(even) td.desc-cell{background:#f1f5f9}
        tbody tr.row-detail{opacity:0.85}
        tbody tr.row-detail td{font-size:11px;padding:3px 4px}
        .val-plan{color:#64748b;font-weight:600}
        .val-curr{color:#334155;font-weight:700}
        .val-actual{font-weight:800; color:#1e293b;}
        .bg-green{background:#22c55e!important;color:#fff!important}
        .bg-yellow{background:#eab308!important;color:#000!important}
        .bg-red{background:#ef4444!important;color:#fff!important}
        .status-running{background:#22c55e!important;color:#fff!important;font-weight:900;overflow:hidden;text-overflow:ellipsis}
        .status-break{background:#eab308!important;color:#000!important;font-weight:900;overflow:hidden;text-overflow:ellipsis}
        .status-downtime{background:#ef4444!important;color:#fff!important;font-weight:900;overflow:hidden;text-overflow:ellipsis}
        .status-idle{background:#94a3b8!important;color:#fff!important;font-weight:900;overflow:hidden;text-overflow:ellipsis}
        .status-not-running{background:#64748b!important;color:#fff!important;font-weight:900;overflow:hidden;text-overflow:ellipsis}
        .status-tryout{background:#3b82f6!important;color:#fff!important;font-weight:900;overflow:hidden;text-overflow:ellipsis}
        .status-1stcheck{background:#a855f7!important;color:#fff!important;font-weight:900;overflow:hidden;text-overflow:ellipsis}

        @keyframes blink-red{0%,100%{opacity:1}50%{opacity:0.3}}
        @keyframes blink-yellow{0%,100%{opacity:1}50%{opacity:0.3}}
        @keyframes blink-green{0%,100%{opacity:1}50%{opacity:0.3}}
        .bg-red-blink{background:#ef4444!important;color:#fff!important;animation:blink-red .8s ease-in-out infinite!important}
        .bg-yellow-blink{background:#eab308!important;color:#000!important;animation:blink-yellow .8s ease-in-out infinite!important}
        .bg-green-blink{background:#22c55e!important;color:#fff!important;animation:blink-green .8s ease-in-out infinite!important}

        .large-table th,.large-table td{
            padding:0.4vw 0.3vw!important;
            font-size:1.1vw!important;
            overflow:hidden!important;text-overflow:ellipsis!important;max-width:0!important;
            color: #1e293b;
        }
        .right-detail-table th, .right-detail-table td {
            max-width: none !important;
            overflow: visible !important;
            text-overflow: clip !important;
            padding: 0.3vw 0.2vw !important;
            font-size: 0.75vw !important;
        }
        .right-detail-table thead th {
            font-size: 0.7vw !important;
            padding: 0.4vw 0.2vw !important;
        }
        .large-table thead th{font-size:0.9vw!important;padding:0.5vw 0.3vw!important}
        .large-table thead th.line-header{font-size:1.2vw!important;padding:0.6vw 0.3vw!important}
        .large-table td.desc-cell{font-size:1.2vw!important;padding-left:0.5vw!important}
        .large-table .val-plan, .large-table .val-curr, .large-table .val-actual { font-size: 1.3vw!important; }
        .large-table .status-running,.large-table .status-break,
        .large-table .status-downtime,.large-table .status-idle,
        .large-table .status-not-running,.large-table .status-tryout,
        .large-table .status-1stcheck{font-size:1.2vw!important;padding:0.6vw!important}
        .sep-row td{background:#e2e8f0!important;height:3px;padding:0!important;border:0!important}
        .sep-row .desc-cell{background:#e2e8f0!important}
        .detail-header td{background:#dbeafe!important;font-size:0.8vw;font-weight:800;color:#1e40af!important;padding:0.3vw 0.4vw!important}
        .detail-header .desc-cell{background:#dbeafe!important}

        /* opaque background for sticky left table */
        .left-kpi-table td{background:#fff}
        .left-kpi-table tr:nth-child(even) td{background:#fafafa}
        .left-kpi-table td.desc-cell{background:#f1f5f9}

        /* detail section */
        .det-scroll{overflow-x:auto;overflow-y:auto;max-height:320px;scrollbar-width:thin;scrollbar-color:#e5e7eb #f9fafb}
        .det-scroll::-webkit-scrollbar{height:5px;width:5px}
        .det-scroll::-webkit-scrollbar-track{background:#f9fafb}
        .det-scroll::-webkit-scrollbar-thumb{background:#d1d5db;border-radius:99px}
        .det-scroll::-webkit-scrollbar-thumb:hover{background:#9ca3af}
        .det-table{border-collapse:collapse;width:100%;min-width:900px;font-size:11px}
        .det-table thead tr{position:sticky;top:0;z-index:2;background:#f8fafc}
        .det-table thead th{padding:7px 8px;font-size:9px;font-weight:800;text-transform:uppercase;letter-spacing:0.07em;color:#6b7280;white-space:nowrap;border-bottom:2px solid #e5e7eb;background:#f8fafc}
        .det-table thead th:first-child{border-left:3px solid #e5e7eb}
        .det-table thead th:last-child{border-right:3px solid #e5e7eb}
        .det-table tbody tr{border-bottom:1px solid #f3f4f6;transition:background 0.12s}
        .det-table tbody tr:nth-child(even){background:#f9fafb}
        .det-table tbody tr:hover{background:#eff6ff!important}
        .det-table tbody tr:last-child{border-bottom:none}
        .det-table td{padding:6px 8px;font-size:10px;white-space:nowrap;vertical-align:middle;color:#6b7280}
        .det-section-label{display:flex;align-items:center;gap:8px;padding:8px 14px;background:linear-gradient(90deg,#f1f5f9 0%,#f8fafc 100%);border-top:2px solid #e5e7eb;border-bottom:1px solid #e9ecef}
        .det-section-label .label-text{font-size:10px;font-weight:900;text-transform:uppercase;letter-spacing:0.12em;color:#64748b}
        .det-section-label .label-badge{display:inline-flex;align-items:center;padding:1px 7px;background:#dbeafe;color:#1d4ed8;border-radius:99px;font-size:9px;font-weight:800;letter-spacing:0.04em}
        .det-section-label .label-badge.zero{background:#f3f4f6;color:#9ca3af}
        .det-empty{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:6px;padding:28px 16px;background:#fafafa;color:#c4c8d0}
        .det-empty svg{width:32px;height:32px;opacity:0.5}
        .det-empty span{font-size:11px;font-weight:700;letter-spacing:0.04em;color:#b0b7c3}
        .detail-section{margin-top:8px}
        .single-top-header th{
            font-size:12px!important;
            padding:6px 8px!important;
        }
        /* Fill vertical space — stretch table to container, allow .table-scroll to scroll */
        .table-scroll{position:relative}
        .table-scroll table{border-collapse:collapse}

        /* ── THEME TOGGLE BTN ── */
        .theme-btn{
            display:inline-flex;align-items:center;justify-content:center;
            width:32px;height:32px;border-radius:6px;
            background:#f1f5f9;border:1px solid #e2e8f0;
            color:#475569;cursor:pointer;transition:all 0.12s;
        }
        .theme-btn:hover{background:#e2e8f0;color:#1e293b;}

        /* ── DARK MODE ── */
        body.dark{background:#0f172a;color:#f8fafc}
        body.dark .header{background:#1e293b;border-color:#334155}
        body.dark .header-left .title{color:#f8fafc}
        body.dark .header-left .subtitle{color:#94a3b8}
        body.dark .header-right{color:#cbd5e1}
        body.dark .header-right .clock{color:#60a5fa}
        body.dark .header-right .exit-btn{background:#334155;color:#94a3b8;border-color:#475569}
        body.dark .header-right .exit-btn:hover{background:#7f1d1d;color:#fca5a5;border-color:#991b1b}
        body.dark .filter-bar button{background:#334155;color:#cbd5e1;border-color:#475569}
        body.dark .filter-bar button:hover{background:#475569}
        body.dark .filter-bar button.active{background:#3b82f6;color:#fff;border-color:#3b82f6}
        body.dark #shiftBar button{background:#334155;color:#cbd5e1;border-color:#475569}
        body.dark #shiftBar button:hover{background:#475569}
        body.dark #shiftBar button.active{background:#10b981;color:#fff;border-color:#10b981}
        body.dark th, body.dark td{border-color:#334155;color:#f8fafc}
        body.dark thead th{background:#0f172a;color:#94a3b8}
        body.dark thead th.line-header{background:#1e3a8a;color:#f8fafc}
        body.dark td.desc-cell{background:#0f172a;color:#cbd5e1}
        body.dark tbody tr:nth-child(even) td{background:#1e293b}
        body.dark tbody tr:nth-child(even) td.desc-cell{background:#1e293b}
        body.dark .table-scroll::-webkit-scrollbar-track{background:#0f172a}
        body.dark .table-scroll::-webkit-scrollbar-thumb{background:#475569}
        body.dark .table-scroll::-webkit-scrollbar-thumb:hover{background:#64748b}
        body.dark .val-plan{color:#94a3b8}
        body.dark .val-curr{color:#cbd5e1}
        body.dark .val-actual{color:#f8fafc}
        body.dark .theme-btn{background:#334155;color:#fbbf24;border-color:#475569;}
        body.dark .theme-btn:hover{background:#475569;}
        body.dark .sep-row td{background:#334155!important}
        body.dark .sep-row .desc-cell{background:#334155!important}
        body.dark .detail-header td{background:#1e3a8a!important;color:#93c5fd!important}
        body.dark .detail-header .desc-cell{background:#1e3a8a!important}

        /* ── DETAIL TABLE COLORS ── */
        .det-job{color:#1e293b}
        .det-qty{color:#374151}
        .det-good{color:#16a34a}
        .det-repair{color:#d97706}
        .det-reject{color:#dc2626}
        .det-tpt{color:#2563eb}
        body.dark .det-job{color:#f8fafc}
        body.dark .det-qty{color:#cbd5e1}
        body.dark .det-good{color:#4ade80}
        body.dark .det-repair{color:#fbbf24}
        body.dark .det-reject{color:#f87171}
        body.dark .det-tpt{color:#60a5fa}
        
        .card-bg{background:#fff}
        body.dark .card-bg{background:#0f172a}
        .border-divider{border-color:#e2e8f0}
        body.dark .border-divider{border-color:#334155}
        .progress-pct{color:#1e293b}
        body.dark .progress-pct{color:#f8fafc}

        body.dark .left-kpi-table td{background:#0f172a}
        body.dark .left-kpi-table tr:nth-child(even) td{background:#1e293b}
        body.dark .left-kpi-table td.desc-cell{background:#1e293b}

        /* ── SMALL MOBILE (<480px) ── */
        @media(max-width:480px){
            .header{height:44px;padding:0 8px}
            .header-left .title{font-size:11px}
            .header-left .subtitle{display:none}
            .header-right .clock{font-size:13px}
            .filter-bar{gap:3px}
            .filter-bar button{padding:3px 7px;font-size:9px}
            th,td{font-size:9px;padding:3px 2px}
            thead th{font-size:8px;padding:3px 2px}
            thead th.line-header{font-size:10px;padding:4px 2px}
            td.desc-cell{font-size:9px;padding-left:4px}
        }
        /* ── TABLET (768px) ── */
        @media(min-width:768px){
            .header{height:54px;padding:0 16px}
            .header-left .title{font-size:15px}
            .header-right .clock{font-size:17px}
            .filter-bar button{padding:4px 12px;font-size:10px}
            th,td{font-size:11px;padding:4px 5px}
            thead th{font-size:9px;padding:5px 4px}
            thead th.line-header{font-size:12px;padding:7px 4px}
            td.desc-cell{font-size:11px;padding-left:8px}
        }
        /* ── LAPTOP / HD (1024px) ── */
        @media(min-width:1024px){
            .header{height:56px;padding:0 18px}
            .header-left .title{font-size:16px}
            .header-right .clock{font-size:18px}
            .filter-bar button{padding:4px 13px;font-size:10px}
            th,td{font-size:12px;padding:4px 6px}
            thead th{font-size:10px;padding:6px 4px}
            thead th.line-header{font-size:13px;padding:8px 4px}
            td.desc-cell{font-size:11px;padding-left:10px}
            .large-table th,.large-table td{font-size:12px!important;padding:7px 6px!important}
            .large-table thead th{font-size:10px!important;padding:8px 6px!important}
            .large-table thead th.line-header{font-size:13px!important;padding:11px 6px!important}
            .large-table td.desc-cell{font-size:12px!important;padding-left:11px!important}
            .single-top-header th{font-size:13px!important;padding:8px 11px!important}
        }
        /* ── LAPTOP / HD (1280px) ── */
        @media(min-width:1280px){
            .header{height:60px;padding:0 20px}
            .header-left .title{font-size:18px}
            .header-right .clock{font-size:20px}
            .filter-bar button{padding:4px 14px;font-size:11px}
            th,td{font-size:12px;padding:4px 6px}
            thead th{font-size:10px;padding:6px 4px}
            thead th.line-header{font-size:13px;padding:8px 4px}
            td.desc-cell{font-size:11px;padding-left:10px}
            .large-table th,.large-table td{font-size:13px!important;padding:8px 7px!important}
            .large-table thead th{font-size:11px!important;padding:9px 7px!important}
            .large-table thead th.line-header{font-size:14px!important;padding:12px 7px!important}
            .large-table td.desc-cell{font-size:12px!important;padding-left:12px!important}
            .single-top-header th{font-size:13px!important;padding:9px 12px!important}
        }
        /* ── LAPTOP HD (1536px) ── */
        @media(min-width:1536px){
            .header{height:64px;padding:0 24px}
            .header-left .title{font-size:20px}
            .header-right .clock{font-size:22px}
            .filter-bar button{padding:5px 16px;font-size:11px}
            th,td{font-size:12px;padding:5px 7px}
            thead th{font-size:11px;padding:7px 5px}
            thead th.line-header{font-size:14px;padding:9px 5px}
            td.desc-cell{font-size:12px;padding-left:11px}
            .large-table th,.large-table td{font-size:14px!important;padding:9px 8px!important}
            .large-table thead th{font-size:12px!important;padding:10px 8px!important}
            .large-table thead th.line-header{font-size:15px!important;padding:13px 8px!important}
            .large-table td.desc-cell{font-size:13px!important;padding-left:13px!important}
            .single-top-header th{font-size:14px!important;padding:10px 13px!important}
        }
        /* ── FULL HD (1920px) ── */
        @media(min-width:1920px){
            .header{height:72px;padding:0 32px}
            .header-left .title{font-size:24px}
            .header-left .subtitle{font-size:12px}
            .header-right .clock{font-size:28px}
            .filter-bar button{padding:7px 20px;font-size:13px}
            th,td{font-size:14px;padding:6px 10px}
            thead th{font-size:12px;padding:8px 6px}
            thead th.line-header{font-size:15px;padding:11px 6px}
            td.desc-cell{font-size:13px;padding-left:14px}
            .large-table th,.large-table td{font-size:16px!important;padding:11px 9px!important}
            .large-table thead th{font-size:14px!important;padding:12px 9px!important}
            .large-table thead th.line-header{font-size:18px!important;padding:16px 9px!important}
            .large-table td.desc-cell{font-size:15px!important;padding-left:14px!important}
            .large-table .status-running,.large-table .status-break,
            .large-table .status-downtime,.large-table .status-idle,
            .large-table .status-not-running,.large-table .status-tryout,
            .large-table .status-1stcheck{font-size:17px!important;padding:13px!important}
            .single-top-header th{font-size:17px!important;padding:12px 16px!important}
        }
        /* ── QHD / 2K (2560px) ── */
        @media(min-width:2560px){
            .header{height:90px;padding:0 48px}
            .header-left .title{font-size:30px}
            .header-left .subtitle{font-size:15px}
            .header-right .clock{font-size:36px}
            .filter-bar button{padding:10px 28px;font-size:16px}
            th,td{font-size:18px;padding:10px 14px}
            thead th{font-size:15px;padding:12px 10px}
            thead th.line-header{font-size:20px;padding:16px 10px}
            td.desc-cell{font-size:17px;padding-left:20px}
            .large-table th,.large-table td{font-size:20px!important;padding:15px 12px!important}
            .large-table thead th{font-size:18px!important;padding:16px 12px!important}
            .large-table thead th.line-header{font-size:22px!important;padding:20px 12px!important}
            .large-table td.desc-cell{font-size:19px!important;padding-left:20px!important}
            .large-table .status-running,.large-table .status-break,
            .large-table .status-downtime,.large-table .status-idle,
            .large-table .status-not-running,.large-table .status-tryout,
            .large-table .status-1stcheck{font-size:21px!important;padding:17px!important}
            .single-top-header th{font-size:21px!important;padding:16px 22px!important}
        }
        /* ── 4K / 65-INCH MONITOR (3840px) ── */
        @media(min-width:3840px){
            .header{height:120px;padding:0 64px}
            .header-left .title{font-size:40px}
            .header-left .subtitle{font-size:20px}
            .header-right .clock{font-size:48px}
            .filter-bar{gap:12px}
            .filter-bar button{padding:14px 36px;font-size:22px;border-radius:10px}
            #shiftBar button{padding:14px 36px;font-size:22px;border-radius:10px}
            .live-dot{width:14px;height:14px;margin-right:10px}
            th,td{font-size:24px;padding:14px 20px}
            thead th{font-size:20px;padding:18px 16px}
            thead th.line-header{font-size:28px;padding:24px 16px}
            td.desc-cell{font-size:22px;padding-left:28px}
            .large-table th,.large-table td{font-size:26px!important;padding:22px 18px!important}
            .large-table thead th{font-size:22px!important;padding:24px 18px!important}
            .large-table thead th.line-header{font-size:30px!important;padding:30px 18px!important}
            .large-table td.desc-cell{font-size:25px!important;padding-left:26px!important}
            .large-table .status-running,.large-table .status-break,
            .large-table .status-downtime,.large-table .status-idle,
            .large-table .status-not-running,.large-table .status-tryout,
            .large-table .status-1stcheck{font-size:27px!important;padding:24px!important}
            .single-top-header th{font-size:28px!important;padding:24px 32px!important}
        }
    </style>
</head>
<body>
<div class="monitor-container">
    <div class="header">
        <div class="header-left">
            <div class="title"><span class="live-dot"></span>PRODUCTION PRESS ACHIEVEMENT</div>
            <div class="subtitle">STAMPING PLANT — MONITOR MODE</div>
        </div>
        <div class="header-right">
            <div class="filter-bar" id="filterBar">
                <button class="active" data-line="all">Semua</button>
                <button data-line="A">A</button>
                <button data-line="B">B</button>
                <button data-line="C">C</button>
                <button data-line="D">D</button>
            </div>
            <div class="filter-bar" id="shiftBar">
                <button onclick="setShift(1,true)" data-shift="1" class="active">Shift Pagi</button>
                <button onclick="setShift(2,true)" data-shift="2" class="">Shift Malam</button>
            </div>
            <button class="theme-btn" onclick="toggleTheme()" title="Toggle Dark/Light Mode" id="themeBtn" style="margin-left: 4px; margin-right: 8px;">
                <svg id="themeIcon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                </svg>
            </button>
            <div style="text-align: right; padding-right: 6px;">
                <div id="shiftLabel">Shift A</div>
                <div id="dateLabel">--</div>
            </div>
            <div class="clock" id="liveClock">--:--:--</div>
            <a href="{{ route('supervisor.dashboard') }}" class="exit-btn">
                <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M6 18L18 6M6 6l12 12"/></svg>
                Exit
            </a>
        </div>
    </div>
    <div class="table-wrap">
        <div class="table-scroll">
            <table id="monitorTable">
                <thead id="monitorThead"></thead>
                <tbody id="monitorTbody"></tbody>
            </table>
        </div>
    </div>
</div>

<div id="shiftToast" class="shift-toast" role="alert">
    <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
    <span id="shiftToastMsg"></span>
</div>

<script>
// Init theme early to prevent flash
(function(){
    if(localStorage.getItem('monitor-theme')==='dark'){
        document.body.classList.add('dark');
    }
})();

function toggleTheme() {
    const body = document.body;
    body.classList.toggle('dark');
    const isDark = body.classList.contains('dark');
    localStorage.setItem('monitor-theme', isDark ? 'dark' : 'light');
    updateThemeIcon(isDark);
}
function updateThemeIcon(isDark) {
    const icon = document.getElementById('themeIcon');
    if(!icon) return;
    if(isDark) {
        icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />';
    } else {
        icon.innerHTML = '<path d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>';
    }
}
document.addEventListener('DOMContentLoaded', () => {
    updateThemeIcon(document.body.classList.contains('dark'));
});

const LINES = @json($lines);
let LINE_KPI = {};
let LINE_META = {};
let LINE_DETAIL = {};
let LINE_STATUSES = {};
let LAST_HASH = '';
let selectedLine = null;
const _initTime = new Date();
const _h = _initTime.getHours();
const _m = _initTime.getMinutes();
const _isShift2 = (_h >= 21 || _h < 7 || (_h === 7 && _m < 30));
let selectedShift = _isShift2 ? 2 : 1;
let prevCellClasses = {};
let lastManualShiftAt = 0;
let shiftToastTimer = null;

function currentShiftFromClock(){
    const n = new Date(), h = n.getHours(), m = n.getMinutes();
    return (h >= 21 || h < 7 || (h === 7 && m < 30)) ? 2 : 1;
}

function showShiftToast(s) {
    const el = document.getElementById('shiftToast');
    const msg = document.getElementById('shiftToastMsg');
    if (!el || !msg) return;
    msg.textContent = s === 1 ? '⏰ SHIFT BERUBAH → SHIFT PAGI' : '⏰ SHIFT BERUBAH → SHIFT MALAM';
    el.classList.toggle('night', s === 2);
    el.classList.add('show');
    clearTimeout(shiftToastTimer);
    shiftToastTimer = setTimeout(() => el.classList.remove('show'), 8000);
}

function setShift(s, fromButton) {
    const prev = selectedShift;
    selectedShift = s;
    if (fromButton) lastManualShiftAt = Date.now();
    detailPage = 0;
    detailPages = 1;
    stopRotate();
    document.querySelectorAll('#shiftBar button').forEach(b => b.classList.remove('active'));
    document.querySelector(`#shiftBar button[data-shift="${s}"]`)?.classList.add('active');
    allCells = null;
    LAST_HASH = '';
    fetchData();
    if (s !== prev) showShiftToast(s);
}

const KPI_ROWS = ['JOB','QTY','GSPH','STROKE','REPAIR','REJECT','TOTAL_DT','DIES_T','PROD_T','MACH_T','MAT_T','LOG_T','OVERTIME'];

function pad(n){ return String(n).padStart(2,'0') }

function updateClock(){
    const n=new Date();
    document.getElementById('liveClock').textContent=`${pad(n.getHours())}:${pad(n.getMinutes())}:${pad(n.getSeconds())}`;
    document.getElementById('dateLabel').textContent=n.toLocaleDateString('en-US',{day:'2-digit',month:'short',year:'numeric'});
    document.getElementById('shiftLabel').textContent = selectedShift === 1 ? 'Shift Pagi' : 'Shift Malam';
    document.querySelectorAll('#shiftBar button').forEach(b => b.classList.remove('active'));
    document.querySelector(`#shiftBar button[data-shift="${selectedShift}"]`)?.classList.add('active');

    const auto = currentShiftFromClock();
    if (auto !== selectedShift && (Date.now() - lastManualShiftAt) > 5*60*1000) {
        setShift(auto, false);
    }
}
setInterval(updateClock,1000);
updateClock();

document.getElementById('filterBar').addEventListener('click',function(e){
    const btn=e.target.closest('button'); if(!btn) return;
    document.querySelectorAll('#filterBar button').forEach(b=>b.classList.remove('active'));
    btn.classList.add('active');
    const newSel=btn.dataset.line==='all'?null:btn.dataset.line;
    if((selectedLine===null)!==(newSel===null)) allCells=null; // flush cache when switching mode
    selectedLine=newSel;
    detailPage = 0;
    detailPages = 1;
    stopRotate();
    renderTable();
});

function fetchData(){
    const n=new Date();
    if (n.getHours() < 7 || (n.getHours() === 7 && n.getMinutes() < 30)) {
        n.setDate(n.getDate() - 1);
    }
    const date=`${n.getFullYear()}-${pad(n.getMonth()+1)}-${pad(n.getDate())}`;
    const shift=selectedShift;

    Promise.all([
        fetch(`{{ route('supervisor.dashboard.api') }}?date=${date}&shift=${shift}`).then(r=>r.json()),
        fetch(`{{ route('supervisor.dashboard.detail') }}?date=${date}&shift=${shift}`).then(r=>r.json()),
        fetch(`{{ route('supervisor.overview.lineStatus') }}`).then(r=>r.json()),
    ]).then(([kpi,det,sts])=>{
        const h=JSON.stringify(kpi.line_kpi)+JSON.stringify(kpi.line_meta)+JSON.stringify(det.detail)+JSON.stringify(sts.line_statuses);
        if(h===LAST_HASH) return;
        LAST_HASH=h;
        LINE_KPI=kpi.line_kpi||{};
        LINE_META=kpi.line_meta||{};
        LINE_DETAIL=det.detail||{};
        LINE_STATUSES=sts.line_statuses||{};
        renderTable();
    }).catch(e=>console.error('fetch err',e));
}

function kv(line,desc){
    for(const k of(LINE_KPI[line]||[])) if(k.desc===desc) return k;
    return null;
}

function meta(line,k){ return (LINE_META[line]||{})[k]||'-' }

function noPlan(line){
    const jp = meta(line,'jobPlan');
    if (jp === '0' || jp === '-') return true;
    const k = kv(line,'QTY');
    return !k || (parseFloat(k.plan) || 0) <= 0;
}

function getClasses(desc, k){
    if(!k) return {act:'', curr:'', style:''};
    let act='', curr='', style='';
    if(desc==='GSPH'){
        const planVal = parseFloat(k.plan);
        const actVal = parseFloat(k.actual);
        if(!isNaN(actVal) && !isNaN(planVal) && planVal>0){
            const pct = (actVal/planVal)*100;
            act = pct>=100?'bg-green':pct>=80?'bg-yellow':'bg-red';
        } else if(actVal>0) act = 'bg-green';
        
        if(k.current && k.current!=='-'){
            const currVal = parseFloat(k.current);
            if(!isNaN(currVal) && !isNaN(planVal) && planVal>0){
                const currPct = (currVal/planVal)*100;
                curr = currPct>=100?'bg-green':currPct>=80?'bg-yellow':'bg-red';
            } else if(currVal>0) curr = 'bg-green';
        }
    }else if(desc==='REPAIR'||desc==='REJECT'){
        const p=parseFloat(k.actualPct||k.actual);
        if(p>5) act='bg-red';
        else if(p>2) act='bg-yellow';
        
        if(k.current && k.current!=='-' && k.current!=='0 pcs' && parseFloat(k.current)>0) curr = act?act+'-blink':'';
    }else if(['DIES_T','PROD_T','TOTAL_DT','MACH_T','MAT_T','LOG_T','OVERTIME'].includes(desc)){
        const v=parseFloat(k.actual);
        if(!isNaN(v) && v>0) act='bg-red';
        
        if(k.current && k.current!=='-' && k.current!=='0 m' && parseFloat(k.current)>0) curr='bg-red-blink';
    }
    
    if(act==='bg-yellow') style='color:#000!important;';
    else if(act!=='') style='color:#fff!important;';
    
    return {act, curr, style};
}

function dtCell(v,st,cls){ return `<td class="${cls||''}" style="${st||''}">${v}</td>`; }
function chk(c){ return c?'<span style="display:inline-flex;align-items:center;justify-content:center;width:1vw;height:1vw;border-radius:0.2vw;border:0.1vw solid #22c55e;background:#f0fdf4;color:#16a34a;font-size:0.7vw;font-weight:900;line-height:1">&#10003;</span>':'<span style="display:inline-flex;align-items:center;justify-content:center;padding:0 0.3vw;border-radius:0.2vw;background:#f3f4f6;color:#9ca3af;font-size:0.7vw;font-weight:700">-</span>'; }
function chkP(c, lbl){
    return `<div style="display:flex;flex-direction:column;align-items:center;gap:0.1vw;"><span style="font-size:0.5vw;color:#64748b;line-height:1;font-weight:800">${lbl}</span>` + (c?'<span style="display:inline-flex;align-items:center;justify-content:center;width:0.8vw;height:0.8vw;border-radius:0.1vw;border:0.1vw solid #22c55e;background:#f0fdf4;color:#16a34a;font-size:0.6vw;font-weight:900;line-height:1">&#10003;</span>':'<span style="display:inline-flex;align-items:center;justify-content:center;width:0.8vw;height:0.8vw;border-radius:0.1vw;background:#f3f4f6;color:#9ca3af;font-size:0.6vw;font-weight:700;line-height:1">-</span>') + `</div>`;
}

// ── Fit-to-screen + auto-rotate helpers ──
const MIN_FIT = 0.6;
const ROTATE_MS = 15000;
let detailPage = 0;
let detailPages = 1;
let rotateTimer = null;

function buildRightBody(rows, maxRows){
    maxRows = maxRows || rows.length;
    let h = '';
    let renderedRightRowspan = false;
    for (let i = 0; i < maxRows; i++) {
        const isDataRow = i < rows.length;
        h += `<tr style="${isDataRow ? 'height:1px;' : ''}">`;
        if (isDataRow) {
            const j = rows[i];
            const pt = j.press_time > 0 ? j.press_time + ' m' : '-';
            const dn = j.dandori > 0 ? j.dandori + ' m' : '-';
            const iq = j.iq_check > 0 ? j.iq_check + ' m' : '-';
            const dw = j.downtime > 0 ? j.downtime + ' m' : '-';
            const tp = j.tpt > 0 ? j.tpt + ' m' : '-';
            
            let gsphSt = 'font-weight:700;';
            let gsphTxt = (j.gsph_plan > 0 || j.gsph_actual > 0) ? j.gsph_actual : '-';
            
            if (j.gsph_plan > 0) {
                const pct = (j.gsph_actual / j.gsph_plan) * 100;
                if (pct >= 100) {
                    gsphSt = 'background-color:#22c55e!important; color:#fff!important; font-weight:900;';
                } else if (pct >= 80) {
                    gsphSt = 'background-color:#eab308!important; color:#000!important; font-weight:900;';
                } else {
                    gsphSt = 'background-color:#ef4444!important; color:#fff!important; font-weight:900; animation:blink-red .8s ease-in-out infinite';
                    gsphTxt = `${j.gsph_actual} <span style="font-size:0.6vw;">⚠️</span>`;
                }
            } else if (j.gsph_actual > 0) {
                gsphSt = 'background-color:#22c55e!important; color:#fff!important; font-weight:900;';
            }

            h += `
                ${dtCell(j.no, 'border-left:none;')}
                ${dtCell(j.job_number, 'text-align:left;font-weight:600;', 'det-job')}
                ${dtCell(`<div style="display:flex;gap:0.3vw;justify-content:center;align-items:center;">${chkP(j.p1,'P1')}${chkP(j.p2,'P2')}${chkP(j.p3,'P3')}${chkP(j.p4,'P4')}</div>`)}
                ${dtCell(j.plan_qty, '', 'det-qty')}
                ${dtCell(j.good, 'font-weight:600;', 'det-good')}
                ${dtCell(j.repair, 'font-weight:600;', 'det-repair')}
                ${dtCell(j.reject, 'font-weight:600;', 'det-reject')}
                ${dtCell(pt)}
                ${dtCell(dn)}
                ${dtCell(iq)}
                ${dtCell(dw, j.downtime > 0 ? 'background-color:#ef4444!important; color:#fff!important; font-weight:700; animation:blink-red .8s ease-in-out infinite' : '')}
                ${dtCell(tp, 'font-weight:700;', 'det-tpt')}
                ${dtCell(j.plan_finish)}
                ${dtCell(j.actual_finish)}
                ${dtCell(gsphTxt, gsphSt)}
            `;
        } else {
            if (!renderedRightRowspan) {
                const remainingRight = maxRows - rows.length;
                h += `<td rowspan="${remainingRight}" colspan="15" style="background:#fff; border:1px solid #e2e8f0; border-left:none; vertical-align:middle; text-align:center; color:#94a3b8; font-size:11px; font-weight:600; height:100%;">TIDAK ADA JADWAL PRODUKSI TAMBAHAN</td>`;
                renderedRightRowspan = true;
            }
        }
        h += '</tr>';
    }
    return h;
}

function computeFit(root){
    const scrollEl = document.querySelector('.table-scroll');
    if(!root || !scrollEl) return 1;
    root.style.zoom = '';
    const availW = scrollEl.clientWidth || window.innerWidth;
    const availH = scrollEl.clientHeight || window.innerHeight;
    const natW = root.offsetWidth || availW;
    const natH = root.offsetHeight || availH;
    let s = Math.min(1, availW / natW, availH / natH);
    return (isFinite(s) && s > 0) ? s : 1;
}

function applyFit(root){
    const scrollEl = document.querySelector('.table-scroll');
    if(!root || !scrollEl) return;
    
    // Disable auto-zoom / scaling because VW units handle responsiveness natively
    scrollEl.style.overflow = 'hidden';
    root.style.transform = '';
    root.style.width = '100%';
    root.style.height = '100%';
    root.style.zoom = '';
}

function stopRotate(){
    if(rotateTimer){ clearInterval(rotateTimer); rotateTimer = null; }
}

function startRotate(){
    stopRotate();
    if(detailPages > 1){
        rotateTimer = setInterval(()=>{
            detailPage = (detailPage + 1) % detailPages;
            renderTable();
        }, ROTATE_MS);
    }
}

function refitRoot(){
    const scrollEl = document.querySelector('.table-scroll');
    const root = scrollEl && scrollEl.firstElementChild;
    if(root) requestAnimationFrame(()=>applyFit(root));
}

// ── Cell cache for incremental rendering (Semua view) ──
let allCells = null;
let allStatusCells = null;

function renderAllLines(){
    const scrollEl=document.querySelector('.table-scroll');
    scrollEl.style.overflow='auto';
    scrollEl.style.overflowX='hidden';
    scrollEl.style.display='block';
    scrollEl.style.width='100%';
    scrollEl.style.height='100%';

    if(!allCells){
        scrollEl.innerHTML=`
            <table id="monitorTable" class="large-table" style="width:100%;height:100%;table-layout:fixed">
                <thead id="monitorThead"></thead>
                <tbody id="monitorTbody"></tbody>
            </table>
        `;
        const thead=document.getElementById('monitorThead');
        let hh='<tr><th rowspan="2" style="width:8%;min-width:60px;overflow:hidden;text-overflow:ellipsis;font-size:16px;text-align:left;padding-left:4px;">DESC</th>';
        LINES.forEach((l,li)=>{hh+=`<th colspan="3" class="line-header">${l} <span id="planBadge-${li}" class="plan-badge" style="display:none">NO PLAN</span></th>`});
        hh+='</tr><tr>';
        LINES.forEach(()=>{hh+='<th style="font-size:16px;">PLAN</th><th style="font-size:16px;">CURR</th><th style="font-size:16px;">ACTUAL</th>'});
        hh+='</tr>';
        thead.innerHTML=hh;

        const tbody=document.getElementById('monitorTbody');
        let b='';
        const cellMap={};
        KPI_ROWS.forEach(desc=>{
            let descHtml = desc;
            if(desc === 'GSPH') {
                descHtml = `${desc} <span title="⚠️ GSPH bersifat Real-Time dan bisa melompat liar di menit awal" style="cursor:help; color:#f59e0b; font-size:11px;">⚠️</span>`;
            }
            b+=`<tr id="row-${desc}"><td class="desc-cell">${descHtml}</td>`;
            LINES.forEach((line,li)=>{
                ['plan','curr','actual'].forEach((col,ci)=>{
                    const id=`cell-${desc}-${li}-${col}`;
                    cellMap[id]=1;
                    b+=`<td id="${id}" class="val-${col}"></td>`;
                });
            });
            b+='</tr>';
        });
        // STATUS row
        b+=`<tr id="row-STATUS"><td class="desc-cell" style="font-size:13px">STATUS</td>`;
        LINES.forEach((line,li)=>{
            b+=`<td id="status-${li}" colspan="3" class="status-not-running"></td>`;
        });
        b+='</tr>';
        tbody.innerHTML=b;

        allCells={};
        KPI_ROWS.forEach(desc=>{
            LINES.forEach((line,li)=>{
                ['plan','curr','actual'].forEach(col=>{
                    const el=document.getElementById(`cell-${desc}-${li}-${col}`);
                    if(el) allCells[`${desc}-${li}-${col}`]=el;
                });
            });
        });
        allStatusCells=[];
        LINES.forEach((line,li)=>{
            const el=document.getElementById(`status-${li}`);
            if(el) allStatusCells.push(el);
        });

        // Fill initial data
        updateAllCells();
        requestAnimationFrame(()=>applyFit(document.querySelector('.table-scroll')?.firstElementChild));
        return;
    }
    updateAllCells();
}

function updateAllCells(){
    KPI_ROWS.forEach((desc,di)=>{
        LINES.forEach((line,li)=>{
            const getCell=(col)=>allCells[`${desc}-${li}-${col}`];
            if(desc==='JOB'){
                setText(getCell('plan'),meta(line,'jobPlan'));
                setText(getCell('curr'),meta(line,'job'));
                setText(getCell('actual'),meta(line,'jobActual'));
            }else if(desc==='STROKE'){
                const s=meta(line,'stroke'),cs=meta(line,'currStroke');
                setText(getCell('plan'),meta(line,'strokePlan')||'-');
                setText(getCell('curr'),cs==='-'?'-':Number(cs||0).toLocaleString('id-ID'));
                setText(getCell('actual'),s==='-'?'-':Number(s).toLocaleString('id-ID'));
            }else{
                const k=kv(line,desc);
                if(k){
                    setText(getCell('plan'),k.plan||'-');
                    setText(getCell('curr'),k.current||'-');
                    const cl = getClasses(desc, k);
                    const pct=k.actualPct?` ${k.actualPct}`:'';
                    const ac=getCell('actual');
                    const cu=getCell('curr');
                    setText(ac,(k.actual||'-')+pct);
                    cu.className = 'val-curr'+(cl.curr?' '+cl.curr:'');
                    ac.className = 'val-actual'+(cl.act?' '+cl.act:'');
                    ac.style.cssText = cl.style || '';
                }else{
                    setText(getCell('plan'),'-');
                    setText(getCell('curr'),'-');
                    setText(getCell('actual'),'-');
                }
            }
        });
    });
    // STATUS updates
    LINES.forEach((line,li)=>{
        const cell=allStatusCells[li];
        if(!cell)return;
        const s=LINE_STATUSES[line];
        if(s){
            const l=(s.label||'NOT RUNNING').toUpperCase();
            const cls=l==='RUNNING'||l==='PRODUCTION'?'status-running':l==='BREAK'||l==='BREAKTIME'?'status-break':l==='DOWNTIME'||l==='TROUBLE'?'status-downtime':l==='TRYOUT'?'status-tryout':l==='1ST CHECK'?'status-1stcheck':l==='IDLE'?'status-idle':'status-not-running';
            if(cell.textContent!==l) cell.textContent=l;
            if(cell.className!==cls) cell.className=cls;
        }else{
            if(cell.textContent!=='—') cell.textContent='—';
            if(cell.className!=='status-not-running') cell.className='status-not-running';
        }
    });
    // NO PLAN badge + notice
    LINES.forEach((line,li)=>{
        const np=noPlan(line);
        const badge=document.getElementById(`planBadge-${li}`);
        if(badge) badge.style.display=np?'inline-block':'none';
    });
}

function setText(el,v){
    if(!el)return;
    if(el.textContent!==v) el.textContent=v;
}

function renderTable(){
    const scrollEl=document.querySelector('.table-scroll');

    // Reset styles to clean state before each render
    scrollEl.style.overflowX='';
    scrollEl.style.overflowY='';
    scrollEl.style.display='block';
    scrollEl.style.width='100%';
    scrollEl.style.height='100%';

    // ── SEMUA (ALL LINES) ──
    if(!selectedLine){
        renderAllLines();
        return;
    }

    // ── SINGLE LINE (A/B/C/D) ──
    const line=selectedLine;
    const lineKey = LINES.find(l => l === line || l === `PRESS ${line}` || l.endsWith(` ${line}`)) || line;

    // Calculate percentage
    const qtyKpi = kv(lineKey, 'QTY');
    const planVal = qtyKpi ? parseFloat(qtyKpi.plan) : 0;
    const actVal = qtyKpi ? parseFloat(qtyKpi.actual) : 0;
    const pct = planVal > 0 ? ((actVal / planVal) * 100).toFixed(2) : '0.00';

    const leftRows = ['JOB','QTY','GSPH','STROKE','REPAIR','REJECT','TOTAL_DT','DIES_T','PROD_T','MACH_T','MAT_T','LOG_T','OVERTIME'];
    const rightRows = LINE_DETAIL[lineKey] || [];

    // CASE A: No detail jobs scheduled -> Make KPI table take 100% full width to prevent empty columns on the right
    if (rightRows.length === 0) {
        scrollEl.innerHTML = `
            <table id="monitorTable" class="large-table" style="width: 100%; height: 100%; table-layout: fixed;">
                <thead id="monitorThead"></thead>
                <tbody id="monitorTbody"></tbody>
            </table>
        `;
        const thead = document.getElementById('monitorThead');
        const tbody = document.getElementById('monitorTbody');
        const tbl = document.getElementById('monitorTable');

        scrollEl.style.overflowX='hidden';
        scrollEl.style.overflowY='auto';
        tbl.style.width='100%';
        tbl.style.height='100%';
        tbl.style.tableLayout='fixed';

        thead.innerHTML = `
            <tr class="single-top-header">
                <th colspan="4" style="background:#1e40af; color:#fff; font-weight:900; letter-spacing:0.08em; text-align:left; padding-left:14px;">PRESS ${line}</th>
            </tr>
            <tr>
                <th style="width:25%;font-size:18px;text-align:left;padding-left:6px;">DESC</th>
                <th style="width:25%;font-size:18px;">PLAN</th>
                <th style="width:25%;font-size:18px;">CURR</th>
                <th style="width:25%;font-size:18px;">ACTUAL</th>
            </tr>
        `;

        let b = '';
        leftRows.forEach(desc => {
            b += '<tr>';
            let descHtml = desc;
            if(desc === 'GSPH') {
                descHtml = `${desc} <span title="⚠️ GSPH bersifat Real-Time dan bisa melompat liar di menit awal" style="cursor:help; color:#f59e0b; font-size:11px;">⚠️</span>`;
            }
            b += `<td class="desc-cell" style="width:25%; max-width:none;">${descHtml}</td>`;

            if (desc === 'JOB') {
                b += `<td class="val-plan">${meta(lineKey,'jobPlan')}</td>`;
                b += `<td class="val-curr">${meta(lineKey,'job')}</td>`;
                b += `<td class="val-actual">${meta(lineKey,'jobActual')}</td>`;
            } else if (desc === 'STROKE') {
                const s=meta(lineKey,'stroke'), cs=meta(lineKey,'currStroke');
                b += `<td class="val-plan">${meta(lineKey,'strokePlan')||'-'}</td>`;
                b += `<td class="val-curr">${cs==='-'?'-':Number(cs||0).toLocaleString('id-ID')}</td>`;
                b += `<td class="val-actual">${s==='-'?'-':Number(s).toLocaleString('id-ID')}</td>`;
            } else {
                const k = kv(lineKey, desc);
                if (k) {
                    const cl = getClasses(desc, k);
                    const pct = k.actualPct ? ` ${k.actualPct}` : '';
                    b += `<td class="val-plan">${k.plan||'-'}</td>`;
                    b += `<td class="val-curr ${cl.curr}">${k.current||'-'}</td>`;
                    b += `<td class="val-actual ${cl.act}" style="${cl.style}">${k.actual||'-'}${pct}</td>`;
                } else {
                    b += `<td>-</td><td>-</td><td>-</td>`;
                }
            }
            b += '</tr>';
        });

        // STATUS
        const st = LINE_STATUSES[lineKey];
        let statusVal = '—';
        let statusClass = 'status-not-running';
        if (st) {
            const l = (st.label || 'NOT RUNNING').toUpperCase();
            statusClass = l === 'RUNNING' || l === 'PRODUCTION' ? 'status-running' : l === 'BREAK' || l === 'BREAKTIME' ? 'status-break' : l === 'DOWNTIME' || l === 'TROUBLE' ? 'status-downtime' : l === 'TRYOUT' ? 'status-tryout' : l === '1ST CHECK' ? 'status-1stcheck' : l === 'IDLE' ? 'status-idle' : 'status-not-running';
            statusVal = l;
        }

        b += `
            <tr>
                <td class="desc-cell status-desc">STATUS</td>
                <td colspan="3" class="${statusClass}" style="text-align:left; padding-left: 20px; font-weight:900;">${statusVal}</td>
            </tr>
        `;

        // PROGRESS PRODUKSI
        const progressPct = parseFloat(pct);
        const barColor = progressPct >= 100 ? '#22c55e' : progressPct >= 80 ? '#eab308' : '#ef4444';
        b += `
            <tr class="progress-row">
                <td colspan="4" class="card-bg border-divider" style="padding:8px 12px; text-align:left; border-top-width:2px; border-top-style:solid;">
                    <div style="font-size:9px; font-weight:800; text-transform:uppercase; letter-spacing:0.1em; color:#64748b; margin-bottom:4px;">PROGRESS PRODUKSI</div>
                    <div style="display:flex; align-items:center; gap:8px; width:100%;">
                        <div style="flex:1; background:#e2e8f0; height:10px; border-radius:9999px; overflow:hidden; border:1px solid #cbd5e1; padding:2px;">
                            <div style="background:${barColor}; width:${Math.min(progressPct,100)}%; height:100%; border-radius:9999px; transition:width 0.5s ease-in-out;"></div>
                        </div>
                        <div class="progress-pct" style="font-size:12px; font-weight:900; min-width:50px; text-align:right;">${pct}%</div>
                    </div>
                </td>
            </tr>
        `;

        tbody.innerHTML = b;
        requestAnimationFrame(()=>applyFit(tbl));
        return;
    }

    // CASE B: Split screen but seamlessly touching (gap: 0px) with matched rowspans
    // Build left side HTML
    let leftBodyHtml = '';
    for (let i = 0; i < leftRows.length; i++) {
        leftBodyHtml += '<tr>';
        const desc = leftRows[i];
        
        let descHtml = desc;
        if(desc === 'GSPH') {
            descHtml = `${desc} <span title="⚠️ GSPH bersifat Real-Time dan bisa melompat liar di menit awal" style="cursor:help; color:#f59e0b; font-size:11px;">⚠️</span>`;
        }
        leftBodyHtml += `<td class="desc-cell">${descHtml}</td>`;

        if (desc === 'JOB') {
            leftBodyHtml += `<td class="val-plan">${meta(lineKey,'jobPlan')}</td>`;
            leftBodyHtml += `<td class="val-curr">${meta(lineKey,'job')}</td>`;
            leftBodyHtml += `<td class="val-actual" style="border-right:none;">${meta(lineKey,'jobActual')}</td>`;
        } else if (desc === 'STROKE') {
            const s=meta(lineKey,'stroke'), cs=meta(lineKey,'currStroke');
            leftBodyHtml += `<td class="val-plan">${meta(lineKey,'strokePlan')||'-'}</td>`;
            leftBodyHtml += `<td class="val-curr">${cs==='-'?'-':Number(cs||0).toLocaleString('id-ID')}</td>`;
            leftBodyHtml += `<td class="val-actual" style="border-right:none;">${s==='-'?'-':Number(s).toLocaleString('id-ID')}</td>`;
        } else {
            const k = kv(lineKey, desc);
            if (k) {
                const cl = getClasses(desc, k);
                const pctVal = k.actualPct ? ` ${k.actualPct}` : '';
                leftBodyHtml += `<td class="val-plan">${k.plan||'-'}</td>`;
                leftBodyHtml += `<td class="val-curr ${cl.curr}">${k.current||'-'}</td>`;
                leftBodyHtml += `<td class="val-actual ${cl.act}" style="border-right:none;${cl.style}">${k.actual||'-'}${pctVal}</td>`;
            } else {
                leftBodyHtml += `<td>-</td><td>-</td><td style="border-right:none;">-</td>`;
            }
        }
        leftBodyHtml += '</tr>';
    }



    // Build right side HTML (handled inside renderCaseB with fit/pagination)
    const st = LINE_STATUSES[lineKey];
    let statusVal = '—';
    let statusClass = 'status-not-running';
    if (st) {
        const l = (st.label || 'NOT RUNNING').toUpperCase();
        statusClass = l === 'RUNNING' || l === 'PRODUCTION' ? 'status-running' : l === 'BREAK' || l === 'BREAKTIME' ? 'status-break' : l === 'DOWNTIME' || l === 'TROUBLE' ? 'status-downtime' : l === 'TRYOUT' ? 'status-tryout' : l === '1ST CHECK' ? 'status-1stcheck' : l === 'IDLE' ? 'status-idle' : 'status-not-running';
        statusVal = l;
    }

    const progressPct = parseFloat(pct);
    const barColor = progressPct >= 100 ? '#22c55e' : progressPct >= 80 ? '#eab308' : '#ef4444';

    // Insert split container layout (reusable for full or per-page render)
    const renderCaseB = (rows, pageInfo, matchLeft) => {
        const body = buildRightBody(rows, matchLeft ? Math.max(leftRows.length, rows.length) : rows.length);
        const pageTag = pageInfo ? ` <span style="display:inline-flex;align-items:center;background:rgba(255,255,255,0.2);border-radius:99px;padding:1px 10px;font-size:11px;letter-spacing:0.1em;vertical-align:middle;">${pageInfo.page}/${pageInfo.pages}</span>` : '';
        scrollEl.innerHTML = `
        <div style="display:flex; flex-direction:column; width:100%; min-height:100%;">
            <div style="display:flex; gap:0px; width:100%; flex:1; align-items:stretch;">
                <!-- Left KPI Table -->
                <div class="card-bg" style="width:28%; display:flex; flex-direction:column; position:sticky; left:0; z-index:10; border-right:3px solid #cbd5e1; box-shadow:4px 0 8px rgba(0,0,0,0.05); flex-shrink: 0;">
                    <table class="large-table left-kpi-table" style="width:100%; flex:1; table-layout:fixed; border-collapse:collapse; border-right:none;">
                        <thead>
                            <tr class="single-top-header">
                                <th colspan="4" style="background:#1e40af; color:#fff; font-weight:900; letter-spacing:0.08em; text-align:left; padding-left:14px; height:30px; border-right:none;">PRESS ${line}</th>
                            </tr>
                            <tr style="height:25px;">
                                <th style="width:28%;font-size:1.1vw;text-align:left;padding-left:6px;">DESC</th>
                                <th style="width:24%;font-size:1.1vw;">PLAN</th>
                                <th style="width:24%;font-size:1.1vw;">CURR</th>
                                <th style="width:24%; border-right:none;font-size:1.1vw;">ACTUAL</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${leftBodyHtml}
                        </tbody>
                    </table>
                </div>
                <!-- Right Detail Table -->
                <div style="width:72%; display:flex; flex-direction:column; overflow:hidden;">
                    <table class="large-table right-detail-table" style="width:100%; flex:1; table-layout:auto; border-collapse:collapse; border-left:none;">
                        <thead>
                            <tr class="single-top-header">
                                <th colspan="15" style="background:#1e40af; color:#fff; font-weight:900; letter-spacing:0.08em; text-align:right; padding-right:14px; height:30px; border-left:none;">DETAIL PRODUKSI : ${pct}%${pageTag}</th>
                            </tr>
                            <tr style="height:25px;">
                                <th style="border-left:none; white-space:nowrap;">NO</th>
                                <th style="white-space:nowrap;">JOB NO</th>
                                <th style="white-space:nowrap;">PROCESS</th>
                                <th style="white-space:nowrap;">PLAN QTY</th>
                                <th class="det-good" style="white-space:nowrap;">GOOD</th>
                                <th class="det-repair" style="white-space:nowrap;">REP</th>
                                <th class="det-reject" style="white-space:nowrap;">REJ</th>
                                <th style="white-space:nowrap;">PRESS TIME</th>
                                <th style="white-space:nowrap;">DANDORI</th>
                                <th style="white-space:nowrap;">10 CHECK</th>
                                <th style="white-space:nowrap;">DOWNTIME</th>
                                <th class="det-tpt" style="white-space:nowrap;">TPT</th>
                                <th style="white-space:nowrap;">PLAN FIN</th>
                                <th style="white-space:nowrap;">ACT FIN</th>
                                <th style="white-space:nowrap; border-right:none;">GSPH</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${body}
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- Shared Footer Status & Progress -->
            <div class="card-bg border-divider" style="width:100%; border-top-width:2px; border-top-style:solid; margin-top:8px; position:sticky; left:0; z-index:11;">
                <table class="large-table" style="width:100%; border-collapse:collapse; table-layout:fixed;">
                    <tbody>
                        <tr>
                            <td class="desc-cell status-desc" style="width:8%; border-bottom:none;">STATUS</td>
                            <td class="${statusClass}" style="text-align:left; padding-left:20px; font-weight:900; border-bottom:none;">${statusVal}</td>
                        </tr>
                        <tr class="progress-row">
                            <td colspan="2" class="card-bg border-divider" style="padding:8px 12px; text-align:left; border-top-width:1px; border-top-style:solid; border-bottom:none;">
                                <div style="font-size:9px; font-weight:800; text-transform:uppercase; letter-spacing:0.1em; color:#64748b; margin-bottom:4px;">PROGRESS PRODUKSI</div>
                                <div style="display:flex; align-items:center; gap:8px; width:100%;">
                                    <div style="flex:1; background:#e2e8f0; height:10px; border-radius:9999px; overflow:hidden; border:1px solid #cbd5e1; padding:2px;">
                                        <div style="background:${barColor}; width:${Math.min(progressPct,100)}%; height:100%; border-radius:9999px; transition:width 0.5s ease-in-out;"></div>
                                    </div>
                                    <div class="progress-pct" style="font-size:12px; font-weight:900; min-width:50px; text-align:right;">${pct}%</div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    `;
        return scrollEl.firstElementChild;
    };

    // Full render + shrink-to-fit; fall back to auto-rotate pages only when rows are too many
    let root = renderCaseB(rightRows, null, true);
    const s = computeFit(root);
    if (s < 0.99 && rightRows.length > 1) {
        const pageSize = Math.max(1, Math.floor(rightRows.length * s * 0.95)); // 5% safety margin
        detailPages = Math.ceil(rightRows.length / pageSize);
        detailPage = Math.min(detailPage, detailPages - 1);
        const pageRows = rightRows.slice(detailPage * pageSize, (detailPage + 1) * pageSize);
        root = renderCaseB(pageRows, { page: detailPage + 1, pages: detailPages }, true);
        startRotate();
    } else {
        detailPages = 1;
        detailPage = 0;
        stopRotate();
    }
    applyFit(root);
}

fetchData();
setInterval(fetchData,5000);
document.addEventListener('visibilitychange',function(){
    if(!document.hidden){LAST_HASH='';fetchData();}
});
let resizeTimer = null;
window.addEventListener('resize', function(){
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(refitRoot, 150);
});
</script>
</body>
</html>
