'use strict';

// Declare app level module which depends on views, and components
angular.module('mainApp', [
    'ngAria',
    'ngSanitize',
    'ngAnimate',
//    'ngTouch',
    'ui.router',
    'ngMaterial',
//    'ui.bootstrap'
    'nemLogging',
    'ui-leaflet'
])
        .config(['$locationProvider',
            '$stateProvider',
            '$urlRouterProvider',
            '$compileProvider',
            '$provide',
            '$httpProvider',
            function ($locationProvider, $stateProvider, $urlRouterProvider, $compileProvider, $provide, $httpProvider) {
                $locationProvider.hashPrefix('!');

//                $locationProvider.html5Mode(!0).hashPrefix("!");

                $urlRouterProvider.otherwise("/");


                $stateProvider
                        .state('main', {
                            title: "Home",
                            url: "/",
                            templateUrl: "app/partials/index.html",
                            controller: ['$scope', function () {

                                }]
                        })

                        .state('explore', {
                            title: "Instance Viewer",
                            url: "/explore/:id",
                            templateUrl: "app/partials/explore.html",
                            controller: ['$rootScope', '$scope', '$http', '$stateParams', '$timeout', 'leafletData',
                                function ($rootScope, $scope, $http, $stateParams, $timeout, leafletData) {

                                    $scope.selectedType = null;
                                    $scope.index = $stateParams.id;
                                    $scope.instance = $rootScope.getConnections($stateParams.id);
                                    $scope.init = function () {
                                        $http.post('server/index.php/orion/check', $scope.instance)
                                                .then(function (e) {
                                                    console.log(e);
                                                    $scope.instance.info = e.data;
                                                }).catch(function (e) {
//                                        $scope.instance.info = {};
                                        });

                                        $http.post('server/index.php/orion/types', $scope.instance)
                                                .then(function (e) {
                                                    $scope.instance.types = e.data;
                                                }).catch(function (e) {
//                                        $scope.instance.types = [];
                                        });

                                        if ($scope.selectedType !== null) {
                                            $scope.setType($scope.selectedType);
                                        }
                                    };
                                    $scope.init();

                                    $scope.setType = function (type) {
                                        $scope.selectedType = type;
                                        $scope.selectedType.data = [];
                                        switch ($scope.tabSelected){
                                            case 'main':
                                                $http.post('server/index.php/orion/entities/' + type.type, $scope.instance)
                                                    .then(function (e) {
                                                        $scope.selectedType.data = e.data;
                                                    }).catch(function (e) {
//                                        $scope.instance.info = {};
                                            });
                                            break;
                                            case  'map':
                                                $scope.setEntityMap(type.type);
                                            break;
                                            case 'subscriptions':
                                                $scope.setEntitySubscription(type.type);
                                            break;
                                            default:
                                            break;
                                        }

                                    };

                                    $scope.$on('$OrionExplorerConnectionsChanged', function (e, connections) {
                                        $scope.instance = connections[$stateParams.id];
                                        $scope.init();
                                    });

                                    $scope.tabSelected = 'main';
                                    $scope.tabs = {
                                        'main': {'init': function () {
                                                $scope.init();
                                            }},
                                        'map': {
                                            'init': function () {
                                                $timeout(function () {
                                                    var eventName = 'resize';
                                                    if (angular.isFunction(window.dispatchEvent)) {
                                                        window.dispatchEvent(new Event(eventName));
                                                    } else {
                                                        try {
                                                            var event;
                                                            if (document.createEvent) {
                                                                event = document.createEvent('HTMLEvents');
                                                                event.initEvent(eventName, true, true);
                                                            } else if (document.createEventObject) {// IE < 9
                                                                event = document.createEventObject();
                                                                event.eventType = eventName;
                                                            }
                                                            event.eventName = eventName;
                                                            if (el.dispatchEvent) {
                                                                el.dispatchEvent(event);
                                                            } else if (el.fireEvent && htmlEvents['on' + eventName]) {// IE < 9
                                                                el.fireEvent('on' + event.eventType, event);// can trigger only real event (e.g. 'click')
                                                            } else if (el[eventName]) {
                                                                el[eventName]();
                                                            } else if (el['on' + eventName]) {
                                                                el['on' + eventName]();
                                                            }
                                                        } catch (e) {
                                                            console.error(e);
                                                        }
                                                    }
                                                }, 200, true);
                                                if ($scope.selectedType) {
                                                    $scope.setEntityMap($scope.selectedType.type);
                                                }
                                            }
                                        },
                                        'subscriptions': {
                                            'init': function () {
                                                $scope.Subscriptions = [];
//                                                if ($scope.selectedType) {
                                                    $scope.setEntitySubscription();
//                                                }
                                            }
                                        }
                                    };

                                    $scope.selectTab = function (tab) {
                                        if (!!$scope.tabs[tab]) {
                                            $scope.tabSelected = tab;
                                            $scope.tabs[tab].init();
                                        }
                                    };

                                    //Map 

                                    $scope.map = {
                                        center: {//Get current location?
                                            lat: 0,
                                            lng: 0,
                                            zoom: 4
                                        },
                                        defaults: {
                                            scrollWheelZoom: false
                                        },
                                        geojson: {}
                                    };
                                    //Get entities in geojson format
                                    $scope.setEntityMap = function (type) {
                                        $http.post("server/index.php/orion/geoentities/" + type, $scope.instance).then(function (data, status) {
                                            angular.extend($scope.map, {
                                                geojson: {
                                                    data: data.data,
                                                    style: {
                                                        fillColor: "green",
                                                        weight: 2,
                                                        opacity: 1,
                                                        color: 'white',
                                                        dashArray: '3',
                                                        fillOpacity: 0.7
                                                    }
                                                }
                                            });
                                            $scope.centerJSON();
                                        });
                                    };
                                    //Experimental center based by geojson
                                    $scope.centerJSON = function () {
                                        leafletData.getMap().then(function (map) {
                                            var latlngs = [];
                                            console.log($scope.map.geojson.data.features[0].geometry);
                                            for (var i in $scope.map.geojson.data.features) {
                                                var point =  $scope.map.geojson.data.features[i];                                                
//                                                console.log(coord);
//                                                for (var j in $scope.map.geojson.data.features[0].geometry.coordinates) {
//                                                    var points = $scope.map.geojson.data.features[0].geometry.coordinates[j];
//                                                    for (var k in points) {
                                                        latlngs.push(L.GeoJSON.coordsToLatLng(point.geometry.coordinates));
//                                                    }
//                                                }
                                            }
//                                            console.log(latlngs);
                                            map.fitBounds(latlngs);
                                        });
                                    };
                                    
                                    //Subscriptions
                                    $scope.Subscriptions = [];
                                    $scope.setEntitySubscription = function(type){//by type not implemented
                                        $http.post("server/index.php/orion/subscription", $scope.instance).then(function (data, status) {
                                            $scope.Subscriptions = data.data;
                                        });
                                    };
                                }]
                        })
                        .state('help', {
                            title: "Help",
                            url: "/help",
                            templateUrl: "app/partials/help.html"
                        })
                        .state('legal', {
                            title: "Legal Information",
                            url: "/legal",
                            templateUrl: "app/partials/legal.html"
                        })

                        ;


                $compileProvider.debugInfoEnabled(false);
                //https://developer.mozilla.org/en-US/docs/Web/API/Storage/LocalStorage
                if (!window.localStorage) {
                    Object.defineProperty(window, "localStorage", new (function () {
                        var aKeys = [], oStorage = {};
                        Object.defineProperty(oStorage, "getItem", {
                            value: function (sKey) {
                                return sKey ? this[sKey] : null;
                            },
                            writable: false,
                            configurable: false,
                            enumerable: false
                        });
                        Object.defineProperty(oStorage, "key", {
                            value: function (nKeyId) {
                                return aKeys[nKeyId];
                            },
                            writable: false,
                            configurable: false,
                            enumerable: false
                        });
                        Object.defineProperty(oStorage, "setItem", {
                            value: function (sKey, sValue) {
                                if (!sKey) {
                                    return;
                                }
                                document.cookie = escape(sKey) + "=" + escape(sValue) + "; expires=Tue, 19 Jan 2038 03:14:07 GMT; path=/";
                            },
                            writable: false,
                            configurable: false,
                            enumerable: false
                        });
                        Object.defineProperty(oStorage, "length", {
                            get: function () {
                                return aKeys.length;
                            },
                            configurable: false,
                            enumerable: false
                        });
                        Object.defineProperty(oStorage, "removeItem", {
                            value: function (sKey) {
                                if (!sKey) {
                                    return;
                                }
                                document.cookie = escape(sKey) + "=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/";
                            },
                            writable: false,
                            configurable: false,
                            enumerable: false
                        });
                        this.get = function () {
                            var iThisIndx;
                            for (var sKey in oStorage) {
                                iThisIndx = aKeys.indexOf(sKey);
                                if (iThisIndx === -1) {
                                    oStorage.setItem(sKey, oStorage[sKey]);
                                } else {
                                    aKeys.splice(iThisIndx, 1);
                                }
                                delete oStorage[sKey];
                            }
                            for (aKeys; aKeys.length > 0; aKeys.splice(0, 1)) {
                                oStorage.removeItem(aKeys[0]);
                            }
                            for (var aCouple, iKey, nIdx = 0, aCouples = document.cookie.split(/\s*;\s*/); nIdx < aCouples.length; nIdx++) {
                                aCouple = aCouples[nIdx].split(/\s*=\s*/);
                                if (aCouple.length > 1) {
                                    oStorage[iKey = unescape(aCouple[0])] = unescape(aCouple[1]);
                                    aKeys.push(iKey);
                                }
                            }
                            return oStorage;
                        };
                        this.configurable = false;
                        this.enumerable = true;
                    })());
                }


                // Intercept http calls.
                $provide.factory('ErrorHttpInterceptor', [function () {
                        return {
                            'request': function (config) {
                                if (/server/.test(config.url)) {
                                    config.url = config.url + "?t=" + (new Date()).getTime();
                                }
                                return config;
                            },
                            'response': function (response) {
                                return response;
                            },
                            // On request failure
                            'requestError': function (rejection) {
                                return rejection;
                            },
                            // On response failure
                            'responseError': function (rejection) {
                                return rejection;
                            }
                        };
                    }]);

                // Add the interceptor to the $httpProvider.
                $httpProvider.interceptors.push('ErrorHttpInterceptor');


            }])

        .run(['$rootScope', '$state', '$mdDialog', function ($rootScope, $state, $mdDialog) {
                console.log("%c APP STARTED ", ["background: black", "color: white", "font-size: 11px"].join(";"));


                $rootScope.$on('$stateChangeSuccess', function (event, s, sp) {
                    $script([
                        'app/bower_components/material-design-lite/material.min.js'
                    ], function () {
                        $rootScope.pageTitle = s.title || s.name;
                    });
                });


//                var dialog = document.querySelector('#connection-modal');
//
//                if (!dialog.showModal) {
//                    dialogPolyfill.registerDialog(dialog);
//                }
                $rootScope.conn = {
                    "mode": "New",
                    "hostname": "",
                    "port": 1026,
                    "name": "",
                    "headers": [
                        {name: "Fiware-Service", value: null},
                        {name: "Fiware-ServicePath", value: null},
                        {name: "X-Auth-Token", value: null}
                    ]
                };



                $rootScope.addConnection = function (ev) {
                    $rootScope.conn = {
                        "mode": "New",
                        "hostname": "",
                        "port": 1026,
                        "name": "",
                        "headers": [
                            {name: "Fiware-Service", value: null},
                            {name: "Fiware-ServicePath", value: null},
                            {name: "X-Auth-Token", value: null}
                        ]
                    };

                    $mdDialog.show({
                        controller: 'DialogConnController',
                        templateUrl: 'app/partials/dialogs/conn.tpl.html',
                        parent: angular.element(document.body),
                        targetEvent: ev,
                        clickOutsideToClose: false,
                        fullscreen: true,
                        locals: {
                            conn: $rootScope.conn
                        }
                    })
                            .then(function (answer) {
                                console.log(answer);
                                $rootScope.connections = $rootScope.getConnections();
                                $rootScope.connections.push(answer);
                                $rootScope.saveConnections(true);
                            }, function () {
                                $rootScope.cancelConnection();//Clear editor
                            });

                };

                $rootScope.editConnection = function ($index, ev) {
                    $rootScope.connections = $rootScope.getConnections();
                    $rootScope.connections[$index]._index = $index;
                    $rootScope.conn = $rootScope.connections[$index];
                    $mdDialog.show({
                        controller: 'DialogConnController',
                        templateUrl: 'app/partials/dialogs/conn.tpl.html',
                        parent: angular.element(document.body),
                        targetEvent: ev,
                        clickOutsideToClose: false,
                        fullscreen: true,
                        locals: {
                            conn: $rootScope.conn
                        }
                    }).then(function (answer) {
                        var $index = answer._index;
                        delete answer._index;
                        $rootScope.connections = $rootScope.getConnections();
                        $rootScope.connections[$index] = answer;
                        $rootScope.saveConnections(true);
                    }, function () {
                        $rootScope.cancelConnection();//Clear editor
                    });
                };

                $rootScope.removeConnection = function ($index) {
                    $rootScope.connections = $rootScope.getConnections();
                    $rootScope.connections.splice($index, 1);
                    $rootScope.saveConnections(false);
                    $state.go('main');
                };

//                $rootScope.saveConnection = function () {
//                    var conn = $rootScope.conn;
//                    var mode = conn.mode;
//                    delete conn.mode;
//                    switch (mode) {
//                        case "New":
//                            $rootScope.connections = $rootScope.getConnections();
//                            $rootScope.connections.push(conn);
//                            break;
//                        case "Edit":
//                            var $index = conn._index;
//                            delete conn._index;
//                            $rootScope.connections = $rootScope.getConnections();
//                            $rootScope.connections[$index] = conn;
//                            break;
//                        default:
//                            break;
//                    }
//                    $rootScope.saveConnections(true);
//                    $rootScope.cancelConnection();//Clear editor
//                };

                $rootScope.cancelConnection = function () {
                    $rootScope.conn = {
                        "mode": null,
                        "hostname": "",
                        "port": 1026,
                        "name": "",
                        "headers": [
                            {name: "Fiware-Service", value: null},
                            {name: "Fiware-ServicePath", value: null},
                            {name: "X-Auth-Token", value: null}
                        ]
                    };
                };



                $rootScope.getConnections = function ($index) {
                    $rootScope.connections = JSON.parse(localStorage.getItem('connections')) || null;
                    if (null == $rootScope.connections) {
                        $rootScope.connections = [];
                        localStorage.setItem('connections', JSON.stringify($rootScope.connections));
                    }
                    return ($index) ? $rootScope.connections[$index] || null : $rootScope.connections;
                };


                $rootScope.saveConnections = function (emit) {
                    localStorage.setItem('connections', JSON.stringify($rootScope.connections));
                    if (emit) {
                        $rootScope.$broadcast("$OrionExplorerConnectionsChanged", $rootScope.connections);
                    }
                };

                $rootScope.getConnections();

            }])
        .controller('DialogConnController', ['$scope', '$mdDialog', '$rootScope', 'conn', function ($scope, $mdDialog, $rootScope, conn) {
                console.log($mdDialog, conn);
                $scope.connections = $rootScope.getConnections();
                $scope.connection = conn;

                $scope.addHeader = function () {
                    $scope.connection.headers.push({name: '', value: null});
                };
                $scope.removeHeader = function ($index) {
                    $scope.connection.headers.splice($index, 1);
                };

                $scope.hide = function () {
                    $mdDialog.hide();
                };

                $scope.cancel = function () {
                    $mdDialog.cancel();
                };

                $scope.answer = function () {
                    if (location.hostname === "orionexplorer.vm9it.com" && /(^127\.)|(^192\.168\.)|(^10\.)|(^172\.1[6-9]\.)|(^172\.2[0-9]\.)|(^172\.3[0-1]\.)|(localhost|localdomain|.local$)|(^::1$)|(^[fF][cCdD])/.test("" + $scope.connection.hostname)) {
                        alert("Sorry but localhost instances aren't allowed here :( ");
                    } else {
                        $mdDialog.hide($scope.connection);
                    }
                };
            }])
        /** .controller('AppCtrl', function ($scope, $mdDialog) {
         $scope.status = '  ';
         $scope.customFullscreen = false;
         
         $scope.showAlert = function (ev) {
         // Appending dialog to document.body to cover sidenav in docs app
         // Modal dialogs should fully cover application
         // to prevent interaction outside of dialog
         $mdDialog.show(
         $mdDialog.alert()
         .parent(angular.element(document.querySelector('#popupContainer')))
         .clickOutsideToClose(true)
         .title('This is an alert title')
         .textContent('You can specify some description text in here.')
         .ariaLabel('Alert Dialog Demo')
         .ok('Got it!')
         .targetEvent(ev)
         );
         };
         
         $scope.showConfirm = function (ev) {
         // Appending dialog to document.body to cover sidenav in docs app
         var confirm = $mdDialog.confirm()
         .title('Would you like to delete your debt?')
         .textContent('All of the banks have agreed to forgive you your debts.')
         .ariaLabel('Lucky day')
         .targetEvent(ev)
         .ok('Please do it!')
         .cancel('Sounds like a scam');
         
         $mdDialog.show(confirm).then(function () {
         $scope.status = 'You decided to get rid of your debt.';
         }, function () {
         $scope.status = 'You decided to keep your debt.';
         });
         };
         
         $scope.showPrompt = function (ev) {
         // Appending dialog to document.body to cover sidenav in docs app
         var confirm = $mdDialog.prompt()
         .title('What would you name your dog?')
         .textContent('Bowser is a common name.')
         .placeholder('Dog name')
         .ariaLabel('Dog name')
         .initialValue('Buddy')
         .targetEvent(ev)
         .ok('Okay!')
         .cancel('I\'m a cat person');
         
         $mdDialog.show(confirm).then(function (result) {
         $scope.status = 'You decided to name your dog ' + result + '.';
         }, function () {
         $scope.status = 'You didn\'t name your dog.';
         });
         };
         
         $scope.showAdvanced = function (ev) {
         $mdDialog.show({
         controller: DialogController,
         templateUrl: 'dialog1.tmpl.html',
         parent: angular.element(document.body),
         targetEvent: ev,
         clickOutsideToClose: true,
         fullscreen: $scope.customFullscreen // Only for -xs, -sm breakpoints.
         })
         .then(function (answer) {
         $scope.status = 'You said the information was "' + answer + '".';
         }, function () {
         $scope.status = 'You cancelled the dialog.';
         });
         };
         
         $scope.showTabDialog = function (ev) {
         $mdDialog.show({
         controller: DialogController,
         templateUrl: 'tabDialog.tmpl.html',
         parent: angular.element(document.body),
         targetEvent: ev,
         clickOutsideToClose: true
         })
         .then(function (answer) {
         $scope.status = 'You said the information was "' + answer + '".';
         }, function () {
         $scope.status = 'You cancelled the dialog.';
         });
         };
         
         $scope.showPrerenderedDialog = function (ev) {
         $mdDialog.show({
         controller: DialogController,
         contentElement: '#myDialog',
         parent: angular.element(document.body),
         targetEvent: ev,
         clickOutsideToClose: true
         });
         };
         
         function DialogController($scope, $mdDialog) {
         $scope.hide = function () {
         $mdDialog.hide();
         };
         
         $scope.cancel = function () {
         $mdDialog.cancel();
         };
         
         $scope.answer = function (answer) {
         $mdDialog.hide(answer);
         };
         }
         });
         **/
        ;
