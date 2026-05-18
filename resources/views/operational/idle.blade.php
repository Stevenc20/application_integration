@extends('layouts.supervisor')

@section('content')
<div class="p-6">

    <div class="flex justify-between items-center mb-6">

    <div>
        <h1 class="text-2xl font-bold">Idle</h1>
        <p class="text-gray-500 text-sm">
            {{ now()->format('d F Y') }}
        </p>
    </div>


@endsection

</div>
</div>

