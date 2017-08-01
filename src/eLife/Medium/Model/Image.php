<?php

namespace eLife\Medium\Model;

final class Image
{
    private $domain;
    private $path;

    public function __construct(string $domain, string $path)
    {
        $this->domain = $domain;
        $this->path = $path;
    }

    public function getDomain() : string
    {
        return $this->domain;
    }

    public function getPath() : string
    {
        return $this->path;
    }

    public static function fromUrl(string $image) : self
    {
        $pieces = explode('/', $image);
        // https: / http:
        array_shift($pieces);
        // Empty space.
        array_shift($pieces);
        // Domain
        $domain = array_shift($pieces);
        // Type
        $type = array_shift($pieces);
        if ($type === 'fit') {
            // extra type (c)
            array_shift($pieces);
            // height
            array_shift($pieces);
        }
        // width
        array_shift($pieces);
        // Final result.
        return new static(
            $domain,
            implode('/', $pieces)
        );
    }

    public function __toString() : string
    {
        return 'https://'.$this->domain.$this->path;
    }
}
