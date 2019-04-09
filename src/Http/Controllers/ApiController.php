<?php

namespace App\Http\Controllers;

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
