<?php

namespace App\Notifications;

use App\Models\Quiz;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NuovaIscrizioneQuizNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public User $viewer, public Quiz $quiz)
    {
        $this->onQueue('emails');
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        /** @var User $notifiable */
        return (new MailMessage())
            ->subject(__('notifications.new_enrollment_subject'))
            ->markdown('emails.nuova-iscrizione-quiz', [
                'admin'  => $notifiable,
                'viewer' => $this->viewer,
                'quiz'   => $this->quiz,
                'appUrl' => config('app.url'),
            ]);
    }

    public function toDatabase(object $notifiable): array
    {
        $viewerName = $this->viewer->fullAnagraphicName();

        return [
            'title' => __('notifications.new_enrollment_db_title'),
            'body'  => __('notifications.new_enrollment_db_body', [
                'name'  => $viewerName,
                'title' => \Illuminate\Support\Str::limit($this->quiz->title, 40),
            ]),
            'url'   => route('admin.enrollments.index'),
            'icon'  => 'fas fa-user-clock',
            'color' => 'warning',
        ];
    }
}
