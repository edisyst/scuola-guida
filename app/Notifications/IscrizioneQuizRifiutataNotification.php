<?php

namespace App\Notifications;

use App\Models\Quiz;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class IscrizioneQuizRifiutataNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Quiz $quiz, public ?string $motivazione = null)
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
            ->subject(__('notifications.enrollment_rejected_subject'))
            ->markdown('emails.iscrizione-quiz-rifiutata', [
                'user'        => $notifiable,
                'quiz'        => $this->quiz,
                'motivazione' => $this->motivazione,
                'appUrl'      => config('app.url'),
            ]);
    }

    public function toDatabase(object $notifiable): array
    {
        $body = __('notifications.enrollment_rejected_db_body', ['title' => \Illuminate\Support\Str::limit($this->quiz->title, 40)]);

        if ($this->motivazione) {
            $body .= ' Motivo: ' . \Illuminate\Support\Str::limit($this->motivazione, 40);
        }

        return [
            'title' => __('notifications.enrollment_rejected_db_title'),
            'body'  => $body,
            'url'   => route('quiz.enrollments.mine'),
            'icon'  => 'fas fa-times-circle',
            'color' => 'danger',
        ];
    }
}
