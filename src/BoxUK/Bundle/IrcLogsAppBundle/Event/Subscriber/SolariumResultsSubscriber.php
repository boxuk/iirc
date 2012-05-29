<?php

namespace BoxUK\Bundle\IrcLogsAppBundle\Event\Subscriber;

use Knp\Component\Pager\Event\AfterEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use BoxUK\Bundle\IrcLogsAppBundle\Solr\SolrRepository;

/**
 *
 */
class SolariumResultsSubscriber implements EventSubscriberInterface
{

    /**
     * @var \BoxUK\Bundle\IrcLogsAppBundle\Solr\SolrRepository
     */
    private $solr;

    /**
     * @param \BoxUK\Bundle\IrcLogsAppBundle\Solr\SolrRepository $solr
     */
    public function __construct(SolrRepository $solr)
    {
        $this->solr = $solr;
    }

    public function addContext(AfterEvent $event)
    {

        $items = $event->getPaginationView()->getItems();

        if (0 === count($items)) {
            return;
        }

        $newItems = new \ArrayIterator(array());

        foreach ($items as $item) {
            /** @var $item \Solarium_Document_ReadOnly */
            $channel = $item->channel;
            $datetime = $item->datetime;
            $line = $item->lineNumber;

            $results = $this->solr->getContext(date_create($datetime, new \DateTimeZone('UTC')), $channel, $line);

            $context = array();

            foreach ($results as $result) {
                $context[] = $result->nick . ': ' . $result->message;
            }

            $context = join("\n", $context);

            $doc = new \Solarium_Document_ReadOnly(array_merge($item->getFields(), array('context' => $context)));
            $newItems->append($doc);
        }

        $event->getPaginationView()->setItems($newItems);
    }

    public static function getSubscribedEvents()
    {
        return array(
            'knp_pager.after' => array('addContext')
        );
    }
}
