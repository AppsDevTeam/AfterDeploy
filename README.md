Server requirements
============
- Git
- Composer
- Bower
- OP Cache (optional)
- APCu (optional)

Installation
==========
1. Add repository to `composer.json`
<pre>
  "require": {
	"php": ">= 5.3.7",
	"adt/deployment": "dev-master"
  },

  "require-dev": {
	"dg/ftp-deployment": "dev-master",
  },
	
  "repositories": [
      {
          "type": "git",
          "url": "https://github.com/AppsDevTeam/deployment.git"
      }
  ]
</pre>

2. `composer update`
3. Before including `vendor/autoload.php` you have to handle after deploy request like this:
<pre>
// $tempDir will be cleared
$developers = array('127.0.0.1', 'yourIP');
$tempDir = '__DIR__ . '/../temp'';
$remoteAddr = isset($_SERVER["REMOTE_ADDR"]) ? $_SERVER["REMOTE_ADDR"] : NULL;
if(in_array($remoteAddr, $developers) && isset($_GET["afterDeploy"])) {
        $deployment = __DIR__ . "/../vendor/adt/deployment/src/Deployment.php";
        
        if(file_exists($deployment)) {
          include $deployment;
          ADT\Deployment\Deployment::install($tempDir);
        }
}
include __DIR__ . '/../vendor/autoload.php';
</pre>
4. Edit deployment configuration file `deployment.ini` like:
<pre>
; remote FTP server
remote = ftps://user:pass@host:port
; local path (optional)
local = .
; run in test-mode? (can be enabled by option -t or --test too)
test = no
; files and directories to ignore
ignore = "
	/private/app/config/config.local.neon
	/private/log/*
	/private/sessions/*
	/private/temp/*
	/private/vendor/*
	!/private/vendor/adt
	/private/vendor/adt/*
	!/private/vendor/adt/deployment
	!/private/vendor/others
	/web/data/*
	/web/vendor/*
	/deployment.*
	.git*
"
; is allowed to delete remote files? (defaults to yes)
allowdelete = yes
after[] = http://example.com/?afterDeploy
preprocess = no
</pre>
5. Run dg/ftp-deployment script `php private/vendor/dg/ftp-deployment/Deployment/deployment.php deployment.ini`
