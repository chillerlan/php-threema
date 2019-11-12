<?php
/**
 * Trait GatewayOptionsTrait
 *
 * @filesource   GatewayOptionsTrait.php
 * @created      24.01.2018
 * @package      chillerlan\Threema
 * @author       Smiley <smiley@chillerlan.net>
 * @copyright    2018 Smiley
 * @license      MIT
 */

namespace chillerlan\Threema;

trait GatewayOptionsTrait{

	/**
	 * @var string
	 */
	protected $gatewayID;

	/**
	 * @var string
	 */
	protected $gatewaySecret;

	/**
	 * @var string
	 */
	protected $gatewayHMACAlgo = 'sha256';

}
