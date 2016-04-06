<?php
/**
 * Class TinyCurlEndpoint
 *
 * @filesource   TinyCurlEndpoint.php
 * @created      02.04.2016
 * @package      chillerlan\Threema\Endpoint
 * @author       Smiley <smiley@chillerlan.net>
 * @copyright    2016 Smiley
 * @license      MIT
 */

namespace chillerlan\Threema\Endpoint;

use chillerlan\Threema\GatewayOptions;
use chillerlan\TinyCurl\{
	Request, Response\ResponseInterface, URL
};

/**
 *
 */
class TinyCurlEndpoint extends EndpointAbstract{

	/**
	 * @var \chillerlan\TinyCurl\Request
	 */
	private $request;

	/**
	 * TinyCurlEndpoint constructor.
	 *
	 * @param \chillerlan\Threema\GatewayOptions $gatewayOptions
	 * @param \chillerlan\TinyCurl\Request       $request
	 */
	public function __construct(GatewayOptions $gatewayOptions, Request $request){
		parent::__construct($gatewayOptions);

		$this->request = $request;
	}

	/**
	 * @param string $endpoint
	 * @param array  $params
	 * @param array  $body
	 *
	 * @return \chillerlan\TinyCurl\Response\ResponseInterface
	 * @throws \chillerlan\Threema\Endpoint\EndpointException
	 */
	private function getResponse(string $endpoint, array $params = [], array $body = []):ResponseInterface{
		$endpoint = self::API_BASE.$endpoint;
		$params   = array_merge($params, [
			'from'   => getenv('THREEMA_GATEWAY_ID'),
			'secret' => getenv('THREEMA_GATEWAY_SECRET')
		]);

		$url = !empty($body)
			? new URL($endpoint, $params, 'POST', $body)
			: new URL($endpoint, $params);

		$response = $this->request->fetch($url);

		if($response->info->http_code === 200){
			return $response;
		}
		// @codeCoverageIgnoreStart
		elseif(array_key_exists($response->info->http_code, self::API_ERRORS)){
			throw new EndpointException('gateway error: '.self::API_ERRORS[$response->info->http_code]);
		}

		throw new EndpointException('unknown error: "compiles on my machine."');
		// @codeCoverageIgnoreEnd
	}

	/**
	 * @inheritdoc
	 */
	public function checkCredits():int{
		return intval($this->getResponse('/credits')->body->content);
	}

	/**
	 * @inheritdoc
	 */
	public function checkCapabilities(string $threemaID):array{
		$response = $this->getResponse('/capabilities/'.$this->checkThreemaID($threemaID))->body->content;
		$response = !empty($response) ? explode(',', $response) : [];

		sort($response);

		return $response;
	}

	/**
	 * @inheritdoc
	 */
	public function getIdByPhone(string $phoneno):string{
		return $this->getResponse('/lookup/phone/'.$this->checkPhoneNo($phoneno))->body->content;
	}

	/**
	 * @inheritdoc
	 */
	public function getIdByPhoneHash(string $phonenoHash):string{
		return $this->getResponse('/lookup/phone_hash/'.$this->checkHash($phonenoHash))->body->content;
	}

	/**
	 * @inheritdoc
	 */
	public function getIdByEmail(string $email):string{
		return $this->getResponse('/lookup/email/'.$this->checkEmail($email))->body->content;
	}

	/**
	 * @inheritdoc
	 */
	public function getIdByEmailHash(string $emailHash):string{
		return $this->getResponse('/lookup/email_hash/'.$this->checkHash($emailHash))->body->content;
	}

	/**
	 * @inheritdoc
	 */
	public function getPublicKey(string $threemaID):string{
		return $this->checkHash($this->getResponse('/pubkeys/'.$this->checkThreemaID($threemaID))->body->content);
	}

	/**
	 * @inheritdoc
	 */
	public function sendSimple(string $to, string $message):string{
		// TODO: Implement sendSimple() method.
	}

	/**
	 * @inheritdoc
	 */
	public function sendE2E(string $threemaID, string $box, string $nonce):string{
		// TODO: Implement sendE2E() method.
	}

	/**
	 * @inheritdoc
	 */
	public function upload(string $blob):string{
		// TODO: Implement upload() method.
	}

	/**
	 * @inheritdoc
	 */
	public function download(string $blobID){
		// TODO: Implement download() method.
	}

}
