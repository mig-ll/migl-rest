<?php
/**
 * Rest Error Middleware
 *
 */
namespace Migl\Rest\Error;

use Cake\Error\Middleware\ErrorHandlerMiddleware;

class RestErrorHandleMiddleware extends ErrorHandlerMiddleware
{

    public function __invoke($request, $response, $next){
        try { return $next($request, $response); }
        catch (Exception $e) { return $this->handleException($e, $request, $response); }
        catch (Throwable $e) { return $this->handleException($e, $request, $response); }
    }

    public function handleException($exception, $request, $response)
    {
        $response->cors($request)
            ->allowOrigin(['*'])
            ->allowMethods(['*'])
            ->allowHeaders(['Origin', 'X-Requested-With', 'Content-Type', 'Accept', 'Authorization'])
            ->maxAge(300)
            ->build();

        if ($exception->getCode() >= 400 && $exception->getCode() < 506) {
            $response->withStatus($exception->getCode(  ));
        }else{
            $response->withStatus(500);
        }

        $response
            ->withType('application/json')
            ->withStringBody(json_encode(
                [ 'code'=>$exception->getCode(  ), 'message'=>$exception->getMessage(  ) ],
                JSON_HEX_TAG | JSON_HEX_QUOT | JSON_PRETTY_PRINT
            ));

        return $response;
    }
}
