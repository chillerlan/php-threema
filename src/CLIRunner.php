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

use chillerlan\Threema\Crypto\CryptoInterface;
use ReflectionClass;
use ReflectionMethod;

/**
 * 
 */
class CLIRunner implements CLIRunnerInterface{

	/**
	 * @var array
	 */
	const COMMANDS = [
		// local
		'keypair'      => 'getKeypair',
		'hash_email'   => 'hashEmail',
		'hash_phone'   => 'hashPhone',
#		'encrypt'      => '',
#		'decrypt'      => '',
		// network
		'credits'      => 'checkCredits',
		'check'        => 'checkCapabilities',
#		'idbyemail'    => '',
#		'idbyphone'    => '',
#		'keybyid'      => '',
#		'send'         => '',
#		'sende2e'      => '',
#		'sendimage'    => '',
#		'sendfile'     => '',
#		'receive'      => '',
	];

	/**
	 * @var \chillerlan\Threema\Crypto\CryptoInterface
	 */
	protected $cryptoInterface;

	/**
	 * @var \chillerlan\Threema\GatewayOptions
	 */
	protected $gatewayOptions;

	/**
	 * @var \chillerlan\Threema\Gateway
	 */
	protected $threemaGateway;

	/**
	 * @var \ReflectionClass
	 */
	protected $reflection;

	/**
	 * @var array[\ReflectionMethod]
	 */
	private   $CLIRunnerInterfaceMap;

	/**
	 * CLIRunner constructor.
	 *
	 * @param \chillerlan\Threema\Crypto\CryptoInterface $cryptoInterface
	 * @param \chillerlan\Threema\GatewayOptions         $gatewayOptions
	 */
	public function __construct(CryptoInterface $cryptoInterface, GatewayOptions $gatewayOptions){
		$this->cryptoInterface = $cryptoInterface;
		$this->gatewayOptions  = $gatewayOptions;
		$this->threemaGateway  = new Gateway($this->cryptoInterface, $gatewayOptions);
		$this->reflection      = new ReflectionClass(CLIRunnerInterface::class);

		foreach($this->reflection ->getMethods() as $method){
			$this->CLIRunnerInterfaceMap[$method->name] = $method;
		}
	}

	/**
	 * @inheritdoc
	 */
	public function run(array $arguments):string{
		/** @noinspection PhpUnusedLocalVariableInspection */
		$scriptName = basename(array_shift($arguments));
		$command    = strtolower(array_shift($arguments));

		if(array_key_exists($command, self::COMMANDS) && array_key_exists(self::COMMANDS[$command], $this->CLIRunnerInterfaceMap)){
			try{
				$method = $this->CLIRunnerInterfaceMap[self::COMMANDS[$command]];
				$method = new ReflectionMethod($this, $method->name);

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
	 * @inheritdoc
	 */
	public function help():string{
		// return info in case no command was found
		$help  = 'Threema Gateway CLI tool.'.PHP_EOL;
		$help .= 'Crypto: '.$this->threemaGateway->cryptoVersion().PHP_EOL.PHP_EOL;

		foreach(self::COMMANDS as $command => $method){
			$comment = $this->reflection->getMethod($method)->getDocComment();
			$comment = str_replace(["\t", ' *'], '', substr($comment, 3, -2));

			$params  = explode('@', $comment);
			$comment = trim(array_shift($params));

			$paramNames = [];
			$paramDoc = [];
			$returnDoc = '';
			if(count($params) > 0){
				foreach($params as $p){
					$p = explode(' ', $p, 2);
					if(isset($p[1])){
						if($p[0] === 'param'){
							$p = (explode(' ', trim($p[1]), 3));
							$paramNames[] = '<'.trim($p[1], ' $').'>';
							$d =  trim($p[1]);
#							$d .= ' ('.trim($p[0]).'): ';
							$d .= isset($p[2]) ? ': '.trim($p[2]) : '';
							$paramDoc[] = $d;
						}
						else if($p[0] === 'return'){
							$p = explode(' ', trim($p[1]), 2);
#							$returnDoc  = '('.trim($p[0]).') ';
							$returnDoc .= isset($p[1]) ? trim($p[1]) : '';
						}
					}

				}
			}

			$help .= PHP_EOL.'threema.php '.$command.' '.implode(' ', $paramNames).PHP_EOL;
			$help .= str_repeat('-', strlen($command)+12).PHP_EOL;
			$help .= PHP_EOL.$comment.PHP_EOL;
			$help .= PHP_EOL.implode(PHP_EOL, $paramDoc).PHP_EOL;
			$help .= PHP_EOL.'Returns: '.$returnDoc.PHP_EOL;
		}

		return $help;
	}

	/**
	 * @inheritdoc
	 * @todo file output
	 */
	public function getKeypair(string $privateKeyFile = null, string $publicKeyFile = null):string{
		$keypair = $this->cryptoInterface->getKeypair();

		return 'private:'.$keypair->privateKey.PHP_EOL.'public:'.$keypair->publicKey;
	}

	/**
	 * @inheritdoc
	 */
	public function hashEmail(string $email):string{
		return $this->cryptoInterface->hmac_hash($email, GatewayInterface::HMAC_KEY_EMAIL_BIN);
	}

	/**
	 * @inheritdoc
	 */
	public function hashPhone(string $phoneNo):string{
		return $this->cryptoInterface->hmac_hash($phoneNo, GatewayInterface::HMAC_KEY_PHONE_BIN);
	}

	/**
	 * @inheritdoc
	 */
	public function checkCredits():string{
		return $this->threemaGateway->checkCredits();
	}

	/**
	 * @inheritdoc
	 */
	public function checkCapabilities(string $threemaID):string{
		return implode(',', $this->threemaGateway->checkCapabilities($threemaID));
	}
}
