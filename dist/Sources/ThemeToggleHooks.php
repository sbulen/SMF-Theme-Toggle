<?php
/**
 *	Logic for the Theme Toggle mod hooks.
 *
 *	Copyright 2022-2024 Shawn Bulen
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
 *
 * Hook function - Add the theme toggle button to the main menu.
 *
 * Need to be careful what you put in here, because it's cached...
 *
 * Hook: integrate_menu_buttons
 *
 * @param array $buttons
 * @return null
 *
 */
function theme_toggle_buttons(&$buttons)
{
	global $txt, $user_info, $scripturl, $modSettings;

	// First off, ignore any alert popup actions.
	if (isset($_REQUEST['area']) && ($_REQUEST['area'] == 'alerts_popup'))
		return;

	// If using the profile menu, bail...
	if (!empty($modSettings['themetog_profile_menu']))
		return;

	// If no setting, bail...
	if (empty($modSettings['themetog_second_theme']))
		return;

	// Not for guests
	if (!empty($user_info['is_guest']))
		return;

	// Terminology is weird - the guest theme is the default theme
	// If they were somehow set the same, uh, nothing to do...
	if ($modSettings['themetog_second_theme'] == $modSettings['theme_guests'])
		return;

	// Users must be allowed to select themes
	if (empty($modSettings['theme_allow']))
		return;

	// Add to the main menu.
	// Note that this is actually where http vs js is decided...
	// If given both onclick & href, the onclick executes first.  
	// If the onclick returns false (like our js does) the href never executes.
	// OTOH, if onclick cannot execute (no js), it's not returning false, so the href takes over.
	loadLanguage('ThemeToggle');

	$buttons['themetog'] = array(
		'title' => $txt['themetog_name_short'],
		'icon' => 'tt_sun_moon.png',
		'href' => $scripturl . '?action=themetog',
		'onclick' => 'return theme_toggle();',
		'show' => true
	);
}

/**
 *
 * Hook function - Add the theme toggle button to the profile popup menu part 1 of 2.
 *
 * Hook: integrate_profile_areas
 *
 * @param array $profile_areas
 * @return null
 *
 */
function theme_toggle_buttons_profarea(&$profile_areas)
{
	global $txt, $user_info, $scripturl, $modSettings;

	// First off, ignore any alert popup actions.
	if (isset($_REQUEST['area']) && ($_REQUEST['area'] == 'alerts_popup'))
		return;

	// If using the main menu, bail...
	if (empty($modSettings['themetog_profile_menu']))
		return;

	// If no setting, bail...
	if (empty($modSettings['themetog_second_theme']))
		return;

	// Not for guests
	if (!empty($user_info['is_guest']))
		return;

	// Terminology is weird - the guest theme is the default theme
	// If they were somehow set the same, uh, nothing to do...
	if ($modSettings['themetog_second_theme'] == $modSettings['theme_guests'])
		return;

	// Users must be allowed to select themes
	if (empty($modSettings['theme_allow']))
		return;

	// Add to the main menu.
	// Note that, like Logoff, this isn't actually displayed in the main menu, only the popup.
	loadLanguage('ThemeToggle');

	$profile_areas['profile_action']['areas']['themetog'] =
		array(
			'label' => $txt['themetog_name_short'],
			'custom_url' => $scripturl . '?action=themetog',
			'icon' => 'switch',
			'enabled' => !empty($_REQUEST['area']) && $_REQUEST['area'] === 'popup',
			'permission' => array(
				'own' => array('is_not_guest'),
				'any' => array(),
				),
		);
}

/**
 *
 * Hook function - Add the theme toggle button to the profile popup menu part 2 of 2.
 *
 * Hook: integrate_profile_popup
 *
 * @param array $profile_items
 * @return null
 *
 */
