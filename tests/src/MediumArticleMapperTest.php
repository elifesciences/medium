<?php

namespace tests\eLife\Medium;

use eLife\Medium\MediumArticleMapper;
use eLife\Medium\Model\MediumArticle;
use eLife\Medium\Response\ImageResponse;
use eLife\Medium\Response\ImageSizeResponse;
use eLife\Medium\Response\ImageSourceResponse;
use eLife\Medium\Response\MediumArticleListResponse;
use eLife\Medium\Response\MediumArticleResponse;
use PHPUnit_Framework_TestCase;

/**
 * @SuppressWarnings(PHPMD.ForbiddenDateTime)
 */
final class MediumArticleMapperTest extends PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function testMapResponseFromDatabaseResult()
    {
        $articles = [
            self::articleFixture(),
            self::articleFixture(),
            self::articleFixture(),
        ];
        $articleResponse = MediumArticleMapper::mapResponseFromDatabaseResult($articles, 'https://iiif/');

        $this->assertInstanceOf(MediumArticleListResponse::class, $articleResponse);
        $this->assertNotEmpty($articleResponse->getHeaders());
        $this->assertNotNull($articleResponse->getHeaders()['Content-Type']);
        $this->assertGreaterThan(0, $articleResponse->getVersion());

        $this->assertNotEmpty($articleResponse->items);
        $this->assertContainsOnlyInstancesOf(MediumArticleResponse::class, $articleResponse->items);
    }

    public static function articleFixture()
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

    /**
     * @test
     */
    public function testMapResponseFromMediumArticle()
    {
        $article = new MediumArticle();
        $article->setTitle('Hardened hearts reveal organ evolution');
        $article->setUri('https://medium.com/life-on-earth/hardened-hearts-reveal-organ-evolution-8eb882a8bf18#');
        $article->setImpactStatement('Fossilized hearts have been found in specimens of an extinct fish in Brazil.');
        $article->setGuid('8eb882a8bf18');
        $article->setPublished(new \DateTime());
        $article->setImageDomain('cdn-images-1.medium.com');
        $article->setImagePath('1*eDBmGJ3a3IkqSp6HhAFqPQ.jpeg');
        $article->setImageAlt('alt text');
        $article->setImageMediaType('image/jpeg');
        $article->setImageWidth(1000);
        $article->setImageHeight(500);

        $articleResponse = MediumArticleMapper::mapResponseFromMediumArticle($article, 'https://iiif/');

        $this->assertSame($article->getTitle(), $articleResponse->title);
        $this->assertSame($article->getUri(), $articleResponse->uri);
        $this->assertSame($article->getImpactStatement(), $articleResponse->impactStatement);
        $this->assertSame($article->getPublished('c'), $articleResponse->published->format('c'));
        $this->assertInstanceOf(ImageResponse::class, $articleResponse->image);
        $this->assertSame('https://iiif/1*eDBmGJ3a3IkqSp6HhAFqPQ.jpeg', $articleResponse->image->uri);
        $this->assertSame('alt text', $articleResponse->image->alt);
        $this->assertInstanceOf(ImageSourceResponse::class, $articleResponse->image->source);
        $this->assertSame('https://cdn-images-1.medium.com/1*eDBmGJ3a3IkqSp6HhAFqPQ.jpeg', $articleResponse->image->source->uri);
        $this->assertSame('image/jpeg', $articleResponse->image->source->mediaType);
        $this->assertSame('1 eDBmGJ3a3IkqSp6HhAFqPQ.jpeg', $articleResponse->image->source->filename);
        $this->assertInstanceOf(ImageSizeResponse::class, $articleResponse->image->size);
        $this->assertSame(1000, $articleResponse->image->size->width);
        $this->assertSame(500, $articleResponse->image->size->height);
    }

    /**
     * This test will be to detect BC if the mapping is changed. It will still pass if Medium changes their RSS.
     *
     * @test
     */
    public function testXmlToMediumArticleList()
    {
        $xml = file_get_contents(__DIR__.'/mediumFixture_19-01-17.xml');

        $articles = MediumArticleMapper::xmlToMediumArticleList($xml);
        $this->assertNotEmpty($articles);
        $this->assertContainsOnlyInstancesOf(MediumArticle::class, $articles);

        $article = array_shift($articles);

        if ($article instanceof MediumArticle) {
            $this->assertSame('"Fishing out the origin of joint lubrication for arthritis research" in Life on Earth', $article->getTitle());
            $this->assertSame('https://medium.com/life-on-earth/fishing-out-the-origin-of-joint-lubrication-for-arthritis-research-6f75eb2c75a0', $article->getUri());
            $this->assertSame('Lubricated joints evolved millions of years earlier than previously thought.', $article->getImpactStatement());
            $this->assertSame('2016-08-19T11:11:01+00:00', $article->getPublished('c'));
            $this->assertSame('d262ilb51hltx0.cloudfront.net', $article->getImageDomain());
            $this->assertSame('1*r8Dosc01-Cyo82eZo-oRMA.png', $article->getImagePath());
            $this->assertSame($article->getTitle(), $article->getImageAlt());
        } else {
            $this->fail('Article must be instance of MediumArticles');
        }
    }

    /**
     * This test will be to detect BC if the mapping is changed. It will still pass if Medium changes their RSS.
     *
     * @test
     */
    public function testXmlToMediumArticleListWithoutImage()
    {
        $xml = file_get_contents(__DIR__.'/mediumWithoutImageFixture_19-01-17.xml');

        $articles = MediumArticleMapper::xmlToMediumArticleList($xml);
        $this->assertNotEmpty($articles);
        $this->assertContainsOnlyInstancesOf(MediumArticle::class, $articles);

        $article = array_shift($articles);

        if ($article instanceof MediumArticle) {
            $this->assertSame('"Fishing out the origin of joint lubrication for arthritis research" in Life on Earth', $article->getTitle());
            $this->assertSame('https://medium.com/life-on-earth/fishing-out-the-origin-of-joint-lubrication-for-arthritis-research-6f75eb2c75a0', $article->getUri());
            $this->assertSame('Lubricated joints evolved millions of years earlier than previously thought.', $article->getImpactStatement());
            $this->assertSame('2016-08-19T11:11:01+00:00', $article->getPublished('c'));
            $this->assertNull($article->getImageDomain());
            $this->assertNull($article->getImagePath());
            $this->assertNull($article->getImageAlt());
        } else {
            $this->fail('Article must be instance of MediumArticles');
        }
    }
}
