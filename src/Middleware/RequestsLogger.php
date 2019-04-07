<?php

namespace JoseLoarca\LaravelApiBuilder\Middleware;

use Closure;
use Illuminate\Http\Request;
use JoseLoarca\LaravelApiBuilder\Logger\LoggerProfile;
use JoseLoarca\LaravelApiBuilder\Logger\LogWriter;

class RequestsLogger
{
    /**
     * LoggerProfile instance.
     *
     * @var LoggerProfile
     */
    protected $loggerProfile;

    /**
     * LogWriter instance.
     *
     * @var LogWriter
     */
    protected $logWriter;

    /**
     * RequestsLogger constructor.
     *
     * @param LoggerProfile $loggerProfile
     * @param LogWriter     $logWriter
     */
    public function __construct(LoggerProfile $loggerProfile, LogWriter $logWriter)
    {
        $this->loggerProfile = $loggerProfile;
        $this->logWriter = $logWriter;
    }

    public function handle(Request $request, Closure $closure)
    {
        if ($this->loggerProfile->shouldLogRequest($request)) {
            $this->logWriter->logRequest($request);
        }

        return $closure($request);
    }
}
