<?php

namespace App\Http\Livewire\Admin;

use App\Models\Question;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class MediaManager extends Component
{
    use WithFileUploads;

    /** Cartella attiva: 'test' o 'production'. */
    public string $folder = 'test';

    public $newImage;

    public ?string $renamingFile = null;
    public string  $newName      = '';

    public ?string $deletingFile = null;
    public int     $deletingRefs = 0;

    protected function rules(): array
    {
        return [
            'newImage' => 'required|image|mimes:jpg,jpeg,png,gif,webp|max:2048',
            'newName'  => ['required', 'regex:/^[\w\-]+\.[a-zA-Z]{2,5}$/'],
        ];
    }

    protected $messages = [
        'newImage.required' => 'Seleziona un file immagine.',
        'newImage.image'    => 'Il file deve essere un\'immagine.',
        'newImage.max'      => 'L\'immagine non può superare 2 MB.',
        'newName.required'  => 'Il nome non può essere vuoto.',
        'newName.regex'     => 'Nome non valido (solo lettere, numeri, trattini, underscore e un\'estensione).',
    ];

    public function mount(): void
    {
        if (! array_key_exists($this->folder, config('media.directories', []))) {
            $this->folder = array_key_first(config('media.directories'));
        }
    }

    public function switchFolder(string $folder): void
    {
        if (! array_key_exists($folder, config('media.directories', []))) {
            return;
        }

        $this->folder = $folder;
        $this->cancelRename();
        $this->cancelDelete();
        $this->reset('newImage');
        $this->resetErrorBag();
    }

    /** Percorso (relativo al disco) della cartella attualmente selezionata. */
    public function getDirectoryProperty(): string
    {
        return config('media.directories.' . $this->folder);
    }

    /** Conteggi file per cartella, per popolare le tab. */
    public function getFolderCountsProperty(): array
    {
        $disk   = config('media.disk');
        $counts = [];

        foreach (config('media.directories', []) as $key => $dir) {
            $this->ensureDirectory($dir);
            $counts[$key] = $this->imageFiles($disk, $dir)->count();
        }

        return $counts;
    }

    public function getFilesProperty(): array
    {
        $disk = config('media.disk');
        $dir  = $this->directory;

        $this->ensureDirectory($dir);

        return $this->imageFiles($disk, $dir)
            ->map(fn ($path) => [
                'path' => $path,
                'name' => basename($path),
                'url'  => Storage::disk($disk)->url($path),
                'size' => $this->humanSize(Storage::disk($disk)->size($path)),
                'refs' => Question::where('image', $path)->count(),
            ])
            ->sortBy('name')
            ->values()
            ->toArray();
    }

    /** Solo file con estensione immagine; esclude .gitkeep e affini. */
    private function imageFiles(string $disk, string $dir): \Illuminate\Support\Collection
    {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        return collect(Storage::disk($disk)->files($dir))
            ->filter(fn ($path) => in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), $allowed, true));
    }

    public function upload(): void
    {
        $this->validateOnly('newImage');

        $disk = config('media.disk');
        $dir  = $this->directory;
        $name = $this->newImage->getClientOriginalName();

        if (Storage::disk($disk)->exists("{$dir}/{$name}")) {
            $this->addError('newImage', "Esiste già un file chiamato «{$name}».");
            return;
        }

        $this->newImage->storeAs($dir, $name, $disk);
        $this->reset('newImage');
        session()->flash('media_success', "Immagine «{$name}» caricata in {$this->folder}.");
    }

    public function startRename(string $path): void
    {
        $this->renamingFile = $path;
        $this->newName      = basename($path);
        $this->resetErrorBag('newName');
    }

    public function cancelRename(): void
    {
        $this->renamingFile = null;
        $this->newName      = '';
    }

    public function rename(): void
    {
        $this->validateOnly('newName');

        $disk    = config('media.disk');
        $dir     = $this->directory;
        $oldPath = $this->renamingFile;
        $newPath = "{$dir}/{$this->newName}";

        if ($oldPath === $newPath) {
            $this->cancelRename();
            return;
        }

        if (Storage::disk($disk)->exists($newPath)) {
            $this->addError('newName', 'Esiste già un file con questo nome.');
            return;
        }

        Storage::disk($disk)->move($oldPath, $newPath);
        Question::where('image', $oldPath)->update(['image' => $newPath]);

        $count = Question::where('image', $newPath)->count();
        $this->cancelRename();
        session()->flash('media_success', "File rinominato. {$count} domande aggiornate.");
    }

    public function confirmDelete(string $path): void
    {
        $this->deletingFile = $path;
        $this->deletingRefs = Question::where('image', $path)->count();
    }

    public function cancelDelete(): void
    {
        $this->deletingFile = null;
        $this->deletingRefs = 0;
    }

    public function delete(): void
    {
        if (! $this->deletingFile) {
            return;
        }

        $name = basename($this->deletingFile);
        Storage::disk(config('media.disk'))->delete($this->deletingFile);
        Question::where('image', $this->deletingFile)->update(['image' => null]);

        $this->cancelDelete();
        session()->flash('media_success', "File «{$name}» eliminato.");
    }

    public function render()
    {
        return view('livewire.admin.media-manager', [
            'files'        => $this->files,
            'directory'    => $this->directory,
            'disk'         => config('media.disk'),
            'folders'      => config('media.directories', []),
            'folderCounts' => $this->folderCounts,
        ]);
    }

    private function ensureDirectory(string $dir): void
    {
        $disk = config('media.disk');

        if (! Storage::disk($disk)->exists($dir)) {
            Storage::disk($disk)->makeDirectory($dir);
        }
    }

    private function humanSize(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1) . ' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 1) . ' KB';
        }
        return $bytes . ' B';
    }
}
