<?php
/**
 * Interface HTTPClientInterface
 *
 * @filesource   HTTPClientInterface.php
 * @created      10.06.2017
 * @package      chillerlan\Threema\HTTP
 * @author       Smiley <smiley@chillerlan.net>
 * @copyright    2017 Smiley
 * @license      MIT
 */

namespace chillerlan\Threema\HTTP;

use chillerlan\Threema\GatewayResponse;

interface HTTPClientInterface{

	public function getResponse(string $endpoint, array $params = [], $method = 'GET', $body = null, $headers = []):GatewayResponse;

}
