<?php

namespace eLife\Medium\Response;

use JMS\Serializer\Annotation\Type;

final class MediumArticleListResponse implements HasHeaders, IsVersioned
{
    /**
     * @Type("array<eLife\Medium\Response\MediumArticleResponse>")
     */
    public $items;

    /**
     * @Type("integer")
     */
    public $total;

    public function __construct(MediumArticleResponse ...$mediumArticles)
    {
        $this->items = $mediumArticles;
        $this->total = count($mediumArticles);
    }

    public function getVersion() : bool
    {
        return 1;
    }

    public function getHeaders() : array
    {
        return [
            'Content-Type' => ContentType::MEDIUM_ARTICLE_LIST_V1,
        ];
    }
}
