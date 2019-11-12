<?php
/**
 * Class Gateway
 *
 * @link https://gateway.threema.ch/en/developer/api
 *
 * @filesource   Gateway.php
 * @created      10.06.2017
 * @package      chillerlan\Threema
 * @author       Smiley <smiley@chillerlan.net>
 * @copyright    2017 Smiley
 * @license      MIT
 */

namespace chillerlan\Threema;

use chillerlan\HTTP\Psr17\RequestFactory;
use chillerlan\HTTP\Psr7\MultipartStream;
use chillerlan\Settings\SettingsContainerInterface;
use finfo;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;

use function chillerlan\HTTP\Psr7\merge_query;

class Gateway{

	protected const API_BASE = 'https://msgapi.threema.ch';

	protected const API_ERRORS = [
		400 => 'bad request',
		401 => 'unauthorized',
		402 => 'no credits remain',
		404 => 'not found',
		413 => 'message too large',
		500 => 'internal server error',
	];

	protected const HMAC_KEY_EMAIL_BIN = "\x30\xa5\x50\x0f\xed\x97\x01\xfa\x6d\xef\xdb\x61\x08\x41\x90\x0f\xeb\xb8\xe4\x30\x88\x1f\x7a\xd8\x16\x82\x62\x64\xec\x09\xba\xd7";
	protected const HMAC_KEY_PHONE_BIN = "\x85\xad\xf8\x22\x69\x53\xf3\xd9\x6c\xfd\x5d\x09\xbf\x29\x55\x5e\xb9\x55\xfc\xd8\xaa\x5e\xc4\xf9\xfc\xd8\x69\xe2\x58\x37\x07\x23";

	protected const FILE_NONCE           = "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x01";
	protected const FILE_THUMBNAIL_NONCE = "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x02";

	/**
	 * @var \chillerlan\Threema\GatewayOptions
	 */
	protected $options;

	/**
	 * @var \Psr\Http\Client\ClientInterface
	 */
	protected $http;

	/**
	 * @var \Psr\Http\Message\RequestFactoryInterface
	 */
	protected $requestFactory;

	/**
	 * Gateway constructor.
	 *
	 * @param \chillerlan\Settings\SettingsContainerInterface $options
	 * @param \Psr\Http\Client\ClientInterface                $http
	 */
	public function __construct(SettingsContainerInterface $options, ClientInterface $http){
		$this->options        = $options;
		$this->http           = $http;
		$this->requestFactory = new RequestFactory;
	}

	/**
	 * @inheritdoc
	 */
	protected function getPadBytes():string{
		$padbytes = 0;

		while($padbytes < 1 || $padbytes > 255){
			$padbytes = ord(random_bytes(1));
		}

		return str_repeat(chr($padbytes), $padbytes);
	}

	/**
	 * @param string $data
	 * @param string $privateKey
	 * @param string $publicKey
	 *
	 * @return array
	 * @throws \chillerlan\Threema\GatewayException
	 */
	protected function createBox(string $data, string $privateKey, string $publicKey):array{

		if(empty($data)){
			throw new GatewayException('invalid data');
		}

		if(!preg_match('/^[a-f\d]{128}$/i', $privateKey.$publicKey)){
			throw new GatewayException('invalid keypair');
		}

		$keypair = sodium_crypto_box_keypair_from_secretkey_and_publickey(sodium_hex2bin($privateKey), sodium_hex2bin($publicKey));

		$nonce = random_bytes(SODIUM_CRYPTO_BOX_NONCEBYTES);
		$box   = sodium_crypto_box($data, $nonce, $keypair);

		$encrypted = ['box' => sodium_bin2hex($box), 'nonce' => sodium_bin2hex($nonce)];

		return $encrypted;
	}


	/**
	 * @return array
	 */
	protected function getAuthParams():array {
		return [
			'from'   => $this->options->gatewayID,
			'secret' => $this->options->gatewaySecret,
		];
	}

	/**
	 * @param \Psr\Http\Message\RequestInterface $request
	 *
	 * @return string
	 * @throws \chillerlan\Threema\GatewayException
	 */
	protected function getResponse(RequestInterface $request):string{
		$response = $this->http->sendRequest($request);
		$status   = $response->getStatusCode();

		if($status === 200){
			return $response->getBody()->getContents();
		}

		if(array_key_exists($status, $this::API_ERRORS)){
			throw new GatewayException('gateway error: '.$this::API_ERRORS[$status]);
		}

		throw new GatewayException('unknown error: "compiles on my machine."'); // @codeCoverageIgnore
	}


