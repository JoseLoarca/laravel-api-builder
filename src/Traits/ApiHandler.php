<?php

/**
 * This class handles API responses.
 *
 * @author José Loarca <joseloarca97@icloud.com>
 *
 */

namespace JoseLoarca\LaravelApiBuilder\Traits;

use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;

trait ApiHandler
{
    /**
     * Return an error response
     *
     * @param  string  $message  Error message
     * @param  int  $code  HTTP Code
     *
     * @return Response
     */
    protected function errorResponse($message, $code)
    {
        return response()->json(['error' => $message, 'code' => $code], $code);
    }

    /**
     * Return a response for a show all request
     *
     * @param  Collection  $collection  Collection to be returned
     * @param  int  $code  HTTP Code
     *
     * @return Response
     */
    protected function showAll(Collection $collection, $code = 200)
    {
        if ($collection->isEmpty()) {
            return $this->successResponse(['data' => $collection], $code);
        }
        $transformer = $collection->first()->transformer;
        $collection = $this->transformData($collection, $transformer);
        return $this->successResponse($collection, $code);
    }

    /**
     * Return a successful response
     *
     * @param  mixed  $data  Request data
     * @param  int  $code  HTTP Code
     *
     * @return Response
     */
    private function successResponse($data, $code)
    {
        return response()->json($data, $code);
    }

    /**
     * Transform request response
     *
     * @param $data
     * @param $transformer
     *
     * @return array
     */
    protected function transformData($data, $transformer)
    {
        $transformation = fractal($data, new $transformer);
        return $transformation->toArray();
    }

    /**
     * Return a response for a show one request
     *
     * @param  Model  $instance  Model instance to be returned
     * @param  int  $code  HTTP Code
     *
     * @return Response
     */
    protected function showOne(Model $instance, $code = 200)
    {
        $transformer = $instance->transformer;
        $instance = $this->transformData($instance, $transformer);
        return $this->successResponse($instance, $code);
    }

    /**
     * Return a successful message
     *
     * @param  mixed  $message  Message
     * @param  int  $code  HTTP Code
     *
     * @return Response
     */
    protected function showMessage($message, $code = 200)
    {
        return $this->successResponse(['data' => $message, 'code' => 200], $code);
    }
}