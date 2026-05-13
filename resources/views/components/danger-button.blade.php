<button {{ $attributes->merge(['type' => 'submit', 'class' => 'sg-btn sg-btn-danger']) }}>
    {{ $slot }}
</button>
