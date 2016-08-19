<?php

namespace eLife\Medium;

use Doctrine\Common\Annotations\AnnotationRegistry;
use eLife\ApiValidator\MessageValidator\JsonMessageValidator;
use eLife\ApiValidator\SchemaFinder\PuliSchemaFinder;
use eLife\Medium\Model\MediumArticle;
use eLife\Medium\Model\MediumArticleQuery;
use eLife\Medium\Response\ExceptionResponse;
use GuzzleHttp\Client;
use JMS\Serializer\SerializerBuilder;
use LogicException;
use Propel\Runtime\ActiveQuery\Criteria;
use Silex\Application;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Yaml\Yaml;
use Throwable;
use Webmozart\Json\JsonDecoder;

final class Kernel
{
    const ROOT = __DIR__.'/../../..';
    const CONFIG = self::ROOT.'/config.yml';
    const PROPEL_CONFIG = self::ROOT.'/cache/propel/conf/config.php';

    public static function create($config = []) : Application
    {
        // Create application.
        $app = new Application();
        // Load config
        $app['config'] = array_merge($config, self::loadConfig());
        // Annotations.
        AnnotationRegistry::registerAutoloadNamespace(
            'JMS\Serializer\Annotation', self::ROOT.'/vendor/jms/serializer/src'
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

    public static function propelInit(Application $app)
    {
        if (!file_exists(self::PROPEL_CONFIG)) {
            throw new LogicException('Propel configuration not found, please run `composer run sync` from the cli.');
        }
        require_once self::PROPEL_CONFIG;
    }

    public static function dependencies(Application $app)
    {
        // Serializer.
        $app['serializer'] = function () {
            return SerializerBuilder::create()->setCacheDir(self::ROOT.'/cache')->build();
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
        // Validator.
        $app['puli.validator'] = function (Application $app) {
            return new JsonMessageValidator(
                new PuliSchemaFinder($app['puli.repository']),
                new JsonDecoder()
            );
        };
        // Medium Guzzle client.
        $app['medium.client'] = function () : Client {
            return new Client(['base_uri' => 'https://medium.com/feed/']);
        };

        $app['propel.query.medium'] = function () : MediumArticleQuery {
            return new MediumArticleQuery();
        };
    }

    public static function routes(Application $app)
    {
        // Routes.
        $app->get('/', function () use ($app) {

            $articles = $app['propel.query.medium']
                ->orderByPublished(Criteria::DESC)
                ->limit(10)
                ->find();

            // Map array of articles from database to response.
            $data = MediumArticleMapper::mapResponseFromDatabaseResult($articles);
            $json = $app['serializer']->serialize($data, 'json');

            return new Response($json, 200, $data->getHeaders());
        });

        $app->get('/import/{mediumUsername}', function ($mediumUsername) use ($app) {
            $data = $app['medium.client']->get('@'.$mediumUsername);
            $articles = MediumArticleMapper::xmlToMediumArticleList($data->getBody());
            $responseArticles = [];
            foreach ($articles as $k => $article) {
                $databaseArticle = (new MediumArticleQuery())->findOneByGuid($article->getGuid());
                if (null === $databaseArticle) {
                    $article->save();
                    $responseArticles[] = $article;
                }
            }
            // Return the fresh data..
            $data = MediumArticleMapper::mapResponseFromDatabaseResult($responseArticles);
            $json = $app['serializer']->serialize($data, 'json');

            return new Response($json, 200, $data->getHeaders());
        });

        if ($app['config']['debug']) {
            // this is only meant to provide.
            $app->get('/random-fixture/{num}', function ($num) use ($app) {
                $articles = [];
                for ($i = 0; $i < $num; ++$i) {
                    $article = new MediumArticle();
                    $article->setTitle('Hardened hearts reveal organ evolution'.md5(random_bytes(20)));
                    $article->setUri('https://medium.com/life-on-earth/hardened-hearts-reveal-organ-evolution-8eb882a8bf18#'.md5(random_bytes(20)));
                    $article->setImpactStatement('Fossilized hearts have been found in specimens of an extinct fish in Brazil.'.md5(random_bytes(20)));
                    $article->setGuid('8eb882a8bf18'.md5(random_bytes(20)));
                    $article->setPublished(new \DateTime());
                    $article->setImageDomain('cdn-images-1.medium.com');
                    $article->setImagePath('1*eDBmGJ3a3IkqSp6HhAFqPQ.jpeg');
                    $article->setImageAlt('alt text');
                    $article->save();
                    $articles[] = $article;
                }
                $data = MediumArticleMapper::mapResponseFromDatabaseResult($articles);
                $json = $app['serializer']->serialize($data, 'json');

                return new Response($json, 200, $data->getHeaders());
            });
        }
    }

    public static function validate(Application $app, Request $request, Response $response)
    {
        $app['puli.validator']->validate(
            $app['psr7.bridge']->createResponse($response)
        );
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
