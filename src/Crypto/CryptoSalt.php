<?php
/**
 * Class CryptoSalt
 *
 * @filesource   CryptoSalt.php
 * @created      02.04.2016
 * @package      chillerlan\Threema\Crypto
 * @author       Smiley <smiley@chillerlan.net>
 * @copyright    2016 Smiley
 * @license      MIT
 */

namespace chillerlan\Threema\Crypto;

/**
 * A Salt implementation in case libsodium is not available for some reason.
 * You should not use it in production.
 *
 * @todo
 */
abstract class CryptoSalt extends CryptoAbstract{

}
