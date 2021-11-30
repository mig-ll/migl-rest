<?php
declare(strict_types=1);

namespace Rest\Controller;

// use App\Controller\AppController as BaseController;
use Cake\Controller\Controller;
use Cake\Event\Event;
use Firebase\JWT\JWT;
use Cake\Utility\Security;

use Rest\Error\Error;

// class AppController extends BaseController
// {
// }

class ApiController extends Controller{

    public function beforeFilter(Event $event) {
        parent::beforeFilter( $event );

        $this->response->header('Access-Control-Allow-Origin','*');
        $this->response->header('Access-Control-Allow-Methods','*');
        $this->response->header('Access-Control-Allow-Headers:','Origin, X-Requested-With, Content-Type, Accept, Authorization');

    }

    public function beforeRender(Event $event)
    {
        parent::beforeRender($event);

        if (!array_key_exists('_serialize', $this->viewVars) &&
            in_array($this->response->type(), ['application/json', 'application/xml'])
        ) {
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

        $errorHandler = new Error();
        $errorHandler->register();

        $this->loadComponent('RequestHandler');

        $this->loadComponent('Auth',[
            'storage'=>'Memory',
            'authenticate'=>[
                'Rest.Jwt' => [
                    'parameter' => 'token',
                    'userModel' => 'Rest.Users',
                    'contain'=> [ 'Roles', 'UsersProfiles' ],
                    'scope' => ['Users.status' => 'ACTIVE', 'Roles.status'=>'ACTIVE', 'Roles.can_login' => 'YES'],
                    'fields' => [
                        'username'=>'username',
                        'password'=>'password',
                        'id' => 'id'
                    ],
                ]
            ]
        ]);

    }

    public function newToken($data){
        return JWT::encode( $data, Security::salt() );
    }

}
