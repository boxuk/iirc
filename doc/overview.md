Internal BoxUK IRC log viewer
=============================

The scope of the project is to parse, index and make available for searching,
a set of irc logs from our internal IRC server.

High level plan
---------------

* A simple frontend:
    * log browser
    * search interface, which should display results, which link to the 
    "log browser" at specific point in a given log.
* Solr integration
    * Simple schema: channel, date, user, message.
    * Console task to parse and index logs to solr.
	
Dev Tasks
---------
* Log processing
	* parser for logs (Al G to provide)
	* Create Solr schema
	* Index logs in solr using [solarium][solarium] with [NelmioSolariumBundle][NelmioSolariumBundle]
	* Paginate solarium search queries with [Pagerfanta][Pagerfanta]
* UI
	* Use [MopaBootstrapBundle][MopaBootstrapBundle] to help get the UI up and running
	* Search interface (freetext/channel/date/user)
	* Browser iterface (point and click version of the Search interface with channel/date links)
	* Paginated results ui with [WhiteOctoberPagerfantaBundle][WhiteOctoberPagerfantaBundle]



[NelmioSolariumBundle]: https://github.com/nelmio/NelmioSolariumBundle "NelmioSolariumBundle"
[solarium]: https://github.com/basdenooijer/solarium "Solarium"
[MopaBootstrapBundle]: https://github.com/phiamo/MopaBootstrapBundle "MopaBootstrapBundle"
[WhiteOctoberPagerfantaBundle]: https://github.com/whiteoctober/WhiteOctoberPagerfantaBundle "WhiteOctoberPagerfantaBundle"
[Pagerfanta]: https://github.com/whiteoctober/Pagerfanta "Pagerfanta"