<div class="sg-card">
    <div class="card-body">

        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Tipo contenuto --}}
        <div class="form-group">
            <label for="studyable_type">{{ __('study_content.field_type') }} <span class="text-danger">*</span></label>
            <select name="studyable_type" id="studyable_type"
                    class="form-control @error('studyable_type') is-invalid @enderror"
                    onchange="scUpdateStudyableOptions()">
                <option value="">— {{ __('common.select') }} —</option>
                <option value="{{ \App\Models\Category::class }}"
                    {{ old('studyable_type', $studyContent?->studyable_type) === \App\Models\Category::class ? 'selected' : '' }}>
                    {{ __('study_content.type_category') }}
                </option>
                <option value="{{ \App\Models\DrivingModule::class }}"
                    {{ old('studyable_type', $studyContent?->studyable_type) === \App\Models\DrivingModule::class ? 'selected' : '' }}>
                    {{ __('study_content.type_module') }}
                </option>
            </select>
            @error('studyable_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        {{-- Categoria EU --}}
        <div class="form-group" id="sc_select_category" style="display:none">
            <label for="studyable_id_cat">{{ __('study_content.field_category') }} <span class="text-danger">*</span></label>
            <select name="studyable_id" id="studyable_id_cat"
                    class="form-control @error('studyable_id') is-invalid @enderror">
                <option value="">— {{ __('common.select') }} —</option>
                @foreach($categories->where('is_eu_directive', true) as $cat)
                    <option value="{{ $cat->id }}"
                        {{ (string)old('studyable_id', $studyContent?->studyable_id) === (string)$cat->id
                           && old('studyable_type', $studyContent?->studyable_type) === \App\Models\Category::class
                           ? 'selected' : '' }}>
                        {{ $cat->name }}
                    </option>
                @endforeach
            </select>
            @error('studyable_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        {{-- Modulo guida --}}
        <div class="form-group" id="sc_select_module" style="display:none">
            <label for="studyable_id_mod">{{ __('study_content.field_module') }} <span class="text-danger">*</span></label>
            <select name="studyable_id" id="studyable_id_mod"
                    class="form-control @error('studyable_id') is-invalid @enderror">
                <option value="">— {{ __('common.select') }} —</option>
                @foreach($modules as $module)
                    <option value="{{ $module->id }}"
                        {{ (string)old('studyable_id', $studyContent?->studyable_id) === (string)$module->id
                           && old('studyable_type', $studyContent?->studyable_type) === \App\Models\DrivingModule::class
                           ? 'selected' : '' }}>
                        {{ $module->code }} — {{ $module->name }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Titolo --}}
        <div class="form-group">
            <label for="title">{{ __('study_content.field_title') }} <span class="text-danger">*</span></label>
            <input type="text" name="title" id="title"
                   value="{{ old('title', $studyContent?->title) }}"
                   class="form-control @error('title') is-invalid @enderror"
                   maxlength="255">
            @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        {{-- Body --}}
        <div class="form-group">
            <label for="body">{{ __('study_content.field_body') }} <span class="text-danger">*</span></label>
            <textarea name="body" id="body" rows="10"
                      class="form-control @error('body') is-invalid @enderror">{{ old('body', $studyContent?->body) }}</textarea>
            @error('body')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        {{-- Media picker modal --}}
        <div id="scMediaPickerModal"
             style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center">
            <div style="background:#fff;width:90%;max-width:900px;height:80vh;border-radius:4px;overflow:hidden;position:relative">
                <div style="padding:10px;background:#f4f6f9;display:flex;justify-content:space-between;align-items:center">
                    <strong>{{ __('menu.media_manager') }}</strong>
                    <button type="button"
                            onclick="document.getElementById('scMediaPickerModal').style.display='none'"
                            class="btn btn-sm btn-secondary">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <iframe src="{{ route('admin.media.index') }}?picker=1"
                        id="scMediaPickerIframe"
                        style="width:100%;height:calc(100% - 42px);border:0"></iframe>
            </div>
        </div>

        {{-- Ordine e pubblicazione --}}
        <div class="form-row">
            <div class="form-group col-md-3">
                <label for="order">{{ __('study_content.field_order') }}</label>
                <input type="number" name="order" id="order"
                       value="{{ old('order', $studyContent?->order ?? 0) }}"
                       class="form-control @error('order') is-invalid @enderror"
                       min="0" max="9999">
                @error('order')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="form-group col-md-4 d-flex align-items-end pb-2">
                <div class="custom-control custom-switch">
                    <input type="hidden" name="is_published" value="0">
                    <input type="checkbox" name="is_published" id="is_published"
                           class="custom-control-input"
                           value="1"
                           {{ old('is_published', $studyContent?->is_published) ? 'checked' : '' }}>
                    <label class="custom-control-label" for="is_published">
                        {{ __('study_content.field_published') }}
                    </label>
                </div>
            </div>
        </div>

        <div class="form-group mb-0">
            <a href="{{ route('study-contents.index') }}" class="sg-btn sg-btn-secondary mr-2">
                {{ __('driving.btn_cancel') }}
            </a>
            <button type="submit" class="sg-btn sg-btn-primary">
                {{ __('driving.btn_save') }}
            </button>
        </div>

    </div>
</div>

@push('scripts')
<script>
function scUpdateStudyableOptions() {
    const type    = document.getElementById('studyable_type').value;
    const catDiv  = document.getElementById('sc_select_category');
    const modDiv  = document.getElementById('sc_select_module');
    const catSel  = document.getElementById('studyable_id_cat');
    const modSel  = document.getElementById('studyable_id_mod');
    const isCat   = (type === '{{ addslashes(\App\Models\Category::class) }}');
    const isMod   = (type === '{{ addslashes(\App\Models\DrivingModule::class) }}');

    catDiv.style.display = isCat ? 'block' : 'none';
    modDiv.style.display = isMod ? 'block' : 'none';
    catSel.disabled      = !isCat;
    modSel.disabled      = !isMod;
}
document.addEventListener('DOMContentLoaded', scUpdateStudyableOptions);

window.addEventListener('message', function(e) {
    if (e.data && e.data.type === 'media-picker-url' && window.scMediaCallback) {
        window.scMediaCallback(e.data.url, { title: '' });
        window.scMediaCallback = null;
        document.getElementById('scMediaPickerModal').style.display = 'none';
    }
});
</script>
@endpush
