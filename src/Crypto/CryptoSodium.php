<?php
/**
 * Class CryptoSodium
 *
 * @filesource   CryptoSodium.php
 * @created      01.04.2016
 * @package      chillerlan\Threema\Crypto
 * @author       Smiley <smiley@chillerlan.net>
 * @copyright    2016 Smiley
 * @license      MIT
 */

namespace chillerlan\Threema\Crypto;

class CryptoSodium extends CryptoAbstract{

	/**
	 * @inheritdoc
	 */
	public function version():string{
		return 'libsodium '.\Sodium\version_string();
	}

	/**
	 * @inheritdoc
	 */
	public function bin2hex(string $bin):string{
		return \Sodium\bin2hex($bin);
	}

	/**
	 * @inheritdoc
	 */
	public function hex2bin(string $hex):string{
		return \Sodium\hex2bin($hex);
	}

	/**
	 * @inheritdoc
	 */
	public function getRandomBytes(int $length):string{
		return \Sodium\randombytes_buf($length);
	}

	/**
	 * @inheritdoc
	 */
	public function getKeypair():array {
		$keypair = \Sodium\crypto_box_keypair();

		$pair = [
			'public'  => $this->bin2hex(\Sodium\crypto_box_publickey($keypair)),
			'private' => $this->bin2hex(\Sodium\crypto_box_secretkey($keypair)),
		];

		\Sodium\memzero($keypair);

		return $pair;
	}

	/**
	 * @inheritdoc
	 */
	public function createBox(string $data, string $privateKey, string $publicKey):array {

		if(empty($data)){
			throw new CryptoException('invalid data');
		}

		if(!preg_match('/^[a-f\d]{128}$/i', $privateKey.$publicKey)){
			throw new CryptoException('invalid keypair');
		}

		$keypair = \Sodium\crypto_box_keypair_from_secretkey_and_publickey($this->hex2bin($privateKey), $this->hex2bin($publicKey));

		\Sodium\memzero($privateKey);
		\Sodium\memzero($publicKey);

		$nonce = $this->getRandomBytes(\Sodium\CRYPTO_BOX_NONCEBYTES);
		$box = \Sodium\crypto_box($data, $nonce, $keypair);

		\Sodium\memzero($keypair);
		\Sodium\memzero($data);

		$encrypted = ['box' => $this->bin2hex($box), 'nonce' => $this->bin2hex($nonce)];

		\Sodium\memzero($nonce);
		\Sodium\memzero($box);

		return $encrypted;
	}

	/**
	 * @inheritdoc
	 */
	public function openBox(string $box, string $nonce, string $privateKey, string $publicKey):string{

		$keypair = \Sodium\crypto_box_keypair_from_secretkey_and_publickey(
			$this->hex2bin($privateKey),
			$this->hex2bin($publicKey)
		);

		\Sodium\memzero($privateKey);
		\Sodium\memzero($publicKey);

		$data = \Sodium\crypto_box_open($this->hex2bin($box), $this->hex2bin($nonce), $keypair);

		\Sodium\memzero($keypair);
		\Sodium\memzero($box);
		\Sodium\memzero($nonce);

		if(empty($data)){
			throw new CryptoException('decryption failed'); // @codeCoverageIgnore
		}

		return $data;
	}

	/**
	 * @inheritdoc
	 */
	public function createSecretBox(string $data, string $nonce, string $key):string{
		$box = \Sodium\crypto_secretbox($data, $nonce, $key);

		\Sodium\memzero($data);
		\Sodium\memzero($nonce);
		\Sodium\memzero($key);

		return $box;
	}

	/**
	 * @inheritdoc
	 */
	public function openSecretBox(string $box, string $nonce, string $key):string{
		$data = \Sodium\crypto_secretbox_open($box, $nonce, $key);

		\Sodium\memzero($box);
		\Sodium\memzero($nonce);
		\Sodium\memzero($key);

		return $data;
	}

}
