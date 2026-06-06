<?php

namespace Tests\Feature;

use App\Http\Livewire\BookmarkButton;
use App\Models\Category;
use App\Models\LicenseType;
use App\Models\Question;
use App\Models\User;
use App\Services\StudyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BookmarkTest extends TestCase
{
    use RefreshDatabase;

    private function viewer(): User
    {
        $licenseType = LicenseType::factory()->create(['is_active' => true]);
        return User::factory()->create([
            'role' => User::ROLE_VIEWER,
            'active_license_type_id' => $licenseType->id,
        ]);
    }

    public function test_viewer_can_add_bookmark(): void
    {
        $viewer = $this->viewer();
        $question = Question::factory()->create();

        $viewer->bookmarkedQuestions()->attach($question->id);

        $this->assertDatabaseHas('question_user_bookmarks', [
            'user_id'     => $viewer->id,
            'question_id' => $question->id,
        ]);
        $this->assertTrue($viewer->bookmarkedQuestions()->where('questions.id', $question->id)->exists());
    }

    public function test_second_toggle_removes_bookmark(): void
    {
        $viewer = $this->viewer();
        $question = Question::factory()->create();

        $viewer->bookmarkedQuestions()->attach($question->id);
        $viewer->bookmarkedQuestions()->detach($question->id);

        $this->assertDatabaseMissing('question_user_bookmarks', [
            'user_id'     => $viewer->id,
            'question_id' => $question->id,
        ]);
    }

    public function test_unique_constraint_prevents_duplicate_bookmarks(): void
    {
        $viewer = $this->viewer();
        $question = Question::factory()->create();

        $viewer->bookmarkedQuestions()->attach($question->id);

        $this->expectException(\Illuminate\Database\QueryException::class);

        $viewer->bookmarkedQuestions()->attach($question->id);
    }

    public function test_bookmarks_index_shows_only_own_bookmarks(): void
    {
        $viewer = $this->viewer();
        $other  = $this->viewer();
        $q1     = Question::factory()->create();
        $q2     = Question::factory()->create();

        $viewer->bookmarkedQuestions()->attach($q1->id);
        $other->bookmarkedQuestions()->attach($q2->id);

        $response = $this->actingAs($viewer)->get(route('bookmarks.index'));

        $response->assertOk();
        $response->assertSee($q1->question);
        $response->assertDontSee($q2->question);
    }

    public function test_destroy_removes_own_bookmark(): void
    {
        $viewer   = $this->viewer();
        $question = Question::factory()->create();

        $viewer->bookmarkedQuestions()->attach($question->id);

        $this->actingAs($viewer)
            ->delete(route('bookmarks.destroy', $question))
            ->assertRedirect();

        $this->assertDatabaseMissing('question_user_bookmarks', [
            'user_id'     => $viewer->id,
            'question_id' => $question->id,
        ]);
    }

    public function test_destroy_returns_403_when_bookmark_not_owned(): void
    {
        $viewer   = $this->viewer();
        $other    = $this->viewer();
        $question = Question::factory()->create();

        // only $other has bookmarked this question
        $other->bookmarkedQuestions()->attach($question->id);

        $this->actingAs($viewer)
            ->delete(route('bookmarks.destroy', $question))
            ->assertForbidden();
    }

    public function test_start_study_with_bookmarks_source(): void
    {
        $viewer   = $this->viewer();
        $question = Question::factory()->create();

        $viewer->bookmarkedQuestions()->attach($question->id);

        $response = $this->actingAs($viewer)
            ->post(route('study.start'), ['source' => StudyService::SOURCE_BOOKMARKS]);

        $response->assertRedirect(route('study.play'));

        $this->get(route('study.play'))
            ->assertOk()
            ->assertSee($question->question);
    }

    public function test_start_study_with_empty_bookmarks_redirects_with_warning(): void
    {
        $viewer = $this->viewer();

        $response = $this->actingAs($viewer)
            ->post(route('study.start'), ['source' => StudyService::SOURCE_BOOKMARKS]);

        $response->assertRedirect(route('bookmarks.index'));
        $response->assertSessionHas('warning');
    }

    public function test_cascade_delete_removes_bookmarks_on_user_deletion(): void
    {
        $viewer   = $this->viewer();
        $question = Question::factory()->create();

        $viewer->bookmarkedQuestions()->attach($question->id);

        $this->assertDatabaseHas('question_user_bookmarks', ['user_id' => $viewer->id]);

        $viewer->delete();

        $this->assertDatabaseMissing('question_user_bookmarks', ['user_id' => $viewer->id]);
    }

    public function test_bookmarks_index_returns_200_for_authenticated_viewer(): void
    {
        $viewer = $this->viewer();

        $this->actingAs($viewer)
            ->get(route('bookmarks.index'))
            ->assertOk();
    }

    public function test_bookmarks_index_redirects_unauthenticated(): void
    {
        $this->get(route('bookmarks.index'))
            ->assertRedirect(route('login'));
    }

    public function test_filter_by_category(): void
    {
        $viewer    = $this->viewer();
        $cat1      = Category::factory()->create();
        $cat2      = Category::factory()->create();
        $q1        = Question::factory()->create(['category_id' => $cat1->id]);
        $q2        = Question::factory()->create(['category_id' => $cat2->id]);

        $viewer->bookmarkedQuestions()->attach([$q1->id, $q2->id]);

        $response = $this->actingAs($viewer)
            ->get(route('bookmarks.index', ['category_id' => $cat1->id]));

        $response->assertOk();
        $response->assertSee($q1->question);
        $response->assertDontSee($q2->question);
    }

    public function test_filter_by_search(): void
    {
        $viewer = $this->viewer();
        $q1     = Question::factory()->create(['question' => 'La cintura di sicurezza è obbligatoria']);
        $q2     = Question::factory()->create(['question' => 'Il limite di velocità in autostrada è 130']);

        $viewer->bookmarkedQuestions()->attach([$q1->id, $q2->id]);

        $response = $this->actingAs($viewer)
            ->get(route('bookmarks.index', ['search' => 'cintura']));

        $response->assertOk();
        $response->assertSee($q1->question);
        $response->assertDontSee($q2->question);
    }

    public function test_save_note_updates_pivot(): void
    {
        $viewer   = $this->viewer();
        $question = Question::factory()->create();

        $viewer->bookmarkedQuestions()->attach($question->id);

        $this->actingAs($viewer);

        Livewire::test(BookmarkButton::class, ['questionId' => $question->id])
            ->set('noteInput', 'nota di test')
            ->call('saveNote')
            ->assertSet('note', 'nota di test');

        $this->assertDatabaseHas('question_user_bookmarks', [
            'user_id'     => $viewer->id,
            'question_id' => $question->id,
            'note'        => 'nota di test',
        ]);
    }

    public function test_note_validation_rejects_over_500_chars(): void
    {
        $viewer   = $this->viewer();
        $question = Question::factory()->create();

        $viewer->bookmarkedQuestions()->attach($question->id);

        $this->actingAs($viewer);

        Livewire::test(BookmarkButton::class, ['questionId' => $question->id])
            ->set('noteInput', str_repeat('a', 501))
            ->call('saveNote')
            ->assertHasErrors(['noteInput' => 'max']);
    }
}
