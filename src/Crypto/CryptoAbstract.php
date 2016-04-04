<?php
/**
 * Class CryptoAbstract
 *
 * @filesource   CryptoAbstract.php
 * @created      02.04.2016
 * @package      chillerlan\Threema\Crypto
 * @author       Smiley <smiley@chillerlan.net>
 * @copyright    2016 Smiley
 * @license      MIT
 */

namespace chillerlan\Threema\Crypto;

/**
 * 
 */
abstract class CryptoAbstract implements CryptoInterface{

	/**
	 * @inheritdoc
	 */
	public function bin2hex(string $bin):string{
		return bin2hex($bin);
	}

	/**
	 * @inheritdoc
	 */
	public function hex2bin(string $hex):string{
		return hex2bin($hex);
	}

	/**
	 * @inheritdoc
	 */
	public function getRandomBytes(int $length):string{
		return random_bytes($length);
	}

	/**
	 * @inheritdoc
	 */
	public function hmac_hash(string $str, string $hmacKey):string{
		return hash_hmac('sha256', $str, $hmacKey);
	}

	/**
	 * @inheritdoc
	 */
	public function getPadBytes():string {
		$padbytes = 0;
		
		while($padbytes < 1 || $padbytes > 255){
			$padbytes = ord($this->getRandomBytes(1));
		}

		return str_repeat(chr($padbytes), $padbytes);
	}

}
