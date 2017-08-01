<?php

namespace eLife\Medium\Response;

use JMS\Serializer\Annotation\Type;

final class ImageSizeResponse
{
    /**
     * @Type("integer")
     */
    public $width;

    /**
     * @Type("integer")
     */
    public $height;

    public function __construct(int $width, int $height)
    {
        $this->width = $width;
        $this->height = $height;
    }
}
