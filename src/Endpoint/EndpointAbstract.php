<?php
/**
 * Class EndpointAbstract
 *
 * @filesource   EndpointAbstract.php
 * @created      06.04.2016
 * @package      chillerlan\Threema\Endpoint
 * @author       Smiley <smiley@chillerlan.net>
 * @copyright    2016 Smiley
 * @license      MIT
 */

namespace chillerlan\Threema\Endpoint;

use chillerlan\Threema\GatewayOptions;
use Dotenv\Dotenv;

/**
 *
 */
abstract class EndpointAbstract implements EndpointInterface{

	/**
	 * @var \chillerlan\Threema\GatewayOptions
	 */
	protected $gatewayOptions;

	/**
	 * TinyCurlEndpoint constructor.
	 *
	 * @param \chillerlan\Threema\GatewayOptions $gatewayOptions
	 */
	public function __construct(GatewayOptions $gatewayOptions){
		$this->gatewayOptions = $gatewayOptions;

		(new Dotenv($gatewayOptions->configPath, $gatewayOptions->configFilename))->load();
	}

	/**
	 * @param string $threemaID
	 *
	 * @return string
	 * @throws \chillerlan\Threema\Endpoint\EndpointException
	 */
	protected function checkThreemaID(string $threemaID):string{

		if(preg_match('/^[a-z\d\*]{8}$/i', $threemaID)){
			return strtoupper($threemaID);
		}

		throw new EndpointException('invalid threema id');
	}

	/**
	 * @param string $phoneNo
	 *
	 * @return string
	 * @throws \chillerlan\Threema\Endpoint\EndpointException
	 */
	protected function checkPhoneNo(string $phoneNo):string{
		$phoneNo = preg_replace('/[^\d]/', '', $phoneNo);

		if(empty($phoneNo)){
			throw new EndpointException('invalid phone number');
		}

		return (string)$phoneNo;
	}

	/**
	 * @param $email
	 *
	 * @return string
	 * @throws \chillerlan\Threema\Endpoint\EndpointException
	 */
	protected function checkEmail($email):string{
		$email = filter_var(trim($email), FILTER_VALIDATE_EMAIL);

		if(empty($email)){
			throw new EndpointException('invalid email');
		}

		return strtolower($email);
	}

	/**
	 * @param string $hash
	 *
	 * @return string
	 * @throws \chillerlan\Threema\Endpoint\EndpointException
	 */
	protected function checkHash(string $hash):string{

		if(preg_match('/^[a-f\d]{64}$/i', $hash)){
			return $hash;
		}

		throw new EndpointException('invalid hash');
	}

}
