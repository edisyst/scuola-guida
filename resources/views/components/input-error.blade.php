@props(['messages'])

@if ($messages)
    <ul {{ $attributes->merge(['class' => 'sg-form-error', 'style' => 'list-style:none;padding:0;margin-top:5px;']) }}>
        @foreach ((array) $messages as $message)
            <li>{{ $message }}</li>
        @endforeach
    </ul>
@endif
