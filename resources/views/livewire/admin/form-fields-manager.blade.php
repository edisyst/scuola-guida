<div>
    @if (session()->has('success'))
        <div class="alert alert-success alert-dismissible fade show sg-mb-3" role="alert">
            <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @endif

    {{-- TAB SWITCHER --}}
    <ul class="nav nav-tabs sg-mb-4">
        <li class="nav-item">
            <button type="button"
                    wire:click="$set('activeTab', 'registration')"
                    class="nav-link {{ $activeTab === 'registration' ? 'active' : '' }}">
                <i class="fas fa-user-plus mr-1"></i> {{ __('forms.tab_registration') }}
            </button>
        </li>
        <li class="nav-item">
            <button type="button"
                    wire:click="$set('activeTab', 'enrollment')"
                    class="nav-link {{ $activeTab === 'enrollment' ? 'active' : '' }}">
                <i class="fas fa-id-card mr-1"></i> {{ __('forms.tab_enrollment') }}
            </button>
        </li>
    </ul>

    {{-- CAMPI CORE (sempre bloccati — solo registrazione) --}}
    @if ($activeTab === 'registration')
        <div class="alert alert-info sg-mb-3">
            <i class="fas fa-lock mr-1"></i>
            {!! __('forms.core_fields_note') !!}
        </div>

        <table class="table table-sm sg-mb-4">
            <thead>
                <tr>
                    <th>{{ __('forms.col_field') }}</th>
                    <th class="text-center">{{ __('forms.col_active') }}</th>
                    <th class="text-center">{{ __('forms.col_required') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach (['name' => __('forms.field_name'), 'email' => __('forms.field_email'), 'password' => __('forms.field_password')] as $key => $label)
                <tr class="table-secondary">
                    <td>
                        {{ $label }}
                        <small class="text-muted ml-1">
                            <i class="fas fa-lock"></i> {{ __('forms.core_locked') }}
                        </small>
                    </td>
                    <td class="text-center"><i class="fas fa-check text-success"></i></td>
                    <td class="text-center"><i class="fas fa-check text-success"></i></td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <h6 class="sg-section-title sg-mb-2">{{ __('forms.extra_fields_title') }}</h6>

        <table class="table table-sm sg-mb-4">
            <thead>
                <tr>
                    <th>{{ __('forms.col_field') }}</th>
                    <th class="text-center">{{ __('forms.col_active') }}</th>
                    <th class="text-center">{{ __('forms.col_required') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($regFields as $index => $field)
                <tr>
                    <td>{{ __($field['label_key']) }}</td>
                    <td class="text-center">
                        <button type="button"
                                wire:click="toggle('registration', {{ $index }}, 'enabled')"
                                class="btn btn-sm {{ $field['enabled'] ? 'btn-success' : 'btn-outline-secondary' }}">
                            <i class="fas fa-{{ $field['enabled'] ? 'toggle-on' : 'toggle-off' }}"></i>
                        </button>
                    </td>
                    <td class="text-center">
                        <button type="button"
                                wire:click="toggle('registration', {{ $index }}, 'required')"
                                class="btn btn-sm {{ $field['required'] ? 'btn-warning' : 'btn-outline-secondary' }}"
                                @if(! $field['enabled']) disabled @endif>
                            <i class="fas fa-{{ $field['required'] ? 'asterisk' : 'minus' }}"></i>
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="3" class="text-muted text-center">{{ __('forms.no_extra_fields') }}</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    @endif

    {{-- CAMPI ENROLLMENT --}}
    @if ($activeTab === 'enrollment')
        <table class="table table-sm sg-mb-4">
            <thead>
                <tr>
                    <th>{{ __('forms.col_field') }}</th>
                    <th class="text-center">{{ __('forms.col_active') }}</th>
                    <th class="text-center">{{ __('forms.col_required') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($enrollFields as $index => $field)
                <tr>
                    <td>{{ __($field['label_key']) }}</td>
                    <td class="text-center">
                        <button type="button"
                                wire:click="toggle('enrollment', {{ $index }}, 'enabled')"
                                class="btn btn-sm {{ $field['enabled'] ? 'btn-success' : 'btn-outline-secondary' }}">
                            <i class="fas fa-{{ $field['enabled'] ? 'toggle-on' : 'toggle-off' }}"></i>
                        </button>
                    </td>
                    <td class="text-center">
                        <button type="button"
                                wire:click="toggle('enrollment', {{ $index }}, 'required')"
                                class="btn btn-sm {{ $field['required'] ? 'btn-warning' : 'btn-outline-secondary' }}"
                                @if(! $field['enabled']) disabled @endif>
                            <i class="fas fa-{{ $field['required'] ? 'asterisk' : 'minus' }}"></i>
                        </button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    {{-- SALVA --}}
    <div class="d-flex justify-content-end">
        <button type="button"
                wire:click="save"
                wire:loading.attr="disabled"
                class="btn btn-primary">
            <span wire:loading.remove wire:target="save">
                <i class="fas fa-save mr-1"></i> {{ __('forms.save_btn') }}
            </span>
            <span wire:loading wire:target="save">
                <i class="fas fa-spinner fa-spin mr-1"></i> {{ __('common.saving') }}
            </span>
        </button>
    </div>
</div>
