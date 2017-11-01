var Aligent = Aligent || {};
var ScarabQueue = ScarabQueue || [];

Aligent.Emarsys = Class.create({
    initialize: function(config) {
        this._config = config;

        this._initState();
        this._handleConfig();
        this._bindEvents();

        this._setupCookie();
        this._setupMainApiHandler();
    },

    doSubscribe : function(firstName, lastName, email, additionalData, callback){
        var params = additionalData ? additionalData : {};
        params.firstname = firstName;
        params.lastname = lastName;
        params.email = email;
        if(!params.dob) params.dob = null;
        if(!params.dobYY) params.dobYY = null;
        if(!params.dobMM) params.dobMM = null;
        if(!params.dobDD) params.dobDD = null;
        params.form_key = this._config.formKey;

        new Ajax.Request(this._config.subscribeUrl, {
            method: 'post',
            parameters: params,
            onSuccess : function(response){
                var json = jQuery.parseJSON(response.responseText);
                if(callback){
                    try{
                        callback(json.success, response);
                    }catch(e){

                    }
                }
                // Emarsys expects the "setEmail" command to be sent after a successful subscription. We'll set it here
                // because if a customer does not have an account with the store then we won't have one available in
                // the cookie. If, however, there is a customer in the cookie (because they're signed in) it'll be
                // included after this call and be used instead
                //
                // ie. [
                //   ['setEmail', 'foo@bar.com'],
                //   ...
                //   ['setEmail', 'john@example.org']
                // ]
                // Will end up with John's email used
                ScarabQueue.push(['setEmail', email]);
                Event.fire(document, 'emarsys:send');
            },
            onFailure : function(response){
                if(callback){
                    try{
                        callback(false, response);
                    }catch(e){

                    }
                }
            }

        });
    },

    _handleConfig: function() {
        this._config.cookieName = this._config.cookieName || 'cart_cookie';
        this._config.merchantId = this._config.merchantId || 'MERCHANTID';
        this._config.scarabJsApiId = this._config.scarabJsApiId || 'scarab-js-api';
        this._config.scarabSource = this._config.scarabSource || '//cdn.scarabresearch.com/js/MERCHANTID/scarab-v2.js';
        this._config.subscribeUrl = this._config.subscribeUrl || '/emarsys/index/newslettersubscribe';

        this._configFalseCheck(this._config.sendEmail, 1);
        this._configFalseCheck(this._config.testMode, 1);
        this._configFalseCheck(this._config.sendParentSku, 1);
    },

    _configFalseCheck: function(config, defaultValue) {
        // Explicitly check for 0 being set to confirm we do not take the default value when bool is false.
        if(config === 0) {
            config = 0;
            return;
        }

        config = defaultValue;
    },

    _initState: function() {
        this._state = [];
        this._value = [];
        this._state.cookieReady = false;
        this._state.queueReady = false;
        this._state.pageReady = false;
        this._state.queueSent = false;

        this._resetState();
    },

    _resetState: function() {
        this._state.sendPDP = false;
        this._state.sendPLP = false;
        this._state.sendSuccess = false;
        this._state.sendSearch = false;

        this._value.plp = '';
        this._value.pdp = '';
        this._value.searchTerm = '';
        this._value.successOrderId = '';
        this._value.successCart = '';
    },

    // Set the cookie if it is not set.
    _setupCookie: function() {
        if(!Mage.Cookies.get(this._config.cookieName)){
            //Make Ajax call to update backend.
            new Ajax.Request('/emarsys/index/cookieupdate', {
                onSuccess: function(response) {
                    Event.fire(document, 'emarsys_cookie:ready');
                }.bind(this),
                onFailure: function(response) {
                    Event.fire(document, 'emarsys_cookie:ready');
                }.bind(this)
            });
        } else {
            Event.fire(document, 'emarsys_cookie:ready');
        }
    },

    // Load scarab js file async
    _setupMainApiHandler: function() {
        if (document.getElementById(this._config.scarabJsApiId)) return;

        var js = document.createElement('script');
        js.id = this._config.scarabJsApiId;
        js.src = this._config.scarabSource;

        js.onload = js.onreadystatechange = function() {
            if (!this.readyState || this.readyState == 'complete') {
                Event.fire(document, 'emarsys_scarabqueue:ready');
            }
        };

        var fs = document.getElementsByTagName('script')[0];
        fs.parentNode.insertBefore(js, fs);
    },

    _bindEvents: function() {
        // Scarab JS has been loaded
        Event.observe(document, 'emarsys_scarabqueue:ready', function() {
            this._state.queueReady = true;

            // If we are the last to return, send data.
            Event.fire(document, 'emarsys:send');
        }.bind(this));

        // Cookie has been loaded
        Event.observe(document, 'emarsys_cookie:ready', function() {
            this._state.cookieReady = true;

            // If we are the last to return, send data.
            Event.fire(document, 'emarsys:send');
        }.bind(this));

        // This happens in the footer so all events in the header have been fired and captured by this point.
        Event.observe(document, 'emarsys_pageend:render', function () {
            this._state.pageReady = true;

            // If we are the last to return, send data.
            Event.fire(document, 'emarsys:send');
        }.bind(this));

        // PDP view.
        Event.observe(document, 'emarsys_pdp:view', function (event) {
            this._state.sendPDP = true;
            this._value.pdp = event.memo.data;
        }.bind(this));

        // PLP view.
        Event.observe(document, 'emarsys_plp:view', function (event) {
            this._state.sendPLP = true;
            this._value.plp = event.memo.data;
        }.bind(this));

        // Success view.
        Event.observe(document, 'emarsys_success:view', function (event) {
            this._state.sendSuccess = true;
            this._value.successOrderId = event.memo.orderid;
            this._value.successCart = event.memo.cart;
        }.bind(this));

        // Search view.
        Event.observe(document, 'emarsys_search:view', function (event) {
            this._state.sendSearch = true;
            this._value.searchTerm = event.memo.data;
        }.bind(this));

        // Add to cart ajax from aligent module
        Event.observe(document, 'addToCart:addComplete', function (event) {
            // Reset the state so we do not send PLP,PDP extra info.
            this._resetState();

            // Add to cart should refire event.
            Event.fire(document, 'emarsys:send', {refire:true});
        }.bind(this));

        // Final sending of data
        Event.observe(document, 'emarsys:send', function(event) {
            var refire = (typeof event.memo.refire !== 'undefined') ? event.memo.refire : false;

            // Check that the scarab queue, page load, cookie are all ready and we have not sent already.
            // If we have sent already, check if the even is telling us to refire.
            if (this._state.queueReady && this._state.pageReady && this._state.cookieReady && (!this._state.queueSent || (this._state.queueSent && refire))) {
                this._state.queueSent = true;

                // Enable test mdoe
                if (this._config.testMode) {
                    ScarabQueue.push(['testMode']);
                }

                var cookie = this._getCookieAsArray();
                var userInfo = '';

                // Emarsys will complain if the email or id is an empty string.
                // So only send it if we have it.
                if (this._config.sendEmail) {
                    if (this._checkExists(cookie['user']['email'])) {
                        userInfo = cookie['user']['email'];
                        ScarabQueue.push(['setEmail', userInfo]);
                    }
                } else {
                    if (this._checkExists(cookie['user']['id'])) {
                        userInfo = cookie['user']['id'];
                        ScarabQueue.push(['setCustomerId', userInfo]);
                    }
                }

                ScarabQueue.push(['cart', this._convertCart(cookie['cart'])]);

                // PDP
                if (this._state.sendPDP) {
                    ScarabQueue.push(['view', this._value.pdp]);
                }

                // PLP
                if (this._state.sendPLP) {
                    ScarabQueue.push(['category', this._value.plp]);
                }

                // Success
                if (this._state.sendSuccess) {
                    var purchase = {};
                    purchase.orderId = this._value.successOrderId;
                    purchase.items = this._convertCart(this._value.successCart);

                    ScarabQueue.push(['purchase', purchase]);
                }

                // SendSearch
                if (this._state.sendSearch) {
                    ScarabQueue.push(['searchTerm', this._value.searchTerm]);
                }

                // Tag this event with the current website code for future lookup and segmentation
                if (this._config.sendWebsiteCode) {
                    ScarabQueue.push(['tag', this._config.websiteCode]);
                }

                // Send - only ever called once.
                ScarabQueue.push(['go']);
            } else {
                // wait for the other event to trigger.
            }
        }.bind(this));
    },

    _checkExists: function(check) {
        return (typeof check !== 'undefined' && check);
    },

    //Need to convert cart object to array for emarsys in correct format
    _convertCart: function(cartObj) {
        var arr = [];
        var tempArr = [];

        for (var i in cartObj) {
            if (cartObj.hasOwnProperty(i)) {
                tempArr = [];

                tempArr['item'] = cartObj[i]['id'];

                if (this._config.sendParentSku && this._checkExists(cartObj[i]['parentid'])) {
                    tempArr['item'] = cartObj[i]['parentid'];
                }

                // Qty and price must be numbers.
                tempArr['quantity'] = Number(cartObj[i]['qty']);
                tempArr['price'] = Number(cartObj[i]['price_total']);

                arr.push(tempArr);
            }
        }

        return arr;
    },

    _getCookieAsArray: function() {
        return JSON.parse(Mage.Cookies.get(this._config.cookieName)) || [];
    }
});
