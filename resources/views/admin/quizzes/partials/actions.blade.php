@if(auth()->user()->canEditQuestion())
    <a href="{{ route('admin.questions.edit', $q) }}" class="sg-btn-icon edit" title="Modifica">
        <i class="fas fa-edit"></i>
    </a>
@endif

@if(auth()->user()->canDeleteQuestion())
    <form action="{{ route('admin.questions.destroy', $q) }}" method="POST" style="display:inline;">
        @csrf
        @method('DELETE')
        <button class="sg-btn-icon delete" title="Elimina" onclick="return confirm('Sei sicuro?')">
            <i class="fas fa-trash"></i>
        </button>
    </form>
@endif
