<?php

namespace eLife\Medium\Response;


use JMS\Serializer\Annotation\Type;

use DateTime;
use DateTimeImmutable;

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
        string $impactStatement = null,
        DateTimeImmutable $published,
        ImageResponse $image
    ) {
        $this->uri = $uri;
        $this->title = $title;
        $this->published = DateTime::createFromFormat('Y-m-d\TH:i:sO', $published->format('Y-m-d\TH:i:sO'), $published->getTimezone());
        $this->impactStatement = $impactStatement;
        $this->image = $image;
    }

}
