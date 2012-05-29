<?php

namespace BoxUK\Bundle\IrcLogsAppBundle\Check;

use Liip\MonitorBundle\Check\Check;
use Liip\MonitorBundle\Exception\CheckFailedException;
use Liip\MonitorBundle\Result\CheckResult;

class SolrCheck extends Check
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
     * @return \Liip\MonitorBundle\Result\CheckResult
     * @throws \Liip\MonitorBundle\Exception\CheckFailedException
     */
    public function check()
    {
        try {
            $this->solarium->ping($this->solarium->createPing());

            return $this->buildResult('OK', CheckResult::OK);
        } catch (\Exception $e) {
            return $this->buildResult(sprintf('KO - %s', $e->getMessage()), CheckResult::CRITICAL);
        }
    }

    public function getName()
    {
        return "Solr Health Check";
    }
}
