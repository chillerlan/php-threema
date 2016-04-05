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

use chillerlan\Threema\{
	Crypto\CryptoInterface, Endpoint\EndpointInterface
};
use ReflectionClass;
use ReflectionMethod;
use stdClass;

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
	 * @var \chillerlan\Threema\Crypto\CryptoInterface
	 */
	private $cryptoInterface;

	/**
	 * @var \chillerlan\Threema\Endpoint\EndpointInterface
	 */
	private $endpointInterface;

	/**
	 * @var array[\ReflectionMethod]
	 */
	private $endpointInterfaceMap = [];

	/**
	 * Gateway constructor.
	 *
	 * @param \chillerlan\Threema\Endpoint\EndpointInterface $endpointInterface
	 * @param \chillerlan\Threema\Crypto\CryptoInterface     $cryptoInterface
	 */
	public function __construct(EndpointInterface $endpointInterface, CryptoInterface $cryptoInterface){
		$this->endpointInterface = $endpointInterface;
		$this->cryptoInterface   = $cryptoInterface;

		$reflectionClass = new ReflectionClass(EndpointInterface::class);

		foreach($reflectionClass->getMethods() as $method){
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

	#######################
	# convenience methods #
	#######################

	/**
	 * @return string
	 */
	public function cryptoVersion():string{
		return $this->cryptoInterface->version();
	}

	/**
	 * @return \stdClass
	 */
	public function getKeypair():stdClass{
		return $this->cryptoInterface->getKeypair();
	}

	/**
	 * @param string $data
	 * @param string $privateKey hex string
	 * @param string $publicKey hex string
	 *
	 * @return \stdClass
	 */
	public function encrypt(string $data, string $privateKey, string $publicKey):stdClass{
		return $this->cryptoInterface->encrypt($data, $privateKey, $publicKey);
	}

	/**
	 * @param string $box hex string
	 * @param string $nonce hex string
	 * @param string $privateKey hex string
	 * @param string $publicKey hex string
	 *
	 * @return string
	 */
	public function decrypt(string $box, string $nonce, string $privateKey, string $publicKey):string{
		return $this->cryptoInterface->decrypt($box, $nonce, $privateKey, $publicKey);
	}

}
