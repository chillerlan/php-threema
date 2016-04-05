<?php
/**
 * Class CLIRunner
 *
 * @filesource   CLIRunner.php
 * @created      01.04.2016
 * @package      chillerlan\Threema
 * @author       Smiley <smiley@chillerlan.net>
 * @copyright    2016 Smiley
 * @license      MIT
 */

namespace chillerlan\Threema;

use chillerlan\Threema\{
	Crypto\CryptoInterface, Endpoint\EndpointInterface
};
use ReflectionClass;
use ReflectionMethod;
use stdClass;

/**
 *
 */
class CLIRunner implements CLIRunnerInterface{

	/**
	 * @var array
	 */
	const COMMANDS = [
		// local
		'keypair'    => 'getKeypair',
		'hash_email' => 'hashEmail',
		'hash_phone' => 'hashPhone',
		'encrypt'    => 'encryptFile',
		'decrypt'    => 'decryptFile',
		// network
		'credits'    => 'checkCredits',
		'check'      => 'checkCapabilities',
		'email2id'   => 'getIdByEmail',
		'phone2id'   => 'getIdByPhone',
		'id2pubkey'  => 'getPubkeyById',
		'send'       => 'sendMessage',
	];

	/**
	 * @var \chillerlan\Threema\Crypto\CryptoInterface
	 */
	protected $cryptoInterface;

	/**
	 * @var \chillerlan\Threema\Endpoint\EndpointInterface
	 */
	protected $endpointInterface;

	/**
	 * @var \ReflectionClass
	 */
	protected $reflection;

	/**
	 * @var array[\ReflectionMethod]
	 */
	private $CLIRunnerInterfaceMap;

	/**
	 * CLIRunner constructor.
	 *
	 * @param \chillerlan\Threema\Endpoint\EndpointInterface $endpointInterface
	 * @param \chillerlan\Threema\Crypto\CryptoInterface     $cryptoInterface
	 */
	public function __construct(EndpointInterface $endpointInterface, CryptoInterface $cryptoInterface){
		$this->endpointInterface = $endpointInterface;
		$this->cryptoInterface   = $cryptoInterface;
		$this->reflection        = new ReflectionClass(CLIRunnerInterface::class);

		foreach($this->reflection->getMethods() as $method){
			$this->CLIRunnerInterfaceMap[$method->name] = $method;
		}
	}

	/**
	 * @param array $arguments $_SERVER['argc']
	 *
	 * @return string
	 */
	public function run(array $arguments):string{
		array_shift($arguments); // shift the scriptname off the top
		$command = strtolower(array_shift($arguments));

		if(array_key_exists($command, self::COMMANDS) && array_key_exists(self::COMMANDS[$command], $this->CLIRunnerInterfaceMap)){
			try{
				$method = $this->CLIRunnerInterfaceMap[self::COMMANDS[$command]];
				$method = new ReflectionMethod($this, $method->name);

				// @todo: check method arguments
				return $this->log2cli($method->invokeArgs($this, $arguments));
			}
			catch(GatewayException $gatewayException){
				return $this->log2cli('ERROR: '.$gatewayException->getMessage());
			}
		}

		return $this->log2cli($this->help());
	}

	/**
	 * output a string, wrap at 100 chars
	 *
	 * @param string $string string to output
	 *
	 * @return string
	 */
	protected function log2cli(string $string):string{
		return PHP_EOL.wordwrap($string, 78, PHP_EOL).PHP_EOL;
	}

	/**
	 * @return string
	 * @codeCoverageIgnore
	 */
	protected function readStdIn(){
		$stdin = fopen('php://stdin', 'r');
		$lines = [];

		while($line = trim(fgets($stdin))){

			if(strlen($line) === 0 || $line === "\n"){
				continue;
			}

			$lines[] = $line;
		}

		return implode("\n", $lines);
	}

	/**
	 * @param array $params
	 *
	 * @return \stdClass
	 */
	protected function parseDocBlock(array $params):stdClass{
		$parsed             = new stdClass;
		$parsed->paramNames = [];
		$parsed->paramDoc   = [];
		$parsed->returnDoc  = '';

		if(empty($params)){
			return $parsed; // @codeCoverageIgnore
		}

		foreach($params as $p){
			$p = explode(' ', $p, 2);
			if(isset($p[1])){
				if($p[0] === 'param'){
					$p                    = (explode(' ', trim($p[1]), 3));
					$name                 = '<'.trim($p[1], ' $').'>';
					$parsed->paramNames[] = $name;
					$doc                  = isset($p[2]) ? $name.' '.trim($p[2]) : $name;
					$parsed->paramDoc[]   = $doc;
				}
				else if($p[0] === 'return'){
					$p = explode(' ', trim($p[1]), 2);
					$parsed->returnDoc .= isset($p[1]) ? trim($p[1]) : '';
				}
			}
		}

		$parsed->paramNames = implode(' ', $parsed->paramNames);
		$parsed->paramDoc   = implode(PHP_EOL, $parsed->paramDoc);

		return $parsed;
	}

