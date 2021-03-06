<?php

namespace ADT\AfterDeploy\DI;

class AfterDeployExtension extends \Nette\DI\CompilerExtension
{

	/**
	 * @var array
	 */
	protected static $defaults = array(
		'key' => 'afterDeploy',
		'redis' => [
			'client' => NULL,
			'dbs' => [],
		],
	);

	protected $config;

	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();

		$this->config = $this->getConfig(self::$defaults);
		$this->config['tempDir'] = $builder->parameters['tempDir'];
	}

	public function beforeCompile()
	{
		$builder = $this->getContainerBuilder();

		$builder->getDefinition('application')
			->addSetup('$container = $service->onStartup[] = function($app) { \ADT\AfterDeploy\AfterDeploy::onStartup(?); }', [$this->config]);

	}

}

