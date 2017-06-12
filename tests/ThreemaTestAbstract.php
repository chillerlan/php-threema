<?php
/**
 *
 * @filesource   ThreemaTestAbstract.php
 * @created      10.06.2017
 * @package      chillerlan\ThreemaTest
 * @author       Smiley <smiley@chillerlan.net>
 * @copyright    2017 Smiley
 * @license      MIT
 */

namespace chillerlan\ThreemaTest;

use Dotenv\Dotenv;
use PHPUnit\Framework\TestCase;

/**
 * Class ThreemaTestAbstract
 */
abstract class ThreemaTestAbstract extends TestCase{

	protected function setUp(){
		(new Dotenv(__DIR__.'/../config', '.threema'))->load();
	}
}
