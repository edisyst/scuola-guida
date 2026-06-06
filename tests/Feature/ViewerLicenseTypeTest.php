<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\LicenseType;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ViewerLicenseTypeTest extends TestCase
{
    use RefreshDatabase;

    public function test_viewer_without_active_license_type_is_redirected_to_profile(): void
    {
        $viewer = User::factory()->create(['role' => User::ROLE_VIEWER, 'active_license_type_id' => null]);

        $this->actingAs($viewer)
            ->get(route('study.index'))
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHas('warning', __('flash.license_type_required'));
    }

    public function test_viewer_with_license_type_can_access_study(): void
    {
        $licenseType = LicenseType::factory()->create(['is_active' => true]);
        $viewer      = User::factory()->create([
            'role'                    => User::ROLE_VIEWER,
            'active_license_type_id'  => $licenseType->id,
        ]);

        $this->actingAs($viewer)
            ->get(route('study.index'))
            ->assertOk();
    }

    public function test_study_filters_questions_by_active_license_type(): void
    {
        $licenseTypeA = LicenseType::factory()->create(['code' => 'TEST_A', 'is_active' => true]);
        $licenseTypeB = LicenseType::factory()->create(['code' => 'TEST_B', 'is_active' => true]);

        $categoryA = Category::factory()->create(['name' => 'Category A']);
        $categoryB = Category::factory()->create(['name' => 'Category B']);

        $categoryA->licenseTypes()->attach($licenseTypeA);
        $categoryB->licenseTypes()->attach($licenseTypeB);

        $questionA = Question::factory()->create(['category_id' => $categoryA->id]);
        $questionB = Question::factory()->create(['category_id' => $categoryB->id]);

        $viewer = User::factory()->create([
            'role'                   => User::ROLE_VIEWER,
            'active_license_type_id' => $licenseTypeB->id,
        ]);

        $this->actingAs($viewer)
            ->get(route('study.index'))
            ->assertSee($categoryB->name)
            ->assertDontSee($categoryA->name);
    }

    public function test_simulator_uses_license_type_exam_format_when_available(): void
    {
        $licenseType = LicenseType::factory()->create([
            'is_active'        => true,
            'exam_questions'   => 25,
            'exam_minutes'     => 30,
            'exam_max_errors'  => 4,
        ]);

        $viewer = User::factory()->create([
            'role'                   => User::ROLE_VIEWER,
            'active_license_type_id' => $licenseType->id,
        ]);

        $this->actingAs($viewer)
            ->get(route('simulator.index'))
            ->assertSee('25')
            ->assertSee('30');
    }

    public function test_simulator_uses_config_when_license_type_format_is_null(): void
    {
        $licenseType = LicenseType::factory()->create([
            'is_active'        => true,
            'exam_questions'   => null,
            'exam_minutes'     => null,
            'exam_max_errors'  => null,
        ]);

        $viewer = User::factory()->create([
            'role'                   => User::ROLE_VIEWER,
            'active_license_type_id' => $licenseType->id,
        ]);

        $defaultQuestions = (int) config('simulator.questions');

        $this->actingAs($viewer)
            ->get(route('simulator.index'))
            ->assertSee((string) $defaultQuestions);
    }

    public function test_diagnostic_filters_categories_by_active_license_type(): void
    {
        $licenseTypeB = LicenseType::factory()->create(['code' => 'TEST_DIAG', 'is_active' => true]);

        $categoryA = Category::factory()->create();
        $categoryB = Category::factory()->create();

        $categoryB->licenseTypes()->attach($licenseTypeB);

        Question::factory()->create(['category_id' => $categoryA->id]);
        Question::factory()->create(['category_id' => $categoryB->id]);

        $viewer = User::factory()->create([
            'role'                   => User::ROLE_VIEWER,
            'active_license_type_id' => $licenseTypeB->id,
        ]);

        $service = app(\App\Services\DiagnosticService::class);
        $questions = $service->generateQuestions($viewer);

        $categoryIds = $questions->pluck('category_id')->unique();

        $this->assertTrue($categoryIds->contains($categoryB->id));
        $this->assertFalse($categoryIds->contains($categoryA->id));
    }

    public function test_spaced_repetition_filters_by_active_license_type(): void
    {
        $licenseTypeB = LicenseType::factory()->create(['code' => 'TEST_SPACED', 'is_active' => true]);

        $categoryA = Category::factory()->create();
        $categoryB = Category::factory()->create();

        $categoryB->licenseTypes()->attach($licenseTypeB);

        $questionA = Question::factory()->create(['category_id' => $categoryA->id]);
        $questionB = Question::factory()->create(['category_id' => $categoryB->id]);

        $viewer = User::factory()->create([
            'role'                   => User::ROLE_VIEWER,
            'active_license_type_id' => $licenseTypeB->id,
        ]);

        $service = app(\App\Services\SpacedRepetitionService::class);
        $service->recordAnswer($viewer, $questionA->id, true);
        $service->recordAnswer($viewer, $questionB->id, true);

        // Manipula i timestamp per forzare le domande a essere dovute
        \App\Models\QuestionReview::where('user_id', $viewer->id)
            ->update(['next_review_at' => now()->subHour()]);

        $dueQuestions = $service->getDueQuestions($viewer);
        $dueIds       = $dueQuestions->pluck('question_id')->toArray();

        $this->assertContains($questionB->id, $dueIds);
        $this->assertNotContains($questionA->id, $dueIds);
    }

    public function test_admin_is_not_blocked_by_license_type_middleware(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'active_license_type_id' => null]);

        $this->actingAs($admin)
            ->get(route('study.index'))
            ->assertOk();
    }

    public function test_editor_is_not_blocked_by_license_type_middleware(): void
    {
        $editor = User::factory()->create(['role' => User::ROLE_EDITOR, 'active_license_type_id' => null]);

        $this->actingAs($editor)
            ->get(route('study.index'))
            ->assertOk();
    }

    public function test_update_active_license_type_request_validates_existence(): void
    {
        $viewer = User::factory()->create([
            'role'                   => User::ROLE_VIEWER,
            'active_license_type_id' => null,
        ]);

        $this->actingAs($viewer)
            ->patch(route('profile.license-type.update'), ['active_license_type_id' => 9999])
            ->assertSessionHasErrors('active_license_type_id');
    }

    public function test_update_active_license_type_request_rejects_inactive_license_type(): void
    {
        $inactiveLicenseType = LicenseType::factory()->create(['is_active' => false]);
        $viewer              = User::factory()->create([
            'role'                   => User::ROLE_VIEWER,
            'active_license_type_id' => null,
        ]);

        $this->actingAs($viewer)
            ->patch(route('profile.license-type.update'), ['active_license_type_id' => $inactiveLicenseType->id])
            ->assertSessionHasErrors('active_license_type_id');
    }

    public function test_update_active_license_type_succeeds_with_valid_active_type(): void
    {
        $licenseType = LicenseType::factory()->create(['is_active' => true]);
        $viewer      = User::factory()->create([
            'role'                   => User::ROLE_VIEWER,
            'active_license_type_id' => null,
        ]);

        $this->actingAs($viewer)
            ->patch(route('profile.license-type.update'), ['active_license_type_id' => $licenseType->id])
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHas('success', __('flash.license_type_updated'));

        $this->assertDatabaseHas('users', [
            'id'                    => $viewer->id,
            'active_license_type_id' => $licenseType->id,
        ]);
    }

    public function test_deleting_license_type_sets_user_active_license_type_id_to_null(): void
    {
        $licenseType = LicenseType::factory()->create(['is_active' => true]);
        $viewer      = User::factory()->create([
            'role'                   => User::ROLE_VIEWER,
            'active_license_type_id' => $licenseType->id,
        ]);

        $licenseType->delete();

        $this->assertDatabaseHas('users', [
            'id'                    => $viewer->id,
            'active_license_type_id' => null,
        ]);

        // Viewer is not deleted
        $this->assertTrue(User::find($viewer->id)->exists());
    }
}
