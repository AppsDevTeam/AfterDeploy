<?php

namespace ADT\Deployment;

/**
 * Class Deployment
 * @package ADT\Deployment
 */
class Deployment {

	/** @var array */
	protected static $commands = [];

	/** @var array  */
	protected static $output = [];

	/** @var bool */
	protected static $cliMode = TRUE;

	/**
	 * Remove $dir
	 * @param $dir
	 */
	public static function removeDirectory($dir) {

		if (is_dir($dir)) {
			$objects = scandir($dir);
			foreach ($objects as $object) {
				if ($object != "." && $object != ".." && $object != ".gitignore") {
					if (filetype($dir."/".$object) == "dir")
						self::removeDirectory($dir."/".$object);
					else unlink($dir."/".$object);
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
	public static function cmd($cmd, $store = TRUE) {
		$r = system("cd ../ && $cmd");

		if($store)
			self::$commands[$cmd] = $r;

		return $r;
	}

	/**
	 * @param string $string
	 * @return string
	 */
	public static function log($string) {
		return self::$output[] = $string;
	}

	/**
	 * Install packages/dependencies via bower
	 */
	public static function installBowerDeps() {

		// checks if bower is installed
		if(preg_match("/([0-9\.]+)/", self::cmd("bower -v", TRUE))) {

			$bower = self::cmd('bower install --production');

			if(!empty($bower))
				return self::log("Bower <bgGreen>installed<reset>.");
			return self::log("Bower <yellow>nothing to install<reset>.");
		}

		return self::log("Bower <bgRed>is not installed<reset>!");
	}

	/**
	 * Install packages/dependencies via composer
	 */
	public static function installComposerDeps() {
		// checks if bower is installed
		$version = preg_match("/Composer version .+/", self::cmd("composer -V"), $match);

		if($version) {
			self::cmd("composer install -o -n --no-dev");
			return self::log("Composer <bgGreen>installed<reset>.");
		}

		return self::log("Composer <bgRed>is not installed<reset>.");

	}

	/**
	 * Clears APC and OpCache
	 */
	public static function resetCache() {
		// checks if exists opcache
		if(function_exists("opcache_reset")) {
			$reset = opcache_reset();

			if($reset)
				self::log("OPCache <bgGreen>cleared<reset>.");
		}

		// checks if exists apc cache
		if(function_exists("apc_clear_cache")) {
			apc_clear_cache();
			apc_clear_cache("user");
			self::log("APC <bgGreen>cleared<reset>.");
		}
	}

	/**
	 * Detect access via browser
	 * @return boolean
	 */
	public static function detectMode() {
		return empty($_SERVER["HTTP_USER_AGENT"]);
	}

	/**
	 * Install all packages/dependencies and clear cache
	 * @param string $tempDir
	 */
	public static function install ($tempDir) {
		ob_start();

		self::installComposerDeps();
		self::installBowerDeps();

		// clear $tempDir
		if(isset($tempDir) && is_dir($tempDir)) {
			self::removeDirectory($tempDir);

			if(!file_exists($tempDir)) {
				mkdir($tempDir);
			} else {
				self::log("Temp dir <bgRed><white>was not fully removed<reset>.");
			}

			$i = new \FilesystemIterator($tempDir, \FilesystemIterator::SKIP_DOTS);
			if(iterator_count($i) == 0) {
				self::log("Temp dir <bgGreen>cleared<reset>.");
			} else {
				self::log("Temp dir <bgRed><white>was not cleared properly<reset>.");
			}

		} else self::log("Temp dir <cyan>is not defined<reset>.");

		self::resetCache();

		// send response to output
		self::sendResponse();
		die;
	}

	/**
	 * Sends response to browser/CLI
	 */
	public static function sendResponse() {

		ob_clean();

		if(self::detectMode())
			echo(Ansi::tagsToColors(implode(" ", self::$output)));
		else {
			foreach(self::$commands as $command => $result) {
				echo "<br>\$ <strong>$command</strong>:<br>";
				echo preg_replace("/\r\n|\r|\n/", '<br>', $result);
			}

			echo "<br><br>" . Ansi::stripTags(implode(" ", self::$output));
		}


	}

}


/**
 * Simple ANSI Colors
 * Version 1.0.0
 * https://github.com/SimonEast/Simple-Ansi-Colors
 *
 * Helper class that replaces the following tags into the appropriate
 * ANSI colour codes
 *
 * <black>
 * <red>
 * <green>
 * <yellow>
 * <blue>
 * <magenta>
 * <cyan>
 * <white>
 * <gray>
 * <darkRed>
 * <darkGreen>
 * <darkYellow>
 * <darkBlue>
 * <darkMagenta>
 * <darkCyan>
 * <darkWhite>
 * <darkGray>
 * <bgBlack>
 * <bgRed>
 * <bgGreen>
 * <bgYellow>
 * <bgBlue>
 * <bgMagenta>
 * <bgCyan>
 * <bgWhite>
 * <bold> Not visible on Windows
 * <italics> Not visible on Windows
 * <reset> Clears all colours and styles (required)
 *
 * Note: we don't use commands like bold-off, underline-off as it was introduced
 * in ANSI 2.50+ and does not currently display on Windows using ansicon
 */
class Ansi {
	/**
	 * Whether colour codes are enabled or not
	 *
	 * Valid options:
	 * null - Auto-detected. Color codes will be enabled on all systems except Windows, unless it
	 * has a valid ANSICON environment variable
	 * (indicating that AnsiCon is installed and running)
	 * false - will strip all tags and NOT output any ANSI colour codes
	 * true - will always output color codes
	 */
	public static $enabled = null;
	public static $tags = array(
		'<black>' => "\033[0;30m",
		'<red>' => "\033[1;31m",
		'<green>' => "\033[1;32m",
		'<yellow>' => "\033[1;33m",
		'<blue>' => "\033[1;34m",
		'<magenta>' => "\033[1;35m",
		'<cyan>' => "\033[1;36m",
		'<white>' => "\033[1;37m",
		'<gray>' => "\033[0;37m",
		'<darkRed>' => "\033[0;31m",
		'<darkGreen>' => "\033[0;32m",
		'<darkYellow>' => "\033[0;33m",
		'<darkBlue>' => "\033[0;34m",
		'<darkMagenta>' => "\033[0;35m",
		'<darkCyan>' => "\033[0;36m",
		'<darkWhite>' => "\033[0;37m",
		'<darkGray>' => "\033[1;30m",
		'<bgBlack>' => "\033[40m",
		'<bgRed>' => "\033[41m",
		'<bgGreen>' => "\033[42m",
		'<bgYellow>' => "\033[43m",
		'<bgBlue>' => "\033[44m",
		'<bgMagenta>' => "\033[45m",
		'<bgCyan>' => "\033[46m",
		'<bgWhite>' => "\033[47m",
		'<bold>' => "\033[1m",
		'<italics>' => "\033[3m",
		'<reset>' => "\033[0m",
	);
	/**
	 * This is the primary function for converting tags to ANSI color codes
	 * (see the class description for the supported tags)
	 *
	 * For safety, this function always appends a <reset> at the end, otherwise the console may stick
	 * permanently in the colors you have used.
	 *
	 * @param string $string
	 * @return string
	 */
	public static function tagsToColors($string)
	{
		if (static::$enabled === null) {
			static::$enabled = !static::isWindows() || static::isAnsiCon();
		}
		if (!static::$enabled) {
// Strip tags (replace them with an empty string)
			return static::stripTags($string);
		}
// We always add a <reset> at the end of each string so that any output following doesn't continue the same styling
		$string .= '<reset>';
		return str_replace(array_keys(static::$tags), static::$tags, $string);
	}
	/**
	 * Removes all occurances of ANSI tags from a string
	 * (used when static::$enabled == false or can be called directly if outputting strings to a text file)
	 *
	 * Note: the reason we don't use PHP's strip_tags() here is so that we only remove the ANSI-related ones
	 *
	 * @param string $string Text possibly containing ANSI tags such as <red>, <bold> etc.
	 * @return string Stripped of all valid ANSI tags
	 */
	public static function stripTags($string)
	{
		return str_replace(array_keys(static::$tags), '', $string);
	}
	public static function isWindows()
	{
		return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
	}
	public static function isAnsiCon()
	{
		return !empty($_SERVER['ANSICON'])
		&& substr($_SERVER['ANSICON'], 0, 1) != '0';
	}
}
