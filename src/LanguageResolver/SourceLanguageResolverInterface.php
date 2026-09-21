<?php

/**
 * DeepL Translations Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   LGPL-3.0-or-later
 * @copyright Copyright (c) 2026, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\DeepLBundle\LanguageResolver;

use Contao\DataContainer;


/**
 * Resolves the language a record is translated FROM.
 *
 * Separate from LanguageResolverInterface on purpose: adding the method there
 * would break every resolver a third party has already written. A resolver that
 * does not implement this interface simply contributes no source language, and
 * the configured deepl.source_lang is used instead.
 */
interface SourceLanguageResolverInterface {

    /**
     * Returns the source language for the given record, or an empty string when
     * it cannot be determined.
     */
    public function resolveSource( DataContainer $dc ): string;
}
