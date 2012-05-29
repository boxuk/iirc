<?php

namespace BoxUK\Bundle\IrcLogsAppBundle\Menu;

use Knp\Menu\FactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Mopa\BootstrapBundle\Navbar\AbstractNavbarMenuBuilder;
use Knp\Menu\MenuItem;
use BoxUK\Bundle\IrcLogsAppBundle\Solr\SolrRepository;

class NavbarMenuBuilder extends AbstractNavbarMenuBuilder
{

    /**
     * @var \BoxUK\Bundle\IrcLogsAppBundle\Solr\SolrRepository
     */
    protected $solr;

    /**
     * @param FactoryInterface                                   $factory
     * @param \BoxUK\Bundle\IrcLogsAppBundle\Solr\SolrRepository $solr
     */
    public function __construct(FactoryInterface $factory, SolrRepository $solr)
    {
        $this->solr = $solr;
        parent::__construct($factory);
    }

    /**
     * Creates the app's main navbar menu
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return Knp\Menu\MenuItem
     */
    public function createMainMenu(Request $request)
    {
        $menu = $this->factory->createItem('root');
        $menu->setCurrentUri($request->getRequestUri());
        $menu->setChildrenAttribute('class', 'nav');

        $dropdown = $this->createDropdownMenuItem($menu, "Channels");
        $channels = $this->solr->getChannels();
        asort($channels);
        foreach ($channels as $channel) {
            $dropdown->addChild('#' . $channel, array('route' => 'channel', 'routeParameters' => array('channel' => $channel)));
        }

        return $menu;
    }
}
