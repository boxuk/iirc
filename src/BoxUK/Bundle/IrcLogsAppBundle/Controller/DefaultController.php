<?php

namespace BoxUK\Bundle\IrcLogsAppBundle\Controller;

use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use BoxUK\Bundle\IrcLogsAppBundle\Form\Type\SearchType;
use BoxUK\Bundle\IrcLogsAppBundle\Form\Entity\Search;
use BoxUK\Bundle\IrcLogsAppBundle\Event\Subscriber\SolariumResultsSubscriber;

class DefaultController extends Controller
{
    /**
     * @return \BoxUK\Bundle\IrcLogsAppBundle\Solr\SolrRepository
     */
    public function getSolr()
    {
        return $this->get('box_uk_irc_logs_app.solr_repository');
    }

    /**
     * @return \Knp\Component\Pager\Paginator
     */
    private function getPaginator()
    {
        return $this->get('knp_paginator');
    }

    /**
     * @param \BoxUK\Bundle\IrcLogsAppBundle\Form\Entity\Search $search
     * @return \Symfony\Component\Form\Form
     */
    private function getSearchForm(Search $search)
    {
        $options = array(
            'channels'    => $this->getSolr()->getChannels(),
            'nicks'       => $this->getSolr()->getNicks()
        );
        $form = $this->createForm(new SearchType, $search, $options);

        return $form;
    }

    /**
     * @Cache(expires="+1 minute", public=true)
     * @Route("/", name="home")
     * @Template()
     */
    public function indexAction()
    {
        return array(
            'form'        => $this->getSearchForm(new Search)->createView(),
            'currentDate' => new DateTime,
        );
    }

    /**
     * @Cache(expires="+1 minute", public=true)
     * @Route( "/log/{channel}/", name="channel")
     * @Template()
     */
    public function channelAction($channel)
    {

        $blacklist = $this->container->getParameter('box_uk_irc_logs_app.channels.blacklist');

        if (in_array($channel, $blacklist)) {
            throw new NotFoundHttpException();
        }

        return array(
            'channel' => $channel,
            'dates' => $this->getSolr()->getDatesForChannel($channel),
        );
    }

    /**
     * @Cache(expires="+1 minute", public=true)
     * @Route("/search", name="search")
     */
    public function searchAction()
    {
        $form = $this->getSearchForm($search = new Search);
        $form->bindRequest($this->getRequest());

        if ($form->isValid()) {
            $pagination = $this->findPaginatedMessagesMatchingSearch($search);

            return $this->render('BoxUKIrcLogsAppBundle:Default:search.html.twig', array (
                'form'        => $form->createView(),
                'pagination'  => $pagination
            ));
        }

        return $this->render('BoxUKIrcLogsAppBundle:Default:index.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    /**
     * Returns all logged messages that match the given search criteria
     *
     * @param Search $search
     *
     * @return array
     */
    private function findPaginatedMessagesMatchingSearch(Search $search)
    {
        $solarium = $this->getSolr()->getSolarium();
        $query = $solarium->createSelect();
        $helper = $query->getHelper();

        // Text matching
        $dismax = $query->getDisMax();
        $dismax->setQueryParser('edismax');
        $dismax->setQueryFields(array('message', 'text_ngram'));

        $query->setQuery($search->getQuery());

        // Channel filter
        $channel = $search->getChannel();
        if ($channel) {
            $query
                ->createFilterQuery('channel')
                ->setQuery("channel:{$channel}")
            ;
        }

        // Date filter
        $fromDate = $search->getFrom();
        $toDate = $search->getTo();
        if ($fromDate || $toDate) {
            $from = $fromDate ? $helper->formatDate($fromDate->setTime(0,0,0)) : "*";
            $to = $toDate ? $helper->formatDate($toDate->setTime(23,59,59)) : "NOW";

            $query
                ->createFilterQuery('datetime')
                ->setQuery($helper->rangeQuery('datetime', $from, $to))
            ;
        }

        // Nick filter
        $nick = $search->getNick();
        if ($nick) {
            $query
                ->createFilterQuery('nick')
                ->setQuery("nick:{$nick}")
            ;
        }

        // Blacklist channels filter
        $blacklist = $this->container->getParameter('box_uk_irc_logs_app.channels.blacklist');
        $fq = array();
        foreach ($blacklist as $channel) {
            $fq[] = 'channel:'.$channel;
        }

        $fq = implode(' OR ', $fq);
        $query->createFilterQuery('fq')->setQuery('-(' . $fq . ')');

        $paginator = $this->getPaginator();
        $paginator->subscribe(new SolariumResultsSubscriber($this->getSolr()));

        $pagination = $paginator->paginate(
            array(
                $solarium,
                $query
            ),
            $this->get('request')->query->get('page', 1),
            10
        );

        return $pagination;
    }

    /**
     * @Cache(expires="+1 minute", public=true)
     * @Route( "/log/{channel}/{date}", requirements={"date" = "\d{2}-\d{2}-\d{4}"}, name="log")
     * @Template()
     */
    public function logAction( $channel, $date )
    {
        $blacklist = $this->container->getParameter('box_uk_irc_logs_app.channels.blacklist');

        preg_match('/(\d{2})-(\d{2})-(\d{4})/', $date, $matches);

        if (in_array($channel, $blacklist) || !$matches) {
            throw new NotFoundHttpException();
        }

        list($_, $day, $month, $year) = $matches;

        $solarium = $this->getSolr()->getSolarium();
        $query = $solarium->createSelect();
        $helper = $query->getHelper();

        $from = $helper->formatDate(
            date_create(null, new \DateTimeZone('UTC'))
                ->setDate($year, $month, $day)
                ->setTime(0, 0, 0)
        );
        $to = $helper->formatDate(
            date_create(null, new \DateTimeZone('UTC'))
                ->setDate($year, $month, $day)
                ->setTime(23, 59, 59)
        );

        $query->createFilterQuery('datetime')
            ->setQuery($helper->rangeQuery('datetime', $from, $to));
        $query->createFilterQuery('channel')
            ->setQuery("channel:$channel");
        $query->setRows(100000);
        $query->addSort('datetime', \Solarium_Query_Select::SORT_ASC);

        $results = $solarium->execute($query);

        return array(
            'channel' => $channel,
            'logdate' => $date,
            'lines'   => $results
        );

    }
}
