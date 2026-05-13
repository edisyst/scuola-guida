<button {{ $attributes->merge(['type' => 'button', 'class' => 'sg-btn sg-btn-light']) }}>
    {{ $slot }}
</button>
