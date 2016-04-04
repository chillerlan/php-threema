<?php
/**
 *
 * @filesource   CryptoTestAbstract.php
 * @created      03.04.2016
 * @package      chillerlan\ThreemaTest
 * @author       Smiley <smiley@chillerlan.net>
 * @copyright    2016 Smiley
 * @license      MIT
 */

namespace chillerlan\ThreemaTest\Crypto;

/**
 * Class CryptoTestAbstract
 */
abstract class CryptoTestAbstract extends \PHPUnit_Framework_TestCase{

	const SENDER_PRIVATE    = 'c9e46cbbdf2394b0f402b5293c3b7a19948dca215a00600ed448e826b00c9f29';
	const SENDER_PUBLIC     = 'ec17dd2053eff97fefbaf3ecf905958e49e06341b44ac9252ae95e9a476eb773';
	const RECIPIENT_PRIVATE = 'aeca6f04c5a36fd02797fb52376d31aec0e9ad02b6aef00e205e0ddc50d4f652';
	const RECIPIENT_PUBLIC  = '4a491d10fa4e8f3c543ae0abd34f90f57b71f0508e6c8558948fae8f47ef430c';
	const MESSAGE           = 'This is a random test message! ÄÖÜ 茗荷';
	const BOX               = '8f3b3966c5bcf5be8bcb98e1ee0281b60fc169e024178b84bad86ddf71819384d40aa70be22432892628ca378bb149436a210aca3e37c9ae36534b28';
	const NONCE             = '9bf0a6b0a1429644f52bb325c7b9372f55498d8297f3b9bf';

	/**
	 * @var \chillerlan\Threema\Crypto\CryptoInterface
	 */
	protected $cryptoInterface;
	
	abstract public function testVersion();

	public function testGetKeypair(){
		$keypair    = $this->cryptoInterface->getKeypair();
		$patternHex = '/^[a-f\d]{64}$/i';

		$this->assertRegExp($patternHex, $keypair->privateKey);
		$this->assertRegExp($patternHex, $keypair->publicKey);
	}

	public function testEncrypt(){
		$encrypted = $this->cryptoInterface->encrypt(self::MESSAGE, self::SENDER_PRIVATE, self::RECIPIENT_PUBLIC);

		$this->assertRegExp('/^[a-f\d]{48}$/i', $encrypted->nonce);
		$this->assertRegExp('/^[a-f\d]+$/i', $encrypted->box);
	}

	public function testDecrypt(){
		$decrypted = $this->cryptoInterface->decrypt(self::BOX, self::NONCE, self::RECIPIENT_PRIVATE, self::SENDER_PUBLIC);

		$this->assertEquals(self::MESSAGE, $decrypted);
	}

	/**
	 * @expectedException \chillerlan\Threema\Crypto\CryptoException
	 * @expectedExceptionMessage invalid data
	 */
	public function testEncryptInvalidDataException(){
		$this->cryptoInterface->encrypt('', self::SENDER_PRIVATE, self::RECIPIENT_PUBLIC);
		$this->cryptoInterface->encrypt('', '', '');
	}

	/**
	 * @expectedException \chillerlan\Threema\Crypto\CryptoException
	 * @expectedExceptionMessage invalid keypair
	 */
	public function testEncryptInvalidKeypairException(){
		$this->cryptoInterface->encrypt(self::MESSAGE, '', '');
		$this->cryptoInterface->encrypt(self::MESSAGE, '', self::RECIPIENT_PUBLIC);
		$this->cryptoInterface->encrypt(self::MESSAGE, self::SENDER_PRIVATE, '');
	}

}
