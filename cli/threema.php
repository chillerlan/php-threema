<?php
/**
 * @filesource   threema.php
 * @created      02.04.2016
 * @author       Smiley <smiley@chillerlan.net>
 * @copyright    2016 Smiley
 * @license      MIT
 */

namespace chillerlan\ThreemaCLI;

require_once __DIR__.'/../vendor/autoload.php';

use chillerlan\Threema\CLIRunner;
use chillerlan\Threema\Crypto\CryptoSodium;
use chillerlan\Threema\GatewayOptions;

if(!is_cli()){
	exit('please run the Threema Gateway CLI tool in CLI mode only.');
}

$gatewayOptions                 = new GatewayOptions;
$gatewayOptions->configFilename = '.threema'; // @todo TRAVIS REMINDER!
$gatewayOptions->configPath     = __DIR__.'/../config';
$gatewayOptions->storagePath    = __DIR__.'/../storage';
$gatewayOptions->cacert         = __DIR__.'/../storage/cacert.pem';

echo (new CLIRunner(new CryptoSodium, $gatewayOptions))->run($_SERVER['argv']);

exit;

/**
 * @return bool
 */
function is_cli(){
	return !isset($_SERVER['SERVER_SOFTWARE']) && (PHP_SAPI === 'cli' || (is_numeric($_SERVER['argc']) && $_SERVER['argc'] > 0));
}
