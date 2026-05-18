<?php

namespace Tests\Feature;

use App\Models\Question;
use App\Models\User;
use App\Services\StudyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookmarkTest extends TestCase
{
    use RefreshDatabase;

    private function viewer(): User
    {
        return User::factory()->create(['role' => User::ROLE_VIEWER]);
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
}
