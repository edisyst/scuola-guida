<?php

namespace App\Services;

use App\Models\DrivingModule;
use App\Models\StudyContent;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;

class StudyContentService
{
    public function create(array $data, User $actor): StudyContent
    {
        if ($actor->role === 'instructor' && ($data['studyable_type'] ?? '') !== DrivingModule::class) {
            throw new AuthorizationException(__('study_content.instructor_category_forbidden'));
        }

        $data['created_by'] = $actor->id;
        $data['updated_by'] = $actor->id;

        return StudyContent::create($data);
    }

    public function update(StudyContent $content, array $data, User $actor): StudyContent
    {
        if ($actor->role === 'instructor' && $content->studyable_type !== DrivingModule::class) {
            throw new AuthorizationException(__('study_content.instructor_category_forbidden'));
        }

        $data['updated_by'] = $actor->id;
        $content->update($data);

        return $content;
    }

    public function delete(StudyContent $content): void
    {
        $content->delete();
    }

    public function markAsRead(StudyContent $content, User $user): void
    {
        if ($content->readers()->where('user_id', $user->id)->exists()) {
            $content->readers()->updateExistingPivot($user->id, ['read_at' => now()]);
        } else {
            $content->readers()->attach($user->id, ['read_at' => now()]);
        }
    }

    public function getForStudyable(string $type, int $id, bool $publishedOnly = true): Collection
    {
        $query = StudyContent::where('studyable_type', $type)
                             ->where('studyable_id', $id)
                             ->withCount('readers')
                             ->ordered();

        if ($publishedOnly) {
            $query->published();
        }

        return $query->get();
    }
}
