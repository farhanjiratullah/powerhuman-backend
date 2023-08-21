<?php

namespace App\Helpers;

/**
 * Format response.
 */
class ResponseFormatter
{
    /**
     * API Response
     *
     * @var array
     */
    protected static $successResponse = [
        'meta' => [
            'code' => 200,
            'status' => 'success',
            'message' => null,
        ],
        'data' => null,
    ];

    protected static $errorResponse = [
        'meta' => [
            'code' => 400,
            'status' => 'error',
            'message' => null,
        ],
        'errors' => null,
    ];

    /**
     * Give success response.
     */
    public static function success($data = null, $message = null, $code = 200)
    {
        self::$successResponse['meta']['code'] = $code;
        self::$successResponse['meta']['message'] = $message;
        self::$successResponse['data'] = $data;

        return response()->json(self::$successResponse, self::$successResponse['meta']['code']);
    }

    /**
     * Give error response.
     */
    public static function error($errors = null, $message = null, $code = 400)
    {
        self::$errorResponse['meta']['code'] = $code;
        self::$errorResponse['meta']['message'] = $message;
        self::$errorResponse['errors'] = $errors;

        return response()->json(self::$errorResponse, self::$errorResponse['meta']['code']);
    }
}
