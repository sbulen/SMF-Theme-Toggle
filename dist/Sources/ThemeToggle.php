<?php
/**
 *	Main logic for the Theme Toggle mod for SMF.
 *
 *	Copyright 2022 Shawn Bulen
 *
 *	The Theme Toggle mod is free software: you can redistribute it and/or modify
 *	it under the terms of the GNU General Public License as published by
 *	the Free Software Foundation, either version 3 of the License, or
 *	(at your option) any later version.
 *	
 *	This software is distributed in the hope that it will be useful,
 *	but WITHOUT ANY WARRANTY; without even the implied warranty of
 *	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *	GNU General Public License for more details.
 *
 *	You should have received a copy of the GNU General Public License
 *	along with this software.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

// If we are outside SMF throw an error.
if (!defined('SMF')) {
    die('Hacking attempt...');
}

/**
 * Theme Toggle - action.
 *
 * Primary action called from the admin menu for selecting the secondary theme.
 *
 * Action: modsettings
 * Subaction: themetog
 *
 * @return null
 *
 */
function theme_toggle_select()
{
		global $context, $txt, $scripturl, $modSettings, $smcFunc;

		loadLanguage('ThemeToggle');

		// Setup some page settings
		$context['page_title'] = $txt['themetog_settings'];
		$context['post_url'] = $scripturl . '?action=admin;area=modsettings;save;sa=themetog';
		$context[$context['admin_menu_name']]['tab_data']['description'] = $txt['themetog_settings'];
		$context['permissions_excluded'] = [-1];

		// Setup the secondary theme value if none has been specified yet.
		// There needs to be at least 2 valid themes.
		// Terminology is weird - the guest theme is the default theme.
		$all_themes = explode(',', $modSettings['knownThemes']);
		$candidates = array_diff($all_themes, array($modSettings['theme_guests']), array(0));
		if (!isset($modSettings['themetog_second_theme']))
		{
			if (count($candidates) > 0)
			{
				$addSettings = array();
				$addSettings['themetog_second_theme'] = min($candidates);
				updateSettings($addSettings);
			}
		}

		// Get info on the candidate themes
		$theme_array = array();
		if (count($candidates) > 0)
		{
			$request = $smcFunc['db_query']('', '
				SELECT id_theme, value
					FROM {db_prefix}themes
					WHERE id_theme IN ({array_int:candidates})
					AND variable = {string:name}',
				array(
					'candidates' => $candidates,
					'name' => 'name'
				)
			);
			while ($row = $smcFunc['db_fetch_assoc']($request))
				$theme_array[$row['id_theme']] = $row['value'];
			$smcFunc['db_free_result']($request);
		}

		// Setup the data entry form
		$context['settings_title'] = $txt['themetog_settings'];
		$config_vars = array(
			array('select', 
				'themetog_second_theme', 
				$theme_array
			),
		);

		if (isset($_GET['save'])) {
			checkSession();
			saveDBSettings($config_vars);
			redirectexit('action=admin;area=modsettings;sa=themetog');
		}

		prepareDBSettingContext($config_vars);
}

/**
 * Theme Toggle - Helper function that actually swaps the themes and removes traces of the old one from cache.
 *
 * @return null
 *
 */
function theme_toggle()
{
    global $user_info, $modSettings, $smcFunc, $cache_enable;

	// Not for guests
	if (!empty($user_info['is_guest']))
		return;

	// If no settings, bail...
	if (!isset($modSettings['themetog_second_theme']) || !isset($modSettings['theme_guests']) || !isset($user_info['theme']) || !isset($user_info['id']))
		return;

	// Pick the other theme...
	if ($user_info['theme'] == $modSettings['themetog_second_theme'])
		$user_info['theme'] = $modSettings['theme_guests'];
	else
		$user_info['theme'] = $modSettings['themetog_second_theme'];

	$smcFunc['db_query']('', '
		UPDATE {db_prefix}members
			SET id_theme = {int:id_theme}
			WHERE id_member = {int:id_member}',
		array(
			'id_member' => $user_info['id'],
			'id_theme' => $user_info['theme']
		)
	);

	// The user theme id is put in the 'user' cache & in the 'member' cache.
	if (!empty($cache_enable))
	{
		cache_put_data('user_settings-' . $user_info['id'], null);
		cache_put_data('member_data-profile-' . $user_info['id'], null);
	}
}

/**
 * Theme Toggle - action.  Primary action called for the themetog - http version.
 *
 * Action: themetog
 *
 * @return null
 *
 */
function theme_toggle_http()
{
	global $boardurl;

	if (!empty($_SESSION['old_url']))
		redirectexit($_SESSION['old_url']);
	else
		redirectexit($boardurl);
}

/**
 * Theme Toggle - subaction.  Primary action called for the themetog - js version.
 *
 * Action: xmlhttp
 * Subaction: themetog
 *
 * @return null
 *
 */
function theme_toggle_js()
{
	global $context;

	// Do the deed...
	theme_toggle();

	// In case they were diddlying about with the URL...
	// Still do it, but invoke it via http.  This prevents blank screens & browser errors.
	if (empty($_SERVER['HTTP_X_SMF_AJAX']))
	    theme_toggle_http();

	$context['sub_template'] = 'generic_xml';
	$context['xml_data'] = array('status' => 'OK');
}
