<?php

namespace JoseLoarca\LaravelApiBuilder\Logger;

use Illuminate\Http\Request;

class LoggerProfile
{
    public function shouldLogRequest(Request $request): bool
    {
        return in_array(strtolower($request->method()), config('requests-logger.should_log'));
    }
}
