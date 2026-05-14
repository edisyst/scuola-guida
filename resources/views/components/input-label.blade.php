@props(['value'])

<label {{ $attributes->merge(['class' => 'sg-form-label']) }}>
    {{ $value ?? $slot }}
</label>
