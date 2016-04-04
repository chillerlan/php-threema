<?php
/**
 *
 * @filesource   NetworkTestAbstract.php
 * @created      04.04.2016
 * @package      chillerlan\ThreemaTest\Network
 * @author       Smiley <smiley@chillerlan.net>
 * @copyright    2016 Smiley
 * @license      MIT
 */

namespace chillerlan\ThreemaTest\Network;

use chillerlan\ThreemaTest\GatewayTestAbstract;
use stdClass;

/**
 * Class NetworkTestAbstract
 */
abstract class NetworkTestAbstract extends GatewayTestAbstract{
	
	/**
	 * @var \stdClass
	 */
	protected $testdata;

	protected function setUp(){
		parent::setUp();

		// test credentials for a valid gateway account
		$this->testdata            = new stdClass;
		$this->testdata->id        = getenv('THREEMA_TEST_ID');
		$this->testdata->publicKey = getenv('THREEMA_TEST_PUBLIC_KEY');
		$this->testdata->phone     = getenv('THREEMA_TEST_PHONE');
		$this->testdata->phoneHash = getenv('THREEMA_TEST_PHONE_HASH');
		$this->testdata->email     = getenv('THREEMA_TEST_EMAIL');
		$this->testdata->emailHash = getenv('THREEMA_TEST_EMAIL_HASH');
		$this->testdata->credits   = getenv('THREEMA_TEST_CREDITS');
	}

}