	/**
	 * Get remaining credits
	 *
	 * URL: https://msgapi.threema.ch/credits?from=<gatewayID>&secret=<gatewaySecret>
	 *
	 * The API identity and secret must be passed in the corresponding GET parameters for authentication (use URL
	 * encoding). The number of credits left on the account that the given ID belongs to will be returned as a
	 * text/plain response. Note: several IDs may use the same account, and thus share the same credit balance.
	 *
	 * Possible HTTP result codes:
	 *
	 * - 200 (on success)
	 * - 401 (if API identity or secret are incorrect)
	 * - 500 (if a temporary internal server error occurs)
	 *
	 * @return int
	 */
	public function checkCredits():int{
		$url     = $this::API_BASE.'/credits';
		$request = $this->requestFactory->createRequest('GET', merge_query($url, $this->getAuthParams()));

		return (int)$this->getResponse($request);
	}

	/**
	 *Check file reception capability of an ID
	 *
	 * Before you send a file to a Threema ID using the blob upload (+ file message), you may want to check whether the
	 * recipient uses a Threema version that supports receiving files. The receiver may be using an old version, or a
	 * platform where file reception is not supported.
	 *
	 * URL: https://msgapi.threema.ch/capabilities/<threemaID>?from=<gatewayID>&secret=<gatewaySecret>
	 *
	 * The API identity and secret must be passed in the corresponding GET parameters for authentication (use URL
	 * encoding). The result is a text/plain response of supported capabilities, separated by commas. Currently defined
	 * capabilities:
	 *
	 * - text
	 * - image
	 * - video
	 * - audio
	 * - file
	 *
	 * More capabilities may be added in the future (separated with commas), so you should match on substrings when
	 * checking for file. The order in which the capabilities are returned is not defined.
	 *
	 * Example result: text,image,video,audio,file
	 *
	 * Possible HTTP result codes:
	 *
	 * - 200 (on success)
	 * - 401 (if API identity or secret are incorrect)
	 * - 404 (if no matching ID could be found)
	 * - 500 (if a temporary internal server error occurs)
	 *
	 * @param string $threemaID
	 *
	 * @return array
	 */
	public function checkCapabilities(string $threemaID):array{
		$url      = $this::API_BASE.'/capabilities/'.$this->checkThreemaID($threemaID);
		$request  = $this->requestFactory->createRequest('GET', merge_query($url, $this->getAuthParams()));
		$response = explode(',', $this->getResponse($request));

		sort($response);

		return $response;
	}

	/**
	 * Find ID by phone number
	 *
	 * URL: https://msgapi.threema.ch/lookup/phone/<phoneno>?from=<gatewayID>&secret=<gatewaySecret>
	 *
	 * The phone number must be passed in E.164 format, without the leading +. The API identity and secret must be
	 * passed in the corresponding GET parameters for authentication (use URL encoding).
	 * The Threema ID corresponding to the phone number will be returned as a text/plain response.
	 *
	 * Possible HTTP result codes:
	 *
	 * - 200 (on success)
	 * - 401 (if API identity or secret are incorrect)
	 * - 404 (if no matching ID could be found)
	 * - 500 (if a temporary internal server error occurs)
	 *
	 * @param string $phoneno
	 *
	 * @return string
	 */
	public function getIdByPhone(string $phoneno):string{
		$url     = $this::API_BASE.'/lookup/phone/'.$this->checkPhoneNo($phoneno);
		$request = $this->requestFactory->createRequest('GET', merge_query($url, $this->getAuthParams()));

		return $this->getResponse($request);
	}

