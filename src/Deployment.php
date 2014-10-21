<?php

namespace ADT\Deployment;
use Nette\Utils\FileSystem;

/**
 * Class Deployment
 * @package ADT\Deployment
 */
class Deployment {

	public static function install ($tempDir) {

		$message = "";
		@system("cd ../ && composer install -o -n --no-dev");
		$message .= "Composer installed. ";

		@system("cd ../ && bower install");
		$message .= "Bower installed. ";

		// empty temp directory
		FileSystem::delete($tempDir);
		FileSystem::createDir($tempDir);

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