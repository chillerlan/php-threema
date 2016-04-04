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

	public function testInstance(){
		$this->assertInstanceOf(CLIRunner::class, $this->CLIrunner);
	}

	public function testCLICatchException(){
		$this->assertEquals('ERROR: invalid threema id', trim($this->CLIrunner->run(['file.php', 'check','#foobar'])));
	}

	public function testHelp(){
		$expected = 'Threema Gateway CLI tool.'.PHP_EOL.'Crypto:';
		$this->assertContains($expected, $this->CLIrunner->run(['file.php', 'help']));
		$this->assertContains($expected, $this->CLIrunner->help());
	}

	public function testKeypair(){
		// @todo: lazytest
		$this->assertContains('private:', $this->CLIrunner->run(['file.php', 'keypair']));
		$this->assertContains('public:', $this->CLIrunner->run(['file.php', 'keypair']));
		$this->assertContains('private:', $this->CLIrunner->getKeypair());
		$this->assertContains('public:', $this->CLIrunner->getKeypair());
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

}
