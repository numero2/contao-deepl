<?php

/**
 * DeepL Translations Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @license   LGPL-3.0-or-later
 * @copyright Copyright (c) 2026, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\DeepLBundle\Translation;


/**
 * Why a field was or was not considered translatable - the stage of the
 * decision that settled it, so a surprising result can be traced back to a
 * single rule (see debug:deepl-fields).
 */
enum FieldDecisionReason: string {

    case Config = 'config';
    case DcaFlag = 'dca_flag';
    case InputType = 'input_type';
    case Readonly = 'readonly';
    case Rgxp = 'rgxp';
    case CodeEditor = 'code_editor';
    case Monospace = 'monospace';
    case RawData = 'raw_data';
    case NoSpace = 'nospace';
    case Color = 'color';
    case Secret = 'secret';
    case MultipleText = 'multiple_text';
    case NumericSql = 'numeric_sql';
    case BinarySql = 'binary_sql';
    case DefaultExclude = 'default_exclude';
    case Translatable = 'translatable';
}
