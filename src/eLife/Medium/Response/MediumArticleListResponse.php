<?php

namespace eLife\Medium\Response;

use JMS\Serializer\Annotation\Type;

class MediumArticleListResponse implements HasHeaders
{

    /**
     * @Type("array<eLife\Medium\Response\MediumArticleResponse>")
     */
    public $items;

    public function __construct(MediumArticleResponse ...$mediumArticles)
    {
        $this->items = $mediumArticles;
    }

    public function getHeaders() {
        return [
            'Content-Type' => ContentType::MEDIUM_ARTICLE
        ];
    }

}
