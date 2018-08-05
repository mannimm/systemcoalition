var squareTokens = {};

var initSquare = function(apiKey)
{

	console.log( 'initting square' );

	var applicationId = 'sq0idp-h-HXwQh2bzA1EE1mL_kXfQ'; // <-- Add your application's ID here

	// You can delete this 'if' statement. It's here to notify you that you need
	// to provide your application ID.
	if (applicationId == '') {
		alert('You need to provide a value for the applicationId variable.');
	}

	// Initializes the payment form. See the documentation for descriptions of
	// each of these parameters.
	paymentForm = new SqPaymentForm({
		applicationId: applicationId,
		inputClass: 'sq-input',
		inputStyles: [
		{
			fontSize: '15px'
		}
		],
		cardNumber: {
			elementId: 'sq-card-number',
			placeholder: '**** **** **** ****'
		},
		cvv: {
			elementId: 'sq-cvv',
			placeholder: 'CVV'
		},
		expirationDate: {
			elementId: 'sq-expiration-date',
			placeholder: 'MM/YY'
		},
		postalCode: {
			elementId: 'sq-postal-code'
		},
		callbacks: {

			// Called when the SqPaymentForm completes a request to generate a card
			// nonce, even if the request failed because of an error.
			cardNonceResponseReceived: function(errors, nonce, cardData) {
				if (errors) {
					console.log("Encountered errors:");

					// This logs all errors encountered during nonce generation to the
					// Javascript console.
					errors.forEach(function(error) {
						console.log('  ' + error.message);
					});

					// No errors occurred. Extract the card nonce.
				} else {

					// Delete this line and uncomment the lines below when you're ready
					// to start submitting nonces to your server.
					console.log('Nonce received: ' + nonce);


					/*
					These lines assign the generated card nonce to a hidden input
					field, then submit that field to your server.
					Uncomment them when you're ready to test out submitting nonces.

					You'll also need to set the action attribute of the form element
					at the bottom of this sample, to correspond to the URL you want to
					submit the nonce to.
					*/
					 document.getElementById('card-nonce').value = nonce;
					 //document.getElementById('nonce-form').submit();

				}
			},

			unsupportedBrowserDetected: function() {
				// Fill in this callback to alert buyers when their browser is not supported.
			},

			// Fill in these cases to respond to various events that can occur while a
			// buyer is using the payment form.
			inputEventReceived: function(inputEvent) {
				switch (inputEvent.eventType) {
					case 'focusClassAdded':
						// Handle as desired
						break;
					case 'focusClassRemoved':
						// Handle as desired
						break;
					case 'errorClassAdded':
						// Handle as desired
						break;
					case 'errorClassRemoved':
						// Handle as desired
						break;
					case 'cardBrandChanged':
						// Handle as desired
						break;
					case 'postalCodeChanged':
						// Handle as desired
						break;
				}
			},

			paymentFormLoaded: function() {
				// Fill in this callback to perform actions after the payment form is
				// done loading (such as setting the postal code field programmatically).
				// paymentForm.setPostalCode('94103');
			}
		}
	});
	console.log( paymentForm );

	// This function is called when a buyer clicks the Submit button on the webpage
	// to charge their card.
	function requestCardNonce(event) {

		// This prevents the Submit button from submitting its associated form.
		// Instead, clicking the Submit button should tell the SqPaymentForm to generate
		// a card nonce, which the next line does.
		event.preventDefault();

		paymentForm.requestCardNonce();
	}

};

