<?php

namespace Tests\Feature;

use App\Http\Livewire\Admin\MediaManager;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class MediaManagerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Disco virtuale per evitare di sporcare lo storage reale.
        Storage::fake(config('media.disk'));
    }

    protected function adminUser(): User
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);
        return $user;
    }

    public function test_component_can_be_rendered(): void
    {
        $this->adminUser();

        Livewire::test(MediaManager::class)
            ->assertSet('folder', 'test')
            ->assertOk();
    }

    public function test_switch_folder_changes_active_directory(): void
    {
        $this->adminUser();

        Livewire::test(MediaManager::class)
            ->assertSet('folder', 'test')
            ->call('switchFolder', 'production')
            ->assertSet('folder', 'production');
    }

    public function test_switch_folder_rejects_unknown_folder(): void
    {
        $this->adminUser();

        Livewire::test(MediaManager::class)
            ->call('switchFolder', 'hacker')
            ->assertSet('folder', 'test');
    }

    public function test_admin_can_upload_image_in_active_folder(): void
    {
        $this->adminUser();
        $disk = config('media.disk');
        $dir  = config('media.directories.test');

        $file = UploadedFile::fake()->image('hello.png', 100, 100);

        Livewire::test(MediaManager::class)
            ->set('newImage', $file)
            ->call('upload')
            ->assertHasNoErrors();

        Storage::disk($disk)->assertExists("{$dir}/hello.png");
    }

    public function test_upload_rejects_duplicate_filename(): void
    {
        $this->adminUser();
        $disk = config('media.disk');
        $dir  = config('media.directories.test');

        Storage::disk($disk)->put("{$dir}/exists.png", 'fake');

        $file = UploadedFile::fake()->image('exists.png');

        Livewire::test(MediaManager::class)
            ->set('newImage', $file)
            ->call('upload')
            ->assertHasErrors('newImage');
    }

    public function test_rename_updates_referencing_questions(): void
    {
        $this->adminUser();
        $disk = config('media.disk');
        $dir  = config('media.directories.test');

        $oldPath = "{$dir}/old.png";
        Storage::disk($disk)->put($oldPath, 'fake');

        $category = \App\Models\Category::factory()->create();
        $q1 = Question::factory()->create(['image' => $oldPath, 'category_id' => $category->id]);
        $q2 = Question::factory()->create(['image' => $oldPath, 'category_id' => $category->id]);

        Livewire::test(MediaManager::class)
            ->call('startRename', $oldPath)
            ->set('newName', 'new.png')
            ->call('rename')
            ->assertHasNoErrors();

        $newPath = "{$dir}/new.png";
        Storage::disk($disk)->assertExists($newPath);
        Storage::disk($disk)->assertMissing($oldPath);
        $this->assertSame($newPath, $q1->fresh()->image);
        $this->assertSame($newPath, $q2->fresh()->image);
    }

    public function test_delete_removes_file_and_clears_question_image(): void
    {
        $this->adminUser();
        $disk = config('media.disk');
        $dir  = config('media.directories.test');

        $path = "{$dir}/doomed.png";
        Storage::disk($disk)->put($path, 'fake');

        $category = \App\Models\Category::factory()->create();
        $q = Question::factory()->create(['image' => $path, 'category_id' => $category->id]);

        Livewire::test(MediaManager::class)
            ->call('confirmDelete', $path)
            ->assertSet('deletingFile', $path)
            ->call('delete');

        Storage::disk($disk)->assertMissing($path);
        $this->assertNull($q->fresh()->image);
    }

    public function test_files_filter_out_non_image_files(): void
    {
        $this->adminUser();
        $disk = config('media.disk');
        $dir  = config('media.directories.production');

        Storage::disk($disk)->put("{$dir}/.gitkeep", '');
        Storage::disk($disk)->put("{$dir}/readme.txt", 'hi');
        Storage::disk($disk)->put("{$dir}/real.png", 'fake');

        $component = Livewire::test(MediaManager::class)
            ->call('switchFolder', 'production');

        $files = $component->get('files');
        $this->assertCount(1, $files);
        $this->assertSame('real.png', $files[0]['name']);
    }
}
