<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Check if user has company context and can manage users
        $currentCompany = currentCompany();

        if (!$currentCompany) {
            return false;
        }

        // Check if user has permission to manage users in current company
        return $this->user()->hasAccessToCompany($currentCompany->id);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'action' => 'required|in:create_new,assign_existing',
            'role_id' => 'required|exists:roles,id',
            'is_default' => 'boolean',
        ];

        if ($this->input('action') === 'create_new') {
            $rules = array_merge($rules, [
                'name' => 'required|string|max:255',
                'email' => [
                    'required',
                    'email',
                    'max:255',
                    Rule::unique('users'),
                ],
                'password' => 'required|string|min:8|confirmed',
            ]);
        } elseif ($this->input('action') === 'assign_existing') {
            $rules = array_merge($rules, [
                'user_id' => 'required|exists:users,id',
            ]);
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'action.required' => 'Please specify whether to create a new user or assign an existing user.',
            'action.in' => 'Invalid action. Please choose to create new or assign existing user.',
            'role_id.required' => 'Please select a role for the user.',
            'role_id.exists' => 'The selected role is invalid.',

            'name.required_if' => 'Name is required when creating a new user.',
            'email.required_if' => 'Email is required when creating a new user.',
            'email.unique' => 'A user with this email already exists.',
            'password.required_if' => 'Password is required when creating a new user.',
            'password.min' => 'Password must be at least 8 characters.',
            'password.confirmed' => 'Password confirmation does not match.',

            'user_id.required_if' => 'Please select a user to assign when choosing assign existing.',
            'user_id.exists' => 'The selected user does not exist.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $currentCompany = currentCompany();

            if (!$currentCompany) {
                $validator->errors()->add('company', 'No company context available.');
                return;
            }

            // If assigning existing user, check if user is already in company
            if ($this->action === 'assign_existing' && $this->user_id) {
                $user = \App\Models\User::find($this->user_id);
                if ($user && $user->hasAccessToCompany($currentCompany->id)) {
                    $validator->errors()->add('user_id', 'This user is already assigned to the current company.');
                }
            }

            // Validate that role exists and is appropriate
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
            'role_id' => 'role',
            'user_id' => 'user',
            'is_default' => 'default company',
        ];
    }
}
