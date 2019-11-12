<?php
/**
 * Class GatewayTest
 *
 * @filesource   GatewayTest.php
 * @created      10.06.2017
 * @package      chillerlan\ThreemaTest
 * @author       Smiley <smiley@chillerlan.net>
 * @copyright    2017 Smiley
 * @license      MIT
 */

namespace chillerlan\ThreemaTest;

use chillerlan\DotEnv\DotEnv;
use chillerlan\HTTP\Psr18\CurlClient;
use chillerlan\HTTP\Psr18\LoggingClient;
use chillerlan\Threema\Gateway;
use chillerlan\Threema\GatewayOptions;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Log\AbstractLogger;

class GatewayTest extends TestCase{

	/**
	 * @var \chillerlan\Threema\Gateway
	 */
	protected $gateway;

	/**
	 * @var \chillerlan\DotEnv\DotEnv
	 */
	protected $env;

	/**
	 * @var \Psr\Log\LoggerInterface
	 */
	protected $logger;

	/**
	 * @var \chillerlan\Threema\GatewayOptions
	 */
	protected $options;

	protected function setUp():void{
		$this->env = (new DotEnv(__DIR__.'/../config', '.env', false))->load();

		$requestOptions = new GatewayOptions;
		$requestOptions->ca_info = __DIR__.'/../storage/cacert.pem'; // https://curl.haxx.se/ca/cacert.pem
		$options = [
			'gatewayID'     => $this->env->get('THREEMA_GATEWAY_ID'),
			'gatewaySecret' => $this->env->get('THREEMA_GATEWAY_SECRET'),
			// HTTPOptionsTrait
			'ca_info'       => __DIR__.'/../storage/cacert.pem',
			'userAgent'     => 'chillerlanPhpThreema/1.0-dev +https://github.com/chillerlan/php-threema',
		];

		$this->options = new GatewayOptions($options);
		$this->gateway = new Gateway($this->options, $this->initHttp());
	}

	protected function initHttp():ClientInterface{

		$logger = new class() extends AbstractLogger{
			public function log($level, $message, array $context = []){
				echo sprintf('[%s][%s] %s', date('Y-m-d H:i:s'), $level, trim($message))."\n";
			}
		};

		$http = new CurlClient($this->options);

		return new LoggingClient($http, $logger);
	}

	public function testCheckCredits(){
		$this->assertTrue($this->gateway->checkCredits() > 0);
	}

	public function testCheckCapabilities(){
		$this->assertEquals(['audio', 'file', 'image', 'text', 'video'], $this->gateway->checkCapabilities($this->env->THREEMA_TEST_SEND_ID));
	}

	public function testGetPublicKey(){
		$this->assertSame($this->env->THREEMA_TEST_PUBLIC_KEY, $this->gateway->getPublicKey($this->env->THREEMA_TEST_SEND_ID));
	}

	public function testGetIdByPhone(){
		$this->assertSame($this->env->THREEMA_TEST_ID, $this->gateway->getIdByPhone($this->env->THREEMA_TEST_PHONE));
	}

	public function testGetIdByPhoneHash(){
		$this->assertSame($this->env->THREEMA_TEST_ID, $this->gateway->getIdByPhoneHash($this->gateway->hashPhoneNo($this->env->THREEMA_TEST_PHONE)));
	}

	public function testGetIdByEmail(){
		$this->assertSame($this->env->THREEMA_TEST_ID, $this->gateway->getIdByEmail($this->env->THREEMA_TEST_EMAIL));
	}

	public function testGetIdByEmailHash(){
		$this->assertSame($this->env->THREEMA_TEST_ID, $this->gateway->getIdByEmailHash($this->gateway->hashEmail($this->env->THREEMA_TEST_EMAIL)));
	}

	public function testSendE2EText(){
		$r = $this->gateway->sendE2EText(
			$this->env->THREEMA_TEST_SEND_ID,
			$this->env->THREEMA_GATEWAY_PRIVATE_KEY,
			'this is a random test message!'
		);

		$this->assertRegExp('/^[a-f\d]{16}$/', $r);
	}

	public function testSendE2EFile(){
		$r = $this->gateway->sendE2EFile(
			$this->env->THREEMA_TEST_SEND_ID,
			$this->env->THREEMA_GATEWAY_PRIVATE_KEY,
			file_get_contents(__DIR__.'/../storage/threema.jpg'),
			'threema',
			file_get_contents(__DIR__.'/../storage/threema.jpg')
		);

		$this->assertRegExp('/^[a-f\d]{16}$/', $r);
	}

	public function testSendE2EImage(){
		$r = $this->gateway->sendE2EImage(
			$this->env->THREEMA_TEST_SEND_ID,
			$this->env->THREEMA_GATEWAY_PRIVATE_KEY,
			file_get_contents(__DIR__.'/../storage/threema.jpg')
		);

		$this->assertRegExp('/^[a-f\d]{16}$/', $r);
	}
}