	/**
	 * URL: https://msgapi.threema.ch/lookup/phone_hash/<phonenoHash>?from=<gatewayID>&secret=<gatewaySecret>
	 *
	 * The phone number must be passed as an HMAC-SHA256 hash of the E.164 number without the leading +.
	 * The HMAC key is 85adf8226953f3d96cfd5d09bf29555eb955fcd8aa5ec4f9fcd869e258370723 (in hexadecimal).
	 *
	 * Example: the phone number 41791234567 hashes to
	 * ad398f4d7ebe63c6550a486cc6e07f9baa09bd9d8b3d8cb9d9be106d35a7fdbc.
	 *
	 * The API identity and secret must be passed in the corresponding GET parameters for authentication (use URL
	 * encoding). The Threema ID corresponding to the phone number will be returned as a text/plain response.
	 *
	 * Possible HTTP result codes:
	 *
	 * - 200 (on success)
	 * - 400 (if the hash length is wrong)
	 * - 401 (if API identity or secret are incorrect)
	 * - 404 (if no matching ID could be found)
	 * - 500 (if a temporary internal server error occurs)
	 *
	 * @param string $phonenoHash
	 *
	 * @return string
	 */
	public function getIdByPhoneHash(string $phonenoHash):string{
		$url     = $this::API_BASE.'/lookup/phone_hash/'.$this->checkHash($phonenoHash);
		$request = $this->requestFactory->createRequest('GET', merge_query($url, $this->getAuthParams()));

		return $this->getResponse($request);
	}

	/**
	 * URL: https://msgapi.threema.ch/lookup/email/<email>?from=<gatewayID>&secret=<gatewaySecret>
	 *
	 * The API identity and secret must be passed in the corresponding GET parameters for authentication (use URL
	 * encoding). The Threema ID corresponding to the email address will be returned as a text/plain response.
	 *
	 * Possible HTTP result codes:
	 *
	 * - 200 (on success)
	 * - 401 (if API identity or secret are incorrect)
	 * - 404 (if no matching ID could be found)
	 * - 500 (if a temporary internal server error occurs)
	 *
	 *
	 * @param string $email
	 *
	 * @return string
	 */
	public function getIdByEmail(string $email):string{
		$url     = $this::API_BASE.'/lookup/email/'.$this->checkEmail($email);
		$request = $this->requestFactory->createRequest('GET', merge_query($url, $this->getAuthParams()));

		return $this->getResponse($request);
	}

	/**
	 * Find ID by email address hash
	 *
	 * URL: https://msgapi.threema.ch/lookup/email_hash/<emailHash>?from=<gatewayID>&secret=<gatewaySecret>
	 *
	 * The lowercased and whitespace-trimmed email address must be hashed with HMAC-SHA256.
	 * The HMAC key is 30a5500fed9701fa6defdb610841900febb8e430881f7ad816826264ec09bad7 (in hexadecimal).
	 *
	 * Example: the email address test@threema.ch hashes to
	 * 1ea093239cc5f0e1b6ec81b866265b921f26dc4033025410063309f4d1a8ee2c.
	 *
	 * The API identity and secret must be passed in the corresponding GET parameters for authentication (use URL
	 * encoding). The Threema ID corresponding to the email address will be returned as a text/plain response.
	 *
	 * Possible HTTP result codes:
	 *
	 * - 200 (on success)
	 * - 400 (if the hash length is wrong)
	 * - 401 (if API identity or secret are incorrect)
	 * - 404 (if no matching ID could be found)
	 * - 500 (if a temporary internal server error occurs)
	 *
	 * @param string $emailHash
	 *
	 * @return string
	 */
	public function getIdByEmailHash(string $emailHash):string{
		$url     = $this::API_BASE.'/lookup/email_hash/'.$this->checkHash($emailHash);
		$request = $this->requestFactory->createRequest('GET', merge_query($url, $this->getAuthParams()));

		return $this->getResponse($request);
	}

	/**
	 * @param array $emails
	 * @param array $phonenumbers
	 *
	 * @return mixed
	 */
/*
	public function bulkLookup($emails = [], $phonenumbers = []){
		$lookup = [];

		foreach($phonenumbers as $phonenumber){
			$lookup['phoneHashes'][] = $this->hashPhoneNo($phonenumber);
		}

		foreach($emails as $email){
			$lookup['emailHashes'][] = $this->hashEmail($email);
		}

		$r = $this->getResponse('/lookup/bulk', $this->getAuthParams(), json_encode($lookup));

		return json_decode($r);
	}
*/

