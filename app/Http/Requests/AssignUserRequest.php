<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Check if user has company context
        $currentCompany = currentCompany();

        if (!$currentCompany) {
            return false;
        }

        // Check if user has access to current company
        return $this->user()->hasAccessToCompany($currentCompany->id);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'company_id' => 'required|exists:companies,id',
            'role_id' => 'required|exists:roles,id',
            'is_default' => 'boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'company_id.required' => 'Please select a company.',
            'company_id.exists' => 'The selected company does not exist.',

            'role_id.required' => 'Please select a role.',
            'role_id.exists' => 'The selected role does not exist.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $user = $this->route('user');

            if (!$user) {
                $validator->errors()->add('user', 'User not found.');
                return;
            }

            // Check if current user has access to target company
            if ($this->company_id) {
                $targetCompany = \App\Models\Company::find($this->company_id);
                if ($targetCompany && !$this->user()->hasAccessToCompany($targetCompany->id)) {
                    $validator->errors()->add('company_id', 'You do not have access to this company.');
                }

                // Check if user is already assigned to target company
                if ($user->hasAccessToCompany($this->company_id)) {
                    $validator->errors()->add('company_id', 'User is already assigned to this company.');
                }
            }

            // Validate role exists
            if ($this->role_id) {
                $role = \Spatie\Permission\Models\Role::find($this->role_id);
                if (!$role) {
                    $validator->errors()->add('role_id', 'The selected role does not exist.');
                }
            }
        });
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'company_id' => 'company',
            'role_id' => 'role',
            'is_default' => 'default company',
        ];
    }
}
