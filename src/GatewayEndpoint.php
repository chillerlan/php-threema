<?php
/**
 * Class GatewayEndpoint
 *
 * @filesource   GatewayEndpoint.php
 * @created      02.04.2016
 * @package      chillerlan\Threema
 * @author       Smiley <smiley@chillerlan.net>
 * @copyright    2016 Smiley
 * @license      MIT
 */

namespace chillerlan\Threema;

use chillerlan\Threema\Crypto\CryptoInterface;
use chillerlan\TinyCurl\{
	Request, RequestOptions, Response\ResponseInterface, URL
};

/**
 *
 */
class GatewayEndpoint implements GatewayInterface{

	/**
	 * @var \chillerlan\TinyCurl\Request
	 */
	protected $request;

	/**
	 * @var \chillerlan\Threema\GatewayOptions
	 */
	protected $gatewayOptions;

	/**
	 * @var \chillerlan\Threema\Crypto\CryptoInterface
	 */
	protected $cryptoInterface;

	/**
	 * GatewayEndpoint constructor.
	 *
	 * @param \chillerlan\Threema\Crypto\CryptoInterface $cryptoInterface
	 * @param \chillerlan\Threema\GatewayOptions         $gatewayOptions
	 *
	 * @codeCoverageIgnore
	 */
	public function __construct(CryptoInterface $cryptoInterface, GatewayOptions $gatewayOptions){
		$this->cryptoInterface = $cryptoInterface;
		$this->gatewayOptions  = $gatewayOptions;

		$requestOptions          = new RequestOptions;
		$requestOptions->ca_info = $this->gatewayOptions->cacert;
		$this->request = new Request($requestOptions);
	}

	/**
	 * @param string $endpoint
	 * @param array  $params
	 * @param array  $body
	 *
	 * @return \chillerlan\TinyCurl\Response\ResponseInterface
	 * @throws \chillerlan\Threema\GatewayException
	 * @throws \chillerlan\TinyCurl\RequestException
	 */
	protected function getResponse(string $endpoint, array $params = [], array $body = []):ResponseInterface{
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
			throw new GatewayException('gateway error: '.self::API_ERRORS[$response->info->http_code]);
		}

		throw new GatewayException('unknown error: "compiles on my machine."');
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

	/**
	 * @param string $threemaID
	 *
	 * @return string
	 * @throws \chillerlan\Threema\GatewayException
	 */
	protected function checkThreemaID(string $threemaID):string{

		if(preg_match('/^[a-z\d\*]{8}$/i', $threemaID)){
			return strtoupper($threemaID);
		}

		throw new GatewayException('invalid threema id');
	}

	/**
	 * @param string $phoneNo
	 *
	 * @return string
	 * @throws \chillerlan\Threema\GatewayException
	 */
	protected function checkPhoneNo(string $phoneNo):string{
		$phoneNo = preg_replace('/[^\d]/', '', $phoneNo);

		if(empty($phoneNo)){
			throw new GatewayException('invalid phone number');
		}

		return (string)$phoneNo;
	}

	/**
	 * @param $email
	 *
	 * @return string
	 * @throws \chillerlan\Threema\GatewayException
	 */
	protected function checkEmail($email):string{
		$email = filter_var(trim($email), FILTER_VALIDATE_EMAIL);

		if(empty($email)){
			throw new GatewayException('invalid email');
		}

		return strtolower($email);
	}

	/**
	 * @param string $hash
	 *
	 * @return string
	 * @throws \chillerlan\Threema\GatewayException
	 */
	protected function checkHash(string $hash):string{

		if(preg_match('/^[a-f\d]{64}$/i', $hash)){
			return $hash;
		}

		throw new GatewayException('invalid hash');
	}

}
