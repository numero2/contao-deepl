<?php

/**
 * DeepL Translations Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @license   LGPL-3.0-or-later
 * @copyright Copyright (c) 2026, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\DeepLBundle\Translation;

use Symfony\Contracts\Service\ResetInterface;


/**
 * Decides which DCA fields hold text worth translating.
 *
 * The decision reads the metadata a DCA already carries instead of relying on
 * field names, so fields of third-party extensions are judged the same way as
 * core fields. In order, the first stage that has an opinion wins:
 *
 * 1. hard requirements: a text-like inputType, not readonly or disabled -
 *    nothing can opt a field past these, there is no text to put a button on
 * 2. the project configuration (deepl.fields), "table.field" before "*.field"
 * 3. the field's own 'translate' => true|false flag in the DCA
 * 4. heuristics on eval and sql that mark code, identifiers, numbers and
 *    secrets
 * 5. DEFAULT_EXCLUDES, for what only a field name gives away
 */
class TranslatableFieldClassifier implements ResetInterface {


    /**
     * Fields that look like text to every heuristic but are not - settings,
     * identifiers and code that the DCA does not mark as such. Kept as a
     * default rather than a hard rule: a project can switch any of them back
     * on with "deepl.fields".
     */
    public const DEFAULT_EXCLUDES = [
        '*.cssClass'
    ,   '*.cssID'
    ,   '*.class'
    ,   '*.language'
    ,   '*.urlSuffix'
    ,   '*.timeFormat'
    ,   '*.dateFormat'
    ,   '*.datimFormat'
    ,   '*.attributes'
    ,   '*.formID'
    ,   '*.mooStyle'
    ,   '*.rel'
    ,   '*.csp'
    ,   '*.robotsTxt'
    ,   '*.canonicalKeepParams'
    ,   '*.customRgxp'
    ,   '*.autocomplete'
    ,   'tl_form_field.value'
    ,   '*.bh_info'
    ];

    private const NUMERIC_SQL_TYPES = ['integer','smallint','bigint','decimal','float','boolean'];
    private const BINARY_SQL_TYPES = ['binary','blob','guid'];

    private array $fields;
    private array $cache = [];


    /**
     * @param array<string, bool> $fields The project configuration, "table.field" or "*.field" => bool
     */
    public function __construct( array $fields=[] ) {

        $this->fields = $fields;
    }


    /**
     * The translatable fields of a loaded DCA.
     *
     * @return array<string, FieldDecision>
     */
    public function getTranslatableFields( string $table ): array {

        return array_filter(
            $this->classifyTable($table)
        ,   static fn( FieldDecision $decision ) => $decision->translatable
        );
    }


    /**
     * The decision for every field of a loaded DCA.
     *
     * @return array<string, FieldDecision>
     */
    public function classifyTable( string $table ): array {

        if( !isset($this->cache[$table]) ) {

            $this->cache[$table] = [];

            foreach( $GLOBALS['TL_DCA'][$table]['fields'] ?? [] as $name => $config ) {
                $this->cache[$table][$name] = $this->classify($table, (string) $name, (array) $config);
            }
        }

        return $this->cache[$table];
    }


    public function classify( string $table, string $field, array $config ): FieldDecision {

        $shape = FieldValueShape::fromInputType((string) ($config['inputType'] ?? ''));
        $eval = (array) ($config['eval'] ?? []);

        if( $shape === null ) {
            return new FieldDecision(false, FieldDecisionReason::InputType);
        }

        if( !empty($eval['readonly']) || !empty($eval['disabled']) ) {
            return new FieldDecision(false, FieldDecisionReason::Readonly, $shape);
        }

        $configured = $this->fields[$table.'.'.$field] ?? $this->fields['*.'.$field] ?? null;

        if( $configured !== null ) {
            return new FieldDecision((bool) $configured, FieldDecisionReason::Config, $shape);
        }

        if( isset($config['translate']) ) {
            return new FieldDecision((bool) $config['translate'], FieldDecisionReason::DcaFlag, $shape);
        }

        $excludedBy = $this->findHeuristicExclusion($config, $eval, $shape);

        if( $excludedBy !== null ) {
            return new FieldDecision(false, $excludedBy, $shape);
        }

        if( in_array($table.'.'.$field, self::DEFAULT_EXCLUDES, true) || in_array('*.'.$field, self::DEFAULT_EXCLUDES, true) ) {
            return new FieldDecision(false, FieldDecisionReason::DefaultExclude, $shape);
        }

        return new FieldDecision(true, FieldDecisionReason::Translatable, $shape);
    }


