<?php

namespace App\Http\Requests\Order;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class OrderRequest extends FormRequest
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
     * @return array<string, Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'work_shift_id' => 'required|integer',
            'table_id' => 'required|integer',
            'number_of_person' => 'required|integer'
        ];
    }
}
