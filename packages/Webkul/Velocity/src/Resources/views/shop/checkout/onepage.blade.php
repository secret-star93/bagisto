@extends('shop::layouts.master')

@section('page_title')
    {{ __('shop::app.checkout.onepage.title') }}
@stop

@section('content-wrapper')
    <checkout></checkout>
@endsection

@push('scripts')
    @include('shop::checkout.cart.coupon')

    <script type="text/x-template" id="checkout-template">
        <div class="container">
            <div id="checkout" class="checkout-process row offset-lg-1 col-lg-11 col-md-12">

                <h1 class="col-12">{{ __('velocity::app.checkout.checkout') }}</h1>

                <div class="col-lg-7 col-md-12">
                    <div class="step-content information" id="address-section">
                        @include('shop::checkout.onepage.customer-info')
                    </div>

                    <div
                        class="step-content shipping"
                        id="shipping-section"
                        v-if="showShippingSection">

                        <shipping-section @onShippingMethodSelected="shippingMethodSelected($event)">
                        </shipping-section>
                    </div>

                    <div
                        class="step-content payment"
                        v-if="showPaymentSection"
                        id="payment-section">

                        <payment-section @onPaymentMethodSelected="paymentMethodSelected($event)">
                        </payment-section>

                        <coupon-component
                            @onApplyCoupon="getOrderSummary"
                            @onRemoveCoupon="getOrderSummary">
                        </coupon-component>
                    </div>

                    <div
                        class="step-content review"
                        v-if="showSummarySection"
                        id="summary-section">

                        <review-section :key="reviewComponentKey">
                            <div slot="summary-section">
                                <summary-section
                                    discount="1"
                                    :key="summeryComponentKey"
                                    @onApplyCoupon="getOrderSummary"
                                    @onRemoveCoupon="getOrderSummary"
                                ></summary-section>
                            </div>

                            <div slot="place-order-btn">
                                <div class="mb20">
                                    <button
                                        type="button"
                                        class="theme-btn"
                                        @click="placeOrder()"
                                        :disabled="!isPlaceOrderEnabled"
                                        id="checkout-place-order-button">
                                        {{ __('shop::app.checkout.onepage.place-order') }}
                                    </button>
                                </div>
                            </div>
                        </review-section>
                    </div>
                </div>

                <div class="col-lg-4 col-md-12 offset-lg-1 order-summary-container top pt0">
                    <summary-section :key="summeryComponentKey"></summary-section>
                </div>
            </div>
        </div>
    </script>

    <script type="text/javascript">
        (() => {
            var reviewHtml = '';
            var paymentHtml = '';
            var summaryHtml = '';
            var shippingHtml = '';
            var paymentMethods = '';
            var customerAddress = '';
            var shippingMethods = '';

            var shippingTemplateRenderFns = [];
            var paymentTemplateRenderFns = [];
            var reviewTemplateRenderFns = [];
            var summaryTemplateRenderFns = [];

            @auth('customer')
                @if(auth('customer')->user()->addresses)
                    customerAddress = @json(auth('customer')->user()->addresses);
                    customerAddress.email = "{{ auth('customer')->user()->email }}";
                    customerAddress.first_name = "{{ auth('customer')->user()->first_name }}";
                    customerAddress.last_name = "{{ auth('customer')->user()->last_name }}";
                @endif
            @endauth

            Vue.component('checkout', {
                template: '#checkout-template',
                inject: ['$validator'],

                data: function() {
                    return {
                        allAddress: {},
                        current_step: 1,
                        completed_step: 0,
                        is_customer_exist: 0,
                        disable_button: false,
                        reviewComponentKey: 0,
                        summeryComponentKey: 0,
                        showPaymentSection: false,
                        showSummarySection: false,
                        isPlaceOrderEnabled: false,
                        new_billing_address: false,
                        showShippingSection: false,
                        new_shipping_address: false,
                        selected_payment_method: '',
                        selected_shipping_method: '',
                        country: @json(core()->countries()),
                        countryStates: @json(core()->groupedStatesByCountries()),

                        step_numbers: {
                            'information': 1,
                            'shipping': 2,
                            'payment': 3,
                            'review': 4
                        },

                        address: {
                            billing: {
                                address1: [''],

                                use_for_shipping: true,
                            },

                            shipping: {
                                address1: ['']
                            },
                        },
                    }
                },

                created: function() {
                    this.getOrderSummary();

                    if(! customerAddress) {
                        this.new_shipping_address = true;
                        this.new_billing_address = true;
                    } else {
                        this.address.billing.first_name = this.address.shipping.first_name = customerAddress.first_name;
                        this.address.billing.last_name = this.address.shipping.last_name = customerAddress.last_name;
                        this.address.billing.email = this.address.shipping.email = customerAddress.email;

                        if (customerAddress.length < 1) {
                            this.new_shipping_address = true;
                            this.new_billing_address = true;
                        } else {
                            this.allAddress = customerAddress;

                            for (var country in this.country) {
                                for (var code in this.allAddress) {
                                    if (this.allAddress[code].country) {
                                        if (this.allAddress[code].country == this.country[country].code) {
                                            this.allAddress[code]['country'] = this.country[country].name;
                                        }
                                    }
                                }
                            }
                        }
                    }
                },

                methods: {
                    navigateToStep: function(step) {
                        if (step <= this.completed_step) {
                            this.current_step = step
                            this.completed_step = step - 1;
                        }
                    },

                    haveStates: function(addressType) {
                        if (this.countryStates[this.address[addressType].country] && this.countryStates[this.address[addressType].country].length)
                            return true;

                        return false;
                    },

                    validateForm: function(scope) {
                        var isManualValidationFail = false;

                        if (scope == 'address-form') {
                            let form = $(document).find('form[data-vv-scope=address-form]');

                            // validate that if all the field contains some value
                            if (form) {
                                form.find(':input').each((index, element) => {
                                    let value = $(element).val();

                                    if (value == "" && element.id != 'billing[company_name]' && element.id != 'shipping[company_name]') {
                                        isManualValidationFail = true;
                                    }
                                });
                            }

                            // validate that if customer wants to use different shipping address
                            if (this.address.billing.use_for_shipping) {
                                if (this.address.shipping.address_id) {
                                    isManualValidationFail = true;
                                }
                            }
                        }

                        if (!isManualValidationFail) {
                            this.$validator.validateAll(scope)
                            .then(result => {
                                if (result) {
                                    document.body.style.cursor = 'wait';

                                    if (scope == 'address-form') {
                                        this.saveAddress();
                                        document.body.style.cursor = 'default';
                                    } else if (scope == 'shipping-form') {
                                        document.body.style.cursor = 'wait';
                                        this.saveShipping();
                                    } else if (scope == 'payment-form') {
                                        document.body.style.cursor = 'wait';
                                        this.savePayment();
                                    }

                                    this.isPlaceOrderEnabled = true;
                                } else {
                                    this.isPlaceOrderEnabled = false;
                                }
                            });
                        } else {
                            this.isPlaceOrderEnabled = false;
                        }
                    },

                    isCustomerExist: function() {
                        this.$validator.attach('email', 'required|email');

                        var this_this = this;

                        this.$validator.validate('email', this.address.billing.email)
                            .then(function(isValid) {
                                if (! isValid)
                                    return;

                                this_this.$http.post("{{ route('customer.checkout.exist') }}", {email: this_this.address.billing.email})
                                    .then(function(response) {
                                        this_this.is_customer_exist = response.data ? 1 : 0;

                                        if (response.data)
                                            document.body.style.cursor = 'default';
                                    })
                                    .catch(function (error) {})

                            })
                    },

                    loginCustomer: function() {
                        var this_this = this;

                        this_this.$http.post("{{ route('customer.checkout.login') }}", {
                                email: this_this.address.billing.email,
                                password: this_this.address.billing.password
                            })
                            .then(function(response) {
                                if (response.data.success) {
                                    window.location.href = "{{ route('shop.checkout.onepage.index') }}";
                                } else {
                                    window.flashMessages = [{'type': 'alert-error', 'message': response.data.error }];

                                    this_this.$root.addFlashMessages()
                                }
                            })
                            .catch(function (error) {})
                    },

                    getOrderSummary () {
                        this.$http.get("{{ route('shop.checkout.summary') }}")
                            .then(response => {
                                summaryHtml = Vue.compile(response.data.html)

                                this.summeryComponentKey++;
                                this.reviewComponentKey++;
                            })
                            .catch(function (error) {})
                    },

                    saveAddress: function() {
                        this.disable_button = true;

                        this.$http.post("{{ route('shop.checkout.save-address') }}", this.address)
                            .then(response => {
                                this.disable_button = false;

                                if (this.step_numbers[response.data.jump_to_section] == 2) {
                                    this.showShippingSection = true;
                                    shippingHtml = Vue.compile(response.data.html);
                                } else {
                                    paymentHtml = Vue.compile(response.data.html)
                                }

                                this.completed_step = this.step_numbers[response.data.jump_to_section] + 1;
                                this.current_step = this.step_numbers[response.data.jump_to_section];

                                if (response.data.jump_to_section == "payment") {
                                    this.showPaymentSection = true;
                                    paymentMethods  = response.data.paymentMethods;
                                }

                                shippingMethods = response.data.shippingMethods;

                                this.validateForm('shipping-form');
                                this.getOrderSummary();
                            })
                            .catch(error => {
                                this.disable_button = false;

                                this.handleErrorResponse(error.response, 'address-form')
                            })
                    },

                    saveShipping: function() {
                        this.disable_button = true;

                        this.$http.post("{{ route('shop.checkout.save-shipping') }}", {'shipping_method': this.selected_shipping_method})
                            .then(response => {
                                this.disable_button = false;

                                this.showPaymentSection = true;

                                paymentHtml = Vue.compile(response.data.html)

                                this.completed_step = this.step_numbers[response.data.jump_to_section] + 1;

                                this.current_step = this.step_numbers[response.data.jump_to_section];

                                paymentMethods = response.data.paymentMethods;

                                if (this.selected_payment_method) {
                                    this.savePayment();
                                }

                                this.validateForm('payment-form');
                                this.getOrderSummary();
                            })
                            .catch(error => {
                                this.disable_button = false;

                                this.handleErrorResponse(error.response, 'shipping-form')
                            })
                    },

                    savePayment: function() {
                        this.disable_button = true;

                        this.$http.post("{{ route('shop.checkout.save-payment') }}", {'payment': this.selected_payment_method})
                        .then(response => {
                            this.disable_button = false;

                            this.showSummarySection = true;

                            reviewHtml = Vue.compile(response.data.html)
                            this.completed_step = this.step_numbers[response.data.jump_to_section] + 1;
                            this.current_step = this.step_numbers[response.data.jump_to_section];

                            document.body.style.cursor = 'auto';

                            this.getOrderSummary();
                        })
                        .catch(error => {
                            this.disable_button = false;
                            this.handleErrorResponse(error.response, 'payment-form')
                        });
                    },

                    placeOrder: function() {
                        if (this.isPlaceOrderEnabled) {
                            this.disable_button = false;
                            this.isPlaceOrderEnabled = false;

                            this.$http.post("{{ route('shop.checkout.save-order') }}", {'_token': "{{ csrf_token() }}"})
                            .then(function(response) {
                                if (response.data.success) {
                                    if (response.data.redirect_url) {
                                        window.location.href = response.data.redirect_url;
                                    } else {
                                        window.location.href = "{{ route('shop.checkout.success') }}";
                                    }
                                }
                            })
                            .catch(error => {
                                this.disable_button = true;

                                window.flashMessages = [{'type': 'alert-error', 'message': "{{ __('shop::app.common.error') }}" }];

                                this.$root.addFlashMessages();
                            })
                        } else {
                            this.disable_button = true;
                        }
                    },

                    handleErrorResponse: function(response, scope) {
                        if (response.status == 422) {
                            serverErrors = response.data.errors;
                            this.$root.addServerErrors(scope)
                        } else if (response.status == 403) {
                            if (response.data.redirect_url) {
                                window.location.href = response.data.redirect_url;
                            }
                        }
                    },

                    shippingMethodSelected: function(shippingMethod) {
                        this.selected_shipping_method = shippingMethod;
                    },

                    paymentMethodSelected: function(paymentMethod) {
                        this.selected_payment_method = paymentMethod;
                    },

                    newBillingAddress: function() {
                        this.new_billing_address = true;
                        this.isPlaceOrderEnabled = false;
                    },

                    newShippingAddress: function() {
                        this.new_shipping_address = true;
                        this.isPlaceOrderEnabled = false;
                    },

                    backToSavedBillingAddress: function() {
                        this.isPlaceOrderEnabled = true;
                        this.new_billing_address = false;
                    },

                    backToSavedShippingAddress: function() {
                        this.isPlaceOrderEnabled = true;
                        this.new_shipping_address = false;
                    },
                }
            });

            Vue.component('shipping-section', {
                inject: ['$validator'],

                data: function() {
                    return {
                        templateRender: null,

                        selected_shipping_method: '',

                        first_iteration : true,
                    }
                },

                staticRenderFns: shippingTemplateRenderFns,

                mounted: function() {
                    for (method in shippingMethods) {
                        if (this.first_iteration) {

                            for (rate in shippingMethods[method]['rates']) {
                                this.selected_shipping_method = shippingMethods[method]['rates'][rate]['method'];

                                this.first_iteration = false;

                                this.methodSelected();
                            }
                        }
                    }

                    this.templateRender = shippingHtml.render;
                    for (var i in shippingHtml.staticRenderFns) {
                        shippingTemplateRenderFns.push(shippingHtml.staticRenderFns[i]);
                    }

                    eventBus.$emit('after-checkout-shipping-section-added');
                },

                render: function(h) {
                    return h('div', [
                        (this.templateRender ?
                            this.templateRender() :
                            '')
                        ]);
                },

                methods: {
                    methodSelected: function() {
                        this.$parent.validateForm('shipping-form');

                        this.$emit('onShippingMethodSelected', this.selected_shipping_method)

                        eventBus.$emit('after-shipping-method-selected');
                    }
                }
            })

            Vue.component('payment-section', {
                inject: ['$validator'],

                data: function() {
                    return {
                        templateRender: null,

                        payment: {
                            method: ""
                        },

                        first_iteration : true,
                    }
                },

                staticRenderFns: paymentTemplateRenderFns,

                mounted: function() {
                    for (method in paymentMethods) {
                        if (this.first_iteration) {
                            this.payment.method = paymentMethods[method]['method'];
                            this.first_iteration = false;
                            this.methodSelected();
                        }
                    }

                    this.templateRender = paymentHtml.render;
                    for (var i in paymentHtml.staticRenderFns) {
                        paymentTemplateRenderFns.push(paymentHtml.staticRenderFns[i]);
                    }

                    eventBus.$emit('after-checkout-payment-section-added');
                },

                render: function(h) {
                    return h('div', [
                        (this.templateRender ?
                            this.templateRender() :
                            '')
                        ]);
                },

                methods: {
                    methodSelected: function() {
                        this.$parent.validateForm('payment-form');

                        this.$emit('onPaymentMethodSelected', this.payment)

                        eventBus.$emit('after-payment-method-selected');
                    }
                }
            })

            Vue.component('review-section', {
                data: function () {
                    return {
                        error_message: '',
                        templateRender: null,
                    }
                },

                staticRenderFns: reviewTemplateRenderFns,

                render: function (h) {
                    return h(
                        'div', [
                            this.templateRender ? this.templateRender() : ''
                        ]
                    );
                },

                mounted: function() {
                    this.templateRender = reviewHtml.render;

                    for (var i in reviewHtml.staticRenderFns) {
                        reviewTemplateRenderFns[i] = reviewHtml.staticRenderFns[i];
                    }

                    this.$forceUpdate();
                }
            });

            Vue.component('summary-section', {
                inject: ['$validator'],

                staticRenderFns: summaryTemplateRenderFns,

                props: {
                    discount: {
                        default: 0,
                        type: [String, Number],
                    }
                },

                data: function() {
                    return {
                        changeCount: 0,
                        coupon_code: null,
                        error_message: null,
                        templateRender: null,
                        couponChanged: false,
                    }
                },

                mounted: function() {
                    this.templateRender = summaryHtml.render;

                    for (var i in summaryHtml.staticRenderFns) {
                        summaryTemplateRenderFns[i] = summaryHtml.staticRenderFns[i];
                    }

                    this.$forceUpdate();
                },

                render: function(h) {
                    return h('div', [
                        (this.templateRender ?
                            this.templateRender() :
                            '')
                        ]);
                },

                methods: {
                    onSubmit: function() {
                        var this_this = this;
                        const emptyCouponErrorText = "Please enter a coupon code";
                    },

                    changeCoupon: function() {
                        if (this.couponChanged == true && this.changeCount == 0) {
                            this.changeCount++;

                            this.error_message = null;

                            this.couponChanged = false;
                        } else {
                            this.changeCount = 0;
                        }
                    },

                    removeCoupon: function () {
                        var this_this = this;
                    }
                }
            });
        })()
    </script>

@endpush