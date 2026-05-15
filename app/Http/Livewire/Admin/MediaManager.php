<?php

namespace App\Http\Livewire\Admin;

use App\Models\Question;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class MediaManager extends Component
{
    use WithFileUploads;

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

    public function getFilesProperty(): array
    {
        $disk = config('media.disk');
        $dir  = config('media.directory');

        if (! Storage::disk($disk)->exists($dir)) {
            Storage::disk($disk)->makeDirectory($dir);
        }

        return collect(Storage::disk($disk)->files($dir))
            ->map(fn ($path) => [
                'path' => $path,
                'name' => basename($path),
                'url'  => Storage::disk($disk)->url($path),
                'size' => $this->humanSize(Storage::disk($disk)->size($path)),
                'refs' => Question::where('image', $path)->count(),
            ])
            ->values()
            ->toArray();
    }

    public function upload(): void
    {
        $this->validateOnly('newImage');

        $disk = config('media.disk');
        $dir  = config('media.directory');
        $name = $this->newImage->getClientOriginalName();

        if (Storage::disk($disk)->exists("{$dir}/{$name}")) {
            $this->addError('newImage', "Esiste già un file chiamato «{$name}».");
            return;
        }

        $this->newImage->storeAs($dir, $name, $disk);
        $this->reset('newImage');
        session()->flash('media_success', "Immagine «{$name}» caricata.");
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
        $dir     = config('media.directory');
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
            'files'     => $this->files,
            'directory' => config('media.directory'),
            'disk'      => config('media.disk'),
        ]);
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
