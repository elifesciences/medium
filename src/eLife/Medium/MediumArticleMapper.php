<?php

namespace eLife\Medium;

use Assert\Assertion;
use eLife\Medium\Model\Image;
use eLife\Medium\Model\MediumArticle;
use eLife\Medium\Response\ImageResponse;
use eLife\Medium\Response\MediumArticleListResponse;
use eLife\Medium\Response\MediumArticleResponse;
use SimpleXMLIterator;

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
        $root = new SimpleXMLIterator($xmlString);
        $articles = [];

        $parse_paragraph = function (string $html) {
            // @todo more robust solution.
            $pieces = explode('class="medium-feed-snippet">', $html);
            $para = explode('</p>', $pieces[1]);
            return $para[0];
        };

        $parse_image = function (string $html) {
            // @todo more robust solution.
            $matches = [];
            preg_match('/<img\s+[^>]*src="([^"]*)"[^>]*>/', $html, $matches);
            if (isset($matches[1])) {
                return self::mapImageFromUrl($matches[1]);
            }
            return [];
        };

        foreach ($root as $items) {
            foreach ($items as $node_key => $item) {
                if ($node_key === 'item') {
                    $article = new MediumArticle();
                    foreach ($item as $node_type => $node) {
                        switch ($node_type) {
                            case 'title':
                                $article->setTitle((string) $node);
                                $article->setImageAlt((string) $node);
                                break;
                            case 'description':
                                $image = $parse_image($node);
                                $article->setImageDomain($image['domain']);
                                $article->setImagePath($image['path']);
                                $article->setImpactStatement($parse_paragraph($node));
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
            }
        }
        return $articles;
    }

    public static function mapImageFromUrl($image) {
        $pieces = explode('/', $image);
        // https: / http:
        array_shift($pieces);
        // Empty space.
        array_shift($pieces);
        // Domain
        $domain = array_shift($pieces);
        // Type
        $type = array_shift($pieces);
        if ($type !== 'max') {
            // height
            array_shift($pieces);
        }
        // width
        array_shift($pieces);
        // Final result.
        return [
            'domain' => $domain,
            'path' => implode('/', $pieces)
        ];
    }

}
