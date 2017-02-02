<?php
include_once './vendor/autoload.php';
?>
<!doctype html>
<html lang="en">
    <head>
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">  
        <base href="<?= Application\Util::getBaseurl(false) ?>">
        <meta charset="utf-8">
        <title>Orion Explorer</title>
        <style>
            [ng-cloak] {
                display: none;
            }
        </style>
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />
        <meta name="fragment" content="#!">
        <script src="app/bower_components/angular-loader/angular-loader.js"></script>
        <link rel="stylesheet" href="//ajax.googleapis.com/ajax/libs/angular_material/1.1.1/angular-material.min.css">
        <link rel="stylesheet" href="//unpkg.com/leaflet@0.7.7/dist/leaflet.css">
        <!--<link rel="stylesheet" href="//code.getmdl.io/1.2.0/material.cyan-blue.min.css" />-->
        <link rel="stylesheet" href="app/app.css">
    </head>
    <body ng-cloak>
        <div class="mdl-layout mdl-js-layout mdl-layout--fixed-drawer mdl-layout--fixed-header">
            <header class="demo-header mdl-layout__header mdl-color--grey-100 mdl-color-text--grey-600">
                <div class="mdl-layout__header-row">
                    <span class="mdl-layout-title">{{pageTitle}}</span>
                    <div class="mdl-layout-spacer"></div>
                    <!--                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--expandable">
                                            <label class="mdl-button mdl-js-button mdl-button--icon" for="search">
                                                <i class="material-icons">search</i>
                                            </label>
                                            <div class="mdl-textfield__expandable-holder">
                                                <input class="mdl-textfield__input" type="text" id="search">
                                                <label class="mdl-textfield__label" for="search">Enter your query...</label>
                                            </div>
                                        </div>-->
                    <button class="mdl-button mdl-js-button mdl-js-ripple-effect mdl-button--icon" id="hdrbtn">
                        <i class="material-icons">more_vert</i>
                    </button>
                    <ul class="mdl-menu mdl-js-menu mdl-js-ripple-effect mdl-menu--bottom-right" for="hdrbtn">
                        <li href='https://github.com/VM9/fiware-orion-explorer' _target='__blank' class="mdl-menu__item">About</li>
                        <li ui-sref="help" class="mdl-menu__item">Help <i class="material-icons" role="presentation">help</i></li>
                        <li ui-sref="legal"  class="mdl-menu__item">Legal information</li>
                    </ul>
                </div>
            </header>
            <div class="mdl-layout__drawer mdl-color--blue-grey-500 mdl-color-text--white">
                <span ui-sref="main" class="mdl-layout-title">
                    <img src="app/images/logo.png" style="margin-left: -33px;" />
                    <i>Orion</i>
                    <i>Explorer</i>
                </span>
                <nav class="mdl-navigation">
                    <a ng-repeat="c in connections" class="mdl-navigation__link mdl-color--primary" ui-sref="explore({id: $index})">
                        <i class="material-icons" role="presentation">storage</i>
                        {{c.name}}
                    </a>
                    <a ng-click="addConnection($event)" id="view-source" class="mdl-button mdl-js-button mdl-button--raised mdl-js-ripple-effect mdl-button--accent"><i class="material-icons">add</i> New</a>
                </nav>
            </div>
            <main class="mdl-layout__content">
                <div class="page-content">
                    <div ui-view></div>
                </div>
            </main>
        </div>


        <!-- Dialogs -->

<!--         Connections 
        <dialog id="connection-modal" class="mdl-dialog mdl-cell--4-col-desktop  mdl-shadow--6dp" >
            <div class="mdl-card__title mdl-color--primary mdl-color-text--white">
                <h5 class="mdl-card__title-text"> {{conn.mode}} Instance</h5>
            </div>
            <div class="mdl-dialog__content mdl-grid  mdl-grid--no-spacing">
                <div class="mdl-card__supporting-text">
                    <form action="#" name="conn">
                        <div class="mdl-textfield mdl-js-textfield mdl-textfield--full-width mdl-textfield--floating-label">
                            <input class="mdl-textfield__input" type="text" id="name" rows= "1" ng-model="conn.name" ng-required="true"/>
                            <label class="mdl-textfield__label" for="username">Name</label>
                        </div>
                        <div class="mdl-textfield mdl-js-textfield mdl-textfield--full-width mdl-textfield--floating-label">
                            <input class="mdl-textfield__input" type="text" id="hostname" rows= "1" ng-model="conn.hostname" ng-required="true"/>
                            <label class="mdl-textfield__label" for="hostname">Hostname / IP </label>
                        </div>
                        <div class="mdl-textfield mdl-js-textfield mdl-textfield--full-width mdl-textfield--floating-label">
                            <input class="mdl-textfield__input" type="number" id="port"  pattern="-?[0-9]*(\.[0-9]+)?" ng-model="conn.port"/>
                            <label class="mdl-textfield__label" for="port">Port</label>
                            <span class="mdl-textfield__error">Input is not a number!</span>
                        </div>
                        <table class="mdl-data-table mdl-js-data-table">
                            <caption>
                                <h6>Custom Headers <a ng-click="addHeader()" class="mdl-button mdl-button--raised"><i class="material-icons">add</i></a></h6>

                            </caption>
                            <thead>
                                <tr>
                                    <th class="mdl-data-table__cell--non-numeric">Action</th>
                                    <th class="mdl-data-table__cell--non-numeric">Key</th>
                                    <th class="mdl-data-table__cell--non-numeric">Value</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr ng-repeat="h in conn.headers track by $index">
                                    <td>
                                        <a ng-click="removeHeader($index)" class="mdl-button mdl-js-button mdl-button--fab  mdl-button--mini-fab mdl-button--colored mdl-js-ripple-effect"><i class="material-icons">remove</i></a>
                                    </td>
                                    <td>
                                        <div class="mdl-textfield mdl-js-textfield mdl-textfield--full-width">
                                            <input class="mdl-textfield__input" type="text" ng-model="h.name"/>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="mdl-textfield mdl-js-textfield mdl-textfield--full-width">
                                            <input class="mdl-textfield__input" type="text" ng-model="h.value"/>
                                        </div>
                                    </td>
                                </tr>
                                <tr ng-show="conn.headers.length == 0">
                                    <td colspan="3" style="text-align: center">
                                        No custom headeres
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                    </form>
                </div>
            </div>
            <div class="mdl-dialog__actions" style="text-align: right;">
                <button type="button" class="mdl-button mdl-button--raised mdl-button--colored" ng-click="saveConnection()">Save</button>
                <button type="button" class="mdl-button mdl-button--raised mdl-button--accent close" ng-click="cancelConnection()">Cancel</button>
            </div>
        </dialog>
