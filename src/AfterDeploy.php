<?php

namespace ADT\AfterDeploy;

/**
 * Class AfterDeploy
 * @package ADT\AfterDeploy
 */
class AfterDeploy {

	/**
	 * Default $_GET key
	 */
	const DEFAULT_KEY = 'afterDeploy';

	/** @var string */
	protected static $key = self::DEFAULT_KEY;

	/** @var int seconds */
	protected static $sleep = 1;

	/** @var string */
	protected static $wwwDir;

	/** @var int|bool */
	protected static $useMaintenance = 0;

	/** @var array */
	protected $commands = [];

	/**
	 * Je to statická proměnná, protože se tato třídá používá
	 * na začátku bootstrapu před autoloadem a poté po spuštění aplikace
	 * jako extension. V obou máme texty, které chceme vypisovat,
	 * proto je musíme spojit a vypsat až později.
	 *
	 * @var array
	 */
	protected static $output = [];

	/** @var bool */
	protected $cliMode = TRUE;

	public static function onStartup($config) {
		$config['key'] = static::$key;
		$config['wwwDir'] = static::$wwwDir;
		$config['useMaintenance'] = static::$useMaintenance;
		$config['sleep'] = static::$sleep;

		if (!static::shouldStartDeploy($config)) {
			return;
		}

		(new static)->run($config);
	}

	/**
	 * Remove $dir
	 * @param $dir
	 */
	protected function removeDirectory($dir, $rmDir = FALSE) {

		if (!is_dir($dir)) return;

		foreach (scandir($dir) as $object) {
			if ($object === "." || $object === ".." || $object === ".gitignore") continue;

			if (filetype($dir . "/" . $object) === "dir") {
				$this->removeDirectory($dir . "/" . $object, TRUE);

			} else {
				unlink($dir . "/" . $object);
			}
		}

		if ($rmDir) {
			rmdir($dir);
		}
	}

	/**
	 * Make system command in root
	 * @param $cmd
	 * @param bool $store
	 * @return string
	 */
	protected function cmd($cmd, $store = TRUE, &$returnVar = NULL) {
		exec("cd ../ && $cmd", $output, $returnVar);

		$output = implode("\n", $output);

		if ($store) {
			$this->commands[$cmd] = $output;
		}

		return $output;
	}

	/**
	 * @param string $string
	 * @return string
	 */
	protected function log($string) {
		return static::$output[] = $string;
	}

	/**
	 * Install packages/dependencies via bower
	 */
	protected function installBowerDeps() {

		// checks if bower is installed
		if (preg_match("/([0-9\.]+)/", $this->cmd("bower -v", TRUE))) {

			$bower = $this->cmd('bower install --production 2>&1', TRUE);

			if (!empty($bower)) {
				return $this->log("Bower <bgGreen>installed<reset>.");
			}

			return $this->log("Bower <yellow>nothing to install<reset>.");
		}

		return $this->log("Bower <bgRed>is not installed<reset>!");
	}

	/**
	 * Install packages/dependencies via composer
	 */
	protected function installComposerDeps() {
		// checks if composer is installed
		$version = preg_match("/Composer version .+/", $this->cmd("composer -V --no-ansi", TRUE), $match);

		if ($version) {
			$this->cmd("composer install -o -n --no-dev 2>&1", TRUE, $return);
			if ($return === 0) {
				return $this->log("Composer <bgGreen>installed<reset>.");
			} else {
				return $this->log("Composer <bgRed>install failed<reset>.");
			}
		}

		return $this->log("Composer <bgRed>is not installed<reset>.");
	}

	/**
	 * Clears APC and OpCache
	 */
	protected function resetAPCandOPCache() {
		// checks if exists opcache
		if (function_exists("opcache_reset")) {
			$reset = opcache_reset();

			if ($reset) {
				$this->log("OPCache <bgGreen>cleared<reset>.");
			}
		}

		// checks if exists apc cache
		if (function_exists("apc_clear_cache")) {
			apc_clear_cache();
			apc_clear_cache("user");
			$this->log("APC <bgGreen>cleared<reset>.");
		}
	}

	/**
	 * Clears tempDir
	 * @param array $config
	 */
	protected function clearCache($config = []) {
		// clear tempDir
		if (isset($config['tempDir']) && is_dir($config['tempDir'])) {
			$this->removeDirectory($config['tempDir']);

			$count = 0;
			foreach (scandir($config['tempDir']) as $object) {
				if ($object === "." || $object === ".." || $object === ".gitignore") continue;

				$count++;
			}

			if ($count !== 0) {
				$this->log("Temp dir <bgRed>was not cleared properly<reset>.");
			} else {
				$this->log("Temp dir <bgGreen>cleared<reset>.");
			}

		} else {
			$this->log("Temp dir <cyan>is not defined<reset>.");
		}
	}

