<?php

namespace eLife\Medium\Response;

use eLife\ApiSdk\ApiClient\MediumClient;
use eLife\ApiSdk\MediaType;
use JMS\Serializer\Annotation\Type;

final class MediumArticleListResponse implements HasHeaders
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

    public function getHeaders() : array
    {
        return [
            'Content-Type' => (string) new MediaType(MediumClient::TYPE_MEDIUM_ARTICLE_LIST, 1),
        ];
    }
}
