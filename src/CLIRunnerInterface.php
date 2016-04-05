<?php
/**
 * Interface CLIRunnerInterface
 *
 * @filesource   CLIRunnerInterface.php
 * @created      03.04.2016
 * @package      chillerlan\Threema
 * @author       Smiley <smiley@chillerlan.net>
 * @copyright    2016 Smiley
 * @license      MIT
 */

namespace chillerlan\Threema;

/**
 * @todo
 */
interface CLIRunnerInterface{

	/**
	 * Generates a new key pair and optionally writes the keys to the respective files (in hex).
	 *
	 * @param  string $privateKeyFile  private key file path
	 * @param  string $publicKeyFile   public key file path
	 * @return string                  a string containing public and private key separated by PHP_EOL, or a message in case of file output.
	 */
	public function getKeypair(string $privateKeyFile = null, string $publicKeyFile = null):string;

	/**
	 * Hash an email address for identity lookup.
	 *
	 * @param string $email  a valid email address to hash
	 * @return string        the hashed email address in hex digits
	 */
	public function hashEmail(string $email):string;

	/**
	 * Hash a phone number for identity lookup.
	 *
	 * @param string $phoneNo  a phone number to lookup (E.164 format)
	 * @return string          the hashed phone number in hex digits
	 */
	public function hashPhone(string $phoneNo):string;

	/**
	 * Checks the remaining credits.
	 *
	 * @return string       remaining credits
	 */
	public function checkCredits():string;

	/**
	 * Fetch the capabilities of a Threema ID
	 *
	 * @param string $threemaID  a valid Threema ID
	 * @return string            capabilities: audio,file,image,video,text
	 */
	public function checkCapabilities(string $threemaID):string;

	/**
	 * Lookup the ID linked to the given email address (will be hashed locally).
	 *
	 * @param string $email  a valid email address to hash
	 * @return string        the Threema ID linked to the given email
	 */
	public function getIdByEmail(string $email):string;

	/**
	 * Lookup the ID linked to the given phone number (will be hashed locally).
	 *
	 * @param string $phoneNo  a phone number to lookup (E.164 format)
	 * @return string          the Threema ID linked to the given phone number
	 */
	public function getIdByPhone(string $phoneNo):string;

	/**
	 * Lookup the public key for the given ID.
	 *
	 * @param string $threemaID  a valid Threema ID
	 * @return string            the public key linked to the given Threema ID
	 */
	public function getPubkeyById(string $threemaID):string;

	/**
	 * Encrypts the given input file using the given sender private key and recipient public key and writes the output to the outputfile.
	 * 
	 * @param string $privateKey
	 * @param string $publicKey
	 * @param string $plaintextFile
	 * @param string $encryptedFile
	 *
	 * @return string
	 */
	public function encryptFile(string $privateKey, string $publicKey, string $plaintextFile, string $encryptedFile):string;

	/**
	 * Decrypts the given input file using the given recipient private key and sender public key and writes the output to the outputfile.
	 * 
	 * @param string $privateKey
	 * @param string $publicKey
	 * @param string $encryptedFile
	 * @param string $decryptedFile
	 *
	 * @return string
	 */
	public function decryptFile(string $privateKey, string $publicKey, string $encryptedFile, string $decryptedFile):string;

	/**
	 * Sends a message with server-side encryption to the given ID.
	 * 
	 * @param string $toThreemaID
	 * @param string $message
	 *
	 * @return string
	 */
	public function sendMessage(string $toThreemaID, string $message):string;
}
