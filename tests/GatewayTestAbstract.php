<?php
/**
 *
 * @filesource   GatewayTestAbstract.php
 * @created      02.04.2016
 * @package      chillerlan\ThreemaTest
 * @author       Smiley <smiley@chillerlan.net>
 * @copyright    2016 Smiley
 * @license      MIT
 */

namespace chillerlan\ThreemaTest;

use chillerlan\Threema\{
	CLIRunner, Crypto\CryptoSodium, Endpoint\TinyCurlEndpoint, Gateway, GatewayOptions
};
use chillerlan\TinyCurl\RequestOptions;

abstract class GatewayTestAbstract extends \PHPUnit_Framework_TestCase{

	/**
	 * @var \chillerlan\Threema\Gateway
	 */
	protected $gateway;

	/**
	 * @var \chillerlan\Threema\Crypto\CryptoInterface
	 */
	protected $cryptoInterface;

	/**
	 * @var \chillerlan\Threema\GatewayOptions
	 */
	protected $gatewayOptions;

	/**
	 * @var \chillerlan\Threema\CLIRunner
	 */
	protected $CLIrunner;

	protected function setUp(){
		$this->gatewayOptions = new GatewayOptions;
		$this->gatewayOptions->configFilename = '.threema'; // @todo TRAVIS REMINDER!
		$this->gatewayOptions->configPath     = __DIR__.'/../config';
		$this->gatewayOptions->storagePath    = __DIR__.'/../storage';

		$requestOptions          = new RequestOptions;
		$requestOptions->ca_info = __DIR__.'/../storage/cacert.pem'; // https://curl.haxx.se/ca/cacert.pem

		$http = new TestRequest($requestOptions);
#		$http = new \chillerlan\TinyCurl\Request($requestOptions);

		$endpoint              = new TinyCurlEndpoint($this->gatewayOptions, $http);
		$this->cryptoInterface = new CryptoSodium;
		$this->gateway         = new Gateway($endpoint, $this->cryptoInterface);
		$this->CLIrunner       = new CLIRunner($endpoint, $this->cryptoInterface);
	}

}
