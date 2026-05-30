@if($v !== null)
    <p style="font-size:.8em; margin:0;">
        @if($v > 0)
            <i class="fas fa-arrow-up"></i> +{{ number_format($v, 1) }}%
        @elseif($v < 0)
            <i class="fas fa-arrow-down"></i> {{ number_format($v, 1) }}%
        @else
            <i class="fas fa-minus"></i> 0%
        @endif
        vs periodo prec.
    </p>
@endif
