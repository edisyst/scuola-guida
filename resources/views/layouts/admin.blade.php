@extends('adminlte::page')

@section('title', $title ?? 'Admin')

@section('css')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
@stop

@section('content_header')
    <h1>{{ $header ?? 'Admin Dashboard' }}</h1>
@stop

@section('content')
    @yield('content')
@stop

@section('js')
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

    <script>
        $(function () {

            // Bootstrap switch
            $('input[data-bootstrap-switch]').each(function(){
                $(this).bootstrapSwitch();
            });

            // Config toastr
            toastr.options = {
                closeButton: true,
                progressBar: true,
                positionClass: "toast-top-right",
                timeOut: "3000"
            };

            // 🔥 NOTIFICHE SESSIONE LARAVEL
            @if(session('success'))
                toastr.success(@json(session('success')));
            @endif

            @if(session('error'))
                toastr.error(@json(session('error')));
            @endif

            @if(session('info'))
                toastr.info(@json(session('info')));
            @endif

            @if(session('warning'))
                toastr.warning(@json(session('warning')));
            @endif

        });
    </script>
@stop