	/**
	 * Removes email-sent file from Log dir
	 * @param array $config
	 */
	protected function clearEmailSent($config = []) {
		// clear tempDir
		if (isset($config['logDir']) && is_dir($config['logDir'])) {
			$count = 0;
			if (file_exists($config['logDir'] . "/email-sent")) {
				unlink($config['logDir'] . "/email-sent");
				$count++;
			}

			if ($count === 0) {
				$this->log("Email-sent file in Log dir <bgRed>was not found<reset>.");
			} else {
				$this->log("Email-sent file in Log dir <bgGreen>cleared<reset>.");
			}
		} else {
			$this->log("Log dir for removing email-sent file <cyan>is not defined<reset>.");
		}
	}

	/**
	 * Clear Redis
	 */
	protected function clearRedis($redis = []) {
		if (!class_exists('\Kdyby\Redis\RedisClient')) {
			return;
		}

		if (!$redis['client'] instanceof \Kdyby\Redis\RedisClient || empty($redis['dbs'])) {
			return;
		}

		/** @var \Kdyby\Redis\RedisClient */
		$client = $redis['client'];

		foreach ($redis['dbs'] as $dbIndex) {
			if ($client->select($dbIndex)) {
				$client->flushDb();
			}
		}

		$this->log("Redis <bgGreen>cleared<reset>.");
	}

	/**
	 * Detect access via console
	 * @return boolean
	 */
	protected function isTerminalMode() {
		return empty($_SERVER["HTTP_USER_AGENT"]);
	}

	/**
	 * @param array $config
	 * @return bool
	 */
	protected static function shouldStartDeploy($config) {
		static::$key = !empty($config['key']) ? $config['key'] : static::DEFAULT_KEY;
		return isset($_GET[static::$key]);
	}

	protected static function shouldUseMaintenance($config) {
		return static::$useMaintenance = isset($config['useMaintenance']) ? $config['useMaintenance'] : FALSE;
	}

	/**
	 * @param array $config
	 */
	protected function toggleMaintenance($config) {
		if (empty(static::$wwwDir)) {
			if (!isset($config['wwwDir']) || !is_dir($config['wwwDir'])) {
				if (isset($config['tempDir']) && is_dir($config['tempDir'])) {
					$config['wwwDir'] = $config['tempDir'] . '/../www';
				} else {
					return;
				}
			}

			static::$wwwDir = $config['wwwDir'];
		}

		foreach (scandir(static::$wwwDir) as $object) {
			if ($object === ".maintenance.php") {
				rename(static::$wwwDir . '/' . $object, static::$wwwDir . '/maintenance.php');
			} else if ($object === "maintenance.php") {
				rename(static::$wwwDir . '/' . $object, static::$wwwDir . '/.maintenance.php');
			}
		}
	}

	/**
	 * @param array $config
	 */
	public function sleep($config) {
		if (isset($config['sleep'])) {
			static::$sleep = $config['sleep'];
		}

		sleep(static::$sleep);
	}

	/**
	 * Run this function in bootstrap.php before autoload.php
	 * Install all packages/dependencies and clear cache
	 * @param array $config
	 */
	public function runBase($config = []) {
		if (!static::shouldStartDeploy($config)) {
			return;
		}

		if (static::shouldUseMaintenance($config)) {
			$this->toggleMaintenance($config);
			$this->sleep($config);
		}

		ob_start();

		putenv('PATH=' . system('echo $PATH')); // Bez tohoto nefunguje composer (ENOGIT), protože nefunguje `which git`, protože v php.ini není PATH nastavena.

		$this->installComposerDeps();
		$this->installBowerDeps();

		$this->clearCache($config);
		$this->resetAPCandOPCache();
		$this->clearEmailSent($config);

		ob_clean();

		// die je až v run(), který se spustí po spuštění aplikace
	}

	/**
	 * Clear cache
	 * @param array $config
	 */
	public function run($config = []) {
		if (!static::shouldStartDeploy($config)) {
			return;
		}

		ob_start();

		if (isset($config['redis'])) {
			$this->clearRedis($config['redis']);
		}

		$this->clearCache($config);

		ob_clean();

		if (static::shouldUseMaintenance($config)) {
			$this->toggleMaintenance($config);
			static::$wwwDir = NULL;
		}

		// send response to output
		$this->sendResponse();

		// Nebudeme spouštět laděnku (bar), protože ta většinou potřebuje spoustu služeb a ty zase tempDir a tu jsme promazali.
		\Tracy\Debugger::$productionMode = TRUE;

		die;
	}

	/**
	 * Sends response to browser/CLI
	 */
	protected function sendResponse() {

		$out = '';
		foreach ($this->commands as $command => $result) {
			$out .= "<bgBlue>$ $command:<reset>". "\n$result\n";
		}
		$out .= "\n\n" . implode("\n", static::$output) . "\n";
		$out = \Ansi::tagsToColors($out);

		if ($this->isTerminalMode()) {
			echo $out;
		} else {

			$converter = new \SensioLabs\AnsiConverter\AnsiToHtmlConverter();
			echo "<style> body { background:black; } </style>";
			echo nl2br($converter->convert($out));
		}
	}

}
