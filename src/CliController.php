<?php

namespace splitbrain\meh;

use splitbrain\phpcli\Options;
use splitbrain\phpcli\PSR3CLIv3;
use splitbrain\phpsqlite\SQLite;

class CliController extends PSR3CLIv3
{

    protected function setup(Options $options)
    {
        $options->setHelp('Command line tool for the meh commenting system');

        $options->registerCommand('migrate', 'Upgrade the database structures');

        $options->registerCommand('disqus', 'Import comments from disqus');
        $options->registerArgument('export.xml', 'The export file to import', true, 'disqus');
    }

    protected function main(Options $options)
    {
        switch ($options->getCmd()) {
            case 'migrate':
                $this->migrateDatabase();
                break;
            case 'disqus':
                $this->importDisqus($options->getArgs()[0]);
                break;
            default:
                echo $options->help();
        }
    }

    /**
     * @todo move this to a central place and use configuration for location
     * @return SQLite
     */
    protected function getDatabase()
    {
        $file = __DIR__ . '/../data/meh.sqlite';
        $schema = __DIR__ . '/../db/';
        return new SQLite($file, $schema);
    }

    protected function migrateDatabase()
    {
        $db = $this->getDatabase();
        $db->migrate();
    }


    protected function importDisqus($file)
    {
        $this->info("Importing from $file");
        // do the import here
    }
}
