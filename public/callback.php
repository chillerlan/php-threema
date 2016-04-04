<?php
/**
 *
 * @filesource   callback.php
 * @created      02.04.2016
 * @author       Smiley <smiley@chillerlan.net>
 * @copyright    2016 Smiley
 * @license      MIT
 *
 * @todo
 * 
 * Incoming messages and delivery receipts
 *
 * If your account is operating in end-to-end encrypted mode and incoming messages have been enabled on it, you can
 * specify an HTTPS URL callback that will be called whenever an incoming message or delivery receipt arrives for your
 * API identity. You can set or change the callback URL in the Threema Gateway administration panel.
 *
 * Callback parameters
 *
 * Your callback URL will be called with the following POST parameters (application/x-www-form-urlencoded):
 *
 * from sender identity (8 characters)
 * to your API identity (8 characters, usually starts with '*')
 * messageId message ID assigned by the sender (8 bytes, hex encoded)
 * date message date set by the sender (UNIX timestamp)
 * nonce nonce used for encryption (24 bytes, hex encoded)
 * box encrypted message data (max. 4000 bytes, hex encoded)
 * mac Message Authentication Code (32 bytes, hex encoded, see below)
 * Note that the message first needs to be decrypted before it can be determined whether it is an incoming text message
 * or a delivery receipt.
 *
 * MAC calculation
 *
 * For each callback, the server includes a mac parameter than can be used to verify the authenticity of the call and
 * the included information. This parameter is calculated as follows:
 *
 * mac = HMAC-SHA256(from || to || messageId || date || nonce || box, secret)
 *
 * || denotes concatenation. The parameters are concatenated in the same form as they were included in the POST (i.e.
 * including any hex encoding, but not including any URL encoding). The secret that is used for the HMAC operation is
 * the API authentication secret.
 *
 * It is recommended that receivers verify the mac parameter before attempting to parse the other parameters and
 * decrypt the message.
 *
 * Callback results and retry
 *
 * If the connection to your callback URL fails or your callback does not return an HTTP 200 status, the API will retry
 * 3 more times in intervals of 5 minutes. If all attempts fail, the message is discarded.
 *
 * Certificates and cipher suites
 *
 * The server that hosts the callback URL must use a valid and trusted SSL/TLS certificate (not self-signed). If in
 * doubt, please contact customer service and specify the issuing CA of your certificate.
 */





