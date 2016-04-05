<?php
/**
 * Class GatewayOptions
 *
 * @filesource   GatewayOptions.php
 * @created      02.04.2016
 * @package      chillerlan\Threema\Containers
 * @author       Smiley <smiley@chillerlan.net>
 * @copyright    2016 Smiley
 * @license      MIT
 */

namespace chillerlan\Threema;

/**
 *
 */
class GatewayOptions{
	
	/**
	 * @var string
	 */
	public $configPath = __DIR__.'/../config';

	/**
	 * @var string
	 */
	public $configFilename = '.threema';

	/**
	 * @var string
	 */
	public $storagePath = __DIR__.'/../storage';

}
