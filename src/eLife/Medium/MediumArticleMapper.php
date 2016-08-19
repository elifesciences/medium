<?php

namespace eLife\Medium;

use eLife\Medium\Model\Image;
use eLife\Medium\Model\MediumArticle;
use eLife\Medium\Response\ImageResponse;
use eLife\Medium\Response\MediumArticleListResponse;
use eLife\Medium\Response\MediumArticleResponse;
use eLife\Medium\RSS\ArticleParser;

final class MediumArticleMapper
{
    const API_VERSION = '1';

    public static function mapResponseFromDatabaseResult($articles) : MediumArticleListResponse
    {
        return new MediumArticleListResponse(
            ...self::map([self::class, 'mapResponseFromMediumArticle'], $articles)
        );
    }

    public static function map(callable $fn, $items)
    {
        $r = [];
        foreach ($items as $k => $i) {
            $r[$k] = $fn($i);
        }

        return $r;
    }

    public static function mapResponseFromMediumArticle(MediumArticle $article) : MediumArticleResponse
    {
        return new MediumArticleResponse(
            $article->getUri(),
            $article->getTitle(),
            new \DateTimeImmutable($article->getPublished()->format('c')),
            new ImageResponse(
                $article->getImageAlt(),
                Image::basic($article->getImageDomain(), $article->getImagePath())
            ),
            $article->getImpactStatement()
        );
    }

    public static function xmlToMediumArticleList(string $xmlString) : array
    {
        $root = new ArticleParser($xmlString);
        $articles = [];

        foreach ($root->getItems() as $item) {
            $article = new MediumArticle();
            foreach ($item as $node_type => $node) {
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
                        }
                        $article->setImpactStatement($root->parseParagraph($node));
                        break;
                    case 'link':
                        $link = explode('?source=rss', (string) $node);
                        $article->setUri(array_shift($link));
                        break;
                    case 'guid':
                        $article->setGuid((string) $node);
                        break;
                    case 'pubDate':
                        $article->setPublished(new \DateTime((string) $node));
                        break;
                }
            }
            $articles[] = $article;
        }

        return $articles;
    }
}
