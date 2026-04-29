@extends('layouts.admin')

@section('content')

<a href="{{ route('admin.users.create') }}" class="btn btn-primary mb-3">
    Nuovo Utente
</a>

<table class="table table-bordered">
    <tr>
        <th>Nome</th>
        <th>Email</th>
        <th>Ruolo</th>
        <th>Azioni</th>
    </tr>

    @foreach($users as $user)
        <tr>
            <td>{{ $user->name }}</td>
            <td>{{ $user->email }}</td>
            <td>{{ $user->role }}</td>
            <td>
                <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-warning btn-sm">Modifica</a>

                <form method="POST" action="{{ route('admin.users.destroy', $user) }}" style="display:inline;">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-danger btn-sm">Elimina</button>
                </form>
            </td>
        </tr>
    @endforeach
</table>

{{ $users->links() }}

@endsection
