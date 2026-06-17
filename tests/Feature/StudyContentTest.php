<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\DrivingModule;
use App\Models\LicenseType;
use App\Models\StudyContent;
use App\Models\User;
use App\Services\StudyContentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudyContentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\EnsureTwoFactorAuthenticated::class);
    }

    private function admin(): User { return User::factory()->create(['role' => 'admin']); }
    private function editor(): User { return User::factory()->create(['role' => 'editor']); }
    private function viewer(): User { return User::factory()->create(['role' => 'viewer']); }
    private function instructor(): User { return User::factory()->create(['role' => 'instructor']); }

    private function euCategory(): Category
    {
        return Category::factory()->create(['is_eu_directive' => true]);
    }

    private function module(): DrivingModule
    {
        $lt = LicenseType::factory()->create();
        return DrivingModule::factory()->create(['license_type_id' => $lt->id]);
    }

    public function test_admin_can_create_study_content_for_category(): void
    {
        $admin    = $this->admin();
        $category = $this->euCategory();

        $response = $this->actingAs($admin)->post(route('study-contents.store'), [
            'studyable_type' => Category::class,
            'studyable_id'   => $category->id,
            'title'          => 'Test EU content',
            'body'           => '<p>Body</p>',
            'is_published'   => true,
            'order'          => 0,
        ]);

        $response->assertRedirect(route('study-contents.index'));
        $this->assertDatabaseHas('study_contents', [
            'title'          => 'Test EU content',
            'studyable_type' => Category::class,
            'studyable_id'   => $category->id,
        ]);
    }

    public function test_admin_can_create_study_content_for_driving_module(): void
    {
        $admin  = $this->admin();
        $module = $this->module();

        $response = $this->actingAs($admin)->post(route('study-contents.store'), [
            'studyable_type' => DrivingModule::class,
            'studyable_id'   => $module->id,
            'title'          => 'Module content',
            'body'           => '<p>Body</p>',
            'is_published'   => false,
            'order'          => 1,
        ]);

        $response->assertRedirect(route('study-contents.index'));
        $this->assertDatabaseHas('study_contents', [
            'title'          => 'Module content',
            'studyable_type' => DrivingModule::class,
            'studyable_id'   => $module->id,
        ]);
    }

    public function test_instructor_can_create_study_content_for_driving_module(): void
    {
        $instructor = $this->instructor();
        $module     = $this->module();

        $response = $this->actingAs($instructor)->post(route('study-contents.store'), [
            'studyable_type' => DrivingModule::class,
            'studyable_id'   => $module->id,
            'title'          => 'Instructor module content',
            'body'           => '<p>Body</p>',
            'is_published'   => false,
            'order'          => 0,
        ]);

        $response->assertRedirect(route('study-contents.index'));
        $this->assertDatabaseHas('study_contents', ['title' => 'Instructor module content']);
    }

    public function test_instructor_cannot_create_study_content_for_category(): void
    {
        $instructor = $this->instructor();
        $category   = $this->euCategory();

        $this->actingAs($instructor)->post(route('study-contents.store'), [
            'studyable_type' => Category::class,
            'studyable_id'   => $category->id,
            'title'          => 'Forbidden',
            'body'           => '<p>Body</p>',
        ])->assertStatus(403);
    }

    public function test_viewer_cannot_access_crud(): void
    {
        $viewer = $this->viewer();
        $this->actingAs($viewer)->get(route('study-contents.index'))->assertStatus(403);
        $this->actingAs($viewer)->get(route('study-contents.create'))->assertStatus(403);
    }

    public function test_mark_as_read_records_timestamp_in_pivot(): void
    {
        $module  = $this->module();
        $content = StudyContent::factory()->forModule($module)->published()->create();
        $viewer  = $this->viewer();

        app(StudyContentService::class)->markAsRead($content, $viewer);

        $this->assertDatabaseHas('study_content_user', [
            'study_content_id' => $content->id,
            'user_id'          => $viewer->id,
        ]);

        $pivot = \DB::table('study_content_user')
            ->where('study_content_id', $content->id)
            ->where('user_id', $viewer->id)
            ->first();

        $this->assertNotNull($pivot->read_at);
    }

    public function test_is_read_by_returns_true_after_mark(): void
    {
        $module  = $this->module();
        $content = StudyContent::factory()->forModule($module)->published()->create();
        $viewer  = $this->viewer();

        $this->assertFalse($content->isReadBy($viewer));
        app(StudyContentService::class)->markAsRead($content, $viewer);
        $content->refresh();
        $this->assertTrue($content->isReadBy($viewer));
    }

    public function test_deleting_category_cascades_study_contents(): void
    {
        $category = $this->euCategory();
        StudyContent::factory()->forCategory($category)->count(2)->create();

        $this->assertCount(2, StudyContent::where('studyable_type', Category::class)
            ->where('studyable_id', $category->id)->get());

        $category->delete();

        $this->assertCount(0, StudyContent::where('studyable_type', Category::class)
            ->where('studyable_id', $category->id)->get());
    }

    public function test_deleting_driving_module_cascades_study_contents(): void
    {
        $module = $this->module();
        StudyContent::factory()->forModule($module)->count(2)->create();

        $this->assertCount(2, StudyContent::where('studyable_type', DrivingModule::class)
            ->where('studyable_id', $module->id)->get());

        $module->delete();

        $this->assertCount(0, StudyContent::where('studyable_type', DrivingModule::class)
            ->where('studyable_id', $module->id)->get());
    }
}