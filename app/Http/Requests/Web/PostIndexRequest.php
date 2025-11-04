<?php

namespace App\Http\Requests\Web;

use App\Http\Requests\Concerns\ContentFilterRules;
use Illuminate\Foundation\Http\FormRequest;

class PostIndexRequest extends FormRequest
{
    use ContentFilterRules;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return $this->contentFilterRules();
    }
}
