<div class="sg-card">
    <div class="sg-card-body">

        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Tipo --}}
        <div class="form-group">
            <label>Tipo <span class="text-danger">*</span></label>
            <div class="d-flex" style="gap:12px;">
                @foreach(['pdf' => 'PDF', 'link' => 'Link / Video', 'note' => 'Nota testuale'] as $val => $label)
                    <label class="d-flex align-items-center" style="gap:6px;cursor:pointer;">
                        <input type="radio" name="type" value="{{ $val }}"
                               x-model="type"
                               {{ old('type', $material->type ?? 'pdf') === $val ? 'checked' : '' }}>
                        {{ $label }}
                    </label>
                @endforeach
            </div>
        </div>

        {{-- Titolo --}}
        <div class="form-group">
            <label for="title">Titolo <span class="text-danger">*</span></label>
            <input type="text"
                   id="title"
                   name="title"
                   class="form-control @error('title') is-invalid @enderror"
                   value="{{ old('title', $material->title ?? '') }}"
                   maxlength="255"
                   required>
            @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        {{-- PDF --}}
        <div class="form-group" x-show="type === 'pdf'" x-cloak>
            <label for="file">File PDF <span class="text-danger" x-show="type === 'pdf'" x-cloak>*</span></label>
            @isset($editing)
                @if(isset($material) && $material->url_or_path)
                    <div class="mb-2">
                        <a href="{{ $material->download_url }}" target="_blank" class="text-info">
                            <i class="fas fa-file-pdf mr-1"></i> File attuale
                        </a>
                        <small class="text-muted ml-2">(carica un nuovo file per sostituirlo)</small>
                    </div>
                @endif
            @endisset
            <input type="file"
                   id="file"
                   name="file"
                   class="form-control-file @error('file') is-invalid @enderror"
                   accept=".pdf">
            <small class="text-muted">Max 10 MB, solo PDF.</small>
            @error('file')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>

        {{-- Link --}}
        <div class="form-group" x-show="type === 'link'" x-cloak>
            <label for="url_or_path">URL <span class="text-danger" x-show="type === 'link'" x-cloak>*</span></label>
            <input type="url"
                   id="url_or_path"
                   name="url_or_path"
                   class="form-control @error('url_or_path') is-invalid @enderror"
                   value="{{ old('url_or_path', $material->url_or_path ?? '') }}"
                   maxlength="1000"
                   placeholder="https://...">
            <small class="text-muted">Inserisci un URL completo. I link YouTube verranno incorporati nella pagina studio.</small>
            @error('url_or_path')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        {{-- Nota --}}
        <div class="form-group" x-show="type === 'note'" x-cloak>
            <label for="content">Testo <span class="text-danger" x-show="type === 'note'" x-cloak>*</span></label>
            <textarea id="content"
                      name="content"
                      class="form-control @error('content') is-invalid @enderror"
                      rows="8"
                      placeholder="Scrivi il testo del materiale didattico...">{{ old('content', $material->content ?? '') }}</textarea>
            <small class="text-muted">Testo semplice. Il contenuto viene mostrato ai viewer nella pagina di studio.</small>
            @error('content')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

    </div>
</div>

<style>[x-cloak]{display:none!important;}</style>
