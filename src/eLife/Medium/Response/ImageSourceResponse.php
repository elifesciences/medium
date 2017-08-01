<?php

namespace eLife\Medium\Response;

use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\Type;

final class ImageSourceResponse
{
    /**
     * @Type("string")
     * @SerializedName("mediaType")
     */
    public $mediaType;

    /**
     * @Type("string")
     */
    public $uri;

    /**
     * @Type("string")
     */
    public $filename;

    public function __construct(string $mediaType, string $uri, string $filename)
    {
        $this->mediaType = $mediaType;
        $this->uri = $uri;
        $this->filename = $filename;
    }
}
