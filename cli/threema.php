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

use chillerlan\Threema\{
	CLIRunner, Crypto\CryptoSodium, Endpoint\TinyCurlEndpoint, GatewayOptions
};
use chillerlan\TinyCurl\{
	Request, RequestOptions
};

if(!is_cli()){
	exit('please run the Threema Gateway CLI tool in CLI mode only.');
}

$gatewayOptions                 = new GatewayOptions;
$gatewayOptions->configFilename = '.threema'; // @todo TRAVIS REMINDER!
$gatewayOptions->configPath     = __DIR__.'/../config';
$gatewayOptions->storagePath    = __DIR__.'/../storage';

$requestOptions          = new RequestOptions;
$requestOptions->ca_info = __DIR__.'/../storage/cacert.pem'; // https://curl.haxx.se/ca/cacert.pem

$CLIRunner = new CLIRunner(new TinyCurlEndpoint($gatewayOptions, new Request($requestOptions)), new CryptoSodium);

echo $CLIRunner->run($_SERVER['argv']);

exit;

/**
 * @return bool
 */
function is_cli(){
	return !isset($_SERVER['SERVER_SOFTWARE']) && (PHP_SAPI === 'cli' || (is_numeric($_SERVER['argc']) && $_SERVER['argc'] > 0));
}
