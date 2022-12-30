<?php

/*
 *
 * @Project        Expression project.displayName is undefined on line 5, column 35 in Templates/Licenses/license-default.txt.
 * @Copyright      Djoudi
 * @Created        2017-02-21
 * @Filename       H5PExceptionHandler.php
 * @Description
 *
 */

namespace Alsay\LaravelH5p\Exceptions;

use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class H5PExceptionHandler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        HttpException::class,
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param Exception|Throwable $e
     *
     * @throws Throwable
     */
    public function report(Exception|Throwable $e)
    {
        parent::report($e);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param Request $request
     * @param Exception|Throwable $e
     *
     * @return Response
     * @throws Throwable
     */
    public function render($request, Exception|Throwable $e): Response
    {
        switch ($e) {
            case $e instanceof H5PException:
            case $e instanceof ModelNotFoundException:
                return $this->renderException($e);
            default:
                return parent::render($request, $e);
        }
    }

    protected function renderException($e): Response|bool|string
    {
        return match ($e) {
            $e instanceof ModelNotFoundException => response()->view('errors.404', [], 404),
            $e instanceof H5PException => response()->view('errors.friendly'),
            default => json_encode($e),
        };
    }
}
