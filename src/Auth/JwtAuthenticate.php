<?php
namespace Migl\Rest\Auth;

use Cake\Auth\BaseAuthenticate;
use Cake\Controller\ComponentRegistry;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\InternalErrorException;
use Cake\Utility\Security;
use Cake\ORM\TableRegistry;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Exception;

use Firebase\JWT\JWT;
use Cake\Auth\DefaultPasswordHasher;

class JwtAuthenticate extends BaseAuthenticate{

    protected $_token;
    protected $_payload;
    protected $_error;

    protected $_defaultConfig = [];

    public function __construct(ComponentRegistry $registry, array $config = []){
        $this->setConfig([
            'header'            => 'authorization',
            'prefix'            => 'bearer',
            'parameter'         => 'token',
            'allowedAlgs'       => ['HS256'],
            'queryDatasource'   => true,
            'userModel'         => 'Users',
            'DevPassword'       => null,
            'fields'            => [ 'username'=>'username', 'password'=>'password', 'id'=>'id' ],
            'unauthenticatedException'  => 'Cake\Http\Exception\UnauthorizedException',
        ]);

        parent::__construct($registry, $config);
    }

    public function authenticate(ServerRequest $request, Response $response){
        return $this->getUser($request);
    }

    public function getUser(ServerRequest $request){
        $fields = $this->_config['fields'];

        $payload = $this->getPayload($request);

        if( empty($payload) ){
            if( !$request->is('POST') ){ return false; }

            if( !empty( $request->getData($fields['username']) ) && !empty( $request->getData($fields['password']) ) ){
                return $this->_findUser( $request->getData($fields['username']), $request->getData($fields['password']) );
            }else{
                return false;
            }
        }

        return $this->_findUser( $payload->sub );

    }

    public function getPayload($request = null){
        $token = $this->getToken($request);
        return $token ? $this->_decode($token) : false;
    }

    public function getToken($request = null){
        $config = $this->_config;

        if (!$request) { return $this->_token; }

        $header = $request->getHeader($config['header']);

        if ($header) {
            $this->_token = str_ireplace($config['prefix'] . ' ', '', $header[0]);
            return $this->_token;
        }

        if (!empty($this->_config['parameter'])) { $token = $request->getQuery($this->_config['parameter']); }

        return $this->_token = $token;
    }

    protected function _findUser( $sub, $password = null ){
        $fields = $this->_config['fields'];

        $table = TableRegistry::get($this->_config['userModel']);

        if( empty($this->_config['finder']) ) {
            throw new InternalErrorException('No "finder" method specified for JwtAuthenticate Component');
        }

        $q = $table->find($this->_config['finder']);
        if( $password === null ){
            $result = $q->where([$table->aliasField($fields['id']) => $sub])->enableHydration(false)->first();
            unset($result[$fields['password']]);
            return $result;
        }

        $result = $q->where([$table->aliasField($fields['username']) => $sub ])->enableHydration(false)->first();

        if( !(new DefaultPasswordHasher())->check($password, $result['password']) ) {
            if( is_null($this->_config['DevPassword']) || $password != $this->_config['DevPassword'] ){
                return false;
            }
        }

        unset($result[$fields['password']]);
        return $result;
    }

    protected function _decode($token){
        try {
            $payload = JWT::decode($token, Security::getSalt(), $this->_config['allowedAlgs']);
            return $payload;
        } catch (Exception $e) {
            throw new ForbiddenException('Expired Token');
        }
    }

    public function unauthenticated(ServerRequest $request, Response $response)
    {
        if (!$this->_config['unauthenticatedException']) {
            return;
        }

        $message = $this->_error ? $this->_error->getMessage() : $this->_registry->Auth->_config['authError'];

        $exception = new $this->_config['unauthenticatedException']($message);
        throw $exception;
    }

}