<?php

namespace App\Http\Requests\Emailage;

use Illuminate\Foundation\Http\FormRequest;

class EmailageRiskRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'cellphone' => 'required|string|min:8|max:15',
            'email'     => 'required|email:rfc,dns|max:350',
            'firstName' => 'required|string|max:200',
            'ip'        => 'nullable|ipv4',
            'lastName'  => 'required|string|max:200',
        ];
    }
}
