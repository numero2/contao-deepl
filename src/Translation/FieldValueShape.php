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
 * How the stored value of a field is laid out, and therefore which part of it
 * carries text:
 *
 * - plain:          the value itself
 * - inputUnit:      serialized ['value' => …, 'unit' => …], only "value"
 * - optionWizard:   serialized list of ['value' => …, 'label' => …], only "label"
 * - keyValueWizard: serialized list of ['key' => …, 'value' => …], only "value"
 * - listWizard:     serialized list of strings, every entry
 */
enum FieldValueShape: string {

    case Plain = 'plain';
    case InputUnit = 'inputUnit';
    case OptionWizard = 'optionWizard';
    case KeyValueWizard = 'keyValueWizard';
    case ListWizard = 'listWizard';


    public static function fromInputType( string $inputType ): ?self {

        return match( $inputType ) {
            'text', 'textarea' => self::Plain,
            'inputUnit' => self::InputUnit,
            'optionWizard' => self::OptionWizard,
            'keyValueWizard' => self::KeyValueWizard,
            'listWizard' => self::ListWizard,
            default => null,
        };
    }
}
