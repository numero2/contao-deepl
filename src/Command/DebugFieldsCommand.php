<?php

/**
 * DeepL Translations Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @license   LGPL-3.0-or-later
 * @copyright Copyright (c) 2026, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\DeepLBundle\Command;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\DcaLoader;
use Doctrine\DBAL\Connection;
use numero2\DeepLBundle\LanguageResolver\TableAwareResolverInterface;
use numero2\DeepLBundle\Translation\FieldDecisionReason;
use numero2\DeepLBundle\Translation\TranslatableFieldClassifier;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\VarExporter\LazyObjectInterface;


#[AsCommand(
    name: 'debug:deepl-fields',
    description: 'Shows which fields of a table are offered for translation, and why - provided a language resolver can handle the table at all.',
)]
class DebugFieldsCommand extends Command {


    /**
     * The tables the bundled language resolvers can handle, shown when no
     * table is given. Tables of bundles that are not installed are skipped.
     */
    private const DEFAULT_TABLES = ['tl_page','tl_article','tl_content','tl_form','tl_form_field','tl_news_archive','tl_news','tl_calendar','tl_calendar_events'];

    private ContaoFramework $framework;
    private TranslatableFieldClassifier $classifier;
    private iterable $languageResolvers;
    private Connection $connection;


    public function __construct( ContaoFramework $framework, TranslatableFieldClassifier $classifier, iterable $languageResolvers, Connection $connection ) {

        $this->framework = $framework;
        $this->classifier = $classifier;
        $this->languageResolvers = $languageResolvers;
        $this->connection = $connection;

        parent::__construct();
    }


    protected function configure(): void {

        $this
            ->addArgument('table', InputArgument::OPTIONAL, 'The table name, e.g. tl_content')
            ->addOption('all', 'a', InputOption::VALUE_NONE, 'Also list fields that are skipped for their input type (checkboxes, selects …)')
        ;
    }


    protected function execute( InputInterface $input, OutputInterface $output ): int {

        $io = new SymfonyStyle($input, $output);
        $tables = $input->getArgument('table') ? [$input->getArgument('table')] : self::DEFAULT_TABLES;

        $this->framework->initialize();

        foreach( $tables as $table ) {

            try {
                $this->framework->createInstance(DcaLoader::class, [$table])->load();
            } catch( \Throwable $e ) {
                $io->warning(sprintf('%s: %s', $table, $e->getMessage()));
                continue;
            }

            if( empty($GLOBALS['TL_DCA'][$table]['fields']) ) {

                // an explicitly requested table must exist, the defaults may not
                if( $input->getArgument('table') ) {
                    $io->error(sprintf('Invalid table name: %s', $table));
                    return Command::FAILURE;
                }

                continue;
            }

            $io->section($table);

            // Without a target language the edit mask never gets a button, no
            // matter what the classifier says - so the fields are only worth
            // listing when some resolver can handle the table.
            [$supporting, $undeclared] = $this->findResolvers($table);

            if( !$supporting && !$undeclared ) {

                $io->warning(sprintf('No language resolver supports %s, so no target language can be determined and no translate buttons are shown. Add a resolver implementing %s for it.', $table, 'LanguageResolverInterface'));
                continue;
            }

            if( $supporting ) {

                $io->text(sprintf('Language resolver: <info>%s</info>', implode(', ', $supporting)));

            } else {

                $io->note(sprintf('No resolver declares %s. These resolvers do not tell which tables they handle, buttons only appear if one of them supports it: %s', $table, implode(', ', $undeclared)));
            }

            $this->writeParentCoverage($io, $table);

            $rows = [];

            foreach( $this->classifier->classifyTable($table) as $name => $decision ) {

                if( $decision->reason === FieldDecisionReason::InputType && !$input->getOption('all') ) {
                    continue;
                }

                $rows[] = [
                    $name
                ,   $GLOBALS['TL_DCA'][$table]['fields'][$name]['inputType'] ?? ''
                ,   $decision->translatable ? '<info>yes</info>' : '<comment>no</comment>'
                ,   $decision->reason->value
                ];
            }

            $io->table(['Field', 'Input type', 'Translate', 'Reason'], $rows);
        }

        return Command::SUCCESS;
    }


    /**
     * The resolvers declaring support for the table, and those that cannot
     * tell because they do not implement TableAwareResolverInterface.
     *
     * @return array{0: string[], 1: string[]}
     */
    private function findResolvers( string $table, ?string $parentTable=null ): array {

        $supporting = [];
        $undeclared = [];

        foreach( $this->languageResolvers as $resolver ) {

            // resolvers are lazy services, a proxy would show its generated class name
            $class = $resolver instanceof LazyObjectInterface ? get_parent_class($resolver) : $resolver::class;
            $name = (new \ReflectionClass($class))->getShortName();

            if( !$resolver instanceof TableAwareResolverInterface ) {
                $undeclared[] = $name;
            } elseif( $resolver->supportsTable($table, $parentTable) ) {
                $supporting[] = $name;
            }
        }

        return [$supporting, $undeclared];
    }


    /**
     * For tables whose records hang below different parents (tl_content),
     * support depends on the parent: content of an article resolves, content
     * of a record from another extension may not. Lists every parent actually
     * in use, so the gap shows up before an editor wonders about it.
     *
     * Nested records (the table as its own parent) are skipped, they resolve
     * through the top-most parent.
     */
    private function writeParentCoverage( SymfonyStyle $io, string $table ): void {

        if( empty($GLOBALS['TL_DCA'][$table]['config']['dynamicPtable']) ) {
            return;
        }

        try {
            $parents = $this->connection->fetchFirstColumn(sprintf('SELECT DISTINCT ptable FROM %s', $this->connection->quoteIdentifier($table)));
        } catch( \Throwable $e ) {
            $io->note(sprintf('Could not read the parent tables of %s: %s', $table, $e->getMessage()));
            return;
        }

        $rows = [];

        foreach( $parents as $parent ) {

            if( !$parent || $parent === $table ) {
                continue;
            }

            [$supporting, $undeclared] = $this->findResolvers($table, $parent);

            $rows[] = [
                $parent
            ,   $supporting ? '<info>'.implode(', ', $supporting).'</info>' : ($undeclared ? '<comment>unknown</comment>' : '<error>none - no buttons</error>')
            ];
        }

        if( $rows ) {
            $io->table(['Records below', 'Language resolver'], $rows);
        }
    }
}
