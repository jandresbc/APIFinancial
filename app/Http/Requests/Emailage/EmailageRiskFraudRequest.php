<?php

namespace App\Http\Requests\Emailage;

use Illuminate\Foundation\Http\FormRequest;

class EmailageRiskFraudRequest extends FormRequest
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
            'cellphone'     => 'required|string|min:8|max:15',
            'email'         => 'required|email:rfc,dns|max:350',
            'flag'          => 'required|string|max:10'
        ];
    }
}
