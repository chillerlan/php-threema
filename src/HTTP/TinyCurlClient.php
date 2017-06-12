<?php
/**
 * Class TinyCurlClient
 *
 * @filesource   TinyCurlClient.php
 * @created      10.06.2017
 * @package      chillerlan\Threema\HTTP
 * @author       Smiley <smiley@chillerlan.net>
 * @copyright    2017 Smiley
 * @license      MIT
 */

namespace chillerlan\Threema\HTTP;

use chillerlan\Threema\GatewayResponse;
use chillerlan\TinyCurl\{Request, URL};

class TinyCurlClient extends HTTPClientAbstract{

	/**
	 * @var \chillerlan\TinyCurl\Request
	 */
	protected $client;

	/**
	 * TinyCurlClient constructor.
	 *
	 * @param \chillerlan\TinyCurl\Request $client
	 */
	public function __construct(Request $client){
		parent::__construct($client);
	}

	/**
	 * @param string $endpoint
	 * @param array  $params
	 * @param string $method
	 * @param null   $body
	 * @param array  $headers
	 *
	 * @return \chillerlan\Threema\GatewayResponse
	 */
	public function getResponse(string $endpoint, array $params = [], $method = 'GET', $body = null, $headers = []):GatewayResponse{
		$r = $this->client->fetch(new URL($endpoint, $params, $method, $body, $headers));

		$re = new GatewayResponse;
		$re->code = $r->info->http_code;
		$re->body = $r->body->content;

		return $re;
	}

}
