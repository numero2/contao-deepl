<?php

/**
 * DeepL Translations Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @license   LGPL-3.0-or-later
 * @copyright Copyright (c) 2026, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\DeepLBundle\Translation;


final class FieldDecision {


    public function __construct(
        public readonly bool $translatable,
        public readonly FieldDecisionReason $reason,
        public readonly ?FieldValueShape $valueShape = null,
    ) {
    }
}
