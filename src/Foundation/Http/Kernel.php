<?php

declare(strict_types=1);

namespace PCIT\Framework\Foundation\Http;

use Route;
use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;
use Throwable;

class Kernel
{
    private function sendRequestThroughRouter($request)
    {
        $debug = config('app.debug');

        if (!\defined('PCIT_START')) {
            \define('PCIT_START', 0);
        }

        // ci.khs1994.com/index.php redirect to /dashboard

        // if ('/index.php' === $_SERVER['REQUEST_URI']) {
        if ('/index.php' === $request->server->get('REQUEST_URI')) {
            \Response::redirect('dashboard');

            exit;
        }

        // 引入路由文件
        try {
            require base_path().'framework/routes/web.php';
        } catch (Throwable $e) {
            if ($e instanceof SuccessException) {
                $output = Route::getOutput();

                if ($output instanceof HttpFoundationResponse) {
                    return $output;
                }

                switch (\gettype($output)) {
                    case 'array':
                        return \Response::json(
                            array_merge(
                                $output, ['code' => $e->getCode()]
                            ));

                        break;
                    case 'integer':
                       if ('testing' === config('app.env')) {
                           return $output;
                       }

                        return \Response::make((string) $output);

                        break;
                    case 'string':
                        if ('testing' === config('app.env')) {
                            return $output;
                        }

                        return \Response::make($output);

                        break;
                }

                return \Response::make();
            }

            // 出现错误
            method_exists($e, 'report') && $e->report($e);
            method_exists($e, 'render') && $e->render($request, $e);

            $previousErr = $e->getPrevious();
            // var_dump($previousErr);

            $errDetails['trace'] = $previousErr ? $previousErr->getTrace() : $e->getTrace();

            return \Response::json(array_filter([
                'code' => $e->getCode() ?: 500,
                'message' => $e->getMessage() ?: 'ERROR',
                'documentation_url' => 'https://github.com/pcit-ce/pcit/tree/master/docs/api',
                'file' => $debug ? ($previousErr ? $previousErr->getFile() : $e->getFile()) : null,
                'line' => $debug ? ($previousErr ? $previousErr->getLine() : $e->getLine()) : null,
                'details' => $debug ? $errDetails : null,
            ]));
        }

        // 路由控制器填写错误
        return \Response::json(array_filter([
            'code' => 404,
            'message' => 'Not Found',
            'api_url' => config('app.host').'/api',
            'obj' => $debug ? Route::getObj() ?? null : null,
            'method' => $debug ? Route::getMethod() ?? null : null,
            'details' => $debug ? '路由控制器填写错误' : null,
        ]));
    }

    public function handle($request)
    {
        try {
            ini_set('session.cookie_path', '/');
            ini_set('session.cookie_domain', '.'.config('session.domain'));
            ini_set('session.gc_maxlifetime', '690000'); // s
            ini_set('session.cookie_lifetime', '690000'); // s
            ini_set('session.cookie_secure', 'On');
        } catch (Throwable $e) {
        }

        app()->instance('request', $request);
        app()->instance(\PCIT\Framework\Http\Request::class, $request);

        // session_set_cookie_params());

        $response = $this->sendRequestThroughRouter($request);

        return $response;
    }
}
