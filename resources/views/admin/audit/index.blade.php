@extends('layouts.admin')

@section('content')

<table class="table table-bordered">
    <thead>
        <tr>
            <th>ID</th>
            <th>User</th>
            <th>Evento</th>
            <th>Model</th>
            <th>Old</th>
            <th>New</th>
            <th>Data</th>
        </tr>
    </thead>

    <tbody>
    @foreach($logs as $log)
        <tr>
            <td>{{ $log->id }}</td>
            <td>{{ $log->user?->name ?? 'System' }}</td>
            <td>{{ $log->event }}</td>
            <td>{{ class_basename($log->model_type) }} #{{ $log->model_id }}</td>

            <td><pre>{{ json_encode($log->old_values, JSON_PRETTY_PRINT) }}</pre></td>
            <td><pre>{{ json_encode($log->new_values, JSON_PRETTY_PRINT) }}</pre></td>

            <td>{{ $log->created_at }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

{{ $logs->links() }}

@endsection
