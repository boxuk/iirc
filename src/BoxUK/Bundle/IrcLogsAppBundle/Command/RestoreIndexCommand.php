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
 * Restores a backup to solr
 */
class RestoreIndexCommand extends ContainerAwareCommand
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
            ->setName('iirc:restore')
            ->setDescription('Restores a backup to solr')
            ->addArgument('file', InputArgument::REQUIRED, 'The backup file to restore.');
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

        $file = $input->getArgument('file');

        if(!is_readable($file) && is_file($file)){
            $output->writeln('<error>File not readable.</error>');
            return;
        }

        $backup = unserialize(file_get_contents($file));

        if(!is_array($backup)){
            $output->writeln('<error>Backup not in expected format.</error>');
            return;
        }

        foreach ($backup as $item) {

            try {
                $this->addDocument($item);
            } catch (\Exception $e) {
                $output->writeln(
                    sprintf(
                        '<error>Failed to index item with error \'%s\'. Check Solr logs for more detailed error.</error>',
                        $e->getMessage()
                    )
                );
                $this->rollback();
                return;
            }
        }

        if (false === $this->commit()) {
            $this->rollback();
            $output->writeln('<error>Error committing documents to index. Check Solr logs for more detailed error.</error>');
            return;
        }

        if (false === $this->optimize()) {
            $output->writeln('<error>Error optimizing index. Check Solr logs for more detailed error.</error>');
            return;
        }

    }


    /**
     * @param array $document
     */
    protected function addDocument(array $document)
    {
        $update = $this->solarium->createUpdate();

        $document = $update->createDocument($document);
        $update->addDocument($document);
        $this->solarium->update($update);
    }

    /**
     * @return bool
     */
    protected function commit()
    {
        $update = $this->solarium->createUpdate();
        $update->addCommit();

        try {
            $this->solarium->update($update);
        } catch (\Exception $e) {
            return false;
        }

        return true;

    }

    /**
     * @return bool
     */
    protected function optimize()
    {
        $update = $this->solarium->createUpdate();
        $update->addOptimize(true, false, 5);

        try {
            $this->solarium->update($update);
        } catch (\Exception $e) {
            return false;
        }

        return true;

    }

    /**
     * @return bool
     */
    protected function clearIndex()
    {
        $update = $this->solarium->createUpdate();
        $update->addDeleteQuery('*:*');

        try {
            $this->solarium->update($update);
        } catch (\Exception $e) {
            return false;
        }

        return true;

    }

    /**
     * @return bool
     */
    protected function rollback()
    {
        $update = $this->solarium->createUpdate();
        $update->addRollback();

        try {
            $this->solarium->update($update);
        } catch (\Exception $e) {
            return false;
        }

        return true;

    }


}
