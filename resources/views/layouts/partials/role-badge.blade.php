@php
    /* Mappa centralizzata ruolo → (icona FA, chiave i18n).
       Usata nella sidebar e nella navbar: aggiornare qui per cambiare entrambe. */
    $sgRoleMap = [
        'admin'      => ['icon' => 'fas fa-user-shield',     'label' => __('common.role_admin')],
        'editor'     => ['icon' => 'fas fa-user-pen',        'label' => __('common.role_editor')],
        'viewer'     => ['icon' => 'fas fa-user-graduate',   'label' => __('common.role_viewer')],
        'instructor' => ['icon' => 'fas fa-chalkboard-user', 'label' => __('common.role_instructor')],
    ];
    $sgRoleKey = 'viewer';
    if (auth()->check()) {
        $sgU = auth()->user();
        if ($sgU->isAdmin())          $sgRoleKey = 'admin';
        elseif ($sgU->isEditor())     $sgRoleKey = 'editor';
        elseif ($sgU->isViewer())     $sgRoleKey = 'viewer';
        elseif ($sgU->isInstructor()) $sgRoleKey = 'instructor';
    }
    $sgRoleData = $sgRoleMap[$sgRoleKey];
@endphp
<span class="sg-role-badge role-{{ $sgRoleKey }}">
    <i class="{{ $sgRoleData['icon'] }}"></i>
    {{ $sgRoleData['label'] }}
</span>