/*var createSquareToken = function(done)
{
	// First check if the "Use new card" radio is selected, return if not
	var newCardRadio = document.getElementById('new_card');
	if (newCardRadio && !newCardRadio.checked) return done();

	// Validate the card
	var cardName = document.getElementById('nvncbl_square_cc_owner');
	var cardNumber = document.getElementById('nvncbl_square_cc_number');
	var cardCvc = document.getElementById('nvncbl_square_cc_cid');
	var cardExpMonth = document.getElementById('nvncbl_square_expiration');
	var cardExpYear = document.getElementById('nvncbl_square_expiration_yr');

	var isValid = cardName && cardName.value && cardNumber && cardNumber.value && cardCvc && cardCvc.value && cardExpMonth && cardExpMonth.value && cardExpYear && cardExpYear.value;

	if (!isValid) return done('Invalid card details');

	var cardDetails = {
		name: cardName.value,
		number: cardNumber.value,
		cvc: cardCvc.value,
		exp_month: cardExpMonth.value,
		exp_year: cardExpYear.value
	};

	// AVS
	if (typeof avs_address_line1 != 'undefined')
	{
		cardDetails.address_line1 = avs_address_line1;
		cardDetails.address_zip = avs_address_zip;
	}
	else if (avs_enabled)
	{
		return done('You must first enter your billing address.')
	}

	var cardKey = JSON.stringify(cardDetails);

	if (squareTokens[cardKey])
	{
		setSquareToken(squareTokens[cardKey]);
		return done();
	}

	try { checkout.setLoadWaiting('payment'); } catch (e) {}
	Square.card.createToken(cardDetails, function (status, response)
	{
		try { checkout.setLoadWaiting(false); } catch (e) {}
		if (response.error)
		{
			if (typeof IWD != "undefined")
			{
				IWD.OPC.Checkout.hideLoader();
				IWD.OPC.Checkout.xhr = null;
				IWD.OPC.Checkout.unlockPlaceOrder();
			}
			alert(response.error.message);
		}
		else
		{
			var token = response.id + ':' + response.card.brand + ':' + response.card.last4;
			squareTokens[cardKey] = token;
			setSquareToken(token);
			done();
		}
	});
};

function setSquareToken(token)
{
	try
	{
		var input, inputs = document.getElementsByClassName('nvncbl-squarejs-token');
		if (inputs && inputs[0]) input = inputs[0];
		else input = document.createElement("input");
		input.setAttribute("type", "hidden");
		input.setAttribute("name", "payment[cc_squarejs_token]");
		input.setAttribute("class", 'nvncbl-squarejs-token');
		input.setAttribute("value", token);
		var form = document.getElementById('co-payment-form');
		if (!form) form = document.getElementById('order-billing_method_form');
		if (!form && typeof payment != 'undefined') form = document.getElementById(payment.formId);
		if (!form)
		{
			form = document.getElementById('new-card');
			input.setAttribute("name", "newcard[cc_squarejs_token]");
		}
		form.appendChild(input);
		disableInputs(true);
	} catch (e) {}
}

function disableInputs(disabled)
{
	var elements = document.getElementsByClassName('square-input');
	for (var i = 0; i < elements.length; i++)
	{
		// Don't disable the save cards checkbox
		if (elements[i].type != "checkbox" && elements[i].type != "hidden")
			elements[i].disabled = disabled;
	}
}

var enableInputs = function()
{
	disableInputs(false);
};

// Multi-shipping form support for Square.js
var multiShippingForm = null, multiShippingFormSubmitButton = null;

function submitMultiShippingForm(e)
{
	if (payment.currentMethod != 'nvncbl_square')
		return true;

	if (e.preventDefault) e.preventDefault();

	if (!multiShippingFormSubmitButton) multiShippingFormSubmitButton = document.getElementById('payment-continue');
	if (multiShippingFormSubmitButton) multiShippingFormSubmitButton.disabled = true;

	createSquareToken(function(err)
	{
		if (err)
			alert(err);
		else
		{
			if (multiShippingFormSubmitButton) multiShippingFormSubmitButton.disabled = false;
			multiShippingForm.submit();
		}
	});

	return false;
}

// Multi-shipping form
var initMultiShippingForm = function()
{
	if (typeof payment == 'undefined' || payment.formId != 'multishipping-billing-form') return;

	multiShippingForm = document.getElementById(payment.formId);
	if (!multiShippingForm) return;

	if (multiShippingForm.attachEvent)
		multiShippingForm.attachEvent("submit", submitMultiShippingForm);
	else
		multiShippingForm.addEventListener("submit", submitMultiShippingForm);
};
*/