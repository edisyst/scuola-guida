@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'sg-form-control']) }}>
