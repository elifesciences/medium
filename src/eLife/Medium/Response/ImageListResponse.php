<?php

namespace eLife\Medium\Response;

use JMS\Serializer\Annotation\Type;

final class ImageListResponse
{
    public $alt;

    /**
     * @Type("Array<string, eLife\Medium\Response\ImageVariantResponse>")
     */
    public $sizes;
}
