<?php

/**
 * DeepL Translations Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @license   LGPL-3.0-or-later
 * @copyright Copyright (c) 2026, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\DeepLBundle\LanguageResolver;


/**
 * Tells in advance which tables a resolver can handle.
 *
 * supports() can only answer for one record, because it needs the
 * DataContainer of an open edit mask. This answers for a whole table, so
 * debug:deepl-fields can show where no target language can be determined, and
 * therefore no translate button will ever appear.
 *
 * Separate from LanguageResolverInterface for the same reason as
 * SourceLanguageResolverInterface: adding the method there would break every
 * resolver a third party has already written. A resolver that does not
 * implement it is reported as "cannot tell".
 */
interface TableAwareResolverInterface {

    /**
     * Whether records of the given table can be resolved at all.
     *
     * For tables whose records can hang below different parents (tl_content),
     * $parentTable narrows the question to records below that parent. Without
     * it, the answer is whether any of its records can be resolved.
     */
    public function supportsTable( string $table, ?string $parentTable=null ): bool;
}
