/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'underscore',
    'Magento_Ui/js/form/form',
    'ko',
    'Magento_Customer/js/model/customer',
    'Magento_Customer/js/model/address-list',
    'Magento_Checkout/js/model/address-converter',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/action/create-shipping-address',
    'Magento_Checkout/js/action/select-shipping-address',
    'Magento_Checkout/js/model/shipping-rates-validator',
    'Magento_Checkout/js/model/shipping-address/form-popup-state',
    'Magento_Checkout/js/model/shipping-service',
    'Magento_Checkout/js/action/select-shipping-method',
    'Magento_Checkout/js/model/shipping-rate-registry',
    'Magento_Checkout/js/action/set-shipping-information',
    'Magento_Checkout/js/model/step-navigator',
    'Magento_Ui/js/modal/modal',
    'Magento_Checkout/js/model/checkout-data-resolver',
    'Magento_Checkout/js/checkout-data',
    'uiRegistry',
    'mage/translate',
    'mage/storage',
    'Magento_Checkout/js/model/shipping-rate-service'
], function (
    $,
    _,
    Component,
    ko,
    customer,
    addressList,
    addressConverter,
    quote,
    createShippingAddress,
    selectShippingAddress,
    shippingRatesValidator,
    formPopUpState,
    shippingService,
    selectShippingMethodAction,
    rateRegistry,
    setShippingInformationAction,
    stepNavigator,
    modal,
    checkoutDataResolver,
    checkoutData,
    registry,
    $t,
    storage
) {
    'use strict';

    var popUp = null;

    return Component.extend({
        defaults: {
            template: 'Magento_Checkout/shipping',
            shippingFormTemplate: 'Magento_Checkout/shipping-address/form',
            shippingMethodListTemplate: 'Magento_Checkout/shipping-address/shipping-method-list',
            shippingMethodItemTemplate: 'Magento_Checkout/shipping-address/shipping-method-item',
            imports: {
                countryOptions: '${ $.parentName }.shippingAddress.shipping-address-fieldset.country_id:indexedOptions'
            }
        },
        visible: ko.observable(!quote.isVirtual()),
        errorValidationMessage: ko.observable(false),
        isCustomerLoggedIn: customer.isLoggedIn,
        isFormPopUpVisible: formPopUpState.isVisible,
        isFormInline: addressList().length === 0,
        isNewAddressAdded: ko.observable(false),
        saveInAddressBook: 1,
        quoteIsVirtual: quote.isVirtual(),
        getResidential: ko.observable(window.checkoutConfig.residential),
        getLiftgate: ko.observable(window.checkoutConfig.liftgate),
        getDelivery: ko.observable(window.checkoutConfig.delivery),
        /**
         * @return {exports}
         */
        initialize: function () {
            var self = this,
                hasNewAddress,
                fieldsetName = 'checkout.steps.shipping-step.shippingAddress.shipping-address-fieldset';

            this._super();

            if (!quote.isVirtual()) {
                stepNavigator.registerStep(
                    'shipping',
                    '',
                    $t('Shipping'),
                    this.visible, _.bind(this.navigate, this),
                    this.sortOrder
                );
            }
            checkoutDataResolver.resolveShippingAddress();

            hasNewAddress = addressList.some(function (address) {
                return address.getType() == 'new-customer-address'; //eslint-disable-line eqeqeq
            });

            this.isNewAddressAdded(hasNewAddress);

            this.isFormPopUpVisible.subscribe(function (value) {
                if (value) {
                    self.getPopUp().openModal();
                }
            });

              this.subscribeToCheckboxChanges();
            // Restore the state of the checkboxes
            this.restoreCheckboxState();
            // Other initialization code
// =====
            this.getResidential.subscribe(function(value) {
                self.getResidentialChecked(value);
            });

            
            this.getLiftgate.subscribe(function(value) {
                self.getLiftgateChecked(value);
            });
            
            


            this.getDelivery.subscribe(function(value) {
                self.getDeliveryChecked(value);
            })

            quote.shippingMethod.subscribe(function () {
                self.errorValidationMessage(false);
            });

            registry.async('checkoutProvider')(function (checkoutProvider) {
                var shippingAddressData = checkoutData.getShippingAddressFromData();

                if (shippingAddressData) {
                    checkoutProvider.set(
                        'shippingAddress',
                        $.extend(true, {}, checkoutProvider.get('shippingAddress'), shippingAddressData)
                    );
                }
                checkoutProvider.on('shippingAddress', function (shippingAddrsData, changes) {
                    var isStreetAddressDeleted, isStreetAddressNotEmpty;

                    /**
                     * In last modifying operation street address was deleted.
                     * @return {Boolean}
                     */
                    isStreetAddressDeleted = function () {
                        var change;

                        if (!changes || changes.length === 0) {
                            return false;
                        }

                        change = changes.pop();

                        if (_.isUndefined(change.value) || _.isUndefined(change.oldValue)) {
                            return false;
                        }

                        if (!change.path.startsWith('shippingAddress.street')) {
                            return false;
                        }

                        return change.value.length === 0 && change.oldValue.length > 0;
                    };

                    isStreetAddressNotEmpty = shippingAddrsData.street && !_.isEmpty(shippingAddrsData.street[0]);

                    if (isStreetAddressNotEmpty || isStreetAddressDeleted()) {
                        checkoutData.setShippingAddressFromData(shippingAddrsData);
                    }
                });
                shippingRatesValidator.initFields(fieldsetName);
            });

            return this;
        },

        /**
         * Navigator change hash handler.
         *
         * @param {Object} step - navigation step
         */
        navigate: function (step) {
            step && step.isVisible(true);
        },

        /**
         * @return {*}
         */
        getPopUp: function () {
            var self = this,
                buttons;

            if (!popUp) {
                buttons = this.popUpForm.options.buttons;
                this.popUpForm.options.buttons = [
                    {
                        text: buttons.save.text ? buttons.save.text : $t('Save Address'),
                        class: buttons.save.class ? buttons.save.class : 'action primary action-save-address',
                        click: self.saveNewAddress.bind(self)
                    },
                    {
                        text: buttons.cancel.text ? buttons.cancel.text : $t('Cancel'),
                        class: buttons.cancel.class ? buttons.cancel.class : 'action secondary action-hide-popup',

                        /** @inheritdoc */
                        click: this.onClosePopUp.bind(this)
                    }
                ];

                /** @inheritdoc */
                this.popUpForm.options.closed = function () {
                    self.isFormPopUpVisible(false);
                };

                this.popUpForm.options.modalCloseBtnHandler = this.onClosePopUp.bind(this);
                this.popUpForm.options.keyEventHandlers = {
                    escapeKey: this.onClosePopUp.bind(this)
                };

                /** @inheritdoc */
                this.popUpForm.options.opened = function () {
                    // Store temporary address for revert action in case when user click cancel action
                    self.temporaryAddress = $.extend(true, {}, checkoutData.getShippingAddressFromData());
                };
                popUp = modal(this.popUpForm.options, $(this.popUpForm.element));
            }

            return popUp;
        },

        /**
         * Revert address and close modal.
         */
        onClosePopUp: function () {
            checkoutData.setShippingAddressFromData($.extend(true, {}, this.temporaryAddress));
            this.getPopUp().closeModal();
        },

        /**
         * Show address form popup
         */
        showFormPopUp: function () {
            this.isFormPopUpVisible(true);
        },

        /**
         * Save new shipping address
         */
        saveNewAddress: function () {
            var addressData,
                newShippingAddress;

            this.source.set('params.invalid', false);
            this.triggerShippingDataValidateEvent();

            if (!this.source.get('params.invalid')) {
                addressData = this.source.get('shippingAddress');
                // if user clicked the checkbox, its value is true or false. Need to convert.
                addressData['save_in_address_book'] = this.saveInAddressBook ? 1 : 0;

                // New address must be selected as a shipping address
                newShippingAddress = createShippingAddress(addressData);
                selectShippingAddress(newShippingAddress);
                checkoutData.setSelectedShippingAddress(newShippingAddress.getKey());
                checkoutData.setNewCustomerShippingAddress($.extend(true, {}, addressData));
                this.getPopUp().closeModal();
                this.isNewAddressAdded(true);
            }
        },

        /**
         * Shipping Method View
         */
        rates: shippingService.getShippingRates(),
        isLoading: shippingService.isLoading,
        isSelected: ko.computed(function () {
            return quote.shippingMethod() ?
                quote.shippingMethod()['carrier_code'] + '_' + quote.shippingMethod()['method_code'] :
                null;
        }),

        /**
         * @param {Object} shippingMethod
         * @return {Boolean}
         */
        selectShippingMethod: function (shippingMethod) {
            selectShippingMethodAction(shippingMethod);
            checkoutData.setSelectedShippingRate(shippingMethod['carrier_code'] + '_' + shippingMethod['method_code']);

            return true;
        },

        /**
         * Set shipping information handler
         */
        setShippingInformation: function () {
            if (this.validateShippingInformation()) {
                quote.billingAddress(null);
                checkoutDataResolver.resolveBillingAddress();
                registry.async('checkoutProvider')(function (checkoutProvider) {
                    var shippingAddressData = checkoutData.getShippingAddressFromData();

                    if (shippingAddressData) {
                        checkoutProvider.set(
                            'shippingAddress',
                            $.extend(true, {}, checkoutProvider.get('shippingAddress'), shippingAddressData)
                        );
                    }
                });
                setShippingInformationAction().done(
                    function () {
                        stepNavigator.next();
                    }
                );
            }
        },

        /**
         * @return {Boolean}
         */
        validateShippingInformation: function () {
            var shippingAddress,
                addressData,
                loginFormSelector = 'form[data-role=email-with-possible-login]',
                emailValidationResult = customer.isLoggedIn(),
                field,
                option = _.isObject(this.countryOptions) && this.countryOptions[quote.shippingAddress().countryId],
                messageContainer = registry.get('checkout.errors').messageContainer;

            if (!quote.shippingMethod()) {
                this.errorValidationMessage(
                    $t('The shipping method is missing. Select the shipping method and try again.')
                );

                return false;
            }

            if (!customer.isLoggedIn()) {
                $(loginFormSelector).validation();
                emailValidationResult = Boolean($(loginFormSelector + ' input[name=username]').valid());
            }

            if (this.isFormInline) {
                this.source.set('params.invalid', false);
                this.triggerShippingDataValidateEvent();

                if (!quote.shippingMethod()['method_code']) {
                    this.errorValidationMessage(
                        $t('The shipping method is missing. Select the shipping method and try again.')
                    );
                }

                if (emailValidationResult &&
                    this.source.get('params.invalid') ||
                    !quote.shippingMethod()['method_code'] ||
                    !quote.shippingMethod()['carrier_code']
                ) {
                    this.focusInvalid();

                    return false;
                }

                shippingAddress = quote.shippingAddress();
                addressData = addressConverter.formAddressDataToQuoteAddress(
                    this.source.get('shippingAddress')
                );

                //Copy form data to quote shipping address object
                for (field in addressData) {
                    if (addressData.hasOwnProperty(field) &&  //eslint-disable-line max-depth
                        shippingAddress.hasOwnProperty(field) &&
                        typeof addressData[field] != 'function' &&
                        _.isEqual(shippingAddress[field], addressData[field])
                    ) {
                        shippingAddress[field] = addressData[field];
                    } else if (typeof addressData[field] != 'function' &&
                        !_.isEqual(shippingAddress[field], addressData[field])) {
                        shippingAddress = addressData;
                        break;
                    }
                }

                if (customer.isLoggedIn()) {
                    shippingAddress['save_in_address_book'] = 1;
                }
                selectShippingAddress(shippingAddress);
            } else if (customer.isLoggedIn() &&
                option &&
                option['is_region_required'] &&
                !quote.shippingAddress().region
            ) {
                messageContainer.addErrorMessage({
                    message: $t('Please specify a regionId in shipping address.')
                });

                return false;
            }

            if (!emailValidationResult) {
                $(loginFormSelector + ' input[name=username]').trigger('focus');

                return false;
            }

            return true;
        },

        /**
         * Trigger Shipping data Validate Event.
         */
        triggerShippingDataValidateEvent: function () {
            this.source.trigger('shippingAddress.data.validate');

            if (this.source.get('shippingAddress.custom_attributes')) {
                this.source.trigger('shippingAddress.custom_attributes.data.validate');
            }
        },

        subscribeToCheckboxChanges: function () {
            var self = this;
            // Subscribe to changes in the residential checkbox
            this.getResidential.subscribe(function (isChecked) {
                self.updateCheckboxState('residential', isChecked);
            });
            // Subscribe to changes in the liftgate checkbox
            this.getLiftgate.subscribe(function (isChecked) {
                self.updateCheckboxState('liftgate', isChecked);
            });
            // Subscribe to changes in the delivery checkbox
            this.getDelivery.subscribe(function (isChecked) {
                self.updateCheckboxState('delivery', isChecked);
            });
        },

        /**
         * Update the state of the checkbox in localStorage.
         * @param {String} checkboxName - The name of the checkbox (residential, liftgate, or delivery).
         * @param {Boolean} isChecked - Whether the checkbox is checked or not.
         */
        updateCheckboxState: function (checkboxName, isChecked) {
            localStorage.setItem(checkboxName, isChecked ? 'checked' : '');
        },

        /**
         * Restore the state of the checkboxes from localStorage.
         */
        restoreCheckboxState: function () {
            // Restore the state of the residential checkbox
            var residentialState = localStorage.getItem('residential');
            if (residentialState === 'checked') {
                this.getResidential(true);
            }
            // Restore the state of the liftgate checkbox
            var liftgateState = localStorage.getItem('liftgate');
            if (liftgateState === 'checked') {
                this.getLiftgate(true);
            }
            // Restore the state of the delivery checkbox
            var deliveryState = localStorage.getItem('delivery');
            if (deliveryState === 'checked') {
                this.getDelivery(true);
            }
        },


        getResidentialChecked: function(isChecked){
            var residential = document.getElementById('residential');
            var liftgate = document.getElementById('liftgate');
            var delivery = document.getElementById('delivery');
 
            if (residential.checked) {
                liftgate.checked = true;
                delivery.checked = true;
                liftgate.disabled = true;
                delivery.disabled = true;
            } else {
                liftgate.checked = false;
                delivery.checked = false;
                liftgate.disabled = false;
                delivery.disabled = false;
            }
 
            // var liftgates = liftgate.value;
            // var deliveries = delivery.value;
 
                storage.post(
                    'order/index/customShip',
                    JSON.stringify(
                        {
                            'field' : "check_option",
                            'residential' : (isChecked) ? 1 : 0,
                            'liftgate' : (isChecked) ? 1 : 0,
                            'delivery' : (isChecked) ? 1 : 0
                        }
                    ),
                    true
                ).done(
                    function () {
                        // alert('Success');
                        // fullScreenLoader.stopLoader();
                    }
                ).fail(
                    function () {
                        // fullScreenLoader.stopLoader();
                    }
                );
            
 
        },
 
        getLiftgateChecked: function(isChecked) {
            var liftgate = document.getElementById('liftgate');
          
            // Send the Liftgate option value to the backend based on whether the checkbox is checked or unchecked
            storage.post(
                'order/index/customShip',
                JSON.stringify({
                    'field': "check_option",
                    'liftgate': isChecked ? 1 : 0, // If isChecked is true, set liftgate to 1; otherwise, set it to 0
                }),
                true
            ).done(function () {
                // Handle success if needed
                // alert('Success');
                // fullScreenLoader.stopLoader();
            }).fail(function () {
                // Handle failure if needed
                // fullScreenLoader.stopLoader();
            });
        },
        
        
 
        getDeliveryChecked: function(isChecked) {
            var delivery = document.getElementById('delivery');
          

            storage.post(
                'order/index/customShip',
                JSON.stringify({
                    'field': "check_option",
                    'delivery': isChecked ? 1 : 0,
                }),
                true
            ).done(function () {
                // Handle success if needed
                // alert('Success');
                // fullScreenLoader.stopLoader();
            }).fail(function () {
                // Handle failure if needed
                // fullScreenLoader.stopLoader();
            });
        },
        
    })
       
    });
   
    

