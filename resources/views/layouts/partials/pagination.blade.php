@if ($paginator->hasPages())
    <nav class="pagination" aria-label="Paginación">
        <span>Mostrando {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} de {{ $paginator->total() }}</span>
        <div class="pagination-links">
            @if ($paginator->onFirstPage())<span class="disabled" aria-disabled="true">←</span>@else<a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Página anterior">←</a>@endif
            @foreach ($paginator->getUrlRange(max(1, $paginator->currentPage() - 2), min($paginator->lastPage(), $paginator->currentPage() + 2)) as $page => $url)
                @if ($page == $paginator->currentPage())<span class="current" aria-current="page">{{ $page }}</span>@else<a href="{{ $url }}">{{ $page }}</a>@endif
            @endforeach
            @if ($paginator->hasMorePages())<a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Página siguiente">→</a>@else<span class="disabled" aria-disabled="true">→</span>@endif
        </div>
    </nav>
@endif
