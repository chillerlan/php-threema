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
		$this->assertInstanceOf(Gateway::class, $this->threemaGateway);
	}

	/**
	 * @expectedException \chillerlan\Threema\GatewayException
	 * @expectedExceptionMessage invalid threema id
	 */
	public function testCheckInvalidThreemaIdException(){
		$this->threemaGateway->checkCapabilities('');
		$this->threemaGateway->checkCapabilities('#foo4711');
		$this->threemaGateway->checkCapabilities('ECHOECHOECHOECHO');
	}

	/**
	 * @expectedException \chillerlan\Threema\GatewayException
	 * @expectedExceptionMessage invalid phone number
	 */
	public function testCheckInvalidPhoneException(){
		$this->threemaGateway->getIdByPhone('');
		$this->threemaGateway->getIdByPhone('#foobar');
	}

	/**
	 * @expectedException \chillerlan\Threema\GatewayException
	 * @expectedExceptionMessage invalid email
	 */
	public function testCheckInvalidEmailException(){
		$this->threemaGateway->getIdByEmail('');
		$this->threemaGateway->getIdByEmail('foobar');
		$this->threemaGateway->getIdByEmail('foo@bar');
	}

	/**
	 * @expectedException \chillerlan\Threema\GatewayException
	 * @expectedExceptionMessage invalid hash
	 */
	public function testCheckInvalidHashException(){
		$this->threemaGateway->getIdByEmailHash('');
		$this->threemaGateway->getIdByEmailHash('zzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzz');
		$this->threemaGateway->getIdByEmailHash('36311e9edca9f61d4c4275ef49b79621e489c442f2d1fc733523463c36c1440');
	}

	/**
	 * @expectedException \chillerlan\Threema\GatewayException
	 * @expectedExceptionMessage "stdClass" does not implement GatewayInterface
	 */
	public function testInvalidGatewayInterfaceException(){
		$gatewayOptions = new GatewayOptions;
		$gatewayOptions->gatewayInterface = stdClass::class;
		new Gateway($this->cryptoInterface, $gatewayOptions);
	}

	/**
	 * @expectedException \chillerlan\Threema\GatewayException
	 * @expectedExceptionMessage method "foobar" does not exist
	 */
	public function testInvalidGatewayMethodException(){
		/** @noinspection PhpUndefinedMethodInspection */
		$this->threemaGateway->foobar();
	}

	#######################
	# convenience methods #
	#######################

	public function testCryptoVersion(){
		$this->assertContains('libsodium 1.', $this->threemaGateway->cryptoVersion());
	}

	public function testEncryptDecryptRandom(){
		$sender    = $this->threemaGateway->getKeypair();
		$recipient = $this->threemaGateway->getKeypair();
		$encrypted = $this->threemaGateway->encrypt(self::MESSAGE, $sender->privateKey, $recipient->publicKey);
		$decrypted = $this->threemaGateway->decrypt($encrypted->box, $encrypted->nonce, $recipient->privateKey, $sender->publicKey);

		$this->assertEquals(self::MESSAGE, $decrypted);
	}

}
