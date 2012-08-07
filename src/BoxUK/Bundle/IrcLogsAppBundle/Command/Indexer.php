<?php

namespace BoxUK\Bundle\IrcLogsAppBundle\Command;

interface Indexer
{
    /**
     * Initialize the indexer to return messages from the specified date
     *
     * @param string $lastIndexedDate
     */
    public function init( $lastIndexedDate );

    /**
     * Read another message from the logs to index.  Returns
     * false when there are no more messages
     *
     * @return Message
     */
    public function read();
}
