<?php
/**
 * Class HTTPClientAbstract
 *
 * @filesource   HTTPClientAbstract.php
 * @created      10.06.2017
 * @package      chillerlan\Threema\HTTP
 * @author       Smiley <smiley@chillerlan.net>
 * @copyright    2017 Smiley
 * @license      MIT
 */

namespace chillerlan\Threema\HTTP;

abstract class HTTPClientAbstract implements HTTPClientInterface{

	protected $client;

	public function __construct($client){
		$this->client = $client;
	}

}
