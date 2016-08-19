<?php

namespace eLife\Medium\Response;

use elife\Medium\Model\Image;
use JMS\Serializer\Annotation\Type;

final class ImageResponse
{
    /**
     * @Type("string")
     */
    public $alt;

    /**
     * @Type("array<string, array<integer,string>>")
     */
    public $sizes;

    public function __construct(string $alt, Image $image)
    {
        $this->alt = $alt;
        $this->sizes = [
            '2:1' => [
                900 => $image->crop(900, 450),
                1800 => $image->crop(1800, 900),
            ],
            '16:9' => [
                250 => $image->crop(250, 141),
                500 => $image->crop(500, 281),
            ],
            '1:1' => [
                70 => $image->crop(70),
                140 => $image->crop(140),
            ],
        ];
    }
}
