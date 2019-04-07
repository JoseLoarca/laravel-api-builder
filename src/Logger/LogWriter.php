<?php

namespace JoseLoarca\LaravelApiBuilder\Logger;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class LogWriter
{
    public function logRequest(Request $request)
    {
        $method = strtoupper($request->getMethod());
        $uri = $request->getPathInfo();
        $bodyAsJson = json_encode($request->except(config('requests-logger.except')));

        $message = "{$request->ip()} {$method} {$uri} - {$request->userAgent()} - Body: {$bodyAsJson}";

        if (config('requests-logger.log_files')) {
            $files = array_map(function (UploadedFile $file) {
                return $file->getClientOriginalName();
            }, iterator_to_array($request->files));

            $message .= ' - Files: '.implode(', ', $files);
        }

        Log::info($message);
    }
}
