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
$ composer require adt/after-deploy
```

2. Add this code in bootstrap.php before including autoload.php
```php
 include __DIR__ . '/../vendor/adt/after-deploy/src/AfterDeploy.php';
 (new ADT\AfterDeploy\AfterDeploy())
 	->runBase([
 		'tempDir' => '/path/to/tempDir/', // required
 		'logDir' => '/path/to/logDir/', // required
 		'wwwDir' => '/path/to/wwwDir/', // optional, if not given, tempDir/../www is used, on
 		'key' => 'afterDeploy', // optional
 		'useMaintenance' => 1, // optional, default = 0
 		'sleep' => 1 // optional, time to wait before afterDeploy starts in seconds, if useMaintenance is 0 it's not used
 	]
);
```

3. Enable the extension in your neon config:

```neon
extensions:
	afterDeploy: ADT\AfterDeploy\DI\AfterDeployExtension
```

4. Update deployment configuration file `deployment.ini` like:
```neon
after[] = http://example.com/?afterDeploy
```

5. Run `dg/ftp-deployment` script
```
$ php private/vendor/dg/ftp-deployment/Deployment/deployment.php deployment.ini
```

6. Optionally you can set the redis in neon config:

```neon
afterDeploy:
	redis:
		client: @redis.client # \Kdyby\Redis\RedisClient
		dbs:
			- 1 # clear db 1
```

7. If you use [BackgroundQueue](https://github.com/AppsDevTeam/BackgroundQueue) >= [v2.1.1](https://github.com/AppsDevTeam/BackgroundQueue/releases/tag/v2.1.1), you can optionally set it in neon config:

```neon
afterDeploy:
	backgroundQueue:
		service: @backgroundQueue.service # \ADT\BackgroundQueue\Service
```

This will send a noop to currently running consumers, so they check if they should terminate. Telling the consumer to terminate on next check (by `-m 1` or by sending `SIGINT` signal) is not part of this component and is up to you.
