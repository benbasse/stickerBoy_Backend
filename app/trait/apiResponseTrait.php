<?php

namespace App\Traits;

use Illuminate\Http\Response;

trait apiResponseTrait
{
    protected function succesResponse($data, $message = null, $code = Response::HTTP_OK)
    {
        return response()->json([
            'succes' => true,
            'message' => $message,
            'data' => $data
        ], $code);
    }

    protected function errorResponse($message = null, $code = Response::HTTP_BAD_REQUEST)
    {
        return response()->json([
            'succes' => false,
            'message' => $message,
        ], $code);
    }

    protected function unAuthorizeResponse($message = null)
    {
        return $this->errorResponse($message, Response::HTTP_UNAUTHORIZED);
    }
}