	/**
	 * Key lookups
	 *
	 * For the end-to-end encrypted mode, you need the public key of the recipient in order to encrypt a message. While
	 * it's best to obtain this directly from the recipient (extract it from the QR code), this may not be convenient,
	 * and therefore you can also look up the key associated with a given ID from the server.
	 *
	 * URL: https://msgapi.threema.ch/pubkeys/<threemaID>?from=<gatewayID>&secret=<gatewaySecret>
	 *
	 * The API identity and secret must be passed in the corresponding GET parameters for authentication (use URL
	 * encoding). The public key corresponding to the ID will be returned as a text/plain response (hex encoded).
	 *
	 * Possible HTTP result codes:
	 *
	 * - 200 (on success)
	 * - 401 (if API identity or secret are incorrect)
	 * - 404 (if no matching ID could be found)
	 * - 500 (if a temporary internal server error occurs)
	 *
	 * It is strongly recommended that you cache the public keys to avoid querying the API for each message.
	 *
	 * @param string $threemaID
	 *
	 * @return string
	 * @throws \chillerlan\Threema\GatewayException
	 */
	public function getPublicKey(string $threemaID):string{
		$threemaID = $this->checkThreemaID($threemaID);

		if(!$threemaID){
			throw new GatewayException('invalid threema id');
		}

		$url      = $this::API_BASE.'/pubkeys/'.$threemaID;
		$request  = $this->requestFactory->createRequest('GET', merge_query($url, $this->getAuthParams()));
		$response = $this->getResponse($request);

		if(!$response || !$this->checkHash($response)){
			throw new GatewayException('invalid public key');
		}

		return $response;
	}

	/**
	 * Send Messages, Basic mode
	 *
	 * URL: https://msgapi.threema.ch/send_simple
	 *
	 * POST parameters (application/x-www-form-urlencoded):
	 *
	 * - recipient:   choose one of the following:
	 *   - to           recipient identity (8 characters)
	 *   - phone        recipient phone number (E.164), without leading +
	 *   - email        recipient email address
	 * - text         message text, max. 3500 bytes, UTF-8 encoded
	 *
	 * - from         your API identity (8 characters, usually starts with '*')
	 * - secret       API authentication secret
	 *
	 * By using the phone or email recipient specifiers, one can avoid having to look up the corresponding ID
	 * and instead do everything in one call (may be more suitable for SMS gateway style integration).
	 *
	 * Possible HTTP result codes:
	 *
	 * - 200 (on success)
	 * - 400 (if the recipient identity is invalid or the account is not set up for basic mode)
	 * - 401 (if API identity or secret are incorrect)
	 * - 402 (if no credits remain)
	 * - 404 (if using phone or email as the recipient specifier, and the corresponding recipient could not be found)
	 * - 413 (if the message is too long)
	 * - 500 (if a temporary internal server error occurs)
	 *
	 * On success (HTTP 200), the ID of the new message is returned as text/plain.
	 *
	 * @param string $to
	 * @param string $message
	 *
	 * @return string
	 * @throws \chillerlan\Threema\GatewayException
	 *
	 */
/*
	public function sendSimple(string $to, string $message):string{
		$params = array_merge($this->getRecipient($to), ['text' => $message], $this->getAuthParams());

		ksort($params);

		return $this->getResponse('/send_simple', [], $params);
	}
*/


	/**
	 * @param string $to
	 *
	 * @return array
	 * @throws \chillerlan\Threema\GatewayException
	 */
	protected function getRecipient(string $to):array {

		switch(true){
			case $x = $this->checkEmail($to):
				return ['email' => $x];
			case $x = $this->checkThreemaID($to):
				return ['to' => $x];
			case $x = $this->checkPhoneNo($to):
				return ['phone' => $x];
			default:
				throw new GatewayException('"to" not specified: '.$to);
		}

	}

	/**
	 * End-to-end encrypted mode
	 *
	 * URL: https://msgapi.threema.ch/send_e2e
	 *
	 * POST parameters (application/x-www-form-urlencoded):
	 *
	 * - to      recipient identity (8 characters)
	 * - box     encrypted message data (max. 4000 bytes, hex encoded)
	 * - nonce   nonce used for encryption (24 bytes, hex encoded)
	 *
	 * - from    your API identity (8 characters, usually starts with '*')
	 * - secret  API authentication secret
	 *
	 * Possible HTTP result codes:
	 *
	 * - 200 (on success)
	 * - 400 (if the recipient identity is invalid or the account is not set up for end-to-end mode)
	 * - 401 (if API identity or secret are incorrect)
	 * - 402 (if no credits remain)
	 * - 413 (if the message is too long)
	 * - 500 (if a temporary internal server error occurs)
	 *
	 * On success (HTTP 200), the ID of the new message is returned as text/plain.
	 *
	 * @param string      $recipientThreemaID
	 * @param string      $senderPrivateKey
	 *
	 * @param string      $data
	 *
	 *
	 * @return string
	 * @throws \chillerlan\Threema\GatewayException
	 */
	protected function sendE2E(string $recipientThreemaID, string $senderPrivateKey, string $data):string{
		$recipientThreemaID = $this->checkThreemaID($recipientThreemaID);

		if(!$recipientThreemaID){
			throw new GatewayException('no threema id given');
		}

		$recipientPubKey = $this->getPublicKey($recipientThreemaID);

		$params = array_merge(
			$this->getAuthParams(),
			['to' => $recipientThreemaID],
			$this->createBox($data.$this->getPadBytes(), $senderPrivateKey, $recipientPubKey)
		);

		ksort($params);

		$request = $this->requestFactory->createRequest('POST', merge_query($this::API_BASE.'/send_e2e', $params));

		return $this->getResponse($request);
	}

