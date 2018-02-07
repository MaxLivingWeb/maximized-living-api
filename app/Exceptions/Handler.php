<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use App\Helpers\SlackHelper;

class Handler extends ExceptionHandler
{
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
     * @param  \Exception  $exception
     * @return void
     */
    public function report(Exception $exception)
    {
        $this->_notify($exception);
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $exception)
    {
        if ($exception instanceof ModelNotFoundException)
        {
            return response()->json([], 404);
        }

        return parent::render($request, $exception);
    }

    /**
     * Handles error notifications to Slack.
     *
     * @param Exception $e
     */
    private function _notify(Exception $e): void
    {
        if ($this->isHttpException($e) && $e->getStatusCode() !== 404) {
            if(config('app.env') == 'local' && empty(env('LOCAL_ERROR_SLACK_ID'))) {
                return;
            }
            $code = $e->getStatusCode() ?? $e->getCode() ?? NULL;
            $message = $e->getMessage();
            $application = config('app.name');
            $environment = config('app.env');
            $trace = $e->getTraceAsString();

            $request = request();
            $url = $request->fullUrl() ?? NULL;
            $method = $request->method() ?? NULL;
            $referer = $request->server()['HTTP_REFERER'] ?? NULL;
            $clientIP = $request->ip() ?? NULL;
            $data = $request->input() ?? NULL;

            $message = '*Application*: ' . $application . "\n" .
                '*Environment*: ' . $environment . "\n" .
                (!empty($code) ? '*Code*: ' . $code . "\n" : '') .
                (!empty($url) ? '*URL*: ' . $url . "\n" : '') .
                (!empty($method) ? '*Method*: ' . $method . "\n" : '') .
                (!empty($referer) ? '*Referer*: ' . $referer . "\n" : '') .
                (!empty($clientIP) ? '*Client IP*: ' . $clientIP . "\n" : '') .
                (!empty($data) ? '*Request Data*: ' . print_r($data, TRUE) . "\n" : '') .
                (!empty($message) ? '*Code*: ' . $message . "\n" : '') .
                '*Trace*: ' . "\n" . $trace . "\n" .
                "---------------------------------------------------------------------------------------------------------------------\n\n";

            SlackHelper::slackNotification(
                $message,
                (config('app.env') === 'local' ? env('LOCAL_ERROR_SLACK_ID') : 'C5S9LV83S')
            );
        }
    }
}
