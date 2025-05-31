<?php

namespace Mahfouz\Helpers\Traits;

trait FormatResponse
{
    /**
     * Return a success response.
     *
     * @param  string|null  $message
     * @param  mixed  $data
     * @return array
     */
    public function successResponse($message = null, $data = null)
    {
        return [
            'success' => true,
            'message' => $message,
            'data' => $data,
        ];
    }

    /**
     * Return an error response.
     *
     * @param  string  $message
     * @return array
     */
    public function errorResponse($message)
    {
        return [
            'success' => false,
            'message' => $message,
            'data' => null,
        ];
    }
}
