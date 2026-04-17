<?php

namespace App\Http\Requests;

use App\Services\CaseCreationRules;
use Illuminate\Foundation\Http\FormRequest;

class StoreCaseRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $parties = collect($this->input('parties', []))
            ->map(function ($party) {
                if (!is_array($party)) {
                    return $party;
                }

                foreach (['phone', 'phone_mobile', 'phone_office', 'attorney_phone'] as $field) {
                    if (array_key_exists($field, $party)) {
                        $party[$field] = $this->normalizePhoneNumber($party[$field]);
                    }
                }

                return $party;
            })
            ->all();

        $this->merge([
            'parties' => $parties,
        ]);
    }

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

    private function normalizePhoneNumber($value): ?string
    {
        if (!is_string($value)) {
            return $value;
        }

        $digits = preg_replace('/\D+/', '', $value);

        if (strlen($digits) !== 10) {
            return trim($value) === '' ? null : trim($value);
        }

        return sprintf('%s-%s-%s', substr($digits, 0, 3), substr($digits, 3, 3), substr($digits, 6, 4));
    }
}
