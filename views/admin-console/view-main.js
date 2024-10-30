(function ($) {

    function Main() {
        this.errorTimeoutHandler = null;
        this.ajax = {};
        this.communicationTypes = [];
        this.categories = [];
        this.messages = null;
    }

    Main.prototype = {

        /**
         * Method called to initialize the UI.
         *
         * @param {object} credentials the credentials
         * @param {object} ajax the ajax definition to use
         * @param {object} configuration the current configuration
         * @param {[Array.<object>]} communicationTypes the available communication-types
         * @param {[Array.<object>]} categories the categories available
         * @param {boolean} afterSetup true if the page is loaded after setup, otherwise false
         * @param {boolean} showSecret true if the secret should be shown for marked communication types (null), otherwise false
         * @param {[Array.<object>]} messages array of messages
         */
        initUi: function (credentials, ajax, configuration, communicationTypes, categories, afterSetup, showSecret, messages) {
            var instance = this;

            this.ajax = ajax;
            this.communicationTypes = communicationTypes;
            this.categories = categories;
            this.messages = messages;

            uiUtility_breinify.printConfiguration(configuration, messages,
                '#main-config-overview-breinify', ['apiKey', 'firstName', 'lastName', 'email']);

            // let's get some elements we need
            var $el = $('#main-advanced-settings-breinify');
            var $secretEl = $el.find('input[name="secret"]');
            var $communicationTypeEl = $el.find('select[name="communicationType"]');
            var $categoryEl = $el.find('select[name="category"]');

            /*
             * Set the showSecret flag, which makes sure the secret is shown, for
             * communication types with the value of null regarding the secret.
             */
            if (showSecret) {
                $.data($secretEl[0], 'show-secret', true);
                uiUtility_breinify.borderBlink($secretEl);
            }

            /*
             * Add some listeners to some events which have to be handled.
             */
            $communicationTypeEl.change(function () {
                instance._onCommunicationTypeChange($(this));
            });
            this._onCommunicationTypeChange($communicationTypeEl);

            $categoryEl.change(function () {
                instance._onCategoryChange($(this));
            });
            this._onCategoryChange($categoryEl);

            $secretEl.on('input', function () {
                instance._onSecretChange($(this));
            });
            this._onSecretChange($secretEl);

            uiUtility_breinify.activateCheckBoxLabelClick();

            /*
             * Let's the category blink and show a  message. It is important to do
             * this after the first _onCategoryChange($categoryEl) is triggered.
             * Otherwise, the message would have been hidden directly.
             */
            if (afterSetup) {
                var msg = uiUtility_breinify.getMessage(this.messages, 'breinify-advanced-settings-', 'select-business-type');
                $.data($categoryEl[0], 'msg-identifier', uiUtility_breinify.showMessage(msg, 'info', false));
                uiUtility_breinify.borderBlink($categoryEl);
            }

            // create the credentials for the ajax calls
            var ajaxCredentials = {
                sessionId: credentials.sessionId,
                email: credentials.email,
                apiKey: configuration.apiKey
            };

            // add the activitiesChart
            if ($('#highcharts-current-activities').length > 0) {
                var activitiesChart = new AnalyticsRealtimeActivities(ajaxCredentials, this.ajax, '#highcharts-current-activities', this.messages);
                activitiesChart.update(true);
            }

            // add the current collectives
            if ($('#breinify-dashboard-current-collectives').length > 0) {

                // add the category to the credentials
                var category = uiUtility_breinify.isEmpty(configuration.category) ? 'other' : configuration.category;
                var modCredentials = jQuery.extend({}, ajaxCredentials, {'category': category});
                this._setupCurrentCollectivesDashboard(modCredentials);
            }
        },

        unbind: function () {
            var instance = this;
            uiUtility_breinify.post(this.ajax, 'ajaxmain::dounbind', {}, function (response) {
                instance.handleResponse(response, function () {
                    location.reload();
                });
            });
        },

        deleteErrors: function () {
            var instance = this;
            uiUtility_breinify.post(this.ajax, 'ajaxmain::dodeleteerrors', {}, function (response) {
                instance.handleResponse(response, function () {
                    location.reload();
                });
            });
        },

        saveAdvancedSettings: function () {
            var instance = this;

            var data = uiUtility_breinify.readData('#main-advanced-settings-breinify');
            uiUtility_breinify.post(this.ajax, 'ajaxmain::dosaveadvancedsettings', data, function (response) {
                instance.handleResponse(response, function () {
                    uiUtility_breinify.refresh({'info': uiUtility_breinify.getMessage(instance.messages, 'breinify-advanced-settings-', 'saved')}, ['info', 'afterSetup', 'showSecret']);
                });
            });
        },

        saveActivityTrackerSettings: function () {
            var instance = this;

            var data = uiUtility_breinify.readData('#main-activity-tracker-breinify');
            uiUtility_breinify.post(this.ajax, 'ajaxmain::dosaveactivitytrackersettings', data, function (response) {
                instance.handleResponse(response, function () {
                    uiUtility_breinify.refresh({'info': uiUtility_breinify.getMessage(instance.messages, 'breinify-advanced-settings-', 'saved')});
                });
            });
        },

        handleResponse: function (response, callback) {

            var fireCallback = function () {
                if ($.isFunction(callback)) {
                    callback();
                }
            };

            if (response !== null && typeof response.error !== 'undefined') {
                this.showError(response.error);
            } else {
                fireCallback();
            }
        },

        /**
         * Function to show an error message on the UI.
         *
         * @param message the message to be shown
         */
        showError: function (message) {
            uiUtility_breinify.showMessage(message, 'error');
        },

        login: function (ajax, credentials, messages) {
            $('#wpbody-content').empty();

            var html = '';

            html += '<h1 class="breinify-block-ui-title">' + uiUtility_breinify.getMessage(messages, 'breinify-login-uiblocker-', 'title') + '</h1>';
            html += '<p class="breinify-block-ui-message">' + uiUtility_breinify.getMessage(messages, 'breinify-login-uiblocker-', 'message') + '</p>';

            // block the UI so that no interaction is possible
            $.blockUI({
                css: {
                    'border-radius': '5px',
                    '-moz-border-radius': '5px',
                    '-webkit-border-radius': '5px',
                    'border': '1px solid #42A2DE',
                    'color': '#000000'
                },
                message: html
            });

            // do the log in
            var instance = this;
            uiUtility_breinify.post(ajax, 'ajaxmain::dologin_embrest_login', credentials, function (response, status) {
                var finalize = function () {
                    $.unblockUI();
                    location.reload();
                };
                var showError = function (message) {
                    instance.ajax = ajax;

                    $.unblockUI();

                    // create the error message
                    var html = '';
                    html += '<div class="breinify-notice notice-global notice-error">';
                    html += '<p>' + message + '</p>';
                    html += '</div>';

                    html += '<div class="container-centered-breinify">';

                    html += '<a href="javascript:location.reload();" class="button-primary">';
                    html += uiUtility_breinify.getMessage(messages, 'breinify-login-error-', 'retry');
                    html += '</a>';

                    html += '<span style="padding: 0 2px">&nbsp;</span>';

                    html += '<a href="javascript:main_breinify.unbind();" class="button-primary">';
                    html += uiUtility_breinify.getMessage(messages, 'breinify-login-error-', 'unbind');
                    html += '</a>';

                    html += '</div>';

                    $('#wpbody-content').append(html);
                };

                var validate = function (response, status, cb) {
                    if (status !== 200) {
                        showError(uiUtility_breinify.getMessage(messages, 'breinify-login-error-', status));
                    } else if (typeof response.error !== 'undefined') {
                        showError(uiUtility_breinify.getMessage(messages, 'breinify-login-error-', 'message') + response.error);
                    } else if ($.isFunction(cb)) {
                        cb();
                    }
                };

                validate(response, status, function () {
                    if (response.server !== true) {
                        uiUtility_breinify.post(ajax, 'ajaxmain::dosetsessionid', {
                            'sessionId': response.sessionId
                        }, function (response, status) {
                            validate(response, status, finalize);
                        });
                    } else {
                        validate(response, status, finalize);
                    }
                });
            }, true);
        },

        _onCategoryChange: function ($el) {
            if ($el.length == 0) {
                return;
            }

            var $selected = $el.children('option:selected');
            var category = this._findCategory($selected.val());

            var $dashicon = $el.closest('tr').find('td > span.dashicons');
            $.each($dashicon.data("opentips"), function (idx, el) {
                el.setContent(category.description);
            });

            var $msgEl = $('#' + $.data($el[0], 'msg-identifier'));
            uiUtility_breinify.hideMessage($msgEl);
        },

        _onCommunicationTypeChange: function ($el) {
            if ($el.length == 0) {
                return;
            }

            var $selected = $el.children('option:selected');
            var communicationTypeInfo = this._findCommunicationType($selected.val());

            if (communicationTypeInfo == null) {
                this.showError(uiUtility_breinify.getMessage(this.messages, 'breinify-advanced-settings-invalid-', 'communicationType'));
            }

            //noinspection JSUnresolvedVariable
            var secretSupport = communicationTypeInfo.signatureSupport;
            var $secretRow = $('tr.breinify-input-selector-secret');

            if (typeof secretSupport == 'undefined' || secretSupport == null) {
                var $secretEl = $secretRow.find('input[name="secret"]');
                var showSecret = $.data($secretEl[0], 'show-secret');

                if (showSecret || $secretEl.val()) {
                    $secretRow.show();
                } else {
                    $secretRow.hide();
                }
            } else if (secretSupport === true) {
                $secretRow.show();
            } else {
                $secretRow.hide();
            }
            this._onSecretChange($secretRow.find('input'));

            var $dashicon = $el.closest('tr').find('td > span.dashicons');
            $.each($dashicon.data("opentips"), function (idx, el) {
                el.setContent(communicationTypeInfo.description);
            });
        },

        _onSecretChange: function ($el) {
            if ($el.length == 0) {
                return;
            }

            var $msgEl = $('#' + $.data($el[0], 'msg-identifier'));
            var hideDirect = $el.is(':hidden');

            if (hideDirect || $el.val() === '') {
                uiUtility_breinify.hideMessage($msgEl, null, hideDirect);
            } else if ($msgEl.length === 0) {
                var msg = uiUtility_breinify.getMessage(this.messages, 'breinify-advanced-settings-secret-', 'active');
                $.data($el[0], 'msg-identifier', uiUtility_breinify.showMessage(msg, 'warning', false));
            }
        },

        _findCommunicationType: function (name) {

            var result = null;
            $.each(this.communicationTypes, function (key, type) {
                if (key === name) {
                    result = type;
                    return false;
                }
            });

            return result;
        },

        _findCategory: function (name) {

            var result = null;
            $.each(this.categories, function (key, type) {
                if (key === name) {
                    result = type;
                    return false;
                }
            });

            return result;
        },

        _setupCurrentCollectivesDashboard: function (data) {
            var instance = this;

            // get some messages we need
            var msgLoading = uiUtility_breinify.getMessage(this.messages,
                'breinify-analytics-error-', 'loading');
            var msgNoData = uiUtility_breinify.getMessage(this.messages,
                'breinify-analytics-error-', 'nodata');
            var msgNoVisitors = uiUtility_breinify.getMessage(this.messages,
                'breinify-analytics-error-', 'novisitors');

            //noinspection JSUnresolvedFunction
            var treeMap = new TreeMap();
            treeMap.bind('treemap-current-collectives', true);
            treeMap.setMappers($.extend({}, treeMap.getMappers(), {

                colors: [
                    '#FF9E91',
                    '#CBC4FF',
                    '#8FB2D8',
                    '#E1BEA2',
                    '#D8CC95',
                    '#FFD9CE',
                    '#FFF091',
                    '#A9D2D5'
                ],
                mappedColors: [],
                mapping: {},

                color: function (name) {
                    if (data.category === name) {
                        return '#ADDDAE';
                    } else if(name === 'all') {
                        return '#FFFFFF';
                    } else if (typeof this.mapping[name] !== 'undefined') {
                        return this.mappedColors[this.mapping[name]];
                    } else {
                        var nextColor = this.mappedColors.length;
                        var color = this.colors[nextColor % this.colors.length];
                        this.mapping[name] = nextColor;
                        this.mappedColors.push(color);

                        return color;
                    }
                }
            }));

            //noinspection JSUnresolvedFunction
            var barComperator = new BarComperator();
            barComperator.bind('barcomperator-current-collectives');
            barComperator.setMappers($.extend({}, barComperator.getMappers(), {
                color: function (idx) {
                    return idx === 0 ? '#ADDDAE' : '#B696DD';
                },
                width: function (idx, width) {
                    return Math.floor((idx == 0 ? 0.85 : 1.0) * width);
                }
            }));

            var $contentDashboard = $('#dashboard-content');
            var $msgDashboard = $('#dashboard-message');

            // show the loading message in the beginning
            $msgDashboard.find('span').html(msgLoading);

            //noinspection JSUnresolvedVariable
            layouter.layout($, $contentDashboard);
            $contentDashboard.hide();

            //noinspection JSUnresolvedFunction,JSUnresolvedVariable
            var task = new IntervalTask(60000, function () {

                // do the post call and handle the response
                uiUtility_breinify.post(
                    instance.ajax,
                    'ajaxbreinengineapi::docurrentcollectives_embrest_currentcollectives',
                    data, function (response) {

                        if (response.error) {
                            $msgDashboard.find('span').html(response.error);
                            $msgDashboard.show();
                            $contentDashboard.hide();
                        } else if (response.timeSeriesTimePoint === -1 ||
                            response.timeSeriesBucketSize === -1) {
                            $msgDashboard.find('span').html(msgNoData);
                            $msgDashboard.show();
                            $contentDashboard.hide();
                        } else if (response.visitorCountTotal === 0) {
                            $msgDashboard.find('span').html(msgNoVisitors);
                            $msgDashboard.show();
                            $contentDashboard.hide();
                        } else {
                            //noinspection JSUnresolvedVariable
                            treeMap.data(response.visitorCountCategories);
                            //noinspection JSUnresolvedVariable
                            barComperator.data([response.visitorCountTotal, response.visitorCountCategoryTotal]);
                            $msgDashboard.hide();
                            $contentDashboard.show();
                        }
                    });
            }, IntervalTask.startOnNextMinute(5000), true);
            task.start();
        }
    };

    // register the Main as global object
    window.main_breinify = new Main();
})
(jQuery);