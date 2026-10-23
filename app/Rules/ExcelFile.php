<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class ExcelFile implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        // $mimes = [
        //     'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',

        // ];
        // $mimeFile = $value->getMimeType(); 
        // return in_array($mimeFile, $mimes);

        $names = explode('.', $value->getClientOriginalName()); 
        if (count($names) != 2) return false; 
        
        return $names[1] == 'xlsx' ? true : false;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'File harus Microsoft Excel';
    }
}
