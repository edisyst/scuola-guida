<?php

namespace App\Notifications;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class InstructorStudentOutcome extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public User $student,
        public Quiz $quiz,
        public QuizAttempt $attempt,
    ) {
        $this->onQueue('emails');
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database', WebPushChannel::class];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $pct   = $this->attempt->total_questions > 0
            ? round($this->attempt->score * 100 / $this->attempt->total_questions, 1)
            : 0;
        $esito = $pct >= 60 ? 'Superato' : 'Non superato';

        return (new MailMessage())
            ->subject('Aggiornamento studente: ' . $this->student->name)
            ->line('Il tuo studente **' . $this->student->name . '** ha completato un quiz.')
            ->line('Quiz: **' . $this->quiz->title . '**')
            ->line('Punteggio: **' . $this->attempt->score . '/' . $this->attempt->total_questions . '** (' . $pct . '%)')
            ->line('Esito: **' . $esito . '**')
            ->action('Vedi progressi studente', route('instructor.students.show', $this->student));
    }

    public function toDatabase(object $notifiable): array
    {
        $pct   = $this->attempt->total_questions > 0
            ? round($this->attempt->score * 100 / $this->attempt->total_questions, 1)
            : 0;
        $esito = $pct >= 60 ? 'Superato' : 'Non superato';

        return [
            'title' => 'Aggiornamento studente: ' . $this->student->name,
            'body'  => $this->student->name . ' ha completato «' . Str::limit($this->quiz->title, 30) . '»: '
                . $this->attempt->score . '/' . $this->attempt->total_questions . ' — ' . $esito . '.',
            'url'   => route('instructor.students.show', $this->student),
            'icon'  => 'fas fa-user-graduate',
            'color' => $pct >= 60 ? 'success' : 'danger',
        ];
    }

    public function toWebPush(object $notifiable, object $notification): WebPushMessage
    {
        $pct = $this->attempt->total_questions > 0
            ? round($this->attempt->score * 100 / $this->attempt->total_questions, 1)
            : 0;

        return (new WebPushMessage())
            ->title('Studente: ' . $this->student->name)
            ->body(Str::limit($this->quiz->title, 40) . ' — ' . $pct . '%')
            ->icon('/icons/icon-192.png')
            ->action('Vedi progressi', route('instructor.students.show', $this->student));
    }
}