function theme_toggle_buttons_profpop(&$profile_items)
{
	global $txt, $user_info, $scripturl, $modSettings;

	// First off, ignore any alert popup actions.
	if (isset($_REQUEST['area']) && ($_REQUEST['area'] == 'alerts_popup'))
		return;

	// If using the main menu, bail...
	if (empty($modSettings['themetog_profile_menu']))
		return;

	// If no setting, bail...
	if (empty($modSettings['themetog_second_theme']))
		return;

	// Not for guests
	if (!empty($user_info['is_guest']))
		return;

	// Terminology is weird - the guest theme is the default theme
	// If they were somehow set the same, uh, nothing to do...
	if ($modSettings['themetog_second_theme'] == $modSettings['theme_guests'])
		return;

	// Users must be allowed to select themes
	if (empty($modSettings['theme_allow']))
		return;

	// Add the theme swap entry 2nd to last
	$new = 
		array(array(
			'menu' => 'profile_action',
			'area' => 'themetog',
		));
	array_splice($profile_items, count($profile_items) - 1, 0, $new);
}

/**
 *
 * Hook function - toggle the user's theme.  Used by the http mode only.
 *
 * Change it here, EARLY, while loading the user info, so all subsequent
 * load activity references the new theme.
 *
 * Hook: integrate_user_info
 *
 * @return null
 *
 */
function theme_toggle_user_info()
{
	global $user_info, $modSettings, $smcFunc, $cache_enable;

	// First off, ignore any xml actions & any alert popup actions.
	if (isset($_REQUEST['area']) && ($_REQUEST['area'] == 'alerts_popup'))
		return;
	if (isset($_REQUEST['xml']))
		return;

	// If no setting, bail...
	if (empty($modSettings['themetog_second_theme']))
		return;

	// Not for guests
	if (!empty($user_info['is_guest']))
		return;

	// Terminology is weird - the guest theme is the default theme
	// If they were somehow set the same, uh, nothing to do...
	if ($modSettings['themetog_second_theme'] == $modSettings['theme_guests'])
		return;

	// Only change the theme if the action=themetog (http)
	if (!isset($_REQUEST['action']) || ($_REQUEST['action'] != 'themetog'))
		return;

	// Do the deed...
	theme_toggle();
}

/**
 *
 * Hook function - Add the Theme Toggle action to the main action array in index.php - http version.
 *
 * Hook: theme_toggle_actions
 *
 * @param array $action_array
 * @return null
 *
 */
function theme_toggle_actions(&$action_array)
{
	$action_array['themetog'] = array('ThemeToggle.php', 'theme_toggle_http');
}

/**
 *
 * Hook function - Add the Theme Toggle subaction to the subaction array - js version.
 *
 * Hook: integrate_XMLhttpMain_subActions
 *
 * @param array $subaction_array
 * @return null
 *
 */
function theme_toggle_XMLhttpMain_subActions(&$subaction_array)
{
	$subaction_array['themetog'] = 'theme_toggle_js';
}

/**
 *
 * Hook function - Add admin menu function.
 *
 * Hook: integrate_admin_areas
 *
 * @param array $menu
 *
 * @return null
 *
 */
function theme_toggle_admin_area(&$menu)
{
	global $txt;

	loadLanguage('ThemeToggle');

	$menu['config']['areas']['modsettings']['subsections']['themetog'] = array($txt['themetog_name']);
}

/**
 *
 * Hook function - Add subaction for the modifications admin menu to allow selection of the secondary theme.
 *
 * Hook: integrate_modify_modifications
 *
 * @param array $subactions
 *
 * @return null
 *
 */
function theme_toggle_mod_subaction(&$subActions)
{
	$subActions['themetog'] = 'theme_toggle_select';
}

/**
 *
 * Hook function - preloads ThemeToggle.php so function calls work.
 *
 * Hook: integrate_pre_load
 *
 * @return null
 */
function theme_toggle_preload()
{
	global $sourcedir;

	require_once($sourcedir . '/ThemeToggle.php');
}

/**
 *
 * Hook function - Uses an xml action when js available so templates are not refreshed.
 *
 * Hook: integrate_simple_actions
 *
 * @return null
 */


function theme_toggle_simple_actions(&$simpleActions, &$simpleAreas, &$simpleSubActions, &$extraParams, &$xmlActions)
{
	$xmlActions[] = 'themetog';
}

/**
 *
 * Hook function - Load the javascript.
 *
 * Hook: integrate_load_theme
 *
 * @return null
 */


function theme_toggle_load_js()
{
    loadJavaScriptFile('theme_toggle.js', array('minimize' => true));
}
