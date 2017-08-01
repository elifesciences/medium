<?php

namespace eLife\Medium\Response;

use JMS\Serializer\Annotation\Type;

final class ImageResponse
{
    /**
     * @Type("string")
     */
    public $uri;

    /**
     * @Type("string")
     */
    public $alt;

    /**
     * @Type("eLife\Medium\Response\ImageSourceResponse")
     */
    public $source;

    /**
     * @Type("eLife\Medium\Response\ImageSizeResponse")
     */
    public $size;

    public function __construct(string $uri, string $alt, ImageSourceResponse $source, ImageSizeResponse $size)
    {
        $this->uri = $uri;
        $this->alt = $alt;
        $this->source = $source;
        $this->size = $size;
    }
}
