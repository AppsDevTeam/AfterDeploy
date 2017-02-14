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

2. Enable the extension in your neon config:

```neon
extensions:
	deployment: ADT\Deployment\DI\DeploymentExtension
```

3. Update deployment configuration file `deployment.ini` like:
```neon
after[] = http://example.com/?afterDeploy
```

4. Run `dg/ftp-deployment` script
```
$ php private/vendor/dg/ftp-deployment/Deployment/deployment.php deployment.ini
```

5. Optionaly you can change the key in neon config:

```neon
deployment:
	key: mySecretKey # default: afterDeploy
	redis:
		client: @redis.client # \Kdyby\Redis\RedisClient
		dbs:
			- 1 # clear db 1
```
