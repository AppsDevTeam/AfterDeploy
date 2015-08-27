<?php

namespace ADT\Deployment;

require __DIR__ . '/../libs/Ansi.php';

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

	/**
	 * Remove $dir
	 * @param $dir
	 */
	protected function removeDirectory($dir) {

		if (is_dir($dir)) {
			$objects = scandir($dir);
			foreach ($objects as $object) {
				if ($object == "." || $object == ".." || $object == ".gitignore") continue;

				if (filetype($dir . "/" . $object) == "dir") {
					$this->removeDirectory($dir . "/" . $object);

				} else {
					unlink($dir . "/" . $object);
				}
			}
			reset($objects);
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
	 * Detect access via browser
	 * @return boolean
	 */
	protected function detectMode() {
		return empty($_SERVER["HTTP_USER_AGENT"]);
	}

	/**
	 * Install all packages/dependencies and clear cache
	 * @param string $tempDir
	 */
	public function run($tempDir) {
		putenv('PATH=' . system('echo $PATH')); // Bez tohoto nefunguje composer (ENOGIT), protože nefunguje `which git`, protože v php.ini není PATH nastavena.

		ob_start();

		$this->installComposerDeps();
		$this->installBowerDeps();

		// clear $tempDir
		if (isset($tempDir) && is_dir($tempDir)) {
			$this->removeDirectory($tempDir);

			if (!file_exists($tempDir)) {
				mkdir($tempDir);
			} else {
				$this->log("Temp dir <bgRed><white>was not fully removed<reset>.");
			}

			$i = new \FilesystemIterator($tempDir, \FilesystemIterator::SKIP_DOTS);
			if (iterator_count($i) == 0) {
				$this->log("Temp dir <bgGreen>cleared<reset>.");
			} else {
				$this->log("Temp dir <bgRed><white>was not cleared properly<reset>.");
			}

		} else {
			$this->log("Temp dir <cyan>is not defined<reset>.");
		}

		$this->resetCache();

		ob_clean();

		// send response to output
		$this->sendResponse();
		die;
	}

	/**
	 * Sends response to browser/CLI
	 */
	protected function sendResponse() {

		if ($this->detectMode()) {
			echo(Ansi::tagsToColors(implode(" ", $this->output)));

		} else {
			foreach ($this->commands as $command => $result) {
				echo "<br>\$ <strong>$command</strong>:<br>";
				echo nl2br($result);
			}

			echo "<br><br>" . Ansi::stripTags(implode(" ", $this->output));
		}
	}

}
