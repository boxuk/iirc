<?php

namespace BoxUK\Bundle\IrcLogsAppBundle\Form\Entity;

use DateTime;

class Search 
{
    /**
     * @var string
     */
    private $query;
    
    /**
     * @var string
     */
    private $channel;
    
    /**
     * @var DateTime
     */
    private $from;
    
    /**
     * @var DateTime
     */
    private $to;
    
    /**
     * @var string
     */
    private $nick;
    
    /**
     * @return string 
     */
    public function __toString()
    {
        return 'Search';
    }
    
    /**
     * @return string
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @param string $query 
     */
    public function setQuery($query)
    {
        $this->query = $query;
    }
    
    /**
     * @return string
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * @param string $channel 
     */
    public function setChannel($channel)
    {
        $this->channel = $channel;
    }

    /**
     * @return DateTime
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * @param DateTime $from 
     */
    public function setFrom(DateTime $from = null)
    {
        $this->from = $from;
    }

    /**
     * @return DateTime
     */
    public function getTo()
    {
        return $this->to;
    }

    /**
     * @param DateTime $to 
     */
    public function setTo(DateTime $to = null)
    {
        $this->to = $to;
    }

    /**
     * @return string
     */
    public function getNick()
    {
        return $this->nick;
    }

    /**
     * @param string $nick 
     */
    public function setNick($nick)
    {
        $this->nick = $nick;
    }
}
