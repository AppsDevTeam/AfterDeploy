<?php

namespace ADT\Deployment;

/**
 * Class Deployment
 * @package ADT\Deployment
 */
class Deployment {

	public static function emptyDirectory($dir) {
		if (is_dir($dir)) {
			$objects = scandir($dir);
			foreach ($objects as $object) {
				if ($object != "." && $object != "..") {
					if (filetype($dir."/".$object) == "dir") self::emptyDirectory($dir."/".$object); else unlink($dir."/".$object);
				}
			}
			reset($objects);
			self::emptyDirectory($dir);
		}
	}

	public static function install ($tempDir) {

		$message = "";
		@system("cd ../ && composer install -o -n --no-dev");
		$message .= "Composer installed. ";

		@system("cd ../ && bower install");
		$message .= "Bower installed. ";

		// empty temp directory
		self::emptyDirectory($tempDir);
		mkdir($tempDir);

		$message .="Temp dir cleared. ";

		if(function_exists("opcache_reset")) {
			$reset = opcache_reset();

			if($reset)
				$message .= "OPCache cleared. ";
		}

		if(function_exists("apc_clear_cache")) {
			apc_clear_cache();
			apc_clear_cache("user");
			$message .= "APC cleared.";
		}

		ob_clean();
		echo $message;
		die;
	}



}