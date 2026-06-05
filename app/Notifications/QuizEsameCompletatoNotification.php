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

class QuizEsameCompletatoNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public User $viewer,
        public Quiz $quiz,
        public QuizAttempt $attempt,
    ) {
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
            ->subject(__('notifications.exam_completed_subject', ['name' => $this->viewer->fullAnagraphicName()]))
            ->markdown('emails.quiz-esame-completato', [
                'admin'   => $notifiable,
                'viewer'  => $this->viewer,
                'quiz'    => $this->quiz,
                'attempt' => $this->attempt,
                'appUrl'  => config('app.url'),
            ]);
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => __('notifications.exam_completed_db_title'),
            'body'  => $this->viewer->fullAnagraphicName()
                . ' ha completato «' . Str::limit($this->quiz->title, 35) . '»: '
                . $this->attempt->score . '/' . $this->attempt->total_questions . '.',
            'url'   => route('admin.quizzes.confirmedResults'),
            'icon'  => 'fas fa-flag-checkered',
            'color' => 'info',
        ];
    }
}
