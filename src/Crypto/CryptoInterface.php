<?php
/**
 * Interface CryptoInterface
 *
 * @filesource   CryptoInterface.php
 * @created      01.04.2016
 * @package      chillerlan\Threema\Crypto
 * @author       Smiley <smiley@chillerlan.net>
 * @copyright    2016 Smiley
 * @license      MIT
 */

namespace chillerlan\Threema\Crypto;

interface CryptoInterface{

	/**
	 * @return string
	 */
	public function version():string;

	/**
	 * @param string $bin binary
	 *
	 * @return string hex string
	 */
	public function bin2hex(string $bin):string;

	/**
	 * @param string $hex hex string
	 *
	 * @return string binary
	 */
	public function hex2bin(string $hex):string;

	/**
	 * @param int $length
	 *
	 * @return string binary
	 */
	public function getRandomBytes(int $length):string;

	/**
	 * @return string
	 */
	public function getPadBytes():string;

	/**
	 * @param string $str
	 * @param string $hmacKey binary
	 *
	 * @return string
	 */
	public function hmac_hash(string $str, string $hmacKey):string;

	/**
	 * @return array
	 */
	public function getKeypair():array;

	/**
	 * @param string      $data
	 * @param string      $privateKey hex string
	 * @param string      $publicKey  hex string
	 *
	 * @return array
	 */
	public function createBox(string $data, string $privateKey, string $publicKey):array;

	/**
	 * @param string $box hex string
	 * @param string $nonce hex string
	 * @param string $privateKey hex string
	 * @param string $publicKey hex string
	 *
	 * @return string
	 */
	public function openBox(string $box, string $nonce, string $privateKey, string $publicKey):string;

	/**
	 * @param string $data
	 * @param string $nonce
	 * @param string $key
	 *
	 * @return string
	 */
	public function createSecretBox(string $data, string $nonce, string $key):string;

	/**
	 * @param string $box
	 * @param string $nonce
	 * @param string $key
	 *
	 * @return string
	 */
	public function openSecretBox(string $box, string $nonce, string $key):string;

}
