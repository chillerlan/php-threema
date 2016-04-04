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
	 * @param string $privateKeyFile private key file path
	 * @param string $publicKeyFile  public key file path
	 *
	 * @return string a string containing public and private key separated by PHP_EOL, or a message in case of file output.
	 */
	public function getKeypair(string $privateKeyFile = null, string $publicKeyFile = null):string;

	/**
	 * Hash an email address for identity lookup.
	 *
	 * @param string $email  a valid email address to hash
	 *
	 * @return string        the hashed email address in hex digits
	 */
	public function hashEmail(string $email):string;

	/**
	 * Hash a phone number for identity lookup.
	 *
	 * @param string $phoneNo  a phone number to lookup (E.164 format)
	 *
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
	 * @param string $threemaID a valid Threema ID
	 *
	 * @return string capabilities: audio,file,image,video,text
	 */	
	public function checkCapabilities(string $threemaID):string;
}
