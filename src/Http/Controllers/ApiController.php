<?php

namespace JoseLoarca\LaravelApiBuilder\Http\Controllers;

use App\Http\Controllers\Controller;
use JoseLoarca\LaravelApiBuilder\Traits\ApiHandler;

class ApiController extends Controller
{
    use ApiHandler;

    /**
     * ApiController constructor.
     *
     * @return void
     */
    public function __construct()
    {
    }
}