    public function reset(): void {

        $this->cache = [];
    }


    /**
     * The first signal marking the field as something other than prose, or
     * null when there is none. The order only decides which reason is
     * reported, the outcome is the same.
     */
    private function findHeuristicExclusion( array $config, array $eval, FieldValueShape $shape ): ?FieldDecisionReason {

        // format-validated values: URLs, e-mail addresses, aliases, dates, numbers …
        if( !empty($eval['rgxp']) ) {
            return FieldDecisionReason::Rgxp;
        }

        // the core writes "ace|html", "ace|css" etc., not just "ace"
        if( str_starts_with((string) ($eval['rte'] ?? ''), 'ace') ) {
            return FieldDecisionReason::CodeEditor;
        }

        if( preg_match('/(^|\s)monospace(\s|$)/', (string) ($eval['class'] ?? '')) ) {
            return FieldDecisionReason::Monospace;
        }

        if( !empty($eval['useRawRequestData']) || !empty($eval['preserveTags']) ) {
            return FieldDecisionReason::RawData;
        }

        if( !empty($eval['nospace']) ) {
            return FieldDecisionReason::NoSpace;
        }

        if( !empty($eval['colorpicker']) || !empty($eval['isHexColor']) ) {
            return FieldDecisionReason::Color;
        }

        if( !empty($eval['hideInput']) || !empty($eval['encrypt']) ) {
            return FieldDecisionReason::Secret;
        }

        // tuples such as cssID or teaserCssID
        if( ($config['inputType'] ?? '') === 'text' && !empty($eval['multiple']) ) {
            return FieldDecisionReason::MultipleText;
        }

        return $this->findSqlExclusion($config['sql'] ?? null, $shape);
    }


    /**
     * Reads the column definition in both notations Contao accepts: the
     * classic SQL string and the Doctrine array.
     *
     * Blob columns only count as binary for plain fields - the wizards store
     * their serialized rows in a blob, and those rows are exactly the text.
     */
    private function findSqlExclusion( mixed $sql, FieldValueShape $shape ): ?FieldDecisionReason {

        if( is_string($sql) && $sql !== '' ) {

            if( preg_match('/^\s*(tiny|small|medium|big)?int\b|^\s*(decimal|numeric|float|double|real)\b/i', $sql) ) {
                return FieldDecisionReason::NumericSql;
            }

            if( preg_match('/\bbinary\b|\bascii(_bin)?\b|_bin\b/i', $sql) ) {
                return FieldDecisionReason::BinarySql;
            }

            if( $shape === FieldValueShape::Plain && preg_match('/^\s*(tiny|medium|long)?blob\b/i', $sql) ) {
                return FieldDecisionReason::BinarySql;
            }

            return null;
        }

        if( is_array($sql) ) {

            $type = strtolower((string) ($sql['type'] ?? ''));

            if( in_array($type, self::NUMERIC_SQL_TYPES, true) ) {
                return FieldDecisionReason::NumericSql;
            }

            if( $shape === FieldValueShape::Plain && in_array($type, self::BINARY_SQL_TYPES, true) ) {
                return FieldDecisionReason::BinarySql;
            }

            // the collation may sit in customSchemaOptions, platformOptions or options
            $collation = '';
            array_walk_recursive($sql, static function( $value, $key ) use ( &$collation ) {
                if( $key === 'collation' ) {
                    $collation = (string) $value;
                }
            });

            if( preg_match('/^ascii|_bin$/i', $collation) ) {
                return FieldDecisionReason::BinarySql;
            }
        }

        return null;
    }
}
