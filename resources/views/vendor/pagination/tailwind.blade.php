@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination Navigation" class="flex flex-col sm:flex-row items-center justify-between gap-4 py-4 text-sm">
        {{-- Results Info Text --}}
        <div class="text-xs text-slate-400">
            Showing <strong class="text-cyan-400">{{ $paginator->firstItem() ?? 0 }}</strong> to <strong class="text-cyan-400">{{ $paginator->lastItem() ?? 0 }}</strong> of <strong class="text-slate-200">{{ $paginator->total() }}</strong> records
        </div>

        {{-- Page Buttons --}}
        <div class="inline-flex items-center gap-1.5 bg-slate-900/80 p-1 rounded-xl border border-slate-700/60 shadow-inner">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-slate-600 cursor-not-allowed">
                    <i class="fa-solid fa-chevron-left text-xs"></i>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-slate-300 hover:text-white hover:bg-slate-800 transition-colors">
                    <i class="fa-solid fa-chevron-left text-xs"></i>
                </a>
            @endif

            {{-- Pagination Elements --}}
            @foreach ($elements as $element)
                {{-- "Three Dots" Separator --}}
                @if (is_string($element))
                    <span class="inline-flex items-center justify-center w-8 h-8 text-xs text-slate-500">{{ $element }}</span>
                @endif

                {{-- Array Of Links --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-cyan-600 text-white font-bold text-xs shadow-md">
                                {{ $page }}
                            </span>
                        @else
                            <a href="{{ $url }}" class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-slate-300 hover:text-white hover:bg-slate-800 transition-colors text-xs">
                                {{ $page }}
                            </a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-slate-300 hover:text-white hover:bg-slate-800 transition-colors">
                    <i class="fa-solid fa-chevron-right text-xs"></i>
                </a>
            @else
                <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-slate-600 cursor-not-allowed">
                    <i class="fa-solid fa-chevron-right text-xs"></i>
                </span>
            @endif
        </div>
    </nav>
@endif
