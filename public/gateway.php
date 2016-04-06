<?php
/**
 * @filesource   gateway.php
 * @created      05.04.2016
 * @author       Smiley <smiley@chillerlan.net>
 * @copyright    2015 Smiley
 * @license      MIT
 */

require_once __DIR__.'/../vendor/autoload.php';

use chillerlan\Threema\{
	Crypto\CryptoSodium, Endpoint\TinyCurlEndpoint, Gateway, GatewayOptions
};
use chillerlan\TinyCurl\{
	Request, RequestOptions
};

$gatewayOptions                 = new GatewayOptions;
$gatewayOptions->configFilename = '.threema'; // @todo TRAVIS REMINDER!
$gatewayOptions->configPath     = __DIR__.'/../config';
$gatewayOptions->storagePath    = __DIR__.'/../storage';

$requestOptions                 = new RequestOptions;
$requestOptions->ca_info        = __DIR__.'/../storage/cacert.pem'; // https://curl.haxx.se/ca/cacert.pem

$gateway         = new Gateway(new TinyCurlEndpoint($gatewayOptions, new Request($requestOptions)));
$cryptoInterface = new CryptoSodium;
$form            = $_GET['form'] ?? false;
$response        = [];

if($form && in_array($form, ['encrypt'], true)){
	switch($form){
		case 'encrypt':
			$response['encrypt'] = $cryptoInterface->encrypt($_GET['message'], $_GET['private'], $_GET['public']);
			break;
	}
}

header('Content-type: application/json;charset=utf-8;');
header('Last-Modified: '.date('r'));
header('Expires: Tue, 23 May 1989 13:37:23 GMT');
header('Cache-Control: max-age=0, private, no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
header('Pragma: no-cache');

echo json_encode($response);
exit;
