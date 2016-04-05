<?php
/**
 * @filesource   gateway.php
 * @created      05.04.2016
 * @author       Smiley <smiley@chillerlan.net>
 * @copyright    2015 Smiley
 * @license      MIT
 */

require_once __DIR__.'/../vendor/autoload.php';

use chillerlan\Threema\Crypto\CryptoSodium;
use chillerlan\Threema\Gateway;
use chillerlan\Threema\GatewayOptions;

$gatewayOptions                 = new GatewayOptions;
$gatewayOptions->configFilename = '.threema'; // @todo TRAVIS REMINDER!
$gatewayOptions->configPath     = __DIR__.'/../config';
$gatewayOptions->storagePath    = __DIR__.'/../storage';
$gatewayOptions->cacert         = __DIR__.'/../storage/cacert.pem';

$gateway = new Gateway(new CryptoSodium, $gatewayOptions);

$response = [];

if(isset($_POST['form']) && $_POST['form'] === 'encrypt'){
	$x =  $gateway->encrypt($_POST['message'], $_POST['private'], $_POST['public']);
	$response['result'] = $x->box.PHP_EOL.$x->nonce;
}





header('Content-type: application/json;charset=utf-8;');
header('Last-Modified: '.date('r'));
header('Expires: Tue, 23 May 1989 13:37:23 GMT');
header('Cache-Control: max-age=0, private, no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
header('Pragma: no-cache');

echo json_encode($response);
exit;
