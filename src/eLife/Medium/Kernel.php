<?php

namespace eLife\Medium;

use ComposerLocator;
use Doctrine\Common\Annotations\AnnotationRegistry;
use eLife\ApiValidator\MediaType;
use eLife\ApiValidator\MessageValidator\JsonMessageValidator;
use eLife\ApiValidator\SchemaFinder\PathBasedSchemaFinder;
use eLife\Logging\LoggingFactory;
use eLife\Logging\Monitoring;
use eLife\Medium\Model\MediumArticle;
use eLife\Medium\Model\MediumArticleQuery;
use eLife\Medium\Response\ContentType;
use eLife\Medium\Response\ExceptionResponse;
use eLife\Medium\Response\VersionResolver;
use GuzzleHttp\Client;
use JMS\Serializer\SerializerBuilder;
use JsonSchema\Validator;
use LogicException;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\Connection\Exception\ConnectionException;
use SebastianBergmann\Version;
use Silex\Application;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

final class Kernel
{
    const ROOT = __DIR__.'/../../..';
    const PROPEL_CONFIG = self::ROOT.'/var/cache/propel/conf/config.php';

    public static function create($config = []) : Application
    {
        // Create application.
        $app = new Application();
        // Load config
        $app['config'] = array_merge(
            [
            'ttl' => 3600,
            'debug' => false,
            'validate' => false,
            ], $config
        );
        // Annotations.
        AnnotationRegistry::registerAutoloadNamespace(
            'JMS\Serializer\Annotation', self::ROOT.'/vendor/jms/serializer/src'
        );
        // Propel.
        self::propelInit();
        // DI.
        self::dependencies($app);
        // Routes
        self::routes($app);
        // Validate.
        $app->after(
            function (Request $request, Response $response) use ($app) {
                // Validation.
                if ($app['config']['validate']) {
                    self::validate($app, $request, $response);
                }
            }, 2
        );

        // Cache.
        $app->after(
            function (Request $request, Response $response) use ($app) {
                // cache.
            }, 3
        );

        // Error handling.
        $app->error(
            function (Throwable $e) use ($app) {
                $app['logger']->error('Exception in serving request', ['exception' => $e]);
                $app['monitoring']->recordException($e, 'Exception in serving request');
                if ($app['debug']) {
                    return null;
                }

                return self::handleException($e, $app);
            }
        );

        return $app;
    }

    public static function propelInit()
    {
        if (!file_exists(self::PROPEL_CONFIG)) {
            throw new LogicException('Propel configuration not found, please run `composer run sync` from the cli.');
        }
        include_once self::PROPEL_CONFIG;
    }

    public static function dependencies(Application $app)
    {
        // Serializer.
        $app['serializer'] = function () {
            return SerializerBuilder::create()->setCacheDir(self::ROOT.'/var/cache')->build();
        };
        // PSR-7 Bridge
        $app['psr7.bridge'] = function () {
            return new DiactorosFactory();
        };
        // Validator.
        $app['message-validator'] = function (Application $app) {
            return new JsonMessageValidator(
                new PathBasedSchemaFinder(ComposerLocator::getPath('elife/api').'/dist/model'),
                new Validator()
            );
        };
        // Medium Guzzle client.
        $app['medium.client'] = function () : Client {
            return new Client(['base_uri' => 'https://medium.com/feed/']);
        };

        $app['propel.query.medium'] = function () : MediumArticleQuery {
            return new MediumArticleQuery();
        };

        // Versions
        $app['medium.versions'] = function () : VersionResolver {
            $versions = new VersionResolver();
            // Medium article list version 1.
            $versions->accept(
                ContentType::MEDIUM_ARTICLE_LIST_V1, function ($articles) {
                    return MediumArticleMapper::mapResponseFromDatabaseResult($articles);
                }, true
            );
            // All versions.
            return $versions;
        };

        $app['logger'] = function () {
            $factory = new LoggingFactory(self::ROOT.'/var/logs', 'medium');

            return $factory->logger();
        };

        $app['monitoring'] = function () {
            return new Monitoring();
        };
    }

