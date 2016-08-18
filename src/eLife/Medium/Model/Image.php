<?php

namespace eLife\Medium\Model;

use LogicException;

class Image
{
    public $scaled;
    private $domain;
    private $path;
    private $width;
    private $height;

    private function __construct(string $domain, string $path, bool $scaled = null, int $width = null, int $height = null)
    {
        $this->scaled = $scaled;
        $this->domain = $domain;
        $this->path = $path;
        $this->width = $width;
        $this->height = $height;
    }

    public static function basic($domain, $path)
    {
        return new static (
            $domain,
            $path
        );
    }

    public static function scaled($domain, $path, $width) : self
    {
        return new static(
            $domain,
            $path,
            true,
            $width
        );
    }

    public static function cropped($domain, $path, $height, $width) : self
    {
        return new static(
            $domain,
            $path,
            false,
            $width,
            $height
        );
    }

    public function scale($width) : self
    {
        return new static(
            $this->domain,
            $this->path,
            true,
            $width
        );
    }

    public function crop($width, $height = null) : self
    {
        if (null === $height) {
            $height = $width;
        }
        return new static(
            $this->domain,
            $this->path,
            false,
            $width,
            $height
        );
    }

    public function __toString()
    {
        if (null === $this->scaled) {
            throw new LogicException('
                You must used a scaled or cropped version of this image before using it.
                ($image->crop($height, $width) -or- $image->scale($width))
            ');
        }
        if ($this->scaled) {
            return 'https://' . $this->domain . '/max/' . $this->width . '/' . $this->path;
        }
        return 'https://' . $this->domain . '/fit/c/' . $this->width . '/' . $this->height . '/' . $this->path;
    }

}
