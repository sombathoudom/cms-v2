<?php

namespace App\Http\Requests\Api;

use App\Http\Requests\Concerns\ContentFilterRules;

class PostIndexRequest extends ApiRequest
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
