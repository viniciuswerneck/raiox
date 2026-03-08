@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="flex flex-col items-center gap-6 mt-8">
        
        <div class="flex items-center justify-center w-full gap-4">
            <div class="flex gap-2">
                {{-- Previous Page Link --}}
                @if ($paginator->onFirstPage())
                    <span class="px-3 py-2 rounded-xl bg-white/5 border border-white/5 text-slate-600 cursor-not-allowed flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                    </span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="px-3 py-2 rounded-xl bg-white/5 border border-white/10 text-white hover:bg-indigo-600/20 hover:border-indigo-500/50 hover:text-indigo-400 transition-all flex items-center justify-center group">
                        <svg class="w-5 h-5 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                    </a>
                @endif

                {{-- Pagination Elements --}}
                <div class="hidden sm:flex gap-2">
                    @foreach ($elements as $element)
                        {{-- "Three Dots" Separator --}}
                        @if (is_string($element))
                            <span class="px-4 py-2 rounded-xl bg-transparent text-slate-500 font-bold flex items-center justify-center">{{ $element }}</span>
                        @endif

                        {{-- Array Of Links --}}
                        @if (is_array($element))
                            @foreach ($element as $page => $url)
                                @if ($page == $paginator->currentPage())
                                    <span class="px-4 py-2 rounded-xl bg-indigo-600 text-white font-black shadow-lg shadow-indigo-600/30 flex items-center justify-center">
                                        {{ $page }}
                                    </span>
                                @else
                                    <a href="{{ $url }}" class="px-4 py-2 rounded-xl bg-white/5 border border-white/10 text-slate-400 hover:text-white hover:bg-white/10 transition-all font-bold flex items-center justify-center">
                                        {{ $page }}
                                    </a>
                                @endif
                            @endforeach
                        @endif
                    @endforeach
                </div>

                {{-- Next Page Link --}}
                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="px-3 py-2 rounded-xl bg-white/5 border border-white/10 text-white hover:bg-indigo-600/20 hover:border-indigo-500/50 hover:text-indigo-400 transition-all flex items-center justify-center group">
                        <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                    </a>
                @else
                    <span class="px-3 py-2 rounded-xl bg-white/5 border border-white/5 text-slate-600 cursor-not-allowed flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                    </span>
                @endif
            </div>
            
            <div class="sm:hidden flex gap-2 w-full justify-center mt-2">
                <span class="text-xs text-slate-500 font-bold uppercase tracking-widest">Página {{ $paginator->currentPage() }} de {{ $paginator->lastPage() }}</span>
            </div>
        </div>
    </nav>
@endif
