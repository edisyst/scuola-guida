@if(auth()->user()->canEditQuestion())
    <a href="{{ route('admin.questions.edit', $q) }}" class="btn btn-sm btn-warning">Modifica</a>
@endif

@if(auth()->user()->canDeleteQuestion())
    <form action="{{ route('admin.questions.destroy', $q) }}" method="POST" style="display:inline;">
        @csrf
        @method('DELETE')
        <button class="btn btn-sm btn-danger" onclick="return confirm('Sei sicuro?')">
            Elimina
        </button>
    </form>
@endif
