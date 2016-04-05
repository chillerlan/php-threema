<?php
/**
 *
 * @filesource   GatewayNetworkTest.php
 * @created      03.04.2016
 * @package      chillerlan\ThreemaTest
 * @author       Smiley <smiley@chillerlan.net>
 * @copyright    2016 Smiley
 * @license      MIT
 */

namespace chillerlan\ThreemaTest\Network;

/**
 * Class GatewayNetworkTest
 */
class GatewayNetworkTest extends NetworkTestAbstract{

	public function testCheckCredits(){
		$this->assertEquals($this->testdata->credits, $this->gateway->checkCredits());
	}

	public function testCheckCapabilities(){
		$this->assertEquals(['image','text','video'], $this->gateway->checkCapabilities('ECHOECHO'));
	}

	public function testGetIdByPhone(){
		$this->assertEquals($this->testdata->id, $this->gateway->getIdByPhone($this->testdata->phone));
	}

	public function testGetIdByPhoneHash(){
		$this->assertEquals($this->testdata->id, $this->gateway->getIdByPhoneHash($this->testdata->phoneHash));
	}

	public function testGetIdByEmail(){
		$this->assertEquals($this->testdata->id, $this->gateway->getIdByEmail($this->testdata->email));
	}

	public function testGetIdByEmailHash(){
		$this->assertEquals($this->testdata->id, $this->gateway->getIdByEmailHash($this->testdata->emailHash));
	}

	public function testGetPublicKey(){
		$this->assertEquals($this->testdata->publicKey, $this->gateway->getPublicKey($this->testdata->id));
	}

}
