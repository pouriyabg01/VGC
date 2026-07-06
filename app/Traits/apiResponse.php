<?php

namespace App\Traits;

trait apiResponse
{
    /**
     * Success response message
     *
     * @param $result
     * @param $message
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendResponse($result , $message = null)
    {
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $result,
        ];
        return response()->json($response , 200);
    }

    /**
     * Error response message
     *
     * @param $error
     * @param $errorMessage
     * @param $code
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendError($error , $errorMessage = [] , $code = 404)
    {
        $response = [
            'success' => false,
            'message' => $error
        ];

        if (!empty($errorMessage))
            $response['data'] = $errorMessage;
        return response()->json($response , $code);
    }
}
