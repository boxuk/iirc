<?php

namespace BoxUK\Bundle\IrcLogsAppBundle\Command;

class DefaultIndexer
{
    private $logFile;

    public function __construct( $input, $output, $logFile )
    {
        $this->logFile = $logFile;
    }

    public function init( $lastIndexedDate )
    {
        if (false === $reader = new BackwardsFileReader($this->logFile)) {
            throw new IndexingException(
                'Failed to open file: ' . $this->logFile
            );
        }
        $output->writeln('<info>Indexing: </info>' . $logFile . '');
    }

    public function read()
    {
    }
}
