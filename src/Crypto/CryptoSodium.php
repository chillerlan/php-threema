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

use stdClass;

/**
 * 
 */
class CryptoSodium extends CryptoAbstract{

	public function version():string{
		/** @noinspection PhpUndefinedNamespaceInspection @noinspection PhpUndefinedFunctionInspection */
		return 'libsodium '.\Sodium\version_string();
	}

	/**
	 * @inheritdoc
	 */
	public function bin2hex(string $bin):string{
		/** @noinspection PhpUndefinedNamespaceInspection @noinspection PhpUndefinedFunctionInspection */
		return \Sodium\bin2hex($bin);
	}

	/**
	 * @inheritdoc
	 */
	public function hex2bin(string $hex):string{
		/** @noinspection PhpUndefinedNamespaceInspection @noinspection PhpUndefinedFunctionInspection */
		return \Sodium\hex2bin($hex);
	}

	/**
	 * @inheritdoc
	 */
	public function getRandomBytes(int $length):string{
		/** @noinspection PhpUndefinedNamespaceInspection @noinspection PhpUndefinedFunctionInspection */
		return \Sodium\randombytes_buf($length);
	}

	/**
	 * @inheritdoc
	 */
	public function getKeypair():stdClass{
		$pair = new stdClass;

		/** @noinspection PhpUndefinedNamespaceInspection @noinspection PhpUndefinedFunctionInspection */
		$keypair = \Sodium\crypto_box_keypair();

		/** @noinspection PhpUndefinedNamespaceInspection @noinspection PhpUndefinedFunctionInspection */
		$pair->privateKey = $this->bin2hex(\Sodium\crypto_box_secretkey($keypair));
		/** @noinspection PhpUndefinedNamespaceInspection @noinspection PhpUndefinedFunctionInspection */
		$pair->publicKey  = $this->bin2hex(\Sodium\crypto_box_publickey($keypair));

		/** @noinspection PhpUndefinedNamespaceInspection @noinspection PhpUndefinedFunctionInspection */
		\Sodium\memzero($keypair);

		return $pair;
	}

	/**
	 * @inheritdoc
	 */
	public function encrypt(string $data, string $privateKey, string $publicKey):stdClass {

		if(empty($data)){
			throw new CryptoException('invalid data');
		}

		if(!preg_match('/^[a-f\d]{128}$/i', $privateKey.$publicKey)){
			throw new CryptoException('invalid keypair');
		}

		/** @noinspection PhpUndefinedNamespaceInspection @noinspection PhpUndefinedFunctionInspection */
		$keypair = \Sodium\crypto_box_keypair_from_secretkey_and_publickey($this->hex2bin($privateKey), $this->hex2bin($publicKey));
		/** @noinspection PhpUndefinedNamespaceInspection @noinspection PhpUndefinedFunctionInspection */
		\Sodium\memzero($privateKey);
		/** @noinspection PhpUndefinedNamespaceInspection @noinspection PhpUndefinedFunctionInspection */
		\Sodium\memzero($publicKey);

		/** @noinspection PhpUndefinedNamespaceInspection @noinspection PhpUndefinedConstantInspection */
		$nonce = $this->getRandomBytes(\Sodium\CRYPTO_BOX_NONCEBYTES);
		/** @noinspection PhpUndefinedNamespaceInspection @noinspection PhpUndefinedFunctionInspection */
		$box = \Sodium\crypto_box($data, $nonce, $keypair);

		/** @noinspection PhpUndefinedNamespaceInspection @noinspection PhpUndefinedFunctionInspection */
		\Sodium\memzero($keypair);
		/** @noinspection PhpUndefinedNamespaceInspection @noinspection PhpUndefinedFunctionInspection */
		\Sodium\memzero($data);

		$encrypted        = new \stdClass;
		$encrypted->nonce = $this->bin2hex($nonce);
		$encrypted->box   = $this->bin2hex($box);

		/** @noinspection PhpUndefinedNamespaceInspection @noinspection PhpUndefinedFunctionInspection */
		\Sodium\memzero($nonce);
		/** @noinspection PhpUndefinedNamespaceInspection @noinspection PhpUndefinedFunctionInspection */
		\Sodium\memzero($box);

		return $encrypted;
	}

	/**
	 * @inheritdoc
	 */
	public function decrypt(string $box, string $nonce, string $privateKey, string $publicKey):string{
		/** @noinspection PhpUndefinedNamespaceInspection @noinspection PhpUndefinedFunctionInspection */
		$keypair = \Sodium\crypto_box_keypair_from_secretkey_and_publickey($this->hex2bin($privateKey), $this->hex2bin($publicKey));
		/** @noinspection PhpUndefinedNamespaceInspection @noinspection PhpUndefinedFunctionInspection */
		\Sodium\memzero($privateKey);
		/** @noinspection PhpUndefinedNamespaceInspection @noinspection PhpUndefinedFunctionInspection */
		\Sodium\memzero($publicKey);

		/** @noinspection PhpUndefinedNamespaceInspection @noinspection PhpUndefinedFunctionInspection */
		$data = \Sodium\crypto_box_open($this->hex2bin($box), $this->hex2bin($nonce), $keypair);
		/** @noinspection PhpUndefinedNamespaceInspection @noinspection PhpUndefinedFunctionInspection */
		\Sodium\memzero($keypair);
		/** @noinspection PhpUndefinedNamespaceInspection @noinspection PhpUndefinedFunctionInspection */
		\Sodium\memzero($box);
		/** @noinspection PhpUndefinedNamespaceInspection @noinspection PhpUndefinedFunctionInspection */
		\Sodium\memzero($nonce);

		if(empty($data)){
			throw new CryptoException('decryption failed'); // @codeCoverageIgnore
		}

		return $data;
	}

}
