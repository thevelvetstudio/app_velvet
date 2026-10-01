<?php

namespace App\Http\Requests;

use App\Models\Lead;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'candidate_type' => ['required', 'in:MODEL,MONITOR'], 'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'], 'sex' => ['required', 'in:WOMAN,MAN'], 'phone' => ['required', 'string', 'max:40'],
            'email' => ['required', 'email', 'max:190'], 'country' => ['required', 'string', 'max:120'], 'city' => ['required', 'string', 'max:120'],
            'birth_date' => ['required', 'date', 'before_or_equal:'.now()->subYears(18)->toDateString()], 'experience' => ['required', 'string', 'max:5000'],
            'experience_years' => ['nullable', 'required_if:candidate_type,MONITOR', Rule::in(['1', '2', '3_PLUS'])],
            'speaks_english' => ['required', 'boolean'], 'english_level' => [Rule::requiredIf(fn () => $this->boolean('speaks_english')), 'nullable', Rule::in(['A1', 'A2', 'B1', 'B2', 'C1', 'C2', 'NATIVE'])],
            'availability' => ['required', 'string', 'max:100'], 'work_mode' => ['required', 'string', 'max:100', 'in:En estudio'],
            'source' => ['nullable', 'string', 'max:100'], 'motivation' => ['nullable', 'string', 'max:5000'],
            'goals' => ['nullable', 'array'], 'goals.*' => ['string', 'max:100'], 'data_consent' => ['accepted'],
            'identity_document' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $email = strtolower(trim((string) $this->input('email')));
            $phone = trim((string) $this->input('phone'));

            if ($email && Lead::whereRaw('LOWER(email) = ?', [$email])->exists()) {
                $validator->errors()->add('email', 'Este correo ya tiene una aplicación registrada o está vinculado a Velvet.');
            }

            if ($phone && Lead::where('phone', $phone)->exists()) {
                $validator->errors()->add('phone', 'Este número ya tiene una aplicación registrada o está vinculado a Velvet.');
            }
        });
    }
}

