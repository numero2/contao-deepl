<?php

/**
 * DeepL Translations Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   LGPL-3.0-or-later
 * @copyright Copyright (c) 2024, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\DeepLBundle\LanguageResolver;

use Contao\ContentModel;
use Contao\DataContainer;
use Contao\CalendarModel;
use Contao\CalendarBundle\ContaoCalendarBundle;
use Contao\CalendarEventsModel;


class CalendarResolver extends DefaultResolver {


    public function supports( DataContainer $dc ): bool {

        if( !class_exists(ContaoCalendarBundle::class) ) {
            return false;
        }

        if( ($dc->table === ContentModel::getTable() && $dc->parentTable === CalendarEventsModel::getTable()) || $dc->table === CalendarModel::getTable() ) {
            return true;
        }

        if( $dc->table === CalendarEventsModel::getTable() && $dc->parentTable === CalendarModel::getTable() ) {
            return true;
        }

        if( $dc->table === CalendarModel::getTable() ) {
            return true;
        }

        return false;
    }


    public function resolve( DataContainer $dc ): string {

        $lang = null;

        // tl_content
        if( $dc->table === ContentModel::getTable() && $dc->parentTable === CalendarEventsModel::getTable()) {

            $content = ContentModel::findOneBy('id', $dc->id);

            $event = CalendarEventsModel::findOneBy('id', $content->pid);

            if( !$event ) {
                return '';
            }

            $calendar = CalendarModel::findOneBy('id', $event->pid);

            $lang = $this->getRootLangForPageID((int) $calendar->jumpTo);

        // tl_calendar_events
        } elseif( $dc->table === CalendarEventsModel::getTable() ) {

            $event = CalendarEventsModel::findOneBy('id', $dc->id);

            $caelndar = CalendarModel::findOneBy('id', $event->pid);

            if( !$caelndar ) {
                return '';
            }

            $lang = $this->getRootLangForPageID((int) $caelndar->jumpTo);

        // tl_calendar
        } elseif( $dc->table === CalendarModel::getTable() ) {

            $calendar = CalendarModel::findOneBy('id', $dc->id);
            $lang = $this->getRootLangForPageID((int) $calendar->jumpTo);
        }

        return parent::mapLangauge($lang);
    }
}