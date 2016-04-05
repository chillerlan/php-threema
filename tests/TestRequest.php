<?php
/**
 *
 * @filesource   TestRequest.php
 * @created      05.04.2016
 * @package      chillerlan\ThreemaTest
 * @author       Smiley <smiley@chillerlan.net>
 * @copyright    2016 Smiley
 * @license      MIT
 */

namespace chillerlan\ThreemaTest;

use chillerlan\TinyCurl\{
	Request, Response\ResponseException, Response\ResponseInterface
};
use stdClass;

/**
 * Class TestRequest
 */
class TestRequest extends Request{

	/**
	 * @param string $url
	 *
	 * @return \chillerlan\TinyCurl\Response\ResponseInterface
	 */
	protected function getResponse($url){

		/**
		 * ResponseInterface
		 */
		return new class($url) implements ResponseInterface{
			private $path;

			/**
			 *  ResponseInterface constructor.
			 *
			 * @param $url
			 */
			public function __construct($url){
				$path = explode('/', parse_url($url, PHP_URL_PATH));
				array_shift($path);
				$this->path = $path;
			}

			/**
			 * @param string $property
			 *
			 * @return bool|\stdClass
			 * @throws \chillerlan\TinyCurl\Response\ResponseException
			 */
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

			/**
			 * @return \stdClass
			 */
			private function getInfo(){
				$info = new stdClass;
				$info->http_code = in_array($this->path[0], ['credits', 'capabilities', 'lookup', 'pubkeys']) ? 200 : 404;
				return $info;
			}

			/**
			 * @return \stdClass
			 */
			private function getBody(){
				$body = new stdClass;
				switch($this->path[0]){
					case 'credits':      $body->content = getenv('THREEMA_TEST_CREDITS'); break;
					case 'capabilities': $body->content = $this->path[1] === 'ECHOECHO' ? 'image,text,video' : ''; break;
					case 'pubkeys':      $body->content = $this->path[1] === getenv('THREEMA_TEST_ID') ? getenv('THREEMA_TEST_PUBLIC_KEY') : ''; break;
					case 'lookup':
						switch($this->path[1]){
							case 'email_hash': $body->content = $this->path[2] === getenv('THREEMA_TEST_EMAIL_HASH') ? getenv('THREEMA_TEST_ID') : ''; break;
							case 'phone_hash': $body->content = $this->path[2] === getenv('THREEMA_TEST_PHONE_HASH') ? getenv('THREEMA_TEST_ID') : ''; break;
							case 'email':      $body->content = $this->path[2] === getenv('THREEMA_TEST_EMAIL') ? getenv('THREEMA_TEST_ID') : ''; break;
							case 'phone':      $body->content = $this->path[2] === getenv('THREEMA_TEST_PHONE') ? getenv('THREEMA_TEST_ID') : ''; break;
						}
						break;
				}
				$body->length = strlen($body->content);
				return $body;
			}

		};
	}
}
