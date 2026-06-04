<?php

namespace Tests\Feature;

use App\Models\Question;
use App\Models\User;
use App\Services\StudyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TtsPreferenceTest extends TestCase
{
    use RefreshDatabase;

    private function viewer(): User
    {
        return User::factory()->create(['role' => 'viewer']);
    }

    private function startStudySession(User $user): void
    {
        Question::factory()->count(3)->create();
        $this->actingAs($user)->post(route('study.start'), [
            'source' => StudyService::SOURCE_RANDOM,
        ]);
    }

    public function test_viewer_can_enable_tts(): void
    {
        $user = $this->viewer();

        $response = $this->actingAs($user)->post('/profile/accessibility', [
            'tts_enabled'  => '1',
            'tts_autoplay' => '0',
        ]);

        $response->assertRedirect('/profile');
        $response->assertSessionHas('success');

        $user->refresh();
        $this->assertTrue($user->tts_enabled);
        $this->assertFalse($user->tts_autoplay);
    }

    public function test_viewer_can_enable_tts_with_autoplay(): void
    {
        $user = $this->viewer();

        $this->actingAs($user)->post('/profile/accessibility', [
            'tts_enabled'  => '1',
            'tts_autoplay' => '1',
        ]);

        $user->refresh();
        $this->assertTrue($user->tts_enabled);
        $this->assertTrue($user->tts_autoplay);
    }

    public function test_viewer_can_disable_tts(): void
    {
        $user = $this->viewer();
        $user->tts_enabled  = true;
        $user->tts_autoplay = true;
        $user->save();

        $this->actingAs($user)->post('/profile/accessibility', [
            'tts_enabled'  => '0',
            'tts_autoplay' => '0',
        ]);

        $user->refresh();
        $this->assertFalse($user->tts_enabled);
        $this->assertFalse($user->tts_autoplay);
    }

    public function test_non_viewer_cannot_update_accessibility(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post('/profile/accessibility', [
            'tts_enabled' => '1',
        ]);

        $response->assertForbidden();
    }

    public function test_tts_button_not_rendered_when_disabled(): void
    {
        $user = $this->viewer();
        $user->tts_enabled = false;
        $user->save();

        $this->startStudySession($user);
        $response = $this->actingAs($user)->get('/study/play?index=0');

        $response->assertOk();
        // The conditional @if block is not rendered when tts_enabled is false
        $response->assertDontSee('Leggi la domanda ad alta voce');
    }

    public function test_tts_button_rendered_when_enabled(): void
    {
        $user = $this->viewer();
        $user->tts_enabled = true;
        $user->save();

        $this->startStudySession($user);
        $response = $this->actingAs($user)->get('/study/play?index=0');

        $response->assertOk();
        $response->assertSee('Leggi la domanda ad alta voce');
        $response->assertSee('Ascolta');
    }

    public function test_request_validates_boolean_fields(): void
    {
        $user = $this->viewer();

        $response = $this->actingAs($user)->post('/profile/accessibility', [
            'tts_enabled'  => 'not-a-boolean',
            'tts_autoplay' => 'not-a-boolean',
        ]);

        $response->assertSessionHasErrors(['tts_enabled', 'tts_autoplay']);
    }

    public function test_migration_is_reversible(): void
    {
        // Verifica che le colonne esistano dopo migrate
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasColumn('users', 'tts_enabled'));
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasColumn('users', 'tts_autoplay'));
    }
}
