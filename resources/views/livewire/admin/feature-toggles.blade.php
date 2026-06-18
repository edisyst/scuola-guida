<div>
    {{-- ── Sezione 1: Toggle gestiti dalla piattaforma ─────────────── --}}
    <div class="sg-card sg-mb-3">
        <div class="sg-card-header">
            <h2 class="sg-card-header-title">
                <i class="fas fa-toggle-on mr-2"></i> {{ __('features.section_platform') }}
            </h2>
        </div>
        <div class="sg-card-body">
            <p class="text-muted mb-4">{{ __('features.section_platform_desc') }}</p>

            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <tbody>
                        @foreach($toggles as $key => $value)
                        <tr>
                            <td style="width:40%">
                                <strong>{{ __('features.' . $key) }}</strong>
                                <div class="text-muted small">{{ __('features.' . $key . '_desc') }}</div>
                            </td>
                            <td style="width:20%">
                                @if($value)
                                    <span class="badge badge-success">{{ __('features.enabled') }}</span>
                                @else
                                    <span class="badge badge-secondary">{{ __('features.disabled') }}</span>
                                @endif
                            </td>
                            <td style="width:40%" class="text-right">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox"
                                           class="custom-control-input"
                                           id="toggle_{{ $key }}"
                                           wire:change="toggle('{{ $key }}')"
                                           wire:loading.attr="disabled"
                                           wire:target="toggle('{{ $key }}')"
                                           {{ $value ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="toggle_{{ $key }}">
                                        <span wire:loading wire:target="toggle('{{ $key }}')">
                                            <i class="fas fa-spinner fa-spin fa-sm"></i>
                                        </span>
                                    </label>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ── Sezione 2: Flag gestiti da configurazione (read-only) ────── --}}
    <div class="sg-card">
        <div class="sg-card-header">
            <h2 class="sg-card-header-title">
                <i class="fas fa-lock mr-2"></i> {{ __('features.section_config') }}
            </h2>
        </div>
        <div class="sg-card-body">
            <p class="text-muted mb-4">{{ __('features.section_config_desc') }}</p>

            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th style="width:25%">{{ __('features.flag') }}</th>
                            <th style="width:20%">{{ __('features.current_value') }}</th>
                            <th>{{ __('features.hint') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($configManaged as $item)
                        <tr>
                            <td><code>{{ $item['flag'] }}</code></td>
                            <td>
                                @php $val = $item['value']; @endphp
                                @if(is_bool($val))
                                    @if($val)
                                        <span class="badge badge-success">true</span>
                                    @else
                                        <span class="badge badge-danger">false</span>
                                    @endif
                                @elseif($val === null || $val === '')
                                    <span class="badge badge-secondary">—</span>
                                @else
                                    <span class="badge badge-info">{{ $val }}</span>
                                @endif
                            </td>
                            <td>
                                <small class="text-muted">{!! __($item['hint_key']) !!}</small>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
