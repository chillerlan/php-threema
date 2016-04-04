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
	CLIRunner, Crypto\CryptoSodium, Gateway, GatewayOptions
};

abstract class GatewayTestAbstract extends \PHPUnit_Framework_TestCase{

	/**
	 * @var \chillerlan\Threema\Gateway
	 */
	protected $threemaGateway;

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
		$this->gatewayOptions->gatewayInterface = TestGatewayEndpoint::class;
		$this->gatewayOptions->configFilename = '.threema'; // @todo TRAVIS REMINDER!
		$this->gatewayOptions->configPath     = __DIR__.'/../config';
		$this->gatewayOptions->storagePath    = __DIR__.'/../storage';
		$this->gatewayOptions->cacert         = __DIR__.'/../storage/cacert.pem';

		$this->cryptoInterface = new CryptoSodium;
		$this->threemaGateway  = new Gateway($this->cryptoInterface, $this->gatewayOptions);
		$this->CLIrunner       = new CLIRunner($this->cryptoInterface, $this->gatewayOptions);
	}

}
