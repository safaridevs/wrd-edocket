<?php

namespace App\Http\Requests;

use App\Services\CaseCreationRules;
use Illuminate\Foundation\Http\FormRequest;

class StoreCaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->canCreateCase();
    }

    public function rules(): array
    {
        return app(CaseCreationRules::class)->validationRules($this->input('case_type'));
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            app(CaseCreationRules::class)->validateBusinessRules($validator);
        });
    }
}
