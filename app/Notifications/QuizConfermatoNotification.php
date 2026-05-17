<?php

namespace App\Notifications;

use App\Models\Quiz;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class QuizConfermatoNotification extends Notification implements ShouldQueue
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
            ->subject('Nuovo quiz disponibile per iscrizione')
            ->markdown('emails.quiz-confermato', [
                'user'   => $notifiable,
                'quiz'   => $this->quiz,
                'appUrl' => config('app.url'),
            ]);
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Nuovo quiz disponibile',
            'body'  => 'Il quiz «' . Str::limit($this->quiz->title, 50) . '» è ora aperto alle iscrizioni.',
            'url'   => route('quiz.confirmed.index'),
            'icon'  => 'fas fa-clipboard-check',
            'color' => 'info',
        ];
    }
}