	/**
	 * @param string $recipientThreemaID
	 * @param string $senderPrivateKey
	 * @param string $text
	 *
	 * @return string
	 */
	public function sendE2EText(string $recipientThreemaID, string $senderPrivateKey, string $text):string{
		return $this->sendE2E($recipientThreemaID, $senderPrivateKey, "\x01".$text);
	}

	/**
	 * @param string      $recipientThreemaID
	 * @param string      $senderPrivateKey
	 * @param string      $file      (binary content)
	 * @param string|null $filename
	 * @param string|null $description
	 * @param string|null $thumbnail (binary content)
	 *
	 * @return string
	 * @throws \chillerlan\Threema\GatewayException
	 */
	public function sendE2EFile(string $recipientThreemaID, string $senderPrivateKey, string $file, string $filename = null, string $description = null, string $thumbnail = null):string{

		if(!in_array('file', $this->checkCapabilities($recipientThreemaID))){
			throw new GatewayException('given threema id is not capable of receiving files');
		}

		$key = random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES);

		$content = [
			'b' => $this->upload(sodium_crypto_secretbox($file, $this::FILE_NONCE, $key)),
			'k' => sodium_bin2hex($key),
			'm' => (new finfo(FILEINFO_MIME_TYPE))->buffer($file),
			'n' => $filename ?? '',
			's' => strlen($file),
			'i' => 0,
		];

		if(!empty($description)){
			$content['d'] = $description;
		}

		if($thumbnail){
			// @todo: autocreate thumbnail
			$content['t'] = $this->upload(sodium_crypto_secretbox($thumbnail, $this::FILE_THUMBNAIL_NONCE, $key));
		}

