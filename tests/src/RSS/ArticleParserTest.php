<?php

namespace tests\eLife\Medium\RSS;

use eLife\Medium\Model\Image;
use eLife\Medium\RSS\ArticleParser;
use PHPUnit_Framework_TestCase;

final class ArticleParserTest extends PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function testGetItems()
    {
        $parser = new ArticleParser(self::$fixture);
        $numberOfItems = 0;
        foreach ($parser->getItems() as $item) {
            ++$numberOfItems;
            $this->assertSame('find me!', (string) $item);
        }
        $this->assertEquals(3, $numberOfItems);
    }

    /**
     * @test
     */
    public function testParseParagraph()
    {
        $parser = new ArticleParser(self::$fixture);

        $text = $parser->parseParagraph('<p class="medium-feed-snippet">Find me?</p>');
        $this->assertEquals('Find me?', $text);

        $text = $parser->parseParagraph('<b>some other html before</b><p class="medium-feed-snippet">Find me?</p>');
        $this->assertEquals('Find me?', $text);

        $text = $parser->parseParagraph('<b>some other html before</b><p class="medium-feed-snippet">Find me?</p><p>some other paragraph after</p>');
        $this->assertEquals('Find me?', $text);

        $text = $parser->parseParagraph('<p class="medium-feed-snippet">Find me?</b>');
        $this->assertEquals('Find me?', $text, 'Parser makes best guess with invalid input');

        $text = $parser->parseParagraph('<p class="medium-feed-snippet">Find me?');
        $this->assertEquals('Find me?', $text, 'Parser makes best guess with invalid input');

        $text = $parser->parseParagraph('<b>no paragraph</b>');
        $this->assertEquals('', $text);
    }

    public function testParseImage()
    {
        $parser = new ArticleParser(self::$fixture);

        $image = $parser->parseImage('<img src="https://d262ilb51hltx0.cloudfront.net/max/960/1*r8Dosc01-Cyo82eZo-oRMA.png" />');

        $this->assertInstanceOf(Image::class, $image);
        $this->assertEquals('d262ilb51hltx0.cloudfront.net', $image->getDomain());
        $this->assertEquals('1*r8Dosc01-Cyo82eZo-oRMA.png', $image->getPath());

        $image = $parser->parseImage('<img src="https://d262ilb51hltx0.cloudfront.net/fit/c/500/960/1*r8Dosc01-Cyo82eZo-oRMA.png" />');

        $this->assertInstanceOf(Image::class, $image);
        $this->assertEquals('d262ilb51hltx0.cloudfront.net', $image->getDomain());
        $this->assertEquals('1*r8Dosc01-Cyo82eZo-oRMA.png', $image->getPath());
    }

    public function testParseImageFail() {
      $parser = new ArticleParser(self::$fixture);
      $image = $parser->parseImage('<b>NOT AN IMAGE</b>');

      $this->assertNull($image);
    }

    public static $fixture = <<<'XML'
<?xml version="1.0" ?>
<root>
    <items>
        <item>find me!</item>
        <item>find me!</item>
        <wat>don't find me!</wat>
        <wat>
            <item>don't find me!</item>
        </wat>
    </items>
    <items>
        <item>find me!</item>
        <wat>don't find me!</wat>
    </items>
    <item>don't find me!</item>
</root>
XML;
}
