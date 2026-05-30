@if($v === null)
    <span class="sg-text-muted">—</span>
@elseif($v > 0)
    <span class="text-success font-weight-bold"><i class="fas fa-arrow-up"></i> +{{ number_format($v, 1) }}%</span>
@elseif($v < 0)
    <span class="text-danger font-weight-bold"><i class="fas fa-arrow-down"></i> {{ number_format($v, 1) }}%</span>
@else
    <span class="sg-text-muted"><i class="fas fa-minus"></i> 0%</span>
@endif
