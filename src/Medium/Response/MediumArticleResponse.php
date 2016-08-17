<?php

namespace eLife\Medium\Response;


use JMS\Serializer\Annotation\Type;

use DateTime;

class MediumArticleResponse
{

    /**
     * @Type("string")
     */
    public $uri;

    /**
     * @Type("string")
     */
    public $title;

    /**
     * @Type("DateTime<'c'>")
     */
    public $published;

    /**
     * @Type("string")
     */
    public $impactStatement;

    /**
     * @Type("eLife\Medium\Response\ImageResponse")
     */
    public $image;

    public function  __construct(
        string $uri,
        string $title,
        string $impactStatement,
        DateTime $published,
        ImageResponse $image
    ) {
        $this->uri = $uri;
        $this->title = $title;
        $this->published = $published;
        $this->impactStatement = $impactStatement;
        $this->image = $image;
    }

}
