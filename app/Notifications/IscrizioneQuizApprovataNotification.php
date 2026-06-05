<?php

namespace App\Notifications;

use App\Models\Quiz;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class IscrizioneQuizApprovataNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Quiz $quiz)
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
            ->subject(__('notifications.enrollment_approved_subject'))
            ->markdown('emails.iscrizione-quiz-approvata', [
                'user'   => $notifiable,
                'quiz'   => $this->quiz,
                'appUrl' => config('app.url'),
            ]);
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => __('notifications.enrollment_approved_db_title'),
            'body'  => __('notifications.enrollment_approved_db_body', ['title' => \Illuminate\Support\Str::limit($this->quiz->title, 60)]),
            'url'   => route('quiz.enrollments.mine'),
            'icon'  => 'fas fa-check-circle',
            'color' => 'success',
        ];
    }
}
