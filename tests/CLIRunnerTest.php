<?php
/**
 *
 * @filesource   CLIRunnerTest.php
 * @created      03.04.2016
 * @package      chillerlan\ThreemaTest
 * @author       Smiley <smiley@chillerlan.net>
 * @copyright    2016 Smiley
 * @license      MIT
 */

namespace chillerlan\ThreemaTest;

use chillerlan\Threema\CLIRunner;

/**
 * Class CLIRunnerTest
 */
class CLIRunnerTest extends GatewayTestAbstract{

	const SENDER_PRIVATE    = 'c9e46cbbdf2394b0f402b5293c3b7a19948dca215a00600ed448e826b00c9f29';
	const SENDER_PUBLIC     = 'ec17dd2053eff97fefbaf3ecf905958e49e06341b44ac9252ae95e9a476eb773';
	const RECIPIENT_PRIVATE = 'aeca6f04c5a36fd02797fb52376d31aec0e9ad02b6aef00e205e0ddc50d4f652';
	const RECIPIENT_PUBLIC  = '4a491d10fa4e8f3c543ae0abd34f90f57b71f0508e6c8558948fae8f47ef430c';
	const MESSAGE           = 'This is a random test message! ÄÖÜ 茗荷';

	public function testInstance(){
		$this->assertInstanceOf(CLIRunner::class, $this->CLIrunner);
	}

	public function testCLICatchException(){
		$this->assertEquals('ERROR: invalid threema id', trim($this->CLIrunner->run(['file.php', 'check', '#foobar'])));
	}

	public function testHelp(){
		$expected = 'Threema Gateway CLI tool.'.PHP_EOL.'Crypto:';
		$this->assertContains($expected, $this->CLIrunner->run(['file.php']));
		$this->assertContains($expected, $this->CLIrunner->help());
	}

	public function testKeypair(){
		// @todo: lazytest
		$this->assertContains('private:', $this->CLIrunner->run(['file.php', 'keypair']));
		$this->assertContains('public:', $this->CLIrunner->run(['file.php', 'keypair']));
		$this->assertContains('private:', $this->CLIrunner->getKeypair());
		$this->assertContains('public:', $this->CLIrunner->getKeypair());

		$private = $this->gatewayOptions->storagePath.'/test-privatekey.txt';
		$public  = $this->gatewayOptions->storagePath.'/test-publickey.txt';

		$this->CLIrunner->run(['file.php', 'keypair', $private, $public]);
		$patternHex = '/^[a-f\d]{64}$/i';

		$this->assertRegExp($patternHex, file_get_contents($private));
		$this->assertRegExp($patternHex, file_get_contents($public));
	}

	public function testHashEmail(){
		$expected = '1ea093239cc5f0e1b6ec81b866265b921f26dc4033025410063309f4d1a8ee2c';
		$this->assertEquals($expected, trim($this->CLIrunner->run(['file.php', 'hash_email', 'test@threema.ch'])));
		$this->assertEquals($expected, $this->CLIrunner->hashEmail('test@threema.ch'));
	}

	public function testHashPhone(){
		$expected = 'ad398f4d7ebe63c6550a486cc6e07f9baa09bd9d8b3d8cb9d9be106d35a7fdbc';
		$this->assertEquals($expected, trim($this->CLIrunner->run(['file.php', 'hash_phone', '41791234567'])));
		$this->assertEquals($expected, $this->CLIrunner->hashPhone('41791234567'));
	}

	public function testEncrypt(){
		$plaintext = $this->gatewayOptions->storagePath.'/test-plaintext.txt';
		$encrypted = $this->gatewayOptions->storagePath.'/test-encrypted.txt';
		file_put_contents($plaintext, self::MESSAGE);
		$this->assertContains(' bytes written to: '.$encrypted.PHP_EOL, $this->CLIrunner->encryptFile(self::SENDER_PRIVATE, self::RECIPIENT_PUBLIC, $plaintext, $encrypted));
	}

	public function testDecrypt(){
		$encrypted = $this->gatewayOptions->storagePath.'/test-encrypted.txt';
		$decrypted = $this->gatewayOptions->storagePath.'/test-decrypted.txt';
		$this->assertEquals('44 bytes written to: '.$decrypted.PHP_EOL, $this->CLIrunner->decryptFile(self::RECIPIENT_PRIVATE, self::SENDER_PUBLIC, $encrypted, $decrypted));
		$this->assertEquals(self::MESSAGE, file_get_contents($decrypted));
	}

}
