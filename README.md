Server requirements
============
- Git
- Composer
- Bower
- OP Cache (optional)
- APCu (optional)

Installation & usage
==========

1. The best way to install is using [Composer](http://getcomposer.org/):


```sh
$ composer require adt/deployment
```

2. Before including `vendor/autoload.php` in your `bootstrap.php` you have to handle after deploy request like this:
```php
$developers = [
	'127.0.0.1',
	'1.2.3.4',
];

$remoteAddr = isset($_SERVER["REMOTE_ADDR"]) ? $_SERVER["REMOTE_ADDR"] : NULL;
if (in_array($remoteAddr, $developers) && isset($_GET["afterDeploy"])) {
	$deployment = __DIR__ . "/../vendor/adt/deployment/src/Deployment.php";

	if (file_exists($deployment)) {
		include $deployment;
		(new ADT\Deployment\Deployment)->run();
	}
}
```

4. Update deployment configuration file `deployment.ini` like:
```neon
after[] = http://example.com/?afterDeploy
```

5. Run `dg/ftp-deployment` script
```
$ php private/vendor/dg/ftp-deployment/Deployment/deployment.php deployment.ini
```
