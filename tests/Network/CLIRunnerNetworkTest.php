<?php
/**
 *
 * @filesource   CLIRunnerNetworkTest.php
 * @created      04.04.2016
 * @package      chillerlan\ThreemaTest\Network
 * @author       Smiley <smiley@chillerlan.net>
 * @copyright    2016 Smiley
 * @license      MIT
 */

namespace chillerlan\ThreemaTest\Network;

/**
 * Class CLIRunnerNetworkTest
 */
class CLIRunnerNetworkTest extends NetworkTestAbstract{

	public function testCheckCredits(){
		$this->assertEquals($this->testdata->credits, trim($this->CLIrunner->run(['file.php', 'credits'])));
		$this->assertEquals($this->testdata->credits, $this->CLIrunner->checkCredits());
	}

	public function testCheckCapabilities(){
		$this->assertEquals('image,text,video', trim($this->CLIrunner->run(['file.php', 'check', 'ECHOECHO'])));
		$this->assertEquals('image,text,video', $this->CLIrunner->checkCapabilities('ECHOECHO'));
	}

}
