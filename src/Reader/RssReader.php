<?php

/*
 * This file is part of the DmytrofImportBundle package.
 *
 * (c) Dmytro Feshchenko <dmytro.feshchenko@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dmytrof\ImportBundle\Reader;

use Doctrine\Inflector\{Inflector, NoopWordInflector};
use Dmytrof\ImportBundle\Model\ImportedData;
use Laminas\Feed\Reader\{Reader, Entry\EntryInterface};
use Symfony\Component\Console\Style\SymfonyStyle;

class RssReader extends AbstractReader
{
    public const CODE = 'rss';
    public const TITLE = 'dmytrof.reader.rss.label';
    public const OPTIONS_CLASS = null;
    public const OPTIONS_FORM_CLASS = null;
    public const DATA_IN_ROOT = true;

    /**
     * {@inheritdoc}
     */
    public function getDataFromLink(string $link, array $options = [], ?SymfonyStyle $io = null): ImportedData
    {
        $response = $this->getLinkResponse($link, $options, $io);
        $rawData = $response->getBody()->getContents();

        $xml = new \SimpleXMLElement($rawData);

        $namespaces = $xml->getDocNamespaces();

        $feed = Reader::importString($rawData);

        $data = [];
        foreach ($feed as $entry) {
            $data[] = $this->parseEntryToArray($entry, $namespaces);
        }

        return (new ImportedData($data))->setDataInRoot($this->isDataInRoot());
    }

    /**
     * Parses entry to array
     * @param EntryInterface $entry
     * @param array $namespaces
     * @return array
     */
    protected function parseEntryToArray(EntryInterface $entry, array $namespaces)
    {
        $data = [
            'id'          => $entry->getId(),
            'title'       => $entry->getTitle(),
            'content'     => $entry->getContent(),
            'description' => $entry->getDescription(),
            'updatedAt'   => $entry->getDateModified() ? $this->convertIntoDateTimeObject($entry->getDateModified()) : null,
            'createdAt'   => $entry->getDateCreated() ? $this->convertIntoDateTimeObject($entry->getDateCreated()) : null,
            'authors'     => $entry->getAuthors() ? $entry->getAuthors()->getValues() : [],
            'categories'  => $entry->getCategories() ? $entry->getCategories()->getValues() : [],
            'links'       => $entry->getLinks(),
            'enclosure'   => $entry->getEnclosure(),
        ];

        $data = array_merge($data, $this->parseNamespaces($entry, $namespaces));

        return $data;
    }

    /**
     * Parses entry namespaces
     * @param EntryInterface $entry
     * @param array $namespaces
     * @return array
     */
    protected function parseNamespaces(EntryInterface $entry, array $namespaces): array
    {
        $data = [];
        $inflector = new Inflector(new NoopWordInflector(), new NoopWordInflector());
        foreach ($namespaces as $key => $link) {
            $methodName = 'parse'.$inflector->camelize($key).'Namespace';
            if (method_exists($this, $methodName)) {
                $data[$key] = $this->$methodName($entry, $link);
            }
        }
        return $data;
    }

    /**
     * Parses values from namespace media
     * @param EntryInterface $entry
     * @param string $link
     * @return array[]
     */
    protected function parseMediaNamespace(EntryInterface $entry, string $link): array
    {
        $images = [];
        $videos = [];
        $audios = [];

        $getMediaData = function (\DOMElement $mediaNode) {
            $data = [
                'url'   => $mediaNode->getAttribute('url'),
                'type'  => $mediaNode->getAttribute('type'),
                'size'  => $mediaNode->getAttribute('fileSize'),
            ];
            /** @var \DOMElement $childNode */
            foreach ($mediaNode->childNodes as $childNode) {
                if (!$childNode instanceof \DOMElement) {
                    continue;
                }
                if (in_array($childNode->tagName, ['media:title', 'media:description'])) {
                    $data[str_replace('media:', '', $childNode->tagName)] = $childNode->textContent;
                } else if ($childNode->tagName === 'media:thumbnail') {
                    $data[str_replace('media:', '', $childNode->tagName)] = $childNode->getAttribute('url');
                }
            }
            return $data;
        };

        $nodeList = $entry->getXpath()->evaluate($entry->getXpathPrefix() . '/media:content');
        if ($nodeList->length > 0) {
            /** @var \DOMElement $mediaNode */
            foreach($nodeList as $mediaNode) {
                if (!$mediaNode->getAttribute('medium') || $mediaNode->getAttribute('medium') === 'image') {
                    $images[] = $getMediaData->call($this, $mediaNode);
                } elseif ($mediaNode->getAttribute('medium') === 'video') {
                    $videos[] = array_merge($getMediaData->call($this, $mediaNode), [
                        'duration' => $mediaNode->getAttribute('duration'),
                        'lang' => $mediaNode->hasAttribute('language') ? $mediaNode->getAttribute('language') : $mediaNode->getAttribute('lang'),
                    ]);
                } elseif ($mediaNode->getAttribute('medium') === 'audio') {
                    $audios[] = array_merge($getMediaData->call($this, $mediaNode), [
                        'duration' => $mediaNode->getAttribute('duration'),
                        'lang' => $mediaNode->hasAttribute('language') ? $mediaNode->getAttribute('language') : $mediaNode->getAttribute('lang'),
                    ]);
                }
            }
        }

        $thumbs = [];
        $nodeList = $entry->getXpath()->evaluate($entry->getXpathPrefix() . '/media:thumbnail');
        if ($nodeList->length > 0) {
            foreach($nodeList as $mediaNode) {
                $thumbs[] = array_merge($getMediaData->call($this, $mediaNode), [
                    'width' => $mediaNode->getAttribute('width'),
                    'height' => $mediaNode->getAttribute('height'),
                ]);
            }
        }

        return [
            'images' => $images,
            'videos' => $videos,
            'thumbs' => $thumbs,
            'audios' => $audios,
        ];
    }
}
