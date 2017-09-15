var dueTokens = {};

/**
 * Init Due Scripts
 * @param env
 * @param app_id
 * @param rail_type
 */
function initDue(env, app_id, rail_type) {
    // Load Due dynamically
    if (typeof Due === 'undefined') {
        var resource = document.createElement('script');
        resource.src = 'https://static.due.com/v1.1/due.min.js';
        var script = document.getElementsByTagName('script')[0];
        script.parentNode.insertBefore(resource, script);

        setTimeout(function() {
            Due.load.init(env, rail_type);
            Due.load.setAppId(app_id);
        }, 500);
    } else {
        Due.load.init(env, rail_type);
        Due.load.setAppId(app_id);
    }
}

/**
 * Create Due Token
 * @param callback
 * @returns {*}
 */
function createDueToken(callback) {
    var billingFirstName = billingForm.form.select('[name="billing[firstname]"]').first();
    var billingLastName = billingForm.form.select('[name="billing[lastname]"]').first();
    var billingPostcode = billingForm.form.select('[name="billing[postcode]"]').first();
    var mail = $$('#payment_form_due_cc [name="payment[cc_mail]"]').first();
    var cardType = $$('#payment_form_due_cc [name="payment[cc_type]"]').first();
    var cardNumber = $$('#payment_form_due_cc [name="payment[cc_number]"]').first();
    var cardExpMonth = $$('#payment_form_due_cc [name="payment[cc_exp_month]"]').first();
    var cardExpYear = $$('#payment_form_due_cc [name="payment[cc_exp_year]"]').first();
    var cardCvc = $$('#payment_form_due_cc [name="payment[cc_cid]"]').first();

    // Validate
    var isValid = billingFirstName && billingFirstName.value && billingLastName && billingLastName.value && billingPostcode && billingPostcode.value && mail && mail.value && cardType && cardType.value && cardNumber && cardNumber.value && cardExpMonth && cardExpMonth.value && cardExpYear && cardExpYear.value && cardCvc && cardCvc.value;
    if (!isValid) {
        return callback('Invalid card details', false);
    }

    // Prepare details
    var cardDetails = {
        "name"       : billingFirstName.value+ ' ' + billingLastName.value,
        "email"      : mail.value,
        "card_number": cardNumber.value,
        "cvv"        : cardCvc.value,
        "exp_month"  : cardExpMonth.value < 10 ? '0' + cardExpMonth.value : cardExpMonth.value,
        "exp_year"   : cardExpYear.value,
        "address"    : {
            "postal_code": billingPostcode.value
        }
    };
    console.log(cardDetails);

    var cardKey = JSON.stringify(cardDetails);
    if (dueTokens[cardKey]) {
        setDueToken(dueTokens[cardKey]);
        return callback(null, dueTokens[cardKey]);
    }

    try {
        checkout.setLoadWaiting('payment');
    } catch (e) {
        //
    }

    Due.payments.card.create(cardDetails, function (data) {
        console.log(data);
        try {
            checkout.setLoadWaiting(false);
        } catch (e) {
            //
        }

        if (!data || !data.hasOwnProperty('card_id')) {
            return callback('Unable to tokenize card', false);
        }

        var card_last4 = cardNumber.value.substr(cardNumber.value.length - 4);
        var token = data.card_id + ':' + data.card_hash + ':' + data.risk_token + ':' + cardType.value + ':' + card_last4;
        dueTokens[cardKey] = token;
        setDueToken(token);
        return callback(null, dueTokens[cardKey]);
    });
}

/**
 * Set Due Token
 * @param token
 */
function setDueToken(token) {
    var input;
    var inputs = document.getElementsByClassName('due-token');
    if (inputs && inputs[0]) {
        input = inputs[0];
    } else {
        input = document.createElement('input');
    }

    input.setAttribute('type', 'hidden');
    input.setAttribute('name', 'payment[cc_due_token]');
    input.setAttribute('class', 'token');
    input.setAttribute('value', token);
    input.disabled = false;
    var form = document.getElementById('co-payment-form');
    if (!form && typeof payment !== 'undefined') {
        form = document.getElementById(payment.formId);
    }

    if (!form) {
        console.log('setDueToken: cannot find payment form');
    }
    form.appendChild(input);
    disableInputs(true);
}

/**
 * Disable Inputs
 * @param disabled
 */
function disableInputs(disabled) {
    var elements = document.getElementsByClassName('due-input');
    for (var i = 0; i < elements.length; i++)  {
        // Don't disable the save cards checkbox
        if (elements[i].type === 'checkbox') {
            continue;
        }

        // Don't disable the Due token
        if (elements[i].type === 'hidden' && disabled) {
            continue;
        }

        elements[i].disabled = disabled;
    }
}

/**
 * Enable Inputs
 */
function enableInputs() {
    disableInputs(false);
}
