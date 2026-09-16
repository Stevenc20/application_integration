@extends('layouts.supervisor')

@section('title', 'Security Logs')

@section('content')
<div class="space-y-4">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-black text-slate-800 tracking-tight">Security Logs</h1>
            <p class="text-sm text-slate-400 mt-1">Detail network access logs</p>
        </div>
        <a href="{{ url('security/dashboard') }}" class="text-sm text-blue-600 font-medium">← Dashboard</a>
    </div>

    {{-- Filter --}}
    <form method="GET" class="flex gap-2 flex-wrap">
        <input type="text" name="ip" placeholder="Filter IP" value="{{ request('ip') }}"
            class="px-3 py-2 border border-slate-200 rounded-lg text-sm">
        <select name="status" class="px-3 py-2 border border-slate-200 rounded-lg text-sm">
            <option value="">All Status</option>
            <option value="200" @selected(request('status') == 200)>200 OK</option>
            <option value="401" @selected(request('status') == 401)>401 Unauthorized</option>
            <option value="403" @selected(request('status') == 403)>403 Forbidden</option>
            <option value="404" @selected(request('status') == 404)>404 Not Found</option>
            <option value="500" @selected(request('status') == 500)>500 Error</option>
        </select>
        <select name="method" class="px-3 py-2 border border-slate-200 rounded-lg text-sm">
            <option value="">All Methods</option>
            <option value="GET" @selected(request('method') == 'GET')>GET</option>
            <option value="POST" @selected(request('method') == 'POST')>POST</option>
            <option value="PUT" @selected(request('method') == 'PUT')>PUT</option>
            <option value="DELETE" @selected(request('method') == 'DELETE')>DELETE</option>
        </select>
        <button type="submit" class="px-4 py-2 bg-slate-800 text-white rounded-lg text-sm font-medium">Filter</button>
    </form>

    {{-- Table --}}
    <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="text-left text-xs text-slate-400 uppercase tracking-widest bg-slate-50 border-b border-slate-200">
                    <th class="py-2 px-3">Time</th><th class="py-2 px-3">IP</th><th class="py-2 px-3">Method</th>
                    <th class="py-2 px-3">Endpoint</th><th class="py-2 px-3">Status</th><th class="py-2 px-3">Duration</th><th class="py-2 px-3">User Agent</th>
                </tr></thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr class="border-b border-slate-100 hover:bg-slate-50">
                            <td class="py-2 px-3 text-slate-500 text-xs whitespace-nowrap">{{ $log->created_at->format('H:i:s d/m') }}</td>
                            <td class="py-2 px-3 font-mono text-xs text-slate-800">{{ $log->ip_address }}</td>
                            <td class="py-2 px-3"><span class="px-1.5 py-0.5 rounded text-xs font-mono {{ $log->method === 'GET' ? 'bg-blue-100 text-blue-700' : ($log->method === 'POST' ? 'bg-emerald-100 text-emerald-700' : 'bg-orange-100 text-orange-700') }}">{{ $log->method }}</span></td>
                            <td class="py-2 px-3 text-slate-700 text-xs max-w-[200px] truncate">{{ $log->endpoint }}</td>
                            <td class="py-2 px-3"><span class="font-bold {{ $log->response_status >= 400 ? 'text-red-600' : 'text-slate-600' }}">{{ $log->response_status }}</span></td>
                            <td class="py-2 px-3 text-slate-400 text-xs">{{ $log->response_time_ms }}ms</td>
                            <td class="py-2 px-3 text-slate-400 text-xs max-w-[150px] truncate">{{ $log->user_agent }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-8 text-center text-slate-400">Tidak ada data</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    <div class="mt-4">{{ $logs->links() }}</div>
</div>
@endsection