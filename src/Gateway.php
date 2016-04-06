<?php
/**
 * Class Gateway
 *
 * @filesource   Gateway.php
 * @created      02.04.2016
 * @package      chillerlan\Threema
 * @author       Smiley <smiley@chillerlan.net>
 * @copyright    2016 Smiley
 * @license      MIT
 */

namespace chillerlan\Threema;

use chillerlan\Threema\Endpoint\EndpointInterface;
use ReflectionClass;
use ReflectionMethod;

/**
 * EndpointInterface methods
 *
 * @method checkCredits():int
 * @method checkCapabilities(string $threemaID):string
 * @method getIdByPhone(string $phoneno):string
 * @method getIdByPhoneHash(string $phonenoHash):string
 * @method getIdByEmail(string $email):string
 * @method getIdByEmailHash(string $emailHash):string
 * @method getPublicKey(string $threemaID):string
 * @method sendSimple(string $to, string $message)
 * @method sendE2E(string $threemaID, string $box, string $nonce)
 * @method upload(string $blob)
 * @method download(string $blobID)
 */
class Gateway{

	/**
	 * @var \chillerlan\Threema\Endpoint\EndpointInterface
	 */
	protected $endpointInterface;

	/**
	 * @var array[\ReflectionMethod]
	 */
	protected $endpointInterfaceMap = [];

	/**
	 * Gateway constructor.
	 *
	 * @param \chillerlan\Threema\Endpoint\EndpointInterface $endpointInterface
	 */
	public function __construct(EndpointInterface $endpointInterface){
		$this->endpointInterface = $endpointInterface;
		$this->mapMethods();
	}

	/**
	 * @inheritdoc
	 */
	protected function mapMethods(){
		foreach((new ReflectionClass(EndpointInterface::class))->getMethods() as $method){
			$this->endpointInterfaceMap[$method->name] = $method;
		}
	}

	/**
	 * @param string $method
	 * @param array  $params
	 *
	 * @return mixed
	 * @throws \chillerlan\Threema\GatewayException
	 */
	public function __call(string $method, array $params){

		if(array_key_exists($method, $this->endpointInterfaceMap)){
			$reflectionMethod = new ReflectionMethod($this->endpointInterface, $this->endpointInterfaceMap[$method]->name);

			return $reflectionMethod->invokeArgs($this->endpointInterface, $params);
		}

		throw new GatewayException('method "'.$method.'" does not exist');
	}

}
