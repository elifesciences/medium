<?php

namespace eLife\Medium\RSS;

use eLife\Medium\Model\Image;
use SimpleXMLIterator;

final class ArticleParser
{
    public function __construct($xmlBody)
    {
        $this->body = $xmlBody;
        $this->iterableBody = new SimpleXMLIterator($xmlBody);
    }

    public function getItems()
    {
        foreach ($this->iterableBody as $items) {
            foreach ($items as $node_key => $item) {
                if ($node_key === 'item') {
                    yield $item;
                }
            }
        }
    }

    public function parseParagraph($html) : string
    {
        // Find the start of the paragraph start tag.
        $pieces = explode('<p', $html);
        if (!isset($pieces[1])) {
            return '';
        }
        // Find the end of the paragraph start tag.
        $pieces = explode('>', $pieces[1]);
        if (!isset($pieces[1])) {
            return '';
        }
        array_shift($pieces);
        $para_contents = implode('>', $pieces);

        // Find the end of the paragraph.
        $para = explode('</p', $para_contents);

        return $para[0];
    }

    public function parseImage($html)
    {
        // @todo more robust solution.
        $matches = [];
        preg_match('/<img\s+[^>]*src="([^"]*)"[^>]*>/', $html, $matches);
        if (isset($matches[1])) {
            return Image::fromUrl($matches[1]);
        }

        return null;
    }
}
