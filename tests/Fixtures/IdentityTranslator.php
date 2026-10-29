<?php

namespace Dmytrof\ImportBundle\Tests\Fixtures;

use Symfony\Contracts\Translation\{TranslatorInterface, TranslatorTrait};

/**
 * Translator that returns message ids as-is (with parameters replaced).
 */
final class IdentityTranslator implements TranslatorInterface
{
    use TranslatorTrait;
}
