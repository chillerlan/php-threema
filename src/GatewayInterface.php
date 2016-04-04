<?php
/**
 * Interface GatewayInterface
 *
 * @filesource   GatewayInterface.php
 * @created      01.04.2016
 * @package      chillerlan\Threema
 * @author       Smiley <smiley@chillerlan.net>
 * @copyright    2016 Smiley
 * @license      MIT
 */

namespace chillerlan\Threema;

/**
 * Threema Gateway Interface
 *
 * This interface contains methods and documentation for each endpoint provided by the Threema Gateway API
 * API authentication parameters "from" (API ID) and "secret" (API secret) are being served via environment $_ENV
 *
 * @see /config/.env
 */
interface GatewayInterface{

	const API_BASE = 'https://msgapi.threema.ch';
	const API_ERRORS = [
		400 => 'bad request',
		401 => 'unauthorized',
		402 => 'no credits remain',
		404 => 'not found',
		413 => 'message too large',
		500 => 'internal server error',
	];

	const HMAC_KEY_EMAIL     = '30a5500fed9701fa6defdb610841900febb8e430881f7ad816826264ec09bad7';
	const HMAC_KEY_PHONE     = '85adf8226953f3d96cfd5d09bf29555eb955fcd8aa5ec4f9fcd869e258370723';
	const HMAC_KEY_EMAIL_BIN = "\x30\xa5\x50\x0f\xed\x97\x01\xfa\x6d\xef\xdb\x61\x08\x41\x90\x0f\xeb\xb8\xe4\x30\x88\x1f\x7a\xd8\x16\x82\x62\x64\xec\x09\xba\xd7";
	const HMAC_KEY_PHONE_BIN = "\x85\xad\xf8\x22\x69\x53\xf3\xd9\x6c\xfd\x5d\x09\xbf\x29\x55\x5e\xb9\x55\xfc\xd8\xaa\x5e\xc4\xf9\xfc\xd8\x69\xe2\x58\x37\x07\x23";

	const MESSAGE_TEXT     = 0x01;
	const MESSAGE_IMAGE    = 0x02;
	const MESSAGE_FILE     = 0x17;
	const MESSAGE_DELIVERY = 0x80;

	const LENGTH_MESSAGE_ID      = 8;
	const LENGTH_BLOB_ID         = 16;
	const LENGTH_IMAGE_FILE_SIZE = 4;
	const LENGTH_IMAGE_NONCE     = 24;

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
	public function checkCredits();

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
	public function checkCapabilities(string $threemaID);

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
	public function getIdByPhone(string $phoneno);

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
	public function getIdByPhoneHash(string $phonenoHash);

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
	public function getIdByEmail(string $email);

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
	public function getIdByEmailHash(string $emailHash);

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
	 */
	public function getPublicKey(string $threemaID);

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
	 */
	public function sendSimple(string $to, string $message);

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
	 * @param string $threemaID
	 * @param string $box
	 * @param string $nonce
	 *
	 * @return string
	 */
	public function sendE2E(string $threemaID, string $box, string $nonce);

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
	public function upload(string $blob);

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
	public function download(string $blobID);
}
