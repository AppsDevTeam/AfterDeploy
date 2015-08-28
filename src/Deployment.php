<?php

namespace ADT\Deployment;

use Nette\Utils\Finder;

/**
 * Class Deployment
 * @package ADT\Deployment
 */
class Deployment {

	/** @var array */
	protected $commands = [];

	/** @var array  */
	protected $output = [];

	/** @var bool */
	protected $cliMode = TRUE;

	public static function onStartup($config) {

		if (\Tracy\Debugger::$productionMode) {
			return;
		}

		if (! isset($_GET[$config['key']])) {
			return;
		}

		(new static)->run($config['tempDir']);
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
	protected function cmd($cmd, $store = TRUE) {
		exec("cd ../ && $cmd", $output);
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
		return $this->output[] = $string;
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
		// checks if bower is installed
		$version = preg_match("/Composer version .+/", $this->cmd("composer -V", TRUE), $match);

		if ($version) {
			$this->cmd("composer install -o -n --no-dev 2>&1", TRUE);
			return $this->log("Composer <bgGreen>installed<reset>.");
		}

		return $this->log("Composer <bgRed>is not installed<reset>.");
	}

	/**
	 * Clears APC and OpCache
	 */
	protected function resetCache() {
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
	 * Detect access via console
	 * @return boolean
	 */
	protected function isTerminalMode() {
		return empty($_SERVER["HTTP_USER_AGENT"]);
	}

	/**
	 * Install all packages/dependencies and clear cache
	 * @param string $tempDir
	 */
	public function run($tempDir = NULL) {
		ob_start();

		putenv('PATH=' . system('echo $PATH')); // Bez tohoto nefunguje composer (ENOGIT), protože nefunguje `which git`, protože v php.ini není PATH nastavena.

		$this->installComposerDeps();
		$this->installBowerDeps();

		// clear $tempDir
		if (isset($tempDir) && is_dir($tempDir)) {
			$this->removeDirectory($tempDir);

			if (Finder::findFiles('*')->exclude('.gitignore')->in($tempDir)->count()) {
				$this->log("Temp dir <bgRed>was not cleared properly<reset>.");
			} else {
				$this->log("Temp dir <bgGreen>cleared<reset>.");
			}

		} else {
			$this->log("Temp dir <cyan>is not defined<reset>.");
		}

		$this->resetCache();

		ob_clean();

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
		$out .= "\n\n" . implode("\n", $this->output) . "\n";
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
