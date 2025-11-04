<?php

namespace App\Http\Requests\Concerns;

trait ContentFilterRules
{
    /**
     * @return array<string, mixed>
     */
    protected function contentFilterRules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'tag' => ['nullable', 'string', 'max:255'],
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'month' => ['nullable', 'integer', 'between:1,12'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }
}