    /**
     * @SuppressWarnings(PHPMD.ForbiddenDateTime)
     */
    public static function routes(Application $app)
    {
        // Routes.
        $app->get(
            '/medium-articles', function (Request $request) use ($app) {
                $page = $request->query->get('page', 1);
                $perPage = $request->query->get('per-page', 20);

                if ((int) $perPage > 100) {
                    $perPage = 100;
                }

                $order = $request->query->get('order', Criteria::DESC);

                if (isset($order) && strtolower($order) === 'asc') {
                    $order = Criteria::ASC;
                } else {
                    $order = Criteria::DESC;
                }
                $articles = $app['propel.query.medium']
                ->orderByPublished($order)
                ->paginate($page, $perPage);

                // @todo replace with accept parsing.
                $accept = self::getAcceptHeader($request);
                // Map array of articles from database to response.
                $data = $app['medium.versions']->resolve($accept, $articles);
                $json = $app['serializer']->serialize($data, 'json');
                $headers = $data->getHeaders();
                if (self::isAuth($request)) {
                    $headers['Cache-Control'] = 'private, max-age=0, must-revalidate';
                } else {
                    $headers['Cache-Control'] = 'public, max-age=300, stale-while-revalidate=300, stale-if-error=86400';
                }
                $headers['ETag'] = md5($json);
                $headers['Vary'] = 'Accept';

                return new Response($json, 200, $headers);
            }
        );

        $app->get(
            '/ping', function () {
                return new Response(
                    'pong',
                    200,
                    [
                    'Cache-Control' => 'must-revalidate, no-cache, no-store, private',
                    'Content-Type' => 'text/plain; charset=UTF-8',
                    ]
                );
            }
        );
        if ($app['config']['debug']) {
            $app->get(
                '/import/{mediumUsername}', function ($mediumUsername) use ($app) {
                    $articles = self::import($app, $mediumUsername);
                    // Return the fresh data..
                    $data = $app['medium.versions']->resolve('application/json', $articles);
                    $json = $app['serializer']->serialize($data, 'json');

                    return new Response($json, 200, $data->getHeaders());
                }
            );

            // this is only meant to provide.
            $app->get(
                '/random-fixture/{num}', function ($num) use ($app) {
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
                    $data = $app['medium.versions']->resolve('application/json', $articles);
                    $json = $app['serializer']->serialize($data, 'json');

                    return new Response($json, 200, $data->getHeaders());
                }
            );
        }
    }

    public static function getAcceptHeader(Request $request) : string
    {
        return
          strpos($request->headers->get('Accept'), '*/*') !== false ?
            'application/json' :
            (string) MediaType::fromString($request->headers->get('Accept'));
    }

    public static function isAuth(Request $request) : bool
    {
        if ($request->headers->get('X-Consumer-Groups') == 'admin') {
            return true;
        } else {
            return false;
        }
    }

    public static function import(Application $app, string $mediumUsername)
    {
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
        return $responseArticles;
    }

    public static function validate(Application $app, Request $request, Response $response)
    {
        $app['message-validator']->validate(
            $app['psr7.bridge']->createResponse($response)
        );
    }

    public static function cache(Application $app, Request $request, Response $response)
    {
        $response->setTtl($app['config']['ttl']);
    }

    public static function handleException(Throwable $e, Application $app)
    {
        if ($e instanceof ConnectionException) {
            return new Response(
                $app['serializer']->serialize(
                    new ExceptionResponse($e->getMessage()),
                    'json'
                ),
                503,
                ['Content-Type' => 'application/problem+json']
            );
        }

        if ($e instanceof HttpExceptionInterface) {
            return new Response(
                $app['serializer']->serialize(
                    new ExceptionResponse($e->getMessage()),
                    'json'
                ),
                $e->getStatusCode(),
                ['Content-Type' => 'application/problem+json']
            );
        }

        return new Response(
            $app['serializer']->serialize(
                new ExceptionResponse('Internal server error'),
                'json'
            ),
            500,
            ['Content-Type' => 'application/problem+json']
        );
    }
}
