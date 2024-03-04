<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\Response;

class ApiException extends HttpResponseException
{
    public function __construct($code, $message, $errors = [])
    {
        $data = [
            'success' => false,
            'code' => $code,
        ];
        if (!empty($message)) {
            $data['message'] = $message;
        }
        if (count($errors) > 0) {
            $data['message'] = $errors;
        }
        parent::__construct( response()->json($data)->setStatusCode($code));
    }
}

