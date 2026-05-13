@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'sg-text-success', 'style' => 'font-size:.88rem;font-weight:600;']) }}>
        {{ $status }}
    </div>
@endif
