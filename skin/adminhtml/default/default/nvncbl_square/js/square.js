var squareTokens = {};

var initSquare = function(apiKey)
{
	// Why would it not be loaded?
	if (typeof Square == "undefined")
	{
		var resource = document.createElement('script');
		resource.src = "https://js.square.com/v2/";
		var script = document.getElementsByTagName('script')[0];
		script.parentNode.insertBefore(resource, script);

		setTimeout(function(){
			Square.setPublishableKey(apiKey);
		}, 500);
	}
	else
		Square.setPublishableKey(apiKey);
};

var createSquareToken = function(done)
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
