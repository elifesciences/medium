<?php

namespace tests\eLife\Medium;

use eLife\Medium\Kernel;
use eLife\Medium\Model\Base\MediumArticleQuery;
use eLife\Medium\Model\MediumArticle;
use Mockery\Mock;
use Silex\WebTestCase;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use function GuzzleHttp\json_decode;

class KernelTest extends WebTestCase
{
    public function createApplication() : HttpKernelInterface
    {
        return Kernel::create(['validate' => true]);
    }

    /**
     * @test
     */
    public function testPing()
    {
        $client = $this->createClient();
        $client->request('GET', '/ping');
        $response = $client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('pong', $response->getContent());
        $this->assertEquals('text/plain; charset=UTF-8', $response->headers->get('Content-Type'));
        $this->assertEquals('must-revalidate, no-cache, no-store, private', $response->headers->get('Cache-Control'));
    }

    /**
     * @test
     */
    public function testGetWithData()
    {
        $fixtures = [
            self::articleFixture(),
            self::articleFixture(),
            self::articleFixture(),
            self::articleFixture(),
        ];

        $this->app['propel.query.medium'] = new Mock(MediumArticleQuery::class);
        $this->app['propel.query.medium']->shouldReceive('orderByPublished')->andReturn($this->app['propel.query.medium']);
        $this->app['propel.query.medium']->shouldReceive('paginate')->andReturn($fixtures);

        $client = $this->createClient();
        $client->request('GET', '/medium-articles');

        $json = json_decode($client->getResponse()->getContent(), true);

        if (!$client->getResponse()->isOk()) {
            $this->fail($json['message']);
        }

        $this->assertNotEmpty($json['items']);
        $this->assertTrue($client->getResponse()->isOk()); // validation configuration ensures validity.
    }

    /**
     * @test
     */
    public function testGetWithDataAsc()
    {
        $fixtures = [
            self::articleFixture(),
            self::articleFixture(),
            self::articleFixture(),
            self::articleFixture(),
        ];

        $this->app['propel.query.medium'] = new Mock(MediumArticleQuery::class);
        $this->app['propel.query.medium']->shouldReceive('orderByPublished')->andReturn($this->app['propel.query.medium']);
        $this->app['propel.query.medium']->shouldReceive('paginate')->andReturn($fixtures);

        $client = $this->createClient();
        $client->request('GET', '/medium-articles?order=asc');

        $json = json_decode($client->getResponse()->getContent(), true);

        if (!$client->getResponse()->isOk()) {
            $this->fail($json['message']);
        }

        $this->assertNotEmpty($json['items']);
        $this->assertTrue($client->getResponse()->isOk()); // validation configuration ensures validity.
    }

    /**
     * @test
     */
    public function testGetWithEmptyDataSet()
    {
        $this->app['propel.query.medium'] = new Mock(MediumArticleQuery::class);
        $this->app['propel.query.medium']->shouldReceive('orderByPublished')->andReturn($this->app['propel.query.medium']);
        $this->app['propel.query.medium']->shouldReceive('paginate')->andReturn([]);

        $client = $this->createClient();
        $client->request('GET', '/medium-articles');

        $json = json_decode($client->getResponse()->getContent(), true);

        if (!$client->getResponse()->isOk()) {
            $this->fail($json['message']);
        }

        $this->assertEmpty($json['items']);

        $this->assertTrue($client->getResponse()->isOk()); // validation configuration ensures validity.
    }

    public static function articleFixture() : MediumArticle
    {
        $article = new MediumArticle();
        $article->setTitle('Hardened hearts reveal organ evolution'.md5(random_bytes(20)));
        $article->setUri('https://medium.com/life-on-earth/hardened-hearts-reveal-organ-evolution-8eb882a8bf18#'.md5(random_bytes(20)));
        $article->setImpactStatement('Fossilized hearts have been found in specimens of an extinct fish in Brazil.'.md5(random_bytes(20)));
        $article->setGuid('8eb882a8bf18'.md5(random_bytes(20)));
        $article->setPublished(new \DateTime());
        $article->setImageDomain('cdn-images-1.medium.com');
        $article->setImagePath('1*eDBmGJ3a3IkqSp6HhAFqPQ.jpeg');
        $article->setImageAlt('alt text');

        return $article;
    }
}
