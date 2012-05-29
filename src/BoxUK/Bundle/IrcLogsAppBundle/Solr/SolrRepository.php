<?php
namespace BoxUK\Bundle\IrcLogsAppBundle\Solr;

use Symfony\Component\DependencyInjection\ContainerAware;

class SolrRepository extends ContainerAware
{

    /**
     * @var \Solarium_Client
     */
    private $solarium;

    /**
     * Creates a new instance of this class
     *
     * @param \Solarium_Client $solarium
     */
    public function __construct(\Solarium_Client $solarium)
    {
        $this->solarium = $solarium;
    }

    /**
     * @return \Solarium_Client
     */
    public function getSolarium()
    {
        return $this->solarium;
    }

    /**
     * A list of channels in the Search Index
     *
     * @return array
     */
    public function getChannels()
    {
        return array_diff(
            $this->getUnique('channel'),
            $this->getChannelBlacklist()
        );

    }

    /**
     * A list of usernames in the Search Index
     *
     * @return array
     */
    public function getUsernames()
    {
        return $this->getUnique('username');
    }

    /**
     * A list of nicks in the Search Index
     *
     * @return array
     */
    public function getNicks()
    {
        return $this->getUnique('nick');
    }

    /**
     * A list of unique values for a given field in the Search Index
     *
     * @param string $field
     * @return array
     */
    private function getUnique($field)
    {

        $key = $field . 's';

        $query = $this->solarium->createSelect();
        $facetSet = $query->getFacetSet();
        $facetSet->createFacetField($key)->setField($field);
        $resultset = $this->solarium->select($query);

        return array_keys($resultset->getFacetSet()->getFacet($key)->getValues());

    }

    /**
     * Get's the date of the last message in the solr index, optionally filtered by channel
     *
     * @param string|null $channel
     * @return bool|\DateTime
     */
    public function getLastIndexedDate($channel = null)
    {

        $query = $this->solarium->createSelect();
        $query->setRows(1);
        $query->setFields(array('datetime'));
        $query->addSort('datetime', \Solarium_Query_Select::SORT_DESC);

        if ($channel) {
            $query
                ->createFilterQuery('channel')
                ->setQuery('channel:' . $channel)
            ;
        }

        foreach ($this->solarium->select($query) as $result) {
            return date_create($result->datetime, new \DateTimeZone('UTC'));
        }

        return false;
    }

    /**
     * Get's the date of the first message in the solr index, optionally filtered by channel
     *
     * @param string|null $channel
     * @return bool|\DateTime
     */
    public function getFirstIndexedDate($channel = null)
    {

        $query = $this->solarium->createSelect();
        $query->setRows(1);
        $query->setFields(array('datetime'));
        $query->addSort('datetime', \Solarium_Query_Select::SORT_ASC);

        if ($channel) {
            $query
                ->createFilterQuery('channel')
                ->setQuery('channel:' . $channel)
            ;
        }

        foreach ($this->solarium->select($query) as $result) {
            return date_create($result->datetime, new \DateTimeZone('UTC'));
        }

        return false;
    }


    /**
     * Get's the dates indexed for a given channel
     *
     * @param string $channel
     * @return array
     */
    public function getDatesForChannel($channel)
    {

        $firstDate = $this->getFirstIndexedDate($channel);

        if (!$firstDate) {
            return array();
        }

        $query = $this->solarium->createSelect();
        $helper = $query->getHelper();

        $facetSet = $query->getFacetSet();
        $facet = $facetSet->createFacetRange('dates');
        $facet->setField('datetime');
        $facet->setStart($helper->formatDate($firstDate->setTime(0, 0, 0)));
        $facet->setEnd('NOW/DAY+1DAY');
        $facet->setGap('+1DAY');

        $query
            ->createFilterQuery('channel')
            ->setQuery('channel:' . $channel)
        ;

        $resultset = $this->solarium->select($query);

        $facet = $resultset->getFacetSet()->getFacet('dates');
        $dates = array();
        foreach ($facet as $value => $count) {
            if ($count > 0) {
                $dates[] = date_create($value, new \DateTimeZone('UTC'));
            }
        }

        return $dates;
    }

    /**
     * Get's the last indexed line number for a given channel and date or false if there is no result
     *
     * @param string    $channel
     * @param \DateTime $datetime
     * @return bool|int
     */
    public function getLastLineNumberForChannelAndDate($channel, \DateTime $datetime)
    {

        $from = clone $datetime;
        $to = clone $datetime;

        $query = $this->solarium->createSelect();
        $helper = $query->getHelper();

        $query->setRows(1);
        $query->setFields(array('lineNumber'));
        $query->addSort('lineNumber', \Solarium_Query_Select::SORT_DESC);

        $query
            ->createFilterQuery('channel')
            ->setQuery('channel:' . $channel)
        ;

        $from = $helper->formatDate($from->setTime(0, 0, 0));
        $to = $helper->formatDate($to->setTime(23, 59, 59));

        $query
            ->createFilterQuery('datetime')
            ->setQuery($helper->rangeQuery('datetime', $from, $to))
        ;

        foreach ($this->solarium->select($query) as $result) {
            return $result->lineNumber;
        }

        return false;
    }

    /**
     * @param \DateTime $datetime
     * @param string    $channel
     * @param string    $line
     * @param int       $lines    number of lines either side of the given line to retrieve
     * @return \Solarium_Result_Select
     */
    public function getContext(\DateTime $datetime, $channel, $line, $lines = 3)
    {

        $from = clone $datetime;
        $to = clone $datetime;

        $query = $this->solarium->createSelect();
        $helper = $query->getHelper();

        $query->addSort('lineNumber', \Solarium_Query_Select::SORT_ASC);

        $query
            ->createFilterQuery('channel')
            ->setQuery('channel:' . $channel)
        ;

        $from = $helper->formatDate($from->setTime(0, 0, 0));
        $to = $helper->formatDate($to->setTime(23, 59, 59));

        $query
            ->createFilterQuery('datetime')
            ->setQuery($helper->rangeQuery('datetime', $from, $to))
        ;

        $query
            ->createFilterQuery('lineNumbers')
            ->setQuery($helper->rangeQuery('lineNumber', $line - $lines, $line + $lines))
        ;

        return $this->solarium->select($query);

    }

    /**
     * @return array
     */
    public function getChannelBlacklist()
    {
        return $this->container->getParameter('box_uk_irc_logs_app.channels.blacklist');
    }

}
