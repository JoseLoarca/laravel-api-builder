<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use JoseLoarca\LaravelApiBuilder\Stack\CorsService;
use JoseLoarca\LaravelApiBuilder\Traits\ApiHandler;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Handler extends ExceptionHandler
{
    use ApiHandler;
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];
    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param Exception $exception
     *
     * @throws Exception
     *
     * @return void
     */
    public function report(Exception $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param Request   $request
     * @param Exception $exception
     *
     * @return Response
     */
    public function render($request, Exception $exception)
    {
        $response = $this->handleException($request, $exception);
        app(CorsService::class)->addActualRequestHeaders($response, $request);

        return $response;
    }

    /**
     * Handles different types of exceptions.
     *
     * @param $request
     * @param Exception $exception
     *
     * @return Response
     */
    public function handleException($request, Exception $exception)
    {
        if ($exception instanceof ValidationException) {
            return $this->convertValidationExceptionToResponse($exception, $request);
        }
        if ($exception instanceof ModelNotFoundException) {
            $model = class_basename($exception->getModel());

            return $this->errorResponse("No query results for {$model} with the specified ID.", 404);
        }
        if ($exception instanceof AuthenticationException) {
            return $this->unauthenticated($request, $exception);
        }
        if ($exception instanceof AuthorizationException) {
            return $this->errorResponse('You do not have permission to perform that action.', 403);
        }
        if ($exception instanceof NotFoundHttpException) {
            return $this->errorResponse('We could not find the URL you requested.', 404);
        }
        if ($exception instanceof MethodNotAllowedHttpException) {
            return $this->errorResponse('The specified method for the request is not valid.', 405);
        }
        if ($exception instanceof HttpException) {
            return $this->errorResponse($exception->getMessage(), $exception->getStatusCode());
        }
        if ($exception instanceof QueryException) {
            $code = $exception->errorInfo[1];
            if ($code == 1451) {
                return $this->errorResponse('The resource can not be deleted because it is related to another resource.',
                    409);
            }
        }
        if ($exception instanceof TokenMismatchException) {
            return redirect()->back()->withInput($request->input());
        }
        if (config('app.debug')) {
            return parent::render($request, $exception);
        }

        return $this->errorResponse('An unexpected error has occurred. Please try again.', 500);
    }

    /**
     * Create a response object from the given validation exception.
     *
     * @param ValidationException $e
     * @param Request             $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function convertValidationExceptionToResponse(ValidationException $e, $request)
    {
        $errors = $e->validator->errors()->getMessages();
        if ($this->isFrontend($request)) {
            return $request->ajax() ? response()->json($errors, 422) : redirect()->back()
                ->withInput($request->input())
                ->withErrors($errors);
        }

        return $this->errorResponse($errors, 422);
    }

    /**
     * Checks if a request comes from web.
     *
     * @param $request
     *
     * @return bool
     */
    private function isFrontend($request)
    {
        return $request->acceptsHtml() && collect($request->route()->middleware())->contains('web');
    }

    /**
     * Convert an authentication exception into a response.
     *
     * @param Request                 $request
     * @param AuthenticationException $exception
     *
     * @return Response
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($this->isFrontend($request)) {
            return redirect()->guest('login');
        }

        return $this->errorResponse('Not authenticated.', 401);
    }
}
