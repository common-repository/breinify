(function ($) {

    function Setup() {
        this.blockedUi = false;
        this.configuration = {};
        this.messages = {};
        this.ajax = {};
        this.errorTimeoutHandler = null;
        this.blockUiTimeoutHandler = null;
    }

    Setup.prototype = {

        /**
         * Method called to initialize the UI, used to setup the Breinify account.
         *
         * @param communicationType defines if the ajax is a round-trip or direct
         * @param ajaxUrl the url used to do ajax calls to the PHP/WordPress back-end
         * @param restUrl the url used to do ajax calls to the Breinify back-end
         * @param messages the message to be shown when loading
         * @param defaultPage the page to be shown by default
         */
        initUi: function (communicationType, ajaxUrl, restUrl, messages, defaultPage) {
            var instance = this;
            this.ajax = {
                'communicationType': communicationType,
                'ajaxUrl': ajaxUrl,
                'restUrl': restUrl
            };
            this.messages = messages;

            /*
             * Add the click functionality to the a hrefs in the footer, and
             * toggle the visibility of the correct container on the page, based on
             * the selection
             */
            $("a[data-content-selector]").click(function () {
                var $el = $(this);
                if ($el.hasClass('disabled-anchor-breinify')) {
                    return false;
                } else {
                    instance.setVisibleContent($el.attr('data-content-selector'));
                }
            });

            /*
             * Bind page changing events
             */
            $("a[data-page-selector]").click(function () {
                var $el = $(this);
                if ($el.hasClass('disabled-anchor-breinify')) {
                    return false;
                } else {
                    instance.setVisiblePage($el.attr('data-page-selector'));
                }
            });

            /*
             * Add the click functionality to the different buttons on the page
             */
            $("a.button-primary[data-content-table-selector]").click(function () {
                var tableSelector = $(this).attr('data-content-table-selector');
                var data = uiUtility_breinify.readData(tableSelector);

                var parsleyValidator = $(tableSelector).closest('form').parsley();
                if (!parsleyValidator.validate()) {
                    return;
                }

                // reset the configuration process
                instance.resetConfiguration();

                // keep the username or email
                instance.configuration.email = typeof data.username == 'undefined' ? data.email : data.username;

                // send the data over to the server and wait for the answer
                instance.blockUi(uiUtility_breinify.getMessage(instance.messages, 'breinify-block-ui-title-', data.type),
                    uiUtility_breinify.getMessage(instance.messages, 'breinify-block-ui-message-', data.type));
                uiUtility_breinify.post(instance.ajax, 'ajaxsetup::' + data.type, data, function (response) {
                    if (instance.handleResponse(response) === true) {
                        instance.unblockUi();
                    }
                });
            });

            /*
             * Setup parsley to show tool-tip errors
             */
            var $forms = $('form[data-parsley-validate]');
            $forms.parsley({
                errorsContainer: function (parsleyField) {
                    return parsleyField.$element.attr("title");
                },
                errorsWrapper: false
            });


            /*
             * Parsley only works on id's... sad but true.
             */
            $forms.each(function () {
                var id = $(this).attr('id');
                var $form = $('#' + id);

                $form.parsley().on('field:error', function () {
                    var parsleyInstance = this;
                    var $el = parsleyInstance.$element;
                    var messages = ParsleyUI.getErrorsMessages(parsleyInstance);

                    instance.showToolTip($el, messages);
                }).on('field:success', function () {
                    instance.removeToolTip(this.$element[0]);
                });
            });

            /*
             * Show the default container.
             */
            this.setVisiblePage(defaultPage);
        },

        /**
         * Sets the visible page.
         *
         * @param pageSelector the page to be shown
         */
        setVisiblePage: function (pageSelector) {
            var instance = this;
            var $page = uiUtility_breinify.select(pageSelector);

            var showPage = function () {
                $('.setup-page-breinify').hide();
                $page.show();

                // show the first content
                instance.setVisibleContent($page.children(':first'));
            };

            // check if the page triggers the saving of the configuration
            if ($page.attr('data-save-configuration') === 'true') {
                this.blockUi(uiUtility_breinify.getMessage(this.messages, 'breinify-block-ui-title-', 'setup'),
                    uiUtility_breinify.getMessage(this.messages, 'breinify-block-ui-message-', 'setup'));
                uiUtility_breinify.post(this.ajax, 'ajaxsetup::dosaveconfiguration', this.configuration, function (response) {
                    response.finalized = true;

                    if (instance.handleResponse(response, function () {
                            showPage();
                        })) {
                        instance.unblockUi();
                    }
                });
            } else {
                showPage();
            }
        },

        /**
         * Helper function to specify the visible content, i.e.,
         * the different anchors are enabled/disabled and the content
         * is shown or hidden.
         *
         * @param contentSelector the selector which specifies the content by
         * an selector or the selected content as jQuery object
         */
        setVisibleContent: function (contentSelector) {
            var $content = uiUtility_breinify.select(contentSelector);

            // disable and enable the different anchors
            $content.parent().find(".setup-footer-breinify a[data-content-selector]").each(function () {
                var $anchor = $(this);
                var dataContentSelector = $anchor.attr('data-content-selector');

                if ($content.is($(dataContentSelector))) {
                    $anchor
                        .removeAttr('href')
                        .addClass('disabled-anchor-breinify');
                } else {
                    $anchor
                        .attr('href', '#')
                        .removeClass('disabled-anchor-breinify');
                }
            });

            // show the right content
            this.hideError();
            this.removeToolTips();
            $(".setup-content-breinify").hide();
            $content.show();
        },

        /**
         * Function to show an error message on the UI.
         *
         * @param message the message to be shown
         */
        showError: function (message) {
            var $error = $('#setup-error-breinify');
            $error.find('p').text(message);
            $error.show();

            // cancel any timeout thats available so far
            if (typeof this.errorTimeoutHandler != 'undefined' && this.errorTimeoutHandler !== null) {
                clearTimeout(this.errorTimeoutHandler);
            }

            this.errorTimeoutHandler = setTimeout(function () {
                $error.slideUp(500);
            }, 5000);
        },

        hideError: function () {
            var $error = $('#setup-error-breinify');

            // cancel any timeout thats available so far
            if (typeof this.errorTimeoutHandler != 'undefined' && this.errorTimeoutHandler !== null) {
                clearTimeout(this.errorTimeoutHandler);
            }

            $error.hide();
        },

        /**
         * Function called whenever a response was generated from
         * the server.
         *
         * @param response the response retrieved from the server
         * @param callback a function which is triggred after the response is handled
         */
        handleResponse: function (response, callback) {
            var instance = this;
            var unblockUi = true;

            var fireCallback = function () {
                if ($.isFunction(callback)) {
                    callback();
                }
            };

            if (typeof response.error !== 'undefined') {
                this.showError(response.error);
            } else if (typeof response.delayed !== 'undefined') {
                var data = {
                    'confirmStatusHandler': response.delayed,
                    'removeStatusHandler': true
                };

                setTimeout(function () {
                    instance.blockUi(uiUtility_breinify.getMessage(instance.messages, 'breinify-block-ui-title-', 'confirm'),
                        uiUtility_breinify.getMessage(instance.messages, 'breinify-block-ui-message-', 'confirm'));
                    uiUtility_breinify.post(instance.ajax, 'ajaxsetup::doconfirmcheck_embrest_checkhandler', data, function (response) {
                        if (instance.handleResponse(response, callback)) {
                            instance.unblockUi();
                            fireCallback();
                        }
                    });
                }, 1000);

                unblockUi = false;
            } else if (typeof response.finalized !== 'undefined') {
                delete response.finalized;
                this.configuration = response;
                this.printConfiguration('#config-overview-breinify');
                fireCallback();
            } else {
                this.configuration.firstName = response.user.firstName;
                this.configuration.lastName = response.user.lastName;
                this.configuration.password = response.user.password;

                // keep the data we have so far
                if ($.isArray(response.apiKeys)) {
                    if (response.apiKeys.length === 1) {
                        var apiKey = response.apiKeys[0];

                        // set the information of the selected key
                        this.configuration.apiKey = apiKey.apiKey;
                        this.setVisiblePage('#setup-success-breinify');
                    } else {
                        this.bindApiKeys(response.apiKeys, '#setup-apikey-breinify select[name="apiKey"]');
                        this.setVisiblePage('#setup-apikey-breinify');
                    }

                    fireCallback();
                } else {
                    // this error is handled on back-end side and thus should never happen here
                    this.showError(uiUtility_breinify.getMessage(this.messages, 'breinify-error-', 'apikeys'));
                }
            }

            return unblockUi;
        },

        showToolTip: function ($el, messages) {
            var el = $el[0];
            var instance = this;
            var data = $.data(el, 'breinify-toolTip-object');

            // get the toolTip
            var toolTip;
            if (typeof data == 'undefined') {
                toolTip = new Opentip($el, {
                    target: $el,
                    tipJoint: 'left',
                    style: 'alert',
                    removeElementsOnHide: true,
                    showOn: null
                });
                data = {'toolTip': toolTip};
                $.data(el, 'breinify-toolTip-object', data);
            } else {
                toolTip = data.toolTip;
                clearTimeout(data.handler);
            }

            // set the handler
            data.handler = setTimeout(function () {
                instance.removeToolTip(el);
            }, 3000);

            // set the message
            toolTip.setContent(messages.join('<br/>'));
            toolTip.show();
        },

        removeToolTips: function () {
            var instance = this;
            $('form[data-parsley-validate]').find('input[data-parsley-required]').each(function () {
                instance.removeToolTip(this);
            });
        },

        removeToolTip: function (el) {
            var data = $.data(el, 'breinify-toolTip-object');
            if (typeof data != 'undefined') {

                /*
                 * We deactivate any hiding effects, otherwise the hiding will take
                 * to long, we just want these tool-tips to disappear really fast.
                 */
                data.toolTip.options.hideEffect = 'none';
                data.toolTip.options.hideEffectDuration = 0;

                // now we can hide it fast
                data.toolTip.hide();
                clearTimeout(data.handler);
                $.removeData(el, 'breinify-toolTip-object');
            }
        },

        printConfiguration: function (selector) {
            uiUtility_breinify.printConfiguration(this.configuration, this.messages, selector);
        },

        resetConfiguration: function () {
            this.configuration = {};
        },

        bindApiKeys: function (apiKeys, selector) {
            var instance = this;
            var $comboBox = uiUtility_breinify.select(selector);

            $comboBox.empty();

            $.each(apiKeys, function (idx, val) {
                $option = $('<option value="' + val.apiKey + '">' + val.apiKey + '</option>').appendTo($comboBox);
                $.data($option[0], 'apiKeyData', val);
            });

            // bind the event
            $comboBox.unbind('change');
            $comboBox.bind('change', function () {
                instance.onApiKeyChange($(this));
            });

            // trigger the event to make sure the first is selected
            this.onApiKeyChange($comboBox);
        },

        onApiKeyChange: function ($combobox) {
            var $selected = $combobox.find('option:selected');
            if ($selected.length === 1) {
                var apiKey = $.data($selected[0], 'apiKeyData');
                this.configuration.apiKey = apiKey.apiKey;
            }
        },

        blockUi: function (title, message) {

            var setContent = function (title, message) {
                $('.breinify-block-ui-title').text(title);

                var $message = $('.breinify-block-ui-message');
                if (typeof message == 'undefined') {
                    $message.text('');
                    $message.hide();
                } else {
                    $message.text(message);
                    $message.show();
                }
            };

            // make sure we don't block twice, but we update the message
            if (this.blockedUi) {
                setContent(title, message);
            } else {
                var instance = this;

                this.blockedUi = true;
                this.blockUiTimeoutHandler = setTimeout(function () {
                    instance.hideError();

                    var html = '';

                    html += '<h1 class="breinify-block-ui-title"></h1>';
                    html += '<p class="breinify-block-ui-message"></p>';

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

                    setContent(title, message);
                }, 300);
            }
        },

        unblockUi: function () {
            if (typeof this.blockUiTimeoutHandler != 'undefined' && this.blockUiTimeoutHandler !== null) {
                clearTimeout(this.blockUiTimeoutHandler);
                this.blockUiTimeoutHandler = null;
            }

            this.blockedUi = false;
            $.unblockUI();
        },

        isBlocked: function () {
            return this.blockedUi;
        }
    };

// register the Setup as global object
    window.setup_breinify = new Setup();
})
(jQuery);