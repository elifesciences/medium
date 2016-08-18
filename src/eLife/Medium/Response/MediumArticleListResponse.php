<?php

namespace eLife\Medium\Response;

use Assert\Assertion;
use eLife\Medium\Model\Image;
use eLife\Medium\Model\MediumArticle;
use JMS\Serializer\Annotation\Type;

final class MediumArticleListResponse implements HasHeaders
{

    /**
     * @Type("array<eLife\Medium\Response\MediumArticleResponse>")
     */
    public $items;

    public function __construct(MediumArticleResponse ...$mediumArticles)
    {
        Assertion::notEmpty($mediumArticles);

        $this->items = $mediumArticles;
    }

    public function getHeaders() {
        return [
            'Content-Type' => ContentType::MEDIUM_ARTICLE
        ];
    }

    // This can be moved out to a separate mapper at some point,
    // but for simplicity, I've made it a static constructor.
    public static function mapFromEntities($articles) {
        Assertion::allIsInstanceOf($articles, MediumArticle::class);

        return new static(
            ...self::map(function(MediumArticle $article) {
                return new MediumArticleResponse(
                    $article->getUri(),
                    $article->getTitle(),
                    $article->getImpactStatement(),
                    new \DateTimeImmutable($article->getPublished()->format('c')),
                    new ImageResponse($article->getImageAlt(), Image::basic($article->getImageDomain(), $article->getImagePath()))
                );
            }, $articles)
        );
    }

    public static function map(callable $fn, $items) {
        $r = [];
        foreach ($items as $k => $i) {
            $r[$k] = $fn($i);
        }
        return $r;
    }

}
