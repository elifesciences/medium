<?php

namespace eLife\Medium;

use eLife\Medium\Model\MediumArticle;
use eLife\Medium\Model\MediumArticleQuery;
use eLife\Medium\Response\ImageResponse;
use eLife\Medium\Response\ImageSizeResponse;
use eLife\Medium\Response\ImageSourceResponse;
use eLife\Medium\Response\MediumArticleListResponse;
use eLife\Medium\Response\MediumArticleResponse;
use eLife\Medium\RSS\ArticleParser;

final class MediumArticleMapper
{
    const API_VERSION = '1';

    public static function mapResponseFromDatabaseResult($articles, string $iiif) : MediumArticleListResponse
    {
        return new MediumArticleListResponse(
            ...self::map([self::class, 'mapResponseFromMediumArticle'], $articles, $iiif)
        );
    }

    public static function map(callable $fn, $items, ...$extra)
    {
        $r = [];
        foreach ($items as $k => $i) {
            $r[$k] = $fn($i, ...$extra);
        }

        return $r;
    }

    public static function mapResponseFromMediumArticle(MediumArticle $article, string $iiif) : MediumArticleResponse
    {
        $image = null;
        if ($article->getImageDomain() && $article->getImagePath() && $article->getImageMediaType()) {
            $image = new ImageResponse(
                "$iiif{$article->getImagePath()}",
                $article->getImageAlt(),
                new ImageSourceResponse($article->getImageMediaType(), "https://{$article->getImageDomain()}/{$article->getImagePath()}", preg_replace('/[^A-Za-z0-9_. ()-]/i', ' ', $article->getImagePath())),
                new ImageSizeResponse($article->getImageWidth(), $article->getImageHeight())
            );
        }

        return new MediumArticleResponse(
            $article->getUri(),
            $article->getTitle(),
            new \DateTimeImmutable($article->getPublished()->format('c')),
            $image,
            $article->getImpactStatement()
        );
    }

    public static function xmlToMediumArticleList(string $xmlString) : array
    {
        $root = new ArticleParser($xmlString);
        $articles = [];
        foreach ($root->getItems() as $item) {
            /* @var \SimpleXMLIterator $item */
            foreach ($item as $node_type => $node) {
                switch ($node_type) {
                    case 'guid':
                        $guid = (string) $node;
                        break;
                }
            }

            if (!$article = MediumArticleQuery::create()->findOneByGuid($guid)) {
                $article = new MediumArticle();
                $article->setGuid($guid);
            }

            foreach ($item as $node_type => $node) {
                /* @var \SimpleXMLIterator $node */
                switch ($node_type) {
                    case 'title':
                        $article->setTitle((string) $node);
                        $article->setImageAlt((string) $node);
                        break;
                    case 'description':
                        $image = $root->parseImage($node);
                        if ($image) {
                            $article->setImageDomain($image->getDomain());
                            $article->setImagePath($image->getPath());
                        } else {
                            $article->setImageAlt(null);
                        }
                        $article->setImpactStatement($root->parseParagraph($node));
                        break;
                    case 'link':
                        $link = explode('?source=rss', (string) $node);
                        $article->setUri(array_shift($link));
                        break;
                    case 'pubDate':
                        $article->setPublished(new \DateTime((string) $node));
                        break;
                }
            }
            foreach ($item->xpath('./content:encoded') as $content) {
                $image = $root->parseImage($content);
                if ($image) {
                    $article->setImageDomain($image->getDomain());
                    $article->setImagePath($image->getPath());
                } else {
                    $article->setImageAlt(null);
                }
                $article->setImpactStatement($root->parseParagraph($content));
            }
            $articles[] = $article;
        }

        return $articles;
    }
}
