<?php

namespace Dmytrof\ImportBundle\Tests\Unit\Reader;

use Dmytrof\ImportBundle\Reader\RssReader;
use Dmytrof\ImportBundle\Tests\Fixtures\IdentityTranslator;
use Dmytrof\ImportBundle\Tests\Fixtures\Reader\MockedRssReader;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class RssReaderTest extends TestCase
{
    private const FEED = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0" xmlns:media="http://search.yahoo.com/mrss/">
  <channel>
    <title>Feed</title><link>https://example.com</link><description>Test feed</description>
    <item>
      <guid>post-1</guid>
      <title>First post</title>
      <description>Summary</description>
      <link>https://example.com/post-1</link>
      <category>News</category>
      <pubDate>Mon, 26 Oct 2026 10:00:00 +0000</pubDate>
      <media:content url="https://example.com/a.jpg" medium="image" fileSize="100">
        <media:title>Picture</media:title>
      </media:content>
      <media:content url="https://example.com/v.mp4" medium="video" duration="60" lang="en"/>
      <media:thumbnail url="https://example.com/t.jpg" width="120" height="90"/>
    </item>
    <item>
      <guid>post-2</guid>
      <title>Second post</title>
    </item>
  </channel>
</rss>
XML;

    public function testReadsEntries(): void
    {
        $reader = (new MockedRssReader(new IdentityTranslator()))->queueResponses([new Response(200, [], self::FEED)]);

        $data = $reader->getDataFromLink('https://example.com/rss');
        $entries = $data->getData();

        $this->assertSame('rss', RssReader::getCode());
        $this->assertTrue($data->isDataInRoot());
        $this->assertCount(2, $entries);

        $first = $entries[0];
        $this->assertSame('post-1', $first['id']);
        $this->assertSame('First post', $first['title']);
        $this->assertSame('Summary', $first['description']);
        $this->assertSame(['https://example.com/post-1'], $first['links']);
        $this->assertSame(['News'], $first['categories']);
        $this->assertInstanceOf(\DateTime::class, $first['createdAt']);
        $this->assertSame('2026-10-26T10:00:00+00:00', $first['createdAt']->format(DATE_ATOM));

        $this->assertSame([
            ['url' => 'https://example.com/a.jpg', 'type' => '', 'size' => '100', 'title' => 'Picture'],
        ], $first['media']['images']);
        $this->assertSame('60', $first['media']['videos'][0]['duration']);
        $this->assertSame('en', $first['media']['videos'][0]['lang']);
        $this->assertSame(['url' => 'https://example.com/t.jpg', 'type' => '', 'size' => '', 'width' => '120', 'height' => '90'], $first['media']['thumbs'][0]);
        $this->assertSame([], $first['media']['audios']);

        $this->assertNull($entries[1]['createdAt']);
        $this->assertSame([], $entries[1]['media']['images']);
    }
}
