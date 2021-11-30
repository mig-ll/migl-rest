<?php
// Custom Handler - goes in src/Error/AppError.php
namespace Rest\Error;

use Cake\Network\Response;
use Cake\Routing\Exception\MissingControllerException;
use Cake\Error\ErrorHandler;

class Error extends ErrorHandler{

    public function _displayException($exception){
        $response = new Response();

        $response->header('Access-Control-Allow-Origin','*');
        $response->header('Access-Control-Allow-Methods','*');
        $response->header('Access-Control-Allow-Headers:','Origin, X-Requested-With, Content-Type, Accept, Authorization');
        $response->type('json');
        $ErrorCode = $exception->getCode(  );

        $response->statusCode( $ErrorCode > 500 || $ErrorCode < 400 ? 500 : $ErrorCode );
        $response->body( json_encode(
            [ 'code'=>$exception->getCode(  ), 'message'=>$exception->getMessage(  ) ],
            JSON_HEX_TAG | JSON_HEX_QUOT | JSON_PRETTY_PRINT
        ) );

        parent::_sendResponse( $response );
    }

}
