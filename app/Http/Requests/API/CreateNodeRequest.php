<?php

namespace App\Http\Requests\API;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateNodeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required','string','max:255'],
            'type' => ['required', Rule::in(['Corporation','Building','Property','Tenancy Period','Tenant'])],
            'parent_id' => ['nullable','exists:nodes,id'],
            
            'zip_code' => ['nullable','string','max:20'],
            'monthly_rent' => ['nullable','numeric','min:0'],
            'active' => ['nullable','boolean'],
            'moved_in_date' => ['nullable','date'],
        ];
    }
}
