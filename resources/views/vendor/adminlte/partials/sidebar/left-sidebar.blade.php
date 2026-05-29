@php
    $sidebarClasses = 'sidebar-dark-primary elevation-4';
    if (auth()->check()) {
        if (auth()->user()->isAdmin()) {
            $sidebarClasses = 'sidebar-dark-primary elevation-4';
        } elseif (auth()->user()->isEditor()) {
            $sidebarClasses = 'sidebar-dark-danger elevation-4';
        } elseif (auth()->user()->isViewer()) {
            $sidebarClasses = 'sidebar-dark-warning elevation-4';
        }
    }
@endphp

<aside class="main-sidebar {{ $sidebarClasses }}">

    {{-- Sidebar brand logo --}}
    @if(config('adminlte.logo_img_xl'))
        @include('adminlte::partials.common.brand-logo-xl')
    @else
        @include('adminlte::partials.common.brand-logo-xs')
    @endif

    {{-- Sidebar menu --}}
    <div class="sidebar">
        <nav class="pt-2">
            <ul class="nav nav-pills nav-sidebar flex-column {{ config('adminlte.classes_sidebar_nav', '') }}"
                data-widget="treeview" role="menu"
                @if(config('adminlte.sidebar_nav_animation_speed') != 300)
                    data-animation-speed="{{ config('adminlte.sidebar_nav_animation_speed') }}"
                @endif
                @if(!config('adminlte.sidebar_nav_accordion'))
                    data-accordion="false"
                @endif>
                {{-- Configured sidebar links --}}
                @each('adminlte::partials.sidebar.menu-item', $adminlte->menu('sidebar'), 'item')
            </ul>
        </nav>
    </div>

</aside>
