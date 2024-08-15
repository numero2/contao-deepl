<?php

/**
 * DeepL Translations Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   LGPL-3.0-or-later
 * @copyright Copyright (c) 2024, numero2 - Agentur für digitales Marketing GbR
 */


/**
 * Add fields to tl_page
 */
$GLOBALS['TL_DCA']['tl_page']['fields']['bh_info'] = [
    'inputType'             => 'text'
,   'exclude'               => true
,   'eval'                  => ['maxlength'=>255, 'doNotCopy'=>true, 'tl_class'=>'w50']
,   'sql'                   => "varchar(255) NOT NULL default ''"
];
