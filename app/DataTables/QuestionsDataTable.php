<?php

namespace App\DataTables;

use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class QuestionsDataTable
{
    public function response(Request $request): array
    {
        $query = Question::with('category:id,name');

        if ($search = $request->input('search.value')) {
            $query->where(function ($q) use ($search) {
                $q->where('question', 'like', "%{$search}%")
                    ->orWhereHas('category', fn ($q2) => $q2->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('is_true')) {
            $query->where('is_true', $request->is_true);
        }

        if ($request->filled('has_image')) {
            $query->whereNotNull('image');
        }

        $total    = Question::count();
        $filtered = $query->count();

        $data = $query
            ->skip((int) $request->start)
            ->take((int) $request->length)
            ->latest()
            ->get();

        return [
            'draw'            => (int) $request->draw,
            'recordsTotal'    => $total,
            'recordsFiltered' => $filtered,
            'data'            => $data->map(fn (Question $q) => $this->row($q))->all(),
        ];
    }

    private function row(Question $q): array
    {
        $user = auth()->user();
        $hideAnswer = $user && $user->isViewer();

        return [
            'id'       => $q->id,
            'category' => $q->category->name,
            'question' => '<span title="' . e($q->question) . '">' . e(Str::limit($q->question, 50)) . '</span>',
            'is_true'  => $hideAnswer
                ? ''
                : ($q->is_true
                    ? '<span class="badge badge-success">Vero</span>'
                    : '<span class="badge badge-danger">Falso</span>'),
            'image' => $q->image
                ? '<img src="' . (str_starts_with($q->image, 'http') ? $q->image : asset('storage/' . $q->image)) . '" width="50" class="question-thumb" style="cursor:zoom-in;" data-full-src="' . (str_starts_with($q->image, 'http') ? $q->image : asset('storage/' . $q->image)) . '">'
                : '',
            'actions'  => view('admin.questions.partials.actions', compact('q'))->render(),
            'checkbox' => '<input type="checkbox" class="row-checkbox" value="' . $q->id . '">',
        ];
    }
}
