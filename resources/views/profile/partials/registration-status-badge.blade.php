@switch($user->registration_status)
    @case(\App\Models\User::REG_APPROVED)
        <span class="sg-badge sg-badge-success">
            <i class="fas fa-check-circle"></i> Approvata
        </span>
        @break
    @case(\App\Models\User::REG_PENDING)
        <span class="sg-badge sg-badge-warning">
            <i class="fas fa-hourglass-half"></i> In attesa di approvazione
        </span>
        @break
    @case(\App\Models\User::REG_REJECTED)
        <span class="sg-badge sg-badge-danger">
            <i class="fas fa-times-circle"></i> Rifiutata
        </span>
        @break
    @default
        <span class="sg-badge">
            <i class="fas fa-exclamation-circle"></i> Da compilare
        </span>
@endswitch
