<?php
namespace Migl\Rest\Controller;

use Cake\Controller\Controller;
use Cake\Event\Event;
use Firebase\JWT\JWT;
use Cake\Utility\Security;
use Cake\Network\Exception;

class ApiController extends Controller{

    public function beforeFilter(Event $event)
    {
        parent::beforeFilter( $event );

        $response = $this->getResponse();
        $response = $response->withHeader('Access-Control-Allow-Origin','*');
        $response = $response->withHeader('Access-Control-Allow-Methods','*');
        $response = $response->withHeader('Access-Control-Allow-Headers','Origin, X-Requested-With, Content-Type, Accept, Authorization');
        $this->setResponse( $response );
    }

    public function beforeRender(Event $event)
    {
        parent::beforeRender($event);

        if (
            !array_key_exists('_serialize', $this->viewVars) &&
            in_array($this->response->getType(), ['application/json', 'application/xml'])
        )
        {
            $this->set('_serialize', true);
        }

        // renders json if a client expects json.
        if ($this->request->accepts('application/json'))
        {
            $this->RequestHandler->renderAs($this, 'json');
        }

    }

    public function initialize(  ){
        parent::initialize();

        $this->loadComponent('RequestHandler');
        $this->loadComponent('Auth',[
            'storage'=>'Memory',
            'authenticate'=>[
                'Migl/Rest.Jwt' => [
                    'finder'    => 'auth',
                    'parameter' => 'token',
                    'userModel' => 'Users',
                    'fields'    => [ 'username'=>'username', 'password'=>'password', 'id' => 'id' ],
                ]
            ]
        ]);
    }

    public function newToken($data){ return JWT::encode( $data, Security::getSalt() ); }

}
