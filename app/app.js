'use strict';

// Declare app level module which depends on views, and components
angular.module('mainApp', [
    'ngAria',
    'ngSanitize',
    'ngAnimate',
    'ui.router',
    'ngMaterial',
//    'ui.bootstrap'
])
        .config(['$locationProvider',
            '$stateProvider',
            '$urlRouterProvider',
            '$compileProvider',
            function ($locationProvider, $stateProvider, $urlRouterProvider, $compileProvider) {
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
                            controller: ['$rootScope', '$scope', '$http', '$stateParams',
                                function ($rootScope, $scope, $http, $stateParams) {
                                    $scope.instance = $rootScope.getConnections($stateParams.id);
                                    $scope.init = function () {
                                        $http.post('server/index.php/orion/check', $scope.instance)
                                                .success(function (e) {
                                                    $scope.instance.info = e;
                                                }).error(function (e) {
//                                        $scope.instance.info = {};
                                        });

                                        $http.post('server/index.php/orion/types', $scope.instance)
                                                .success(function (e) {
                                                    $scope.instance.types = e;
                                                }).error(function (e) {
//                                        $scope.instance.types = [];
                                        });
                                        
                                        if($scope.selectedType != null){
                                            console.log($scope.selectedType);
                                        }
                                    };
                                    $scope.init();
                                    
                                    $scope.selectedType = null;
                                    
                                    $scope.setType = function(type){
                                        $scope.selectedType = type;
                                        $scope.selectedType.data = [];
                                        
                                         $http.post('server/index.php/orion/entities/' +type.type, $scope.instance)
                                                .success(function (e) {
                                                    $scope.selectedType.data = e;
                                                }).error(function (e) {
//                                        $scope.instance.info = {};
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
                            templateUrl: "app/partials/help.html"
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

            }])

        .run(['$rootScope', '$state', '$stateParams', function ($rootScope, $state, $stateParams) {
                console.log("%c APP STARTED ", ["background: black", "color: white", "font-size: 11px"].join(";"));



                $rootScope.$on('$stateChangeSuccess', function (event, s, sp) {
                    $script([
                        'app/bower_components/material-design-lite/material.min.js'
                    ], function () {
                        $rootScope.pageTitle = s.title || s.name;
                    });
                });

                var dialog = document.querySelector('#connection-modal');

                if (!dialog.showModal) {
                    dialogPolyfill.registerDialog(dialog);
                }
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
                $rootScope.formenable = false;
                $rootScope.addConnection = function () {
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
                    $rootScope.formenable = true;
                    dialog.showModal();
                };

                $rootScope.editConnection = function (conID) {
                    $rootScope.conn = {
                        "mode": "Edit",
                        "hostname": "",
                        "port": 1026,
                        "name": "",
                        "headers": [
                            {name: "Fiware-Service", value: null},
                            {name: "Fiware-ServicePath", value: null},
                            {name: "X-Auth-Token", value: null}
                        ]
                    };
                    $rootScope.formenable = true;
                    dialog.showModal();
                };

                $rootScope.addHeader = function () {
                    $rootScope.conn.headers.push({name: '', value: null});
                };
                $rootScope.removeHeader = function ($index) {
                    $rootScope.conn.headers.splice($index, 1);
                };

                $rootScope.removeConnection = function ($index) {
                    $rootScope.connections = $rootScope.getConnections();
                    $rootScope.connections.splice($index, 1);
                    $rootScope.saveConnections();
                };

                $rootScope.saveConnection = function () {
                    var conn = $rootScope.conn;

                    switch (conn.mode) {
                        case "New":
                            $rootScope.connections = $rootScope.getConnections();
                            $rootScope.connections.push(conn);
                            break;
                        default:
                            break;
                    }

                    localStorage.setItem('connections', $rootScope.connections);
                    $rootScope.saveConnections();
                    $rootScope.cancelConnection();//Clear editor
                };

                $rootScope.cancelConnection = function () {
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
                    dialog.close();
                };



                $rootScope.getConnections = function ($index) {
                    $rootScope.connections = JSON.parse(localStorage.getItem('connections')) || null;
                    if (null == $rootScope.connections) {
                        $rootScope.connections = [];
                        localStorage.setItem('connections', JSON.stringify($rootScope.connections));
                    }
                    return ($index) ? $rootScope.connections[$index] || null : $rootScope.connections;
                };


                $rootScope.saveConnections = function () {
                    localStorage.setItem('connections', JSON.stringify($rootScope.connections));
                };

                $rootScope.getConnections();


            }])


        ;
