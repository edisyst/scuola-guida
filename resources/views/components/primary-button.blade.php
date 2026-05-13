<button {{ $attributes->merge(['type' => 'submit', 'class' => 'sg-btn sg-btn-primary']) }}>
    {{ $slot }}
</button>
