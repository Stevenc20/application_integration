@if ($paginator->hasPages())
<nav class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">

    <!-- INFO -->
    <div class="text-sm text-gray-500">
        Showing 
        <span class="font-medium text-gray-700">{{ $paginator->firstItem() }}</span>
        to 
        <span class="font-medium text-gray-700">{{ $paginator->lastItem() }}</span>
        of 
        <span class="font-medium text-gray-700">{{ $paginator->total() }}</span>
        results
    </div>

    <!-- PAGINATION -->
    <div class="flex items-center gap-1">

        <!-- PREVIOUS -->
        @if ($paginator->onFirstPage())
            <span class="px-3 py-2 text-gray-400 bg-gray-100 border border-gray-200 rounded-lg cursor-not-allowed">
                ←
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}"
               class="px-3 py-2 text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-red-50 hover:text-red-600 transition">
                ←
            </a>
        @endif

        <!-- NUMBER -->
        @foreach ($elements as $element)

            @if (is_string($element))
                <span class="px-3 py-2 text-gray-400">
                    {{ $element }}
                </span>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)

                    @if ($page == $paginator->currentPage())

                        <!-- ACTIVE -->
                        <span class="px-4 py-2 text-sm font-semibold text-red-700 bg-red-100 border border-red-200 rounded-lg shadow-sm">
                            {{ $page }}
                        </span>

                    @else

                        <!-- NORMAL -->
                        <a href="{{ $url }}"
                           class="px-4 py-2 text-sm text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-red-50 hover:text-red-600 transition">
                            {{ $page }}
                        </a>

                    @endif

                @endforeach
            @endif

        @endforeach

        <!-- NEXT -->
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}"
               class="px-3 py-2 text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-red-50 hover:text-red-600 transition">
                →
            </a>
        @else
            <span class="px-3 py-2 text-gray-400 bg-gray-100 border border-gray-200 rounded-lg cursor-not-allowed">
                →
            </span>
        @endif

    </div>
</nav>
@endif