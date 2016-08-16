<?php

namespace eLife\Medium;

use eLife\Medium\Response\ExceptionResponse;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Silex\Application;
use JMS\Serializer\SerializerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Yaml\Yaml;
use Throwable;

// temp code for testing bootstrap.
class DataModel
{

    public $wat;

    public $is;

    public $dis;

    public function __construct($wat, $is, $dis)
    {
        $this->wat = $wat;
        $this->is = $is;
        $this->dis = $dis;
    }
}

class Kernel
{
    const ROOT = __DIR__ . '/../..';
    const CONFIG = self::ROOT . '/config.yaml';

    public static function create() : Application
    {
        // Create application.
        $app = new Application();
        // Load config
        $app['config'] = self::loadConfig();
        // Annotations.
        AnnotationRegistry::registerAutoloadNamespace(
            'JMS\Serializer\Annotation', self::ROOT . '/vendor/jms/serializer/src'
        );
        // DI.
        self::dependencies($app);
        // Routes
        self::routes($app);
        // Post-hooks
        $app->after(function (Request $request, Response $response) {
            // Login here.
            $response->headers->add(['Content-Type' => 'application/json']);
        }, 1);
        $app->after(function (Request $request, Response $response) use ($app) {
            // Validation.
            if ($app['config']['validate']) {
                // Do stuff.
            }
        }, 2);
        // Error handling.
        $app->error(function (Throwable $e) use ($app) {
            if ($app['debug']) {
                return null;
            }
            return self::handleException($e, $app);
        });
        return $app;
    }

    public static function routes(Application $app) {
        // Routes.
        $app->get('/', function () use ($app) {
            // Get repo
            // Make query based on params (none)
            // return response object
            // serialize
            return new Response(
                $app['serializer']->serialize(
                    new DataModel($app['config']['database']['user'], 'is', 'sparta'),
                    'json'
                ),
                200
            );
        });
    }

    public static function dependencies(Application $app) {
        // Serializer.
        $app['serializer'] = function () {
            return SerializerBuilder::create()->setCacheDir(self::ROOT . '/cache')->build();
        };
    }

    public static function loadConfig()
    {
        if (!file_exists(self::CONFIG)) {
            throw new \LogicException('Configuration file not found');
        }
        return Yaml::parse(file_get_contents(self::CONFIG));
    }

    public static function handleException(Throwable $e, Application $app)
    {
        return new Response(
            $app['serializer']->serialize(
                new ExceptionResponse($e->getMessage()),
                'json'
            ),
            403,
            [ 'Content-Type' => 'application/json' ]
        );
    }

}
