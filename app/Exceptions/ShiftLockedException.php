<?php

namespace App\Exceptions;

use Exception;

class ShiftLockedException extends Exception
{
    public function render($request)
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'locked'  => true,
        ], 403);
    }
}
