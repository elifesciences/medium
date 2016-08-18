<?php

namespace eLife\Medium\Response;

use Assert\Assertion;
use JMS\Serializer\Annotation\Type;

final class MediumArticleListResponse implements HasHeaders
{

    /**
     * @Type("array<eLife\Medium\Response\MediumArticleResponse>")
     */
    public $items;

    public function __construct(MediumArticleResponse ...$mediumArticles)
    {
        $this->items = $mediumArticles;
    }

    public function getHeaders() : array {
        return [
            'Content-Type' => ContentType::MEDIUM_ARTICLE
        ];
    }

}
