<?php

/**
 * Standard API Response Helper
 * Ensures consistent response format across all endpoints
 * 
 * HIGH PRIORITY FIX #21: Consistent Error Response Format
 */
class ResponseHelper
{
    /**
     * Generate success response
     * 
     * @param string $message
     * @param mixed $data
     * @param int $statusCode
     * @return array
     */
    public static function success($message = 'Success', $data = null, $statusCode = 200)
    {
        return [
            'status' => 'success',
            'message' => $message,
            'data' => $data,
        ];
    }

    /**
     * Generate error response
     * 
     * @param string $message
     * @param array $errors
     * @param int $statusCode
     * @return array
     */
    public static function error($message = 'An error occurred', $errors = null, $statusCode = 400)
    {
        $response = [
            'status' => 'error',
            'message' => $message,
        ];

        if ($errors) {
            $response['errors'] = $errors;
        }

        return $response;
    }

    /**
     * Generate validation error response
     * 
     * @param array $errors
     * @return array
     */
    public static function validationError($errors)
    {
        return [
            'status' => 'error',
            'message' => 'Validation failed',
            'errors' => $errors,
        ];
    }

    /**
     * Generate conflict response (409)
     * 
     * @param string $message
     * @param mixed $data
     * @return array
     */
    public static function conflict($message = 'Conflict', $data = null)
    {
        $response = [
            'status' => 'error',
            'message' => $message,
        ];

        if ($data) {
            $response['data'] = $data;
        }

        return $response;
    }

    /**
     * Generate unauthorized response (401/403)
     * 
     * @param string $message
     * @return array
     */
    public static function unauthorized($message = 'Unauthorized')
    {
        return [
            'status' => 'error',
            'message' => $message,
        ];
    }

    /**
     * Generate not found response (404)
     * 
     * @param string $message
     * @return array
     */
    public static function notFound($message = 'Resource not found')
    {
        return [
            'status' => 'error',
            'message' => $message,
        ];
    }

    /**
     * Generate rate limit response (429)
     * 
     * @param string $message
     * @return array
     */
    public static function rateLimitExceeded($message = 'Too many requests. Please try again later.')
    {
        return [
            'status' => 'error',
            'message' => $message,
        ];
    }

    /**
     * Generate unprocessable entity response (422)
     * 
     * @param string $message
     * @param array $errors
     * @return array
     */
    public static function unprocessable($message = 'Unable to process request', $errors = null)
    {
        $response = [
            'status' => 'error',
            'message' => $message,
        ];

        if ($errors) {
            $response['errors'] = $errors;
        }

        return $response;
    }
}