		return $this->sendE2E($recipientThreemaID, $senderPrivateKey, "\x17".json_encode($content));
	}

	/**
	 * @param string $recipientThreemaID
	 * @param string $senderPrivateKey
	 * @param string $image (binary content)
	 *
	 * @return string
	 * @throws \chillerlan\Threema\GatewayException
	 */
	public function sendE2EImage(string $recipientThreemaID, string $senderPrivateKey, string $image):string{

		if(!in_array('image', $this->checkCapabilities($recipientThreemaID))){
			throw new GatewayException('given threema id is not capable of receiving image');
		}

		if(!in_array((new finfo(FILEINFO_MIME_TYPE))->buffer($image), ['image/jpg', 'image/jpeg', 'image/png', 'image/gif'])){
			throw new GatewayException('invalid image');
		}

		$recipientPubKey = $this->getPublicKey($recipientThreemaID);

		$blob    = $this->createBox($image, $senderPrivateKey, $recipientPubKey);
		$blob_id = $this->upload(sodium_hex2bin($blob['box']));

		$message = sodium_hex2bin($blob_id);
		$message .= pack('V', strlen($image));
		$message .= sodium_hex2bin($blob['nonce']);

		return $this->sendE2E($recipientThreemaID, $senderPrivateKey, "\x02".$message);
	}


	/**
	 * Upload
	 *
	 * URL: https://msgapi.threema.ch/upload_blob
	 *
	 * POST parameters (multipart/form-data):
	 *
	 * - blob    blob data (binary), max. 20 MB
	 *
	 * GET parameters:
	 *
	 * - from    your API identity (8 characters, usually starts with '*')
	 * - secret  API authentication secret
	 *
	 * Please note that the authentication parameters must be passed in the request URL,
	 * while the actual blob data needs to be sent as a multipart/form-data parameter.
	 *
	 * Possible HTTP result codes:
	 *
	 * - 200 (on success)
	 * - 400 (if required parameters are missing or the blob is empty)
	 * - 401 (if API identity or secret are incorrect)
	 * - 402 (if no credits remain)
	 * - 413 (if the blob is too big)
	 * - 500 (if a temporary internal server error occurs)
	 *
	 * The ID of the new blob is returned as text/plain. One credit is deducted for the upload of a blob.
	 *
	 * @param string $blob
	 *
	 * @return string
	 */
	protected function upload(string $blob):string{
		$url     = $this::API_BASE.'/upload_blob';
		$request = $this->requestFactory->createRequest('POST', merge_query($url, $this->getAuthParams()));

		$body = new MultipartStream([['name' => 'blob', 'contents' => $blob]]);

		$request = $request
			->withBody($body)
			->withHeader('Content-Type', 'multipart/form-data; boundary='.$body->getBoundary())
			->withHeader('Content-Disposition', 'form-data; name="blob"');
		;

		return $this->getResponse($request);
	}

	/**
	 * URL: https://msgapi.threema.ch/blobs/<blobID>
	 *
	 * GET parameters:
	 *
	 * - from your API identity (8 characters, usually starts with '*')
	 * - secret API authentication secret
	 *
	 * Possible HTTP result codes:
	 *
	 * 200 (on success, body is the blob data as application/octet-stream)
	 * 401 (if API identity or secret are incorrect)
	 * 404 (if no blob with this ID could be found)
	 * 500 (if a temporary internal server error occurs)
	 *
	 * Please note: after a blob download has first been attempted, the blob may be deleted from the server within an
	 * hour.
	 *
	 * @param string $blobID
	 *
	 * @return mixed
	 */
	public function download(string $blobID):string{
		$url     = $this::API_BASE.'/blobs/'.$blobID;
		$request = $this->requestFactory->createRequest('GET', merge_query($url, $this->getAuthParams()));

		return $this->getResponse($request);
	}

	/**
	 * Hashes an email address for identity lookup.
	 *
	 * @param string $email the email address
	 *
	 * @return string the email hash (hex)
	 * @throws \chillerlan\Threema\GatewayException
	 */
	public function hashEmail($email):string{
		$email = $this->checkEmail($email);

		if(!$email){
			throw new GatewayException('invalid email');
		}

		return hash_hmac('sha256', $email, $this::HMAC_KEY_EMAIL_BIN);
	}

	/**
	 * Hashes an phone number address for identity lookup.
	 *
	 * @param string $phoneNo the phone number (in E.164 format, no leading +)
	 *
	 * @return bool|string the phone number hash (hex), false on failure
	 * @throws \chillerlan\Threema\GatewayException
	 */
	public function hashPhoneNo($phoneNo){
		$phoneNo = $this->checkPhoneNo($phoneNo);

		if(!$phoneNo){
			throw new GatewayException('invalid phonenumber');
		}

		return hash_hmac('sha256', $phoneNo, $this::HMAC_KEY_PHONE_BIN);
	}

	/**
	 * @param string $threemaID
	 *
	 * @return null|string
	 */
	protected function checkThreemaID(string $threemaID):?string{
		$threemaID = trim($threemaID);

		/** @noinspection RegExpRedundantEscape */
		if(!preg_match('/^[a-z\d\*]{8}$/i', $threemaID)){
			return null;
		}

		return strtoupper($threemaID);
	}

	/**
	 * @param string $phoneNo
	 *
	 * @return null|string
	 */
	protected function checkPhoneNo(string $phoneNo):?string{
		$phoneNo = trim($phoneNo);

		if(!preg_match('/^[\d]+$/', $phoneNo)){
			return null;
		}

		return $phoneNo;
	}

	/**
	 * @param $email
	 *
	 * @return null|string
	 */
	protected function checkEmail($email):?string{
		$email = filter_var(trim($email), FILTER_VALIDATE_EMAIL);

		if(empty($email)){
			return null;
		}

		return strtolower($email);
	}

	/**
	 * @param string $hash
	 *
	 * @return null|string
	 */
	protected function checkHash(string $hash):?string{
		$hash = trim($hash);

		if(!preg_match('/^[a-f\d]{64}$/i', $hash)){
			return null;
		}

		return $hash;
	}

	/**
	 * @param string $path
	 *
	 * @return array|null
	 */
	protected function checkFile(string $path):?array{

		$mime = (new finfo(FILEINFO_MIME_TYPE ))->buffer($path);

		return ['size' => strlen($path), 'mime' => $mime];
	}
}