-->

        <script>
            /*!
             * $script.js v1.3
             * https://github.com/ded/script.js
             * Copyright: @ded & @fat - Dustin Diaz, Jacob Thornton 2011
             * Follow our software http://twitter.com/dedfat
             * License: MIT
             */
            !function (a, b, c) {
                function t(a, c) {
                    var e = b.createElement("script"), f = j;
                    e.onload = e.onerror = e[o] = function () {
                        e[m] && !/^c|loade/.test(e[m]) || f || (e.onload = e[o] = null, f = 1, c())
                    }, e.async = 1, e.src = a, d.insertBefore(e, d.firstChild)
                }
                function q(a, b) {
                    p(a, function (a) {
                        return!b(a)
                    })
                }
                var d = b.getElementsByTagName("head")[0], e = {}, f = {}, g = {}, h = {}, i = "string", j = !1, k = "push", l = "DOMContentLoaded", m = "readyState", n = "addEventListener", o = "onreadystatechange", p = function (a, b) {
                    for (var c = 0, d = a.length; c < d; ++c)
                        if (!b(a[c]))
                            return j;
                    return 1
                };
                !b[m] && b[n] && (b[n](l, function r() {
                    b.removeEventListener(l, r, j), b[m] = "complete"
                }, j), b[m] = "loading");
                var s = function (a, b, d) {
                    function o() {
                        if (!--m) {
                            e[l] = 1, j && j();
                            for (var a in g)
                                p(a.split("|"), n) && !q(g[a], n) && (g[a] = [])
                        }
                    }
                    function n(a) {
                        return a.call ? a() : e[a]
                    }
                    a = a[k] ? a : [a];
                    var i = b && b.call, j = i ? b : d, l = i ? a.join("") : b, m = a.length;
                    c(function () {
                        q(a, function (a) {
                            h[a] ? (l && (f[l] = 1), o()) : (h[a] = 1, l && (f[l] = 1), t(s.path ? s.path + a + ".js" : a, o))
                        })
                    }, 0);
                    return s
                };
                s.get = t, s.ready = function (a, b, c) {
                    a = a[k] ? a : [a];
                    var d = [];
                    !q(a, function (a) {
                        e[a] || d[k](a)
                    }) && p(a, function (a) {
                        return e[a]
                    }) ? b() : !function (a) {
                        g[a] = g[a] || [], g[a][k](b), c && c(d)
                    }(a.join("|"));
                    return s
                };
                var u = a.$script;
                s.noConflict = function () {
                    a.$script = u;
                    return this
                }, typeof module != "undefined" && module.exports ? module.exports = s : a.$script = s
            }(this, document, setTimeout)

            // load angular before everyone
            $script([
                'app/bower_components/angular/angular.min.js',
                //"//unpkg.com/leaflet@0.7.7/dist/leaflet.min.js",
                'app/bower_components/leaflet/dist/leaflet.js'
            ], function () {
                // load all of the dependencies asynchronously.
                $script([
                    'app/bower_components/angular-aria/angular-aria.min.js',
                    'app/bower_components/angular-animate/angular-animate.min.js',
                    'app/bower_components/angular-material/angular-material.js',
                    'app/bower_components/angular-sanitize/angular-sanitize.min.js',
//                    'app/bower_components/angular-touch/angular-touch.min.js',
                    'app/bower_components/angular-ui-router/release/angular-ui-router.min.js',
                    //                            'app/bower_components/angular-ui-router/release/angular-ui-router.min.js',
                    'app/bower_components/es5-shim/es5-shim.min.js',
                    'app/bower_components/angular-simple-logger/dist/angular-simple-logger.min.js',
                    "app/bower_components/ui-leaflet/dist/ui-leaflet.min.js",
                    'app/app.js',
                    'app/components/directives.js',
                    'app/components/filters.js',
    ], function () {

                    // when all is done, execute bootstrap angular application
                    angular.bootstrap(document, ['mainApp']);
                });
            });
        </script>

        <script src="app/bower_components/material-design-lite/material.min.js" async></script>
        <script src="app/bower_components/dialog-polyfill/dialog-polyfill.js" async></script>
    </body>
</html>
