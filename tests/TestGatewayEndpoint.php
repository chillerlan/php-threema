<?php
/**
 *
 * @filesource   TestGatewayEndpoint.php
 * @created      04.04.2016
 * @package      chillerlan\ThreemaTest
 * @author       Smiley <smiley@chillerlan.net>
 * @copyright    2016 Smiley
 * @license      MIT
 */

namespace chillerlan\ThreemaTest;

use chillerlan\Threema\Crypto\CryptoInterface;
use chillerlan\Threema\GatewayEndpoint;
use chillerlan\Threema\GatewayOptions;
use chillerlan\TinyCurl\Request;
use chillerlan\TinyCurl\RequestOptions;
use chillerlan\TinyCurl\Response\ResponseException;
use chillerlan\TinyCurl\Response\ResponseInterface;
use stdClass;

/**
 * HERE BE DRAGONS
 */
class TestGatewayEndpoint extends GatewayEndpoint{

	/** @noinspection PhpMissingParentConstructorInspection */
	public function __construct(CryptoInterface $cryptoInterface, GatewayOptions $gatewayOptions){
		$this->cryptoInterface = $cryptoInterface;
		$this->gatewayOptions  = $gatewayOptions;

		$requestOptions          = new RequestOptions;
		$requestOptions->ca_info = $this->gatewayOptions->cacert;
		$this->request = new class($requestOptions) extends Request{
			protected function getResponse($url){
				return new class($url) implements ResponseInterface{
					private $path;

					public function __construct($url){
						$path = explode('/', parse_url($url, PHP_URL_PATH));
						array_shift($path);
						$this->path = $path;
					}

					public function __get($property){
						switch($property){
							case 'body': return $this->getBody();
							case 'info': return $this->getInfo();
							case 'json':
							case 'json_array':
							case 'error':
							case 'headers':
								return false;
							default: throw new ResponseException('!$property: '.$property);
						}
					}

					private function getInfo(){
						$info = new stdClass;


						$info->http_code = in_array($this->path[0], ['credits', 'capabilities', 'lookup', 'pubkeys']) ? 200 : 404;

						return $info;
					}

					private function getBody(){
						$body = new stdClass;

						switch($this->path[0]){
							case 'credits':
								$body->content = getenv('THREEMA_TEST_CREDITS');
								break;
							case 'capabilities':
								$body->content = $this->path[1] === 'ECHOECHO' ? 'image,text,video' : '';
								break;
							case 'lookup':

								switch($this->path[1]){
									case 'email_hash':
										$body->content = $this->path[2] === getenv('THREEMA_TEST_EMAIL_HASH') ? getenv('THREEMA_TEST_ID') : '';
										break;
									case 'phone_hash':
										$body->content = $this->path[2] === getenv('THREEMA_TEST_PHONE_HASH') ? getenv('THREEMA_TEST_ID') : '';
										break;
									case 'email':
										$body->content = $this->path[2] === getenv('THREEMA_TEST_EMAIL') ? getenv('THREEMA_TEST_ID') : '';
										break;
									case 'phone':
										$body->content = $this->path[2] === getenv('THREEMA_TEST_PHONE') ? getenv('THREEMA_TEST_ID') : '';
										break;
								}

								break;
							case 'pubkeys':
								$body->content = $this->path[1] === getenv('THREEMA_TEST_ID') ? getenv('THREEMA_TEST_PUBLIC_KEY') : '';
								break;
						}

						$body->length = strlen($body->content);

						return $body;
					}

				};
			}
		};
	}

}
