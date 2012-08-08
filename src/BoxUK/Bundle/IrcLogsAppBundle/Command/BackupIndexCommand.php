<?php

namespace BoxUK\Bundle\IrcLogsAppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Filesystem\Filesystem;
use \SimpleXMLElement;


/**
 * Backs up the current solr data to xml.
 * Can only backup stored=true schema fields
 */
class BackupIndexCommand extends ContainerAwareCommand
{

    /**
     * @var \BoxUK\Bundle\IrcLogsAppBundle\Solr\SolrRepository
     */
    private $solr;

    /**
     * @var \Solarium_Client
     */
    private $solarium;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('iirc:backup')
            ->setDescription('Backs up the current solr data to xml');
    }

    /**
     *
     * @param InputInterface  $input  An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->solr = $this->getContainer()->get('box_uk_irc_logs_app.solr_repository');
        $this->solarium = $this->solr->getSolarium();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $query = $this->solarium->createSelect();
        $query->setRows(0);
        $count = $this->solarium->select($query);

        $count = $count->getNumFound();

        $query = $this->solarium->createSelect();
        $query->setRows($count);
        $query->setFields(array('id', 'channel', 'datetime', 'username', 'nick', 'message', 'lineNumber'));
        $results = $this->solarium->select($query);

        $out = array();
        foreach ($results as $result) {
            /** @var $result \Solarium_Document_ReadOnly */
            array_push($out, $result->getFields());
        }

        $serialized = serialize($out);
        $tmpFile = tempnam(sys_get_temp_dir(), basename(__FILE__));
        if (false !== @file_put_contents($tmpFile, $serialized)) {
            $fs = new Filesystem();
            $outFile = $this->getContainer()->getParameter('kernel.root_dir').'\..\backup_' . time() . '.bak';
            $fs->copy($tmpFile, $outFile);
            $output->writeln('Written to ' . $outFile);
        } else {
            $output->writeln('<error>Failed to write backup</error>');
        }

    }


}
