<?php

namespace eLife\Medium;

use eLife\ApiValidator\MessageValidator\JsonMessageValidator;
use eLife\ApiValidator\SchemaFinder\PuliSchemaFinder;
use eLife\Medium\Model\Image;
use eLife\Medium\Model\MediumArticle;
use eLife\Medium\Model\MediumArticleQuery;
use eLife\Medium\Response\ExceptionResponse;
use eLife\Medium\Response\ImageResponse;
use eLife\Medium\Response\MediumArticleListResponse;
use eLife\Medium\Response\MediumArticleResponse;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Propel\Runtime\Propel;
use Silex\Application;
use JMS\Serializer\SerializerBuilder;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Yaml\Yaml;
use Webmozart\Json\JsonDecoder;
use Throwable;
use DateTimeImmutable;
use LogicException;

class Kernel
{
    const ROOT = __DIR__ . '/../../..';
    const CONFIG = self::ROOT . '/config.yml';
    const PROPEL_CONFIG = self::ROOT . '/cache/propel/conf/config.php';

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
        // Propel.
        self::propelInit($app);
        // DI.
        self::dependencies($app);
        // Routes
        self::routes($app);
        // Validate.
        $app->after(function (Request $request, Response $response) use ($app) {
            // Validation.
            if ($app['config']['validate']) {
                self::validate($app, $request, $response);
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

    public static function loadConfig()
    {
        if (!file_exists(self::CONFIG)) {
            throw new LogicException('Configuration file not found');
        }
        return Yaml::parse(file_get_contents(self::CONFIG));
    }

    public static function dependencies(Application $app)
    {
        // Serializer.
        $app['serializer'] = function () {
            return SerializerBuilder::create()->setCacheDir(self::ROOT . '/cache')->build();
        };
        // Puli.
        $app['puli.factory'] = function () {
            $factoryClass = PULI_FACTORY_CLASS;
            return new $factoryClass();
        };
        // Puli repo.
        $app['puli.repository'] = function (Application $app) {
            return $app['puli.factory']->createRepository();
        };
        // PSR-7 Bridge
        $app['psr7.bridge'] = function (Application $app) {
            return new DiactorosFactory();
        };

        $app['puli.validator'] = function (Application $app) {
            return new JsonMessageValidator(
                new PuliSchemaFinder($app['puli.repository']),
                new JsonDecoder()
            );
        };
    }

    public static function validate(Application $app, Request $request, Response $response) {
        $app['puli.validator']->validate(
            $app['psr7.bridge']->createResponse($response)
        );
    }

    public static function routes(Application $app)
    {
        // Routes.
        $app->get('/', function () use ($app) {

            $articles = MediumArticleQuery::create()
                ->orderByPublished()
                ->limit(10)
                ->find();

            $data = MediumArticleListResponse::mapFromEntities($articles);

            // Make the JSON.
            $json = $app['serializer']->serialize($data, 'json');

            // Get repo
            // Make query based on params (none)
            // return response object
            // serialize
            return new Response($json, 200, $data->getHeaders());
        });
    }

    public static function propelInit(Application $app)
    {
        if (!file_exists(self::PROPEL_CONFIG)) {
            throw new LogicException("Propel configuration not found, please run `composer run sync` from the cli.");
        }
        require_once self::PROPEL_CONFIG;
    }

    public static function handleException(Throwable $e, Application $app)
    {
        return new Response(
            $app['serializer']->serialize(
                new ExceptionResponse($e->getMessage()),
                'json'
            ),
            403,
            ['Content-Type' => 'application/json']
        );
    }

}
