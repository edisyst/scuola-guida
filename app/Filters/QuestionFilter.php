<?php

namespace App\Filters;

use Illuminate\Http\Request;

class QuestionFilter
{
    protected $request;

    protected $allowed = [
        'category_id',
        'search',
    ];

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function apply($query)
    {
        foreach ($this->filters() as $filter => $value) {
            if (method_exists($this, $filter)) {
                $query = $this->$filter($query, $value);
            }
        }

        return $query;
    }

    protected function filters()
    {
        return $this->request->only($this->allowed);
    }

    private function category_id($query, $value)
    {
        return $query->where('category_id', $value);
    }

    private function search($query, $value)
    {
        return $query->where('question', 'like', "%{$value}%");
    }

    // ancora non le uso ma potrei integrarle
    private function is_true($query, $value)
    {
        return $query->where('is_true', $value);
    }
    private function has_image($query, $value)
    {
        if ($value) {
            return $query->whereNotNull('image');
        }

        return $query;
    }
}
