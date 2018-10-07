<?php

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.");
}


function likepost_info()
{
	return array(
		"name"			=> "Polubienia posta",
		"description"	=> "Pokazuje użytkowników którzy dali reputacje za ten post.",
		"website"		=> "http://github.com/inferno211",
		"author"		=> "Piotr `Inferno` Grencel",
		"authorsite"	=> "http://github.com/inferno211",
		"version"		=> "1.0",
		"guid" 			=> "",
		"codename"		=> "",
		"compatibility" => "*"
	);
}

function likepost_install()
{
	global $db, $mybb;

	$setting_group = array(
		'name' => 'likepost_group',
		'title' => 'Polubienia posta',
		'description' => 'Pokazuje użytkowników którzy dali reputacje za ten post.',
		'disporder' => 5,
		'isdefault' => 0
	);

	$gid = $db->insert_query("settinggroups", $setting_group);

	$setting_array = array(
		'likepost_on' => array(
			'title' => 'Włączony',
			'description' => 'Czy plugin ma być włączony?',
			'optionscode' => 'onoff',
			'value' => 1,
			'disporder' => 1
		)
	);

	foreach($setting_array as $name => $setting)
	{
		$setting['name'] = $name;
		$setting['gid'] = $gid;

		$db->insert_query('settings', $setting);
	}
	rebuild_settings();

	require_once MYBB_ROOT."/inc/adminfunctions_templates.php";

	find_replace_templatesets(
		"postbit",
		"#" . preg_quote('{$post[\'message\']}') . "#i",
		'{$post[\'message\']}{$post[\'likepost\']}'
	);

	find_replace_templatesets(
		"postbit_classic",
		"#" . preg_quote('{$post[\'message\']}') . "#i",
		'{$post[\'message\']}{$post[\'likepost\']}'
	);
}

function likepost_is_installed()
{
	global $mybb;
	if(isset($mybb->settings['likepost_on']))
	{
		return true;
	}
	return false;
}

function likepost_uninstall()
{
	global $db;

	$db->delete_query("settinggroups", "name=\"likepost_group\"");
	$db->delete_query("settings", "name LIKE \"likepost%\"");

	rebuild_settings();

	require_once MYBB_ROOT."/inc/adminfunctions_templates.php";

	find_replace_templatesets(
		"postbit",
		"#" . preg_quote('{$post[\'message\']}{$post[\'likepost\']}') . "#i",
		'{$post[\'message\']}'
	);

	find_replace_templatesets(
		"postbit_classic",
		"#" . preg_quote('{$post[\'message\']}{$post[\'likepost\']}') . "#i",
		'{$post[\'message\']}'
	);
}

$plugins->add_hook("postbit", "likespost");

function likespost(&$post)
{
	global $mybb, $db, $lang;
	if(!$mybb->settings['likepost_on']) return 0;

	$lang->load('likepost');
	
	$post['likepost'] = '';

	$query = $db->simple_select("reputation", "*", "`pid`='".$post['pid']."'");
	if($db->num_rows($query) != 0)
	{
		$first_like = true;
		$post['likepost'] = "<strong><i class=\"fa fa-thumbs-o-up\"></i> ".$lang->likepost_desc.":</strong>";
		while($like = $db->fetch_array($query))
		{
			$user_like = get_user($like['adduid']);
			$post['likepost'] .= ((!$first_like) ? ", " : " ")."<a href=\"".$mybb->settings['bburl']."/".get_profile_link($like['adduid'])."\" title=\"".$user_like['username']."\" data-uid=\"".$like['adduid']."\">".format_name($user_like['username'], $user_like['usergroup'])."</a>"; 
			$first_like = false;
		}
	}
}