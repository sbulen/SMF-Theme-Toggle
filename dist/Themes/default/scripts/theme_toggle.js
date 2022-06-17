// Used by the onclick in the menu... Calls the xml subaction to toggle the theme
theme_toggle = function ()
{
	ajax_indicator(true);

	sendXMLDocument(smf_prepareScriptUrl(smf_scripturl) + 'action=xmlhttp;sa=themetog;xml', '', theme_toggle_callback);

	// Do not execute the href...
	return false;
}

// Reload the page upon acknowledgement
theme_toggle_callback = function (XMLDOC)
{
	window.location.reload();

	ajax_indicator(false);
}
