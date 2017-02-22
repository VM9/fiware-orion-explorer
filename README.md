Orion Explorer
=============================
####  A User Interface to Explore [Fiware Orion Context Broker](https://github.com/telefonicaid/fiware-orion) Entities.

### [Demo](http://orionexplorer.vm9it.com/) [![Build Status](https://travis-ci.org/VM9/fiware-orion-explorer.svg?branch=master)](https://travis-ci.org/VM9/fiware-orion-explorer)
This demo is gently hosted by VM9 IT, but we can't garantee that it will be available. So we encourage you to deploy your own instânce. We tryed to make it as simple as possible, that everyone can deploy. At this next section you can see how to build, install and deploy it:


### Running:
To download all dependencies you should install [composer](https://getcomposer.org/), [Node(npm)](https://nodejs.org/en/download/) and [Bower](https://bower.io/#install-bower).

```
git clone https://github.com/VM9/fiware-orion-explorer.git
cd fiware-orion-explorer
composer install --no-dev
npm install
npm install bower -g
bower install
php -S localhost:7000
```

### Orion Explorer Requeriments
- PHP 5.6+ with cURl extention
- Orion Explorer uses localstorage, and more recent version of javascript, css, and html, you should run it over a modern browser. We reccomend latest version of Google Chrome.
- Orion Explorer uses NGSIv2 API, so you must use with on a 1.2.0+ instance, we recommend use the latest stable version of [Fiware Orion Context Broker](https://github.com/telefonicaid/fiware-orion)
- Orion Explorer just use API to communicate with [Fiware Orion Context Broker](https://github.com/telefonicaid/fiware-orion), the port just be visible for the Orion Explorer instance.


### Licence
Orion Context Explorer  is licensed under Affero General Public License (GPL) version 3.
Orion Context Broker, Fiware ALL RIGHTS RESERVED AND OTHER TRADEMARKS ARE THE PROPERTY OF THEIR RESPECTIVE OWNERS. [Legal Notice](https://forge.fiware.org/plugins/mediawiki/wiki/fiware/index.php/FI-WARE_Open_Specification_Legal_Notice_(implicit_patents_license))
