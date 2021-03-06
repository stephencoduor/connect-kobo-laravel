<?php

namespace Stats4sd\KoboLink\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TeamRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // only allow updates if the user can update the current team
        return backpack_auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'max:255'],
            'creator_id' => ['sometimes', 'required', 'exists:users,id'],
            'description' => ['required', 'string', 'max:65000'],
            'avatar' => ['sometimes'],
        ];
    }
}
