<?php

namespace eLife\Medium\Response;

use JMS\Serializer\Annotation\Type;

class MediumArticleListResponse
{

    /**
     * @Type("array<eLife\Medium\Response\MediumArticleResponse>")
     */
    public $items;

    public function __construct(MediumArticleResponse ...$mediumArticles)
    {
        $this->items = $mediumArticles;
    }

}
