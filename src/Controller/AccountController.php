<?php
namespace App\Controller\API\V1;

use Cake\Core\Configure;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\UnauthorizedException;
use Cake\Mailer\Email;
use Cake\ORM\TableRegistry;

class AccountController extends ApiController{

    public function initialize(  ){
        parent::initialize();
        $this->Auth->allow([ 'authorize' ]);
    }

    public function test(){
        $this->set([ 'success'=>'true' ]);
    }

}
