<?php


namespace JoseLoarca\LaravelApiBuilder\Middleware;

use Closure;
use Illuminate\Http\Request;

class RequestsLogger
{
    public function __construct()
    {
    }

    public function handle(Request $request, Closure $closure)
    {

    }
}