<?php

namespace Nassau\Silex\Provider;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Api\BootableProviderInterface;
use Silex\Api\EventListenerProviderInterface;
use Silex\Application;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\Translation\Translator;

class TranslationLoaderProvider implements ServiceProviderInterface, BootableProviderInterface, EventListenerProviderInterface
{

	private $locales;

	public function boot(Application $app)
	{
		if (false === $app->offsetExists('nassau.translation.languages'))
		{
			$app['nassau.translation.languages'] = array_map(function ($filename)
			{
				return basename($filename, '.yaml');
			},
			array_filter(scandir($app['translator.loader.path']), function ($fn)
			{
				return fnmatch('*.yaml', $fn);
			}));
		}

		$this->locales = $app['nassau.translation.languages'];

		$app['translator'] = $app->extend('translator', function (Translator $translator, Application $app)
		{
			$translator->addLoader('yaml', new YamlFileLoader);

			foreach ($app['nassau.translation.languages'] as $lang)
			{
				$translator->addResource('yaml', $app['translator.loader.path'] . '/' . $lang . '.yaml', $lang);
			}

			return $translator;
		});
	}

	public function subscribe(Container $app, EventDispatcherInterface $dispatcher)
	{
		$dispatcher->addListener(KernelEvents::REQUEST, [$this, 'onKernelRequest'], Application::EARLY_EVENT);
	}

	public function onKernelRequest(GetResponseEvent $event)
	{
		$request = $event->getRequest();
		$request->attributes->set('_locale', $request->getPreferredLanguage($this->locales));
	}

	public function register(Container $pimple)
	{
		// noop
	}
}