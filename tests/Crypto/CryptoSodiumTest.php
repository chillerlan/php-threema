<?php
/**
 * Class CryptoSodiumTest
 *
 * @filesource   CryptoSodiumTest.php
 * @created      03.04.2016
 * @package      chillerlan\ThreemaTest\Crypto
 * @author       Smiley <smiley@chillerlan.net>
 * @copyright    2016 Smiley
 * @license      MIT
 */

namespace chillerlan\ThreemaTest\Crypto;

use chillerlan\Threema\Crypto\CryptoSodium;

class CryptoSodiumTest extends CryptoTestAbstract{

	protected $cryptoInterfaceClass = CryptoSodium::class;

	public function testVersion(){
		$this->assertContains('libsodium 1.', $this->cryptoInterface->version());
	}

}