	/**
	 * @return string
	 */
	public function help():string{
		// return info in case no command was found
		$help = 'Threema Gateway CLI tool.'.PHP_EOL;
		$help .= 'Crypto: '.$this->cryptoInterface->version().PHP_EOL.PHP_EOL;

		foreach(self::COMMANDS as $command => $method){
			$comment = $this->reflection->getMethod($method)->getDocComment();
			$comment = str_replace(["\t", ' *'], '', substr($comment, 3, -2));

			$params  = explode('@', $comment);
			$comment = trim(array_shift($params));
			$parsed  = $this->parseDocBlock($params);

			$help .= PHP_EOL.'threema.php '.$command.' '.$parsed->paramNames.PHP_EOL;
			$help .= str_repeat('-', strlen($command) + 12).PHP_EOL;
			$help .= PHP_EOL.$comment.PHP_EOL;
			$help .= PHP_EOL.$parsed->paramDoc.PHP_EOL;
			$help .= PHP_EOL.'Returns: '.$parsed->returnDoc.PHP_EOL.PHP_EOL;
		}

		return $help;
	}

	/**
	 * @param string $path
	 * @param string $data
	 *
	 * @return string
	 */
	protected function writeFile(string $path, string $data):string{
		// @todo: check writable
		if(is_dir(dirname($path))){
			$bytes = file_put_contents($path, $data);
			return $bytes.' bytes written to: '.$path.PHP_EOL;
		}
		// or is not writable
		return $path.' does not exist'; // @codeCoverageIgnore
	}

	/**
	 * @inheritdoc
	 */
	public function getKeypair(string $privateKeyFile = null, string $publicKeyFile = null):string{
		$keypair  = $this->cryptoInterface->getKeypair();
		$message  = !empty($privateKeyFile) ? $this->writeFile($privateKeyFile, $keypair->privateKey) : '';
		$message .= !empty($publicKeyFile)  ? $this->writeFile($publicKeyFile, $keypair->publicKey)   : '';

		return $message.PHP_EOL.'private:'.$keypair->privateKey.PHP_EOL.'public:'.$keypair->publicKey;
	}

	/**
	 * @inheritdoc
	 */
	public function hashEmail(string $email):string{
		return $this->cryptoInterface->hmac_hash($email, EndpointInterface::HMAC_KEY_EMAIL_BIN);
	}

	/**
	 * @inheritdoc
	 */
	public function hashPhone(string $phoneNo):string{
		return $this->cryptoInterface->hmac_hash($phoneNo, EndpointInterface::HMAC_KEY_PHONE_BIN);
	}

	/**
	 * @inheritdoc
	 */
	public function checkCredits():string{
		return $this->endpointInterface->checkCredits();
	}

	/**
	 * @inheritdoc
	 */
	public function checkCapabilities(string $threemaID):string{
		return implode(',', $this->endpointInterface->checkCapabilities($threemaID));
	}

	/**
	 * @inheritdoc
	 */
	public function getIdByEmail(string $email):string{
		return $this->endpointInterface->getIdByEmailHash($this->hashEmail($email));
	}

	/**
	 * @inheritdoc
	 */
	public function getIdByPhone(string $phoneNo):string{
		return $this->endpointInterface->getIdByPhoneHash($this->hashPhone($phoneNo));
	}

	/**
	 * @inheritdoc
	 */
	public function getPubkeyById(string $threemaID):string{
		return $this->endpointInterface->getPublicKey($threemaID);
	}

	/**
	 * @inheritdoc
	 */
	public function encryptFile(string $privateKey, string $publicKey, string $plaintextFile, string $encryptedFile):string{
		$encrypted = $this->cryptoInterface->encrypt(file_get_contents($plaintextFile), $privateKey, $publicKey);
		return $this->writeFile($encryptedFile, $encrypted->nonce."\n".$encrypted->box);
	}

	/**
	 * @inheritdoc
	 */
	public function decryptFile(string $privateKey, string $publicKey, string $encryptedFile, string $decryptedFile):string{
		$data = explode("\n", file_get_contents($encryptedFile));
		return $this->writeFile($decryptedFile, $this->cryptoInterface->decrypt(trim($data[1]), trim($data[0]), $privateKey, $publicKey));
	}

	/**
	 * @inheritdoc
	 */
	public function sendMessage(string $toThreemaID, string $message):string{
		// TODO: Implement sendMessage() method.
	}
}
