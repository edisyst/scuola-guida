<a href="{{ route('questions.edit', $q) }}" class="btn btn-sm btn-warning">Modifica</a>

<form action="{{ route('questions.destroy', $q) }}" method="POST" style="display:inline;">
    @csrf
    @method('DELETE')
    <button class="btn btn-sm btn-danger" onclick="return confirm('Sei sicuro?')">
        Elimina
    </button>
</form>
