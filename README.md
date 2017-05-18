Server requirements
============
- Git
- Composer
- Bower
- OP Cache (optional)
- APCu (optional)
- Redis (optional)

Installation & usage
==========

1. The best way to install is using [Composer](http://getcomposer.org/):


```sh
$ composer require adt/deployment
```

2. Add this code in bootstrap.php before including autoload.php
```php
 include __DIR__ . '/../vendor/adt/deployment/src/Deployment.php';
 (new ADT\Deployment\Deployment())
 	->runBase([
 		'tempDir' => '/path/to/tempDir/', // required
 		'wwwDir' => '/path/to/wwwDir/', // optional, if not given, tempDir/../www is used
 		'key' => 'afterDeploy', // optional
 		'sleep' => 1 // optional, time to wait before afterDeploy starts in seconds
 	]
);
```

3. Enable the extension in your neon config:

```neon
extensions:
	deployment: ADT\Deployment\DI\DeploymentExtension
```

4. Update deployment configuration file `deployment.ini` like:
```neon
after[] = http://example.com/?afterDeploy
```

5. Run `dg/ftp-deployment` script
```
$ php private/vendor/dg/ftp-deployment/Deployment/deployment.php deployment.ini
```

6. Optionaly you can set the redis in neon config:

```neon
deployment:
	redis:
		client: @redis.client # \Kdyby\Redis\RedisClient
		dbs:
			- 1 # clear db 1
```
