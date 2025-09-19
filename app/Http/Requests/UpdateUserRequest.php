<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $currentCompany = currentCompany();
        $user = $this->route('user');

        if (!$currentCompany || !$user) {
            return false;
        }

        // Check if the target user exists in current company
        if (!$user->hasAccessToCompany($currentCompany->id)) {
            return false;
        }

        // Check if current user has access to manage users in current company
        return $this->user()->hasAccessToCompany($currentCompany->id);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = $this->route('user');

        return [
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user ? $user->id : null),
            ],
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
            'name.required' => 'Name is required.',
            'name.max' => 'Name cannot be longer than 255 characters.',

            'email.required' => 'Email address is required.',
            'email.email' => 'Please enter a valid email address.',
            'email.max' => 'Email cannot be longer than 255 characters.',
            'email.unique' => 'This email is already taken by another user.',

            'role_id.required' => 'Please select a role for the user.',
            'role_id.exists' => 'The selected role is invalid.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $currentCompany = currentCompany();
            $user = $this->route('user');

            if (!$currentCompany) {
                $validator->errors()->add('company', 'No company context available.');
                return;
            }

            if (!$user) {
                $validator->errors()->add('user', 'User not found.');
                return;
            }

            // Prevent removing the last admin
            if ($this->role_id) {
                $adminRole = \Spatie\Permission\Models\Role::where('name', 'Admin')->first();
                if ($adminRole) {
                    $adminCount = $currentCompany->users()
                        ->wherePivot('role_id', $adminRole->id)
                        ->count();

                    $currentUserRole = $user->companies()
                        ->where('company_id', $currentCompany->id)
                        ->first()?->pivot->role_id;

                    if ($adminCount <= 1 && $currentUserRole == $adminRole->id && $this->role_id != $adminRole->id) {
                        $validator->errors()->add('role_id', 'Cannot remove the last admin role from the company.');
                    }
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
            'role_id' => 'role',
            'is_default' => 'default company',
        ];
    }
}
