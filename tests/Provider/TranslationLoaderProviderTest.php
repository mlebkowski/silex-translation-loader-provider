<?php
/**
 * Created by PhpStorm.
 * User: puck
 * Date: 29/03/15
 * Time: 00:26
 */

namespace Nassau\Silex\Provider;


use org\bovigo\vfs\vfsStream;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\Translator;

class TranslationLoaderProviderTest extends \PHPUnit_Framework_TestCase
{

	public function testProviderLoadsPreferredLocale()
	{
		$root = vfsStream::setup('translations');
		$translationsPath = $root->url('translations');
		$languages = ['en', 'pl'];

		foreach ($languages as $lang)
		{
			touch($translationsPath . '/' . $lang . '.yaml');
		}

		$translatorMock = $this->getMockBuilder(Translator::class)->disableOriginalConstructor()->getMock();

		foreach ($languages as $idx => $lang)
		{
			$translatorMock->expects($this->at($idx+1))->method('addResource')->with(
				$this->anything(),
				$this->equalTo("$translationsPath/$lang.yaml"),
				$this->equalTo($lang)
			);
		}


		$app = new Application(['debug' => true]);
		$app['translator'] = function () use ($translatorMock)
		{
			return $translatorMock;
		};

		$app->register(new TranslationLoaderProvider, [
			'translator.loader.path' => $translationsPath,
		]);

		$request = Request::create('/');
		$request->headers->set('Accept-Language', 'pl');

		$app->match('/', function () {
			return "hello";
		});

		$app->handle($request);
		$app->offsetGet('translator');

		$this->assertEquals('pl', $request->attributes->get('_locale'));
	}
}
 