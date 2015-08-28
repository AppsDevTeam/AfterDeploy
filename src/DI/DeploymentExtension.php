<?php

namespace ADT\Deployment\DI;

class DeploymentExtension extends \Nette\DI\CompilerExtension
{

	/**
	 * @var array
	 */
	protected static $defaults = array(
		'key' => 'afterDeploy',
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
			->addSetup('$container = $service->onStartup[] = function($app) { \ADT\Deployment\Deployment::onStartup(?); }', [$this->config]);

	}

}

