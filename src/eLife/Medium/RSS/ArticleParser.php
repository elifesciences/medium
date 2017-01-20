<?php

namespace eLife\Medium\RSS;

use eLife\Medium\Model\Image;
use SimpleXMLIterator;
use Symfony\Component\DomCrawler\Crawler;

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
        $crawler = new Crawler((string) $html);
        $paragraphs = $crawler->filterXPath('//p');
        foreach ($paragraphs as $paragraph) {
            /** @var \DOMElement $paragraph */
            if ($text = $paragraph->textContent) {
                return $text;
            }
        }
        return '';
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
