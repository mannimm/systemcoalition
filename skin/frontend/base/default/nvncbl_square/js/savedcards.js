var Nvncbl_SaveNewCard = function()
{
	var saveButton = document.getElementById('nvncbl-savecard-button');
	var wait = document.getElementById('nvncbl-savecard-please-wait');
	saveButton.style.display = "none";
	wait.style.display = "block";

	if (typeof Square != 'undefined')
	{
		createSquareToken(function(err)
		{
			if (err)
			{
				alert(err);
				saveButton.style.display = "block";
				wait.style.display = "none";
			}
			else
				document.getElementById("new-card").submit();
		});
		return false;
	}

	return true;
}
