<?php
/**
 *
 * @filesource   GatewayTest.php
 * @created      03.04.2016
 * @package      chillerlan\ThreemaTest
 * @author       Smiley <smiley@chillerlan.net>
 * @copyright    2016 Smiley
 * @license      MIT
 */

namespace chillerlan\ThreemaTest;

use chillerlan\Threema\{
	Gateway, GatewayOptions
};
use stdClass;

/**
 * Class GatewayTest
 */
class GatewayTest extends GatewayTestAbstract{

	const MESSAGE = 'This is a random test message! ÄÖÜ 茗荷';

	public function testInstance(){
		$this->assertInstanceOf(Gateway::class, $this->gateway);
	}

	/**
	 * @expectedException \chillerlan\Threema\GatewayException
	 * @expectedExceptionMessage invalid threema id
	 */
	public function testCheckInvalidThreemaIdException(){
		$this->gateway->checkCapabilities('');
		$this->gateway->checkCapabilities('#foo4711');
		$this->gateway->checkCapabilities('ECHOECHOECHOECHO');
	}

	/**
	 * @expectedException \chillerlan\Threema\GatewayException
	 * @expectedExceptionMessage invalid phone number
	 */
	public function testCheckInvalidPhoneException(){
		$this->gateway->getIdByPhone('');
		$this->gateway->getIdByPhone('#foobar');
	}

	/**
	 * @expectedException \chillerlan\Threema\GatewayException
	 * @expectedExceptionMessage invalid email
	 */
	public function testCheckInvalidEmailException(){
		$this->gateway->getIdByEmail('');
		$this->gateway->getIdByEmail('foobar');
		$this->gateway->getIdByEmail('foo@bar');
	}

	/**
	 * @expectedException \chillerlan\Threema\GatewayException
	 * @expectedExceptionMessage invalid hash
	 */
	public function testCheckInvalidHashException(){
		$this->gateway->getIdByEmailHash('');
		$this->gateway->getIdByEmailHash('zzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzz');
		$this->gateway->getIdByEmailHash('36311e9edca9f61d4c4275ef49b79621e489c442f2d1fc733523463c36c1440');
	}

	/**
	 * @expectedException \chillerlan\Threema\GatewayException
	 * @expectedExceptionMessage method "foobar" does not exist
	 */
	public function testInvalidGatewayMethodException(){
		/** @noinspection PhpUndefinedMethodInspection */
		$this->gateway->foobar();
	}

	#######################
	# convenience methods #
	#######################

	public function testCryptoVersion(){
		$this->assertContains('libsodium 1.', $this->gateway->cryptoVersion());
	}

	public function testEncryptDecryptRandom(){
		$sender    = $this->gateway->getKeypair();
		$recipient = $this->gateway->getKeypair();
		$encrypted = $this->gateway->encrypt(self::MESSAGE, $sender->privateKey, $recipient->publicKey);
		$decrypted = $this->gateway->decrypt($encrypted->box, $encrypted->nonce, $recipient->privateKey, $sender->publicKey);

		$this->assertEquals(self::MESSAGE, $decrypted);
	}

}
