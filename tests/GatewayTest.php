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

use chillerlan\Threema\Crypto\CryptoSodium;
use chillerlan\Threema\Gateway;
use chillerlan\Threema\HTTP\TinyCurlClient;
use chillerlan\TinyCurl\Request;
use chillerlan\TinyCurl\RequestOptions;

class GatewayTest extends ThreemaTestAbstract{

	/**
	 * @var \chillerlan\Threema\Gateway
	 */
	protected $gateway;

	/**
	 * @var \chillerlan\Threema\HTTP\HTTPClientInterface
	 */
	protected $http;

	/**
	 * @var \chillerlan\Threema\Crypto\CryptoInterface
	 */
	protected $crypto;

	protected function setUp(){
		$this->markTestSkipped();

		parent::setUp();

		$requestOptions = new RequestOptions;
		$requestOptions->ca_info = __DIR__.'/../storage/cacert.pem'; // https://curl.haxx.se/ca/cacert.pem

		$this->http = new TinyCurlClient(new Request($requestOptions));
		$this->crypto = new CryptoSodium;
		$this->gateway = new Gateway($this->http, $this->crypto, getenv('THREEMA_GATEWAY_ID'), getenv('THREEMA_GATEWAY_SECRET'));
	}


	public function testCheckCredits(){
		$this->assertTrue($this->gateway->checkCredits() > 0);
	}

	public function testCheckCapabilities(){
		$this->assertEquals(['audio', 'file', 'image', 'text', 'video'], $this->gateway->checkCapabilities(getenv('THREEMA_TEST_ID')));
	}

	public function testGetPublicKey(){
		$this->assertSame(getenv('THREEMA_TEST_PUBLIC_KEY'), $this->gateway->getPublicKey(getenv('THREEMA_TEST_ID')));
	}

	public function testGetIdByPhone(){
		$this->assertSame(getenv('THREEMA_TEST_ID'), $this->gateway->getIdByPhone(getenv('THREEMA_TEST_PHONE')));
	}

	public function testGetIdByPhoneHash(){
		$this->assertSame(getenv('THREEMA_TEST_ID'), $this->gateway->getIdByPhoneHash($this->gateway->hashPhoneNo(getenv('THREEMA_TEST_PHONE'))));
	}

	public function testGetIdByEmail(){
		$this->assertSame(getenv('THREEMA_TEST_ID'), $this->gateway->getIdByEmail(getenv('THREEMA_TEST_EMAIL')));
	}

	public function testGetIdByEmailHash(){
		$this->assertSame(getenv('THREEMA_TEST_ID'), $this->gateway->getIdByEmailHash($this->gateway->hashEmail(getenv('THREEMA_TEST_EMAIL'))));
	}

	public function testSendE2EText(){
		$this->assertRegExp('/^[a-f\d]{16}$/', $this->gateway->sendE2EText(getenv('THREEMA_TEST_ID'), getenv('THREEMA_GATEWAY_PRIVATE_KEY'), 'this is a random test message!'));
	}

	public function testSendE2EFile(){
		$this->assertRegExp('/^[a-f\d]{16}$/', $this->gateway->sendE2EFile(getenv('THREEMA_TEST_ID'), getenv('THREEMA_GATEWAY_PRIVATE_KEY'),__DIR__.'/../storage/threema.jpg', 'threema',__DIR__.'/../storage/threema.jpg'));
	}
}
