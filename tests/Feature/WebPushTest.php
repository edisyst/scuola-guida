<?php

namespace Tests\Feature;

use App\Console\Commands\SendSpacedRepetitionReminders;
use App\Models\User;
use App\Notifications\BadgeEarned;
use App\Notifications\RegistrazioneApprovataNotification;
use App\Notifications\SpacedRepetitionReminderNotification;
use App\Services\SpacedRepetitionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;
use Tests\TestCase;

class WebPushTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\EnsureTwoFactorAuthenticated::class);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // via() contains WebPushChannel
    // ──────────────────────────────────────────────────────────────────────────

    public function test_registrazione_approvata_includes_webpush_channel(): void
    {
        $notification = new RegistrazioneApprovataNotification();
        $this->assertContains(WebPushChannel::class, $notification->via(new User()));
    }

    public function test_badge_earned_includes_webpush_channel(): void
    {
        $notification = new BadgeEarned('first_quiz');
        $this->assertContains(WebPushChannel::class, $notification->via(new User()));
    }

    public function test_spaced_repetition_reminder_includes_only_webpush_channel(): void
    {
        $notification = new SpacedRepetitionReminderNotification(5);
        $this->assertSame([WebPushChannel::class], $notification->via(new User()));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // toWebPush() returns populated WebPushMessage
    // ──────────────────────────────────────────────────────────────────────────

    public function test_registrazione_approvata_to_webpush_has_title_and_body(): void
    {
        $notification = new RegistrazioneApprovataNotification();
        $dummy = new \stdClass();
        $message = $notification->toWebPush($dummy, $dummy);

        $this->assertInstanceOf(WebPushMessage::class, $message);
        $data = $message->toArray();
        $this->assertNotEmpty($data['title']);
        $this->assertNotEmpty($data['body']);
    }

    public function test_spaced_repetition_reminder_to_webpush_singular(): void
    {
        $notification = new SpacedRepetitionReminderNotification(1);
        $dummy = new \stdClass();
        $message = $notification->toWebPush($dummy, $dummy);

        $this->assertInstanceOf(WebPushMessage::class, $message);
        $data = $message->toArray();
        $this->assertSame('Ripasso intelligente', $data['title']);
        $this->assertStringContainsString('1 domanda', $data['body']);
    }

    public function test_spaced_repetition_reminder_to_webpush_plural(): void
    {
        $notification = new SpacedRepetitionReminderNotification(7);
        $dummy = new \stdClass();
        $message = $notification->toWebPush($dummy, $dummy);

        $data = $message->toArray();
        $this->assertStringContainsString('7 domande', $data['body']);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // POST /push-subscriptions
    // ──────────────────────────────────────────────────────────────────────────

    public function test_viewer_can_subscribe(): void
    {
        $viewer = User::factory()->create(['role' => User::ROLE_VIEWER]);

        $response = $this->actingAs($viewer)->postJson('/push-subscriptions', [
            'endpoint'        => 'https://fcm.googleapis.com/fcm/send/test-endpoint',
            'keys'            => ['p256dh' => 'pubkey', 'auth' => 'authtoken'],
            'contentEncoding' => 'aesgcm',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('push_subscriptions', [
            'subscribable_id'   => $viewer->id,
            'subscribable_type' => User::class,
            'endpoint'          => 'https://fcm.googleapis.com/fcm/send/test-endpoint',
        ]);
    }

    public function test_admin_cannot_subscribe_to_push(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->postJson('/push-subscriptions', [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/test-endpoint',
            'keys'     => ['p256dh' => 'pubkey', 'auth' => 'authtoken'],
        ]);

        $response->assertStatus(403);
    }

    public function test_editor_cannot_subscribe_to_push(): void
    {
        $editor = User::factory()->create(['role' => User::ROLE_EDITOR]);

        $response = $this->actingAs($editor)->postJson('/push-subscriptions', [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/test-endpoint',
            'keys'     => ['p256dh' => 'pubkey', 'auth' => 'authtoken'],
        ]);

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_subscribe(): void
    {
        $response = $this->postJson('/push-subscriptions', [
            'endpoint' => 'https://example.com/push',
        ]);

        $response->assertStatus(401);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // DELETE /push-subscriptions
    // ──────────────────────────────────────────────────────────────────────────

    public function test_viewer_can_unsubscribe(): void
    {
        $viewer = User::factory()->create(['role' => User::ROLE_VIEWER]);
        $endpoint = 'https://fcm.googleapis.com/fcm/send/delete-endpoint';

        $viewer->updatePushSubscription($endpoint, 'pubkey', 'authtoken');
        $this->assertDatabaseHas('push_subscriptions', ['endpoint' => $endpoint]);

        $response = $this->actingAs($viewer)->deleteJson('/push-subscriptions', [
            'endpoint' => $endpoint,
        ]);

        $response->assertNoContent();
        $this->assertDatabaseMissing('push_subscriptions', ['endpoint' => $endpoint]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // GDPR: cascade on user delete
    // ──────────────────────────────────────────────────────────────────────────

    public function test_deleting_user_removes_push_subscriptions(): void
    {
        $viewer = User::factory()->create(['role' => User::ROLE_VIEWER]);
        $viewer->updatePushSubscription('https://example.com/push', 'pubkey', 'authtoken');

        $this->assertDatabaseHas('push_subscriptions', ['subscribable_id' => $viewer->id]);

        $viewer->delete();

        $this->assertDatabaseMissing('push_subscriptions', ['subscribable_id' => $viewer->id]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Artisan command
    // ──────────────────────────────────────────────────────────────────────────

    public function test_reminder_command_sends_push_to_viewers_with_due_reviews_and_subscriptions(): void
    {
        Notification::fake();

        $viewerWithSub = User::factory()->create(['role' => User::ROLE_VIEWER]);
        $viewerWithSub->updatePushSubscription('https://example.com/push/1', 'pub1', 'auth1');

        $viewerNoSub = User::factory()->create(['role' => User::ROLE_VIEWER]);

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $spacedRepetition = $this->mock(SpacedRepetitionService::class);
        $spacedRepetition->shouldReceive('getUpcomingCount')
            ->with(\Mockery::on(fn ($u) => $u->id === $viewerWithSub->id))
            ->andReturn(['due_today' => 3, 'due_tomorrow' => 0, 'due_this_week' => 3]);

        $this->artisan('push:send-review-reminders')
            ->assertSuccessful();

        Notification::assertSentTo(
            $viewerWithSub,
            SpacedRepetitionReminderNotification::class,
            fn ($n) => $n->dueCount === 3
        );
        Notification::assertNotSentTo($viewerNoSub, SpacedRepetitionReminderNotification::class);
        Notification::assertNotSentTo($admin, SpacedRepetitionReminderNotification::class);
    }

    public function test_reminder_command_skips_viewers_with_zero_due_reviews(): void
    {
        Notification::fake();

        $viewer = User::factory()->create(['role' => User::ROLE_VIEWER]);
        $viewer->updatePushSubscription('https://example.com/push/2', 'pub2', 'auth2');

        $spacedRepetition = $this->mock(SpacedRepetitionService::class);
        $spacedRepetition->shouldReceive('getUpcomingCount')
            ->andReturn(['due_today' => 0, 'due_tomorrow' => 0, 'due_this_week' => 0]);

        $this->artisan('push:send-review-reminders')->assertSuccessful();

        Notification::assertNotSentTo($viewer, SpacedRepetitionReminderNotification::class);
    }
}
