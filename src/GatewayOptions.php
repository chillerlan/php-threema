<?php
/**
 * Class GatewayOptions
 *
 * @filesource   GatewayOptions.php
 * @created      24.01.2018
 * @package      chillerlan\Threema
 * @author       Smiley <smiley@chillerlan.net>
 * @copyright    2018 Smiley
 * @license      MIT
 */

namespace chillerlan\Threema;

use chillerlan\HTTP\HTTPOptionsTrait;
use chillerlan\Settings\SettingsContainerAbstract;

/**
 * @property string $gatewayID
 * @property string $gatewaySecret
 * @property string gatewayHMACAlgo
 */
class GatewayOptions extends SettingsContainerAbstract{
	use GatewayOptionsTrait, HTTPOptionsTrait;
}
