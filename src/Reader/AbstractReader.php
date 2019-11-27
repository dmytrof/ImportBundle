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

use Dmytrof\ImportBundle\Exception\ReaderException;
use Dmytrof\ImportBundle\Model\Task;
use Dmytrof\ImportBundle\Reader\Options\ReaderOptionsInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;
use Zend\Json\Json;

abstract class AbstractReader implements ReaderInterface
{
    public const CODE = null;
    public const TITLE = null;
    public const OPTIONS_CLASS = null;
    public const OPTIONS_FORM_CLASS = null;
    public const DATA_IN_ROOT = false;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var Task
     */
    protected $task;

    /**
     * @var Client
     */
    protected $client;

    /**
     * AbstractReader constructor.
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public static function getCode(): string
    {
        return static::CODE;
    }

    /**
     * {@inheritdoc}
     */
    public static function getOptionsClass(): ?string
    {
        return static::OPTIONS_CLASS;
    }

    /**
     * {@inheritdoc}
     */
    public static function getOptionsFormClass(): ?string
    {
        return static::OPTIONS_FORM_CLASS;
    }

    /**
     * {@inheritdoc}
     */
    public static function isDataInRoot(): bool
    {
        return static::DATA_IN_ROOT;
    }

    /**
     * {@inheritdoc}
     */
    public static function hasOptions(): bool
    {
        return static::getOptionsClass() !== null;
    }

    public function __clone()
    {
        $this->client = null;
    }

    /**
     * @return TranslatorInterface
     */
    public function getTranslator(): TranslatorInterface
    {
        return $this->translator;
    }

    /**
     * Returns task
     * @return Task|null
     */
    public function getTask(): ?Task
    {
        return $this->task;
    }

    /**
     * Sets task
     * @param Task $task
     * @return AbstractReader
     */
    public function setTask(Task $task): ReaderInterface
    {
        $this->task = $task;
        return $this;
    }

    /**
     * Returns title of saver
     * @return string
     */
    public function getTitle(): string
    {
        return $this->getTranslator()->trans(static::TITLE ?: 'untitled');
    }

    /**
     * {@inheritdoc}
     */
    public function getOptions(): ?ReaderOptionsInterface
    {
        return $this->hasOptions() ? $this->getTask()->getReaderOptions() : null;
    }

    /**
     * Returns client
     * @return Client
     */
    protected function getClient(): Client
    {
        if (is_null($this->client)) {
            $this->client = new Client();
        }
        return $this->client;
    }

    /**
     * Returns response from request to link
     * @param string $link
     * @return ResponseInterface
     */
    protected function getLinkResponse(string $link): ResponseInterface
    {
        try {
            $response = $this->getClient()->get($link);
        } catch (ClientException $e) {
            $message = $e->getMessage();
            if ($e->hasResponse()) {
                if ($e->getResponse()->getStatusCode() == 404) {
                    $message = 'Resource not found';
                }
            }
            throw new ReaderException($message);
        }

        if ($response->getStatusCode() !== Response::HTTP_OK) {
            throw new ReaderException(sprintf('Response status is not OK: %s. Uri: %s', $response->getStatusCode(), $link));
        }
        return $response;
    }

    /**
     * Checks if response has header with exact value
     * @param ResponseInterface $response
     * @param string $headerName
     * @param string $value
     * @return bool
     */
    protected function hasResponseHeader(ResponseInterface $response, string $headerName, string $value): bool
    {
        return in_array(strtolower($value), array_map('strtolower', $response->getHeader($headerName)));
    }

    /**
     * Checks if response has header with any value
     * @param ResponseInterface $response
     * @param string $headerName
     * @param array $value
     * @return bool
     */
    protected function hasResponseHeaderAnyValue(ResponseInterface $response, string $headerName, array $value): bool
    {
        return !empty(array_intersect(array_map('strtolower', (array) $value), array_map('strtolower', $response->getHeader($headerName))));
    }

    /**
     * Converts json to array
     * @param string $json
     * @return array
     */
    protected function convertJsonToArray(string $json): array
    {
        try {
            $data = Json::decode($json, Json::TYPE_ARRAY);
        } catch (\Exception $e) {
            throw new ReaderException(sprintf('Invalid JSON data'));
        }
        return $data;
    }

    /**
     * Convert date into DateTime object
     * @param integer|string|\DateTime|null $date
     * @return \DateTime
     */
    protected function convertIntoDateTimeObject($date): \DateTime
    {
        $strFormats = [
            'Y-m-d\TH:i:s',
        ];
        if (!$date instanceof \DateTime) {
            $source = $date;
            if (is_scalar($date)) {
                $source = trim($date);
                if ($source == (string) (int) $source) {
                    $timestamp = strlen($source) > 10 ? substr($source, 0, 10) : $source;
                    $date = \DateTime::createFromFormat('U', $timestamp);
                } else {
                    try {
                        $date = new \DateTime($source);
                    } catch (\Exception $e) {
                        foreach ($strFormats as $format) {
                            $date = \DateTime::createFromFormat($format, $source);
                            if ($date instanceof \DateTime) {
                                break;
                            }
                        }
                    }
                }
            }

            if (!$date instanceof \DateTime) {
                $date = new \DateTime();
//                $this->last_error = sprintf('Unable to create DateTime object from "%s"', $source);
            }
        }

        return $date;
    }

    /**
     * Configures getDataFromLink options
     * @param OptionsResolver $resolver
     * @return OptionsResolver
     */
    public function configureGetDataFromLinkOptions(OptionsResolver $resolver): OptionsResolver
    {
        $resolver->setDefault('exampleData', false);

        $resolver->setAllowedTypes('exampleData', ['bool']);
        return $resolver;
    }
}