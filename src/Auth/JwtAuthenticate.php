<?php
namespace Rest\Auth;

use Cake\Auth\BaseAuthenticate;
use Cake\Controller\ComponentRegistry;
use Cake\Core\Configure;
use Cake\Network\Request;
use Cake\Network\Response;
use Cake\Utility\Security;
use Cake\ORM\TableRegistry;
use Exception;
use Firebase\JWT\JWT;
use Cake\Auth\DefaultPasswordHasher;

class JwtAuthenticate extends BaseAuthenticate{

    protected $_token;
    protected $_payload;
    protected $_error;

    public function __construct(ComponentRegistry $registry, $config){
        $this->config([
            'header' => 'authorization',
            'prefix' => 'bearer',
            'parameter' => 'token',
            'allowedAlgs' => ['HS256'],
            'queryDatasource' => true,
            'fields' => ['username' => 'id'],
            'unauthenticatedException' => '\Cake\Network\Exception\UnauthorizedException',
        ]);

        parent::__construct($registry, $config);
    }

    public function authenticate(Request $request, Response $response){
        return $this->getUser($request);
    }

    public function getUser(Request $request){
        $fields = $this->_config['fields'];

        $payload = $this->getPayload($request);

        if( !$payload ){
            if( !$request->is('POST') ){
                return false;
            }

            if(
                !empty( $request->data[$fields['username']] )
                && !empty( $request->data[$fields['password']] )
            ){
                return $this->_findUser(
                    $request->data[$fields['username']],
                    $request->data[$fields['password']]
                );
            }else{
                return false;
            }
        }else if( $payload->exp < time() ) {
            return false;
        }

        return $this->_findUser( $payload->sub );

    }

    public function getPayload($request = null){
        $token = $this->getToken($request);
        return $token ? $this->_decode($token) : false;
    }

    public function getToken($request = null){
        $config = $this->_config;

        if (!$request) {
            return $this->_token;
        }

        $header = $request->header($config['header']);
        if ($header) {
            return $this->_token = str_ireplace($config['prefix'] . ' ', '', $header);
        }

        if (!empty($this->_config['parameter'])) {
            $token = $request->query($this->_config['parameter']);
        }

        return $this->_token = $token;
    }

    protected function _findUser( $sub, $password = null ){
        $fields = $this->_config['fields'];

        $table = TableRegistry::get($this->_config['userModel']);

        $conditions = $password === null
            ? [$table->aliasField($fields['id']) => $sub]
            : [$table->aliasField($fields['username']) => $sub];

        $select = empty($this->_config['select']) ? [] : $this->_config['select'];

        if (!empty($this->_config['scope'])) {
            $conditions = array_merge($conditions, $this->_config['scope']);
        }

        $result = $table->find()
            ->select($select)
            ->where($conditions)
            ->contain( $this->_config['contain'] ? $this->_config['contain'] : [] )
            ->hydrate( false )
            ->first(  );

        if (empty($result)) {
            return false;
        }else{
            if( $password !== null && !(new DefaultPasswordHasher())->check($password, $result['password']) ){
                return false;
            }
        }

        unset($result[$fields['password']]);
        return $result;
    }

    protected function _decode($token){
        try {
            $payload = JWT::decode($token, Security::salt(), $this->_config['allowedAlgs']);

            return $payload;
        } catch (Exception $e) {
            if (Configure::read('debug')) {
                throw $e;
            }
            return false;
        }
    }

    public function unauthenticated(Request $request, Response $response)
    {
        if (!$this->_config['unauthenticatedException']) {
            return;
        }

        $message = $this->_error ? $this->_error->getMessage() : $this->_registry->Auth->_config['authError'];

        $exception = new $this->_config['unauthenticatedException']($message);
        throw $exception;
    }
}