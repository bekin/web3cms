<?php
/*---

	Copyright (C) 2008-2009 FluxBB.org
	based on code copyright (C) 2002-2005 Rickard Andersson
	License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher

---*/
// Return current timestamp (with microseconds) as a float
function get_microtime()
{
    list($usec, $sec) = explode(' ', microtime());
    return ((float)$usec + (float)$sec);
}
// Cookie stuff!
function check_cookie(&$pun_user)
{
    global $db, $db_type, $pun_config, $cookie_name, $cookie_seed;

    $now = time();
    $expire = $now + 31536000; // The cookie expires after a year
    // We assume it's a guest
    $cookie = array('user_id' => 1, 'password_hash' => 'Guest');
    // If a cookie is set, we get the user_id and password hash from it
    if (isset($_COOKIE[$cookie_name]))
        list($cookie['user_id'], $cookie['password_hash']) = @unserialize($_COOKIE[$cookie_name]);

    if ($cookie['user_id'] > 1)
    {
        // Check if there's a user with the user ID and password hash from the cookie
        $db->setQuery('SELECT u.*, g.*, o.logged, o.idle FROM ' . $db->tablePrefix . 'users AS u INNER JOIN ' . $db->tablePrefix . 'groups AS g ON u.group_id=g.g_id LEFT JOIN ' . $db->tablePrefix . 'online AS o ON o.user_id=u.id WHERE u.id=' . intval($cookie['user_id'])) or error('Unable to fetch user information', __FILE__, __LINE__, $db->error());
        $pun_user = $db->fetch_assoc();
        // If user authorisation failed
        if (!isset($pun_user['id']) || md5($cookie_seed . $pun_user['password']) !== $cookie['password_hash'])
        {
            pun_setcookie(1, md5(uniqid(rand(), true)), $expire);
            set_default_user();

            return;
        }
        // Set a default language if the user selected language no longer exists
        if (!@file_exists(SHELL_PATH . 'lang/' . $pun_user['language']))
            $pun_user['language'] = $pun_config['o_default_lang'];
        // Set a default style if the user selected style no longer exists
        if (!@file_exists(SHELL_PATH . 'style/' . $pun_user['style'] . '.css'))
            $pun_user['style'] = $pun_config['o_default_style'];

        if (!$pun_user['disp_topics'])
            $pun_user['disp_topics'] = $pun_config['o_disp_topics_default'];
        if (!$pun_user['disp_posts'])
            $pun_user['disp_posts'] = $pun_config['o_disp_posts_default'];
        // Define this if you want this visit to affect the online list and the users last visit data
        if (!defined('PUN_QUIET_VISIT'))
        {
            // Update the online list
            if (!$pun_user['logged'])
            {
                $pun_user['logged'] = $now;
                // With MySQL/MySQLi/SQLite, REPLACE INTO avoids a user having two rows in the online table
                switch ($db_type)
                {
                    case 'mysql':
                    case 'mysqli':
                    case 'mysql_innodb':
                    case 'mysqli_innodb':
                    case 'mysqli':
                        $db->setQuery('REPLACE INTO ' . $db->tablePrefix . 'online (user_id, ident, logged) VALUES(' . $pun_user['id'] . ', \'' . $db->escape($pun_user['username']) . '\', ' . $pun_user['logged'] . ')')->execute() or error('Unable to insert into online list', __FILE__, __LINE__, $db->error());
                        break;

                    default:
                        $db->setQuery('INSERT INTO ' . $db->tablePrefix . 'online (user_id, ident, logged) SELECT ' . $pun_user['id'] . ', \'' . $db->escape($pun_user['username']) . '\', ' . $pun_user['logged'] . ' WHERE NOT EXISTS (SELECT 1 FROM ' . $db->tablePrefix . 'online WHERE user_id=' . $pun_user['id'] . ')')->execute() or error('Unable to insert into online list', __FILE__, __LINE__, $db->error());
                        break;
                }
                // Reset tracked topics
                set_tracked_topics(null);
            }
            else
            {
                // Special case: We've timed out, but no other user has browsed the forums since we timed out
                if ($pun_user['logged'] < ($now - $pun_config['o_timeout_visit']))
                {
                    $db->setQuery('UPDATE ' . $db->tablePrefix . 'users SET last_visit=' . $pun_user['logged'] . ' WHERE id=' . $pun_user['id'])->execute() or error('Unable to update user visit data', __FILE__, __LINE__, $db->error());
                    $pun_user['last_visit'] = $pun_user['logged'];
                }

                $idle_sql = ($pun_user['idle'] == '1') ? ', idle=0' : '';
                $db->setQuery('UPDATE ' . $db->tablePrefix . 'online SET logged=' . $now . $idle_sql . ' WHERE user_id=' . $pun_user['id'])->execute() or error('Unable to update online list', __FILE__, __LINE__, $db->error());
            }
        }
        else
        {
            if (!$pun_user['logged'])
                $pun_user['logged'] = $pun_user['last_visit'];
        }

        $pun_user['is_guest'] = false;
        $pun_user['is_admmod'] = $pun_user['g_id'] == PUN_ADMIN || $pun_user['g_moderator'] == '1';
    }
    else
    {
        set_default_user();
    }

    return $pun_user;
}
// Converts the CDATA end sequence ]]> into ]]&gt;
function escape_cdata($str)
{
    return str_replace(']]>', ']]&gt;', $str);
}
// Authenticates the provided username and password against the user database
// $user can be either a user ID (integer) or a username (string)
// $password can be either a plaintext password or a password hash including salt ($password_is_hash must be set accordingly)
function authenticate_user($user, $password, $password_is_hash = false)
{
    global $db, $pun_user;
    // Check if there's a user matching $user and $password
    $db->setQuery('SELECT u.*, g.*, o.logged, o.idle FROM ' . $db->tablePrefix . 'users AS u INNER JOIN ' . $db->tablePrefix . 'groups AS g ON g.g_id=u.group_id LEFT JOIN ' . $db->tablePrefix . 'online AS o ON o.user_id=u.id WHERE ' . (is_int($user) ? 'u.id=' . intval($user) : 'u.username=\'' . $db->escape($user) . '\'')) or error('Unable to fetch user info', __FILE__, __LINE__, $db->error());
    $pun_user = $db->fetch_assoc();

    if (!isset($pun_user['id']) ||
            ($password_is_hash && $password != $pun_user['password']) ||
            (!$password_is_hash && pun_hash($password) != $pun_user['password']))
        set_default_user();
    else
        $pun_user['is_guest'] = false;
}
// Try to determine the current URL
function get_current_url($max_length = 0)
{
    $protocol = (!isset($_SERVER['HTTPS']) || strtolower($_SERVER['HTTPS']) == 'off') ? 'http://' : 'https://';
    $port = (isset($_SERVER['SERVER_PORT']) && (($_SERVER['SERVER_PORT'] != '80' && $protocol == 'http://') || ($_SERVER['SERVER_PORT'] != '443' && $protocol == 'https://')) && strpos($_SERVER['HTTP_HOST'], ':') === false) ? ':' . $_SERVER['SERVER_PORT'] : '';

    $url = urldecode($protocol . $_SERVER['HTTP_HOST'] . $port . $_SERVER['REQUEST_URI']);

    if (strlen($url) <= $max_length || $max_length == 0)
        return $url;
    // We can't find a short enough url
    return null;
}
// Fill $pun_user with default values (for guests)
function set_default_user()
{
    global $db, $db_type, $pun_user, $pun_config;
    $remote_addr = get_remote_address();
    // Fetch guest user
    $db->setQuery('SELECT u.*, g.*, o.logged, o.last_post, o.last_search FROM ' . $db->tablePrefix . 'users AS u INNER JOIN ' . $db->tablePrefix . 'groups AS g ON u.group_id=g.g_id LEFT JOIN ' . $db->tablePrefix . 'online AS o ON o.ident=\'' . $remote_addr . '\' WHERE u.id=1') or error('Unable to fetch guest information', __FILE__, __LINE__, $db->error());
    if (!$db->num_rows())
        exit('Unable to fetch guest information. The table \'' . $db->tablePrefix . 'users\' must contain an entry with id = 1 that represents anonymous users.');

    $pun_user = $db->fetch_assoc();
    // Update online list
    if (!$pun_user['logged'])
    {
        $pun_user['logged'] = time();
        // With MySQL/MySQLi/SQLite, REPLACE INTO avoids a user having two rows in the online table
        switch ($db_type)
        {
            case 'mysql':
            case 'mysqli':
            case 'mysql_innodb':
            case 'mysqli_innodb':
            case 'sqlite':
                $db->setQuery('REPLACE INTO ' . $db->tablePrefix . 'online (user_id, ident, logged) VALUES(1, \'' . $db->escape($remote_addr) . '\', ' . $pun_user['logged'] . ')')->execute() or error('Unable to insert into online list', __FILE__, __LINE__, $db->error());
                break;

            default:
                $db->setQuery('INSERT INTO ' . $db->tablePrefix . 'online (user_id, ident, logged) SELECT 1, \'' . $db->escape($remote_addr) . '\', ' . $pun_user['logged'] . ' WHERE NOT EXISTS (SELECT 1 FROM ' . $db->tablePrefix . 'online WHERE ident=\'' . $db->escape($remote_addr) . '\')')->execute() or error('Unable to insert into online list', __FILE__, __LINE__, $db->error());
                break;
        }
    }
    else
        $db->setQuery('UPDATE ' . $db->tablePrefix . 'online SET logged=' . time() . ' WHERE ident=\'' . $db->escape($remote_addr) . '\'')->execute() or error('Unable to update online list', __FILE__, __LINE__, $db->error());

    $pun_user['disp_topics'] = $pun_config['o_disp_topics_default'];
    $pun_user['disp_posts'] = $pun_config['o_disp_posts_default'];
    $pun_user['timezone'] = $pun_config['o_default_timezone'];
    $pun_user['dst'] = $pun_config['o_default_dst'];
    $pun_user['language'] = $pun_config['o_default_lang'];
    $pun_user['style'] = $pun_config['o_default_style'];
    $pun_user['is_guest'] = true;
    $pun_user['is_admmod'] = false;
}
// Set a cookie, FluxBB style!
// Wrapper for forum_setcookie
function pun_setcookie($user_id, $password_hash, $expire)
{
    global $cookie_name, $cookie_seed;

    forum_setcookie($cookie_name, serialize(array($user_id, md5($cookie_seed . $password_hash))), $expire);
}
// Set a cookie, FluxBB style!
function forum_setcookie($name, $value, $expire)
{
    global $cookie_path, $cookie_domain, $cookie_secure;
    // Enable sending of a P3P header
    header('P3P: CP="CUR ADM"');

    if (version_compare(PHP_VERSION, '5.2.0', '>='))
        setcookie($name, $value, $expire, $cookie_path, $cookie_domain, $cookie_secure, true);
    else
        setcookie($name, $value, $expire, $cookie_path . '; HttpOnly', $cookie_domain, $cookie_secure);
}
// Check whether the connecting user is banned (and delete any expired bans while we're at it)
function check_bans()
{
    global $db, $pun_config, $lang_common, $pun_user, $pun_bans;
    // Admins aren't affected
    if ($pun_user['g_id'] == PUN_ADMIN || !$pun_bans)
        return;
    // Add a dot or a colon (depending on IPv4/IPv6) at the end of the IP address to prevent banned address
    // 192.168.0.5 from matching e.g. 192.168.0.50
    $user_ip = get_remote_address();
    $user_ip .= (strpos($user_ip, '.') !== false) ? '.' : ':';

    $bans_altered = false;
    $is_banned = false;

    foreach ($pun_bans as $cur_ban)
    {
        // Has this ban expired?
        if ($cur_ban['expire'] != '' && $cur_ban['expire'] <= time())
        {
            $db->setQuery('DELETE FROM ' . $db->tablePrefix . 'bans WHERE id=' . $cur_ban['id'])->execute() or error('Unable to delete expired ban', __FILE__, __LINE__, $db->error());
            $bans_altered = true;
            continue;
        }

        if ($cur_ban['username'] != '' && utf8_strtolower($pun_user['username']) == utf8_strtolower($cur_ban['username']))
            $is_banned = true;

        if ($cur_ban['ip'] != '')
        {
            $cur_ban_ips = explode(' ', $cur_ban['ip']);

            $num_ips = count($cur_ban_ips);
            for ($i = 0; $i < $num_ips; ++$i)
            {
                // Add the proper ending to the ban
                if (strpos($user_ip, '.') !== false)
                    $cur_ban_ips[$i] = $cur_ban_ips[$i] . '.';
                else
                    $cur_ban_ips[$i] = $cur_ban_ips[$i] . ':';

                if (substr($user_ip, 0, strlen($cur_ban_ips[$i])) == $cur_ban_ips[$i])
                {
                    $is_banned = true;
                    break;
                }
            }
        }

        if ($is_banned)
        {
            $db->setQuery('DELETE FROM ' . $db->tablePrefix . 'online WHERE ident=\'' . $db->escape($pun_user['username']) . '\'')->execute() or error('Unable to delete from online list', __FILE__, __LINE__, $db->error());
            message($lang_common['Ban message'] . ' ' . (($cur_ban['expire'] != '') ? $lang_common['Ban message 2'] . ' ' . strtolower(format_time($cur_ban['expire'], true)) . '. ' : '') . (($cur_ban['message'] != '') ? $lang_common['Ban message 3'] . '<br /><br /><strong>' . pun_htmlspecialchars($cur_ban['message']) . '</strong><br /><br />' : '<br /><br />') . $lang_common['Ban message 4'] . ' ' . CHtml::link($pun_config['o_admin_email'], 'mailto:' . $pun_config['o_admin_email']), true);
        }
    }
    // If we removed any expired bans during our run-through, we need to regenerate the bans cache
    if ($bans_altered)
    {
        if (!defined('FORUM_CACHE_FUNCTIONS_LOADED'))
            require SHELL_PATH . 'include/cache.php';

        generate_bans_cache();
    }
}
// Update "Users online"
function update_users_online()
{
    global $db, $pun_config, $pun_user;

    $now = time();
    // Fetch all online list entries that are older than "o_timeout_online"
    $db->setQuery('SELECT * FROM ' . $db->tablePrefix . 'online WHERE logged<' . ($now - $pun_config['o_timeout_online'])) or error('Unable to fetch old entries from online list', __FILE__, __LINE__, $db->error());
    while ($cur_user = $db->fetch_assoc())
    {
        // If the entry is a guest, delete it
        if ($cur_user['user_id'] == '1')
            $db->setQuery('DELETE FROM ' . $db->tablePrefix . 'online WHERE ident=\'' . $db->escape($cur_user['ident']) . '\'')->execute() or error('Unable to delete from online list', __FILE__, __LINE__, $db->error());
        else
        {
            // If the entry is older than "o_timeout_visit", update last_visit for the user in question, then delete him/her from the online list
            if ($cur_user['logged'] < ($now - $pun_config['o_timeout_visit']))
            {
                $db->setQuery('UPDATE ' . $db->tablePrefix . 'users SET last_visit=' . $cur_user['logged'] . ' WHERE id=' . $cur_user['user_id'])->execute() or error('Unable to update user visit data', __FILE__, __LINE__, $db->error());
                $db->setQuery('DELETE FROM ' . $db->tablePrefix . 'online WHERE user_id=' . $cur_user['user_id'])->execute() or error('Unable to delete from online list', __FILE__, __LINE__, $db->error());
            }
            else if ($cur_user['idle'] == '0')
                $db->setQuery('UPDATE ' . $db->tablePrefix . 'online SET idle=1 WHERE user_id=' . $cur_user['user_id'])->execute() or error('Unable to insert into online list', __FILE__, __LINE__, $db->error());
        }
    }
}
// Generate the "navigator" that appears at the top of every page
function generate_navlinks()
{
    global $pun_config, $lang_common, $pun_user;
    // Index and Userlist should always be displayed
    $links[] = '<li id="navindex">' . CHtml::link($lang_common['Index'], array('forum/'));

    if ($pun_user['g_read_board'] == '1' && $pun_user['g_view_users'] == '1')
        $links[] = '<li id="navuserlist">' . CHtml::link($lang_common['User list'], array('forum/userlist'));

    if ($pun_config['o_rules'] == '1' && (!$pun_user['is_guest'] || $pun_user['g_read_board'] == '1' || $pun_config['o_regs_allow'] == '1'))
        $links[] = '<li id="navrules">' . CHtml::link($lang_common['Rules'], array('forum/misc', 'action' => 'rules'));

    if ($pun_user['is_guest'])
    {
        if ($pun_user['g_read_board'] == '1' && $pun_user['g_search'] == '1')
            $links[] = '<li id="navsearch">' . CHtml::link($lang_common['Search'], array('forum/search'));

        $links[] = '<li id="navregister">' . CHtml::link($lang_common['Register'], array('forum/register'));
        $links[] = '<li id="navlogin">' . CHtml::link($lang_common['Login'], array('forum/login'));

        $info = $lang_common['Not logged in'];
    }
    else
    {
        if (!$pun_user['is_admmod'])
        {
            if ($pun_user['g_read_board'] == '1' && $pun_user['g_search'] == '1')
                $links[] = '<li id="navsearch">' . CHtml::link($lang_common['Search'], array('forum/search'));

            $links[] = '<li id="navprofile">' . CHtml::link($lang_common['Profile'], array('forum/profile', 'id' => $pun_user['id']));
            $links[] = '<li id="navlogout">' . CHtml::link($lang_common['Logout'], array('forum/login', 'action' => 'out', 'id' => $pun_user['id'], 'csrf_token' => pun_hash($pun_user['id'] . pun_hash(get_remote_address()))));
        }
        else
        {
            $links[] = '<li id="navsearch">' . CHtml::link($lang_common['Search'], array('forum/search'));
            $links[] = '<li id="navprofile">' . CHtml::link($lang_common['Profile'], array('forum/profile', 'id' => $pun_user['id']));
            $links[] = '<li id="navadmin">' . CHtml::link($lang_common['Admin'], array('forum/admin_index'));
            $links[] = '<li id="navlogout">' . CHtml::link($lang_common['Logout'], array('forum/login', 'action' => 'out', 'id' => $pun_user['id'], 'csrf_token' => pun_hash($pun_user['id'] . pun_hash(get_remote_address()))));
        }
    }
    // Are there any additional navlinks we should insert into the array before imploding it?
    if ($pun_config['o_additional_navlinks'] != '')
    {
        if (preg_match_all('#([0-9]+)\s*=\s*(.*?)\n#s', $pun_config['o_additional_navlinks'] . "\n", $extra_links))
        {
            // Insert any additional links into the $links array (at the correct index)
            $num_links = count($extra_links[1]);
            for ($i = 0; $i < $num_links; ++$i)
            array_splice($links, $extra_links[1][$i], 0, array('<li id="navextra' . ($i + 1) . '">' . $extra_links[2][$i]));
        }
    }

    return '<ul>' . "\n\t\t\t\t" . implode($lang_common['Menu link separator'] . '</li>' . "\n\t\t\t\t", $links) . '</li>' . "\n\t\t\t" . '</ul>';
}
// Display the profile navigation menu
function generate_profile_menu($page = '')
{
    global $lang_profile, $pun_config, $pun_user, $id;

    ?>
<div id="profile" class="block2col">
	<div class="blockmenu">
		<h2><span><?php echo $lang_profile['Profile menu'] ?></span></h2>
		<div class="box">
			<div class="inbox">
				<ul>
					<li<?php if ($page == 'essentials') echo ' class="isactive"';?>><?php
    echo CHtml::link($lang_profile['Section essentials'], array('forum/profile', 'section' => 'essentials', 'id' => $id));

    ?></li>
					<li<?php if ($page == 'personal') echo ' class="isactive"';?>><?php
    echo CHtml::link($lang_profile['Section personal'], array('forum/profile', 'section' => 'personal', 'id' => $id));

    ?>
</li>
					<li<?php if ($page == 'messaging') echo ' class="isactive"';?>><?php
    echo CHtml::link($lang_profile['Section messaging'], array('forum/profile', 'section' => 'messaging', 'id' => $id));

    ?></li>
<?php if ($pun_config['o_avatars'] == '1' || $pun_config['o_signatures'] == '1'): ?>					<li<?php if ($page == 'personality') echo ' class="isactive"';?>><?php
        echo CHtml::link($lang_profile['Section personality'], array('forum/profile', 'section' => 'personality', 'id' => $id));

        ?></li>
<?php endif; ?>					<li<?php if ($page == 'display') echo ' class="isactive"';?>><?php
        echo CHtml::link($lang_profile['Section display'], array('forum/profile', 'section' => 'display', 'id' => $id));

        ?></li>
					<li<?php if ($page == 'privacy') echo ' class="isactive"';?>><?php
        echo CHtml::link($lang_profile['Section privacy'], array('forum/profile', 'section' => 'privacy', 'id' => $id));
        ?></li>
<?php if ($pun_user['g_id'] == PUN_ADMIN || ($pun_user['g_moderator'] == '1' && $pun_user['g_mod_ban_users'] == '1')): ?>					<li<?php if ($page == 'admin') echo ' class="isactive"';?>><?php
            echo CHtml::link($lang_profile['Section admin'], array('forum/profile', 'section' => 'admin', 'id' => $id));
            ?></li>
<?php endif; ?>				</ul>
			</div>
		</div>
	</div>
<?php

        }
        // Outputs markup to display a user's avatar
        function generate_avatar_markup($user_id)
        {
            global $pun_config;
            $filetypes = array('jpg', 'gif', 'png');
            $avatar_markup = '';
            foreach ($filetypes as $cur_type)
            {
                $path = $pun_config['o_avatars_dir'] . '/' . $user_id . '.' . $cur_type;
                if (file_exists(SHELL_PATH . $path) && $img_size = @getimagesize(SHELL_PATH . $path))
                {
                    $avatar_markup = '<img src="' . $pun_config['o_WEB_PATH'] . '/' . $path . '?m=' . filemtime(SHELL_PATH . $path) . '" ' . $img_size[3] . ' alt="" />';
                    break;
                }
            }

            return $avatar_markup;
        }
        // Save array of tracked topics in cookie
        function set_tracked_topics($tracked_topics)
        {
            global $cookie_name, $cookie_path, $cookie_domain, $cookie_secure, $pun_config;
            $cookie_data = '';
            if (!empty($tracked_topics))
            {
                // Sort the arrays (latest read first)
                arsort($tracked_topics['topics'], SORT_NUMERIC);
                arsort($tracked_topics['forums'], SORT_NUMERIC);
                // Homebrew serialization (to avoid having to run unserialize() on cookie data)
                foreach ($tracked_topics['topics'] as $id => $timestamp)
                $cookie_data .= 't' . $id . '=' . $timestamp . ';';
                foreach ($tracked_topics['forums'] as $id => $timestamp)
                $cookie_data .= 'f' . $id . '=' . $timestamp . ';';
                // Enforce a 4048 byte size limit (4096 minus some space for the cookie name)
                if (strlen($cookie_data) > 4048)
                {
                    $cookie_data = substr($cookie_data, 0, 4048);
                    $cookie_data = substr($cookie_data, 0, strrpos($cookie_data, ';')) . ';';
                }
            }

            forum_setcookie($cookie_name . '_track', $cookie_data, time() + $pun_config['o_timeout_visit']);
            $_COOKIE[$cookie_name . '_track'] = $cookie_data; // Set it directly in $_COOKIE as well
        }
        // Extract array of tracked topics from cookie
        function get_tracked_topics()
        {
            global $cookie_name;
            $cookie_data = isset($_COOKIE[$cookie_name . '_track']) ? $_COOKIE[$cookie_name . '_track'] : false;
            if (!$cookie_data)
                return array('topics' => array(), 'forums' => array());
            if (strlen($cookie_data) > 4048)
                return array('topics' => array(), 'forums' => array());
            // Unserialize data from cookie
            $tracked_topics = array('topics' => array(), 'forums' => array());
            $temp = explode(';', $cookie_data);
            foreach ($temp as $t)
            {
                $type = substr($t, 0, 1) == 'f' ? 'forums' : 'topics';
                $id = intval(substr($t, 1));
                $timestamp = intval(@substr($t, strpos($t, '=') + 1));
                if ($id > 0 && $timestamp > 0)
                    $tracked_topics[$type][$id] = $timestamp;
            }

            return $tracked_topics;
        }
        // Update posts, topics, last_post, last_post_id and last_poster for a forum
        function update_forum($forum_id)
        {
            global $db;
            $db->setQuery('SELECT COUNT(id), SUM(num_replies) FROM ' . $db->tablePrefix . 'topics WHERE forum_id=' . $forum_id) or error('Unable to fetch forum topic count', __FILE__, __LINE__, $db->error());
            list($num_topics, $num_posts) = $db->fetch_row();
            $num_posts = $num_posts + $num_topics; // $num_posts is only the sum of all replies (we have to add the topic posts)
            $db->setQuery('SELECT last_post, last_post_id, last_poster FROM ' . $db->tablePrefix . 'topics WHERE forum_id=' . $forum_id . ' AND moved_to IS NULL ORDER BY last_post DESC LIMIT 1') or error('Unable to fetch last_post/last_post_id/last_poster', __FILE__, __LINE__, $db->error());
            if ($db->num_rows()) // There are topics in the forum
                {
                    list($last_post, $last_post_id, $last_poster) = $db->fetch_row();
                $db->setQuery('UPDATE ' . $db->tablePrefix . 'forums SET num_topics=' . $num_topics . ', num_posts=' . $num_posts . ', last_post=' . $last_post . ', last_post_id=' . $last_post_id . ', last_poster=\'' . $db->escape($last_poster) . '\' WHERE id=' . $forum_id)->execute() or error('Unable to update last_post/last_post_id/last_poster', __FILE__, __LINE__, $db->error());
            }
            else // There are no topics
                $db->setQuery('UPDATE ' . $db->tablePrefix . 'forums SET num_topics=' . $num_topics . ', num_posts=' . $num_posts . ', last_post=NULL, last_post_id=NULL, last_poster=NULL WHERE id=' . $forum_id)->execute() or error('Unable to update last_post/last_post_id/last_poster', __FILE__, __LINE__, $db->error());
        }
        // Deletes any avatars owned by the specified user ID
        function delete_avatar($user_id)
        {
            global $pun_config;
            $filetypes = array('jpg', 'gif', 'png');
            // Delete user avatar
            foreach ($filetypes as $cur_type)
            {
                if (file_exists(SHELL_PATH . $pun_config['o_avatars_dir'] . '/' . $user_id . '.' . $cur_type))
                    @unlink(SHELL_PATH . $pun_config['o_avatars_dir'] . '/' . $user_id . '.' . $cur_type);
            }
        }
        // Delete a topic and all of it's posts
        function delete_topic($topic_id)
        {
            global $db;
            // Delete the topic and any redirect topics
            $db->setQuery('DELETE FROM ' . $db->tablePrefix . 'topics WHERE id=' . $topic_id . ' OR moved_to=' . $topic_id)->execute() or error('Unable to delete topic', __FILE__, __LINE__, $db->error());
            // Create a list of the post IDs in this topic
            $post_ids = '';
            $db->setQuery('SELECT id FROM ' . $db->tablePrefix . 'posts WHERE topic_id=' . $topic_id) or error('Unable to fetch posts', __FILE__, __LINE__, $db->error());
            while ($row = $db->fetch_row())
            $post_ids .= ($post_ids != '') ? ',' . $row[0] : $row[0];
            // Make sure we have a list of post IDs
            if ($post_ids != '')
            {
                strip_search_index($post_ids);
                // Delete posts in topic
                $db->setQuery('DELETE FROM ' . $db->tablePrefix . 'posts WHERE topic_id=' . $topic_id)->execute() or error('Unable to delete posts', __FILE__, __LINE__, $db->error());
            }
            // Delete any subscriptions for this topic
            $db->setQuery('DELETE FROM ' . $db->tablePrefix . 'subscriptions WHERE topic_id=' . $topic_id)->execute() or error('Unable to delete subscriptions', __FILE__, __LINE__, $db->error());
        }
        // Delete a single post
        function delete_post($post_id, $topic_id)
        {
            global $db;
            $db->setQuery('SELECT id, poster, posted FROM ' . $db->tablePrefix . 'posts WHERE topic_id=' . $topic_id . ' ORDER BY id DESC LIMIT 2') or error('Unable to fetch post info', __FILE__, __LINE__, $db->error());
            list($last_id, ,) = $db->fetch_row();
            list($second_last_id, $second_poster, $second_posted) = $db->fetch_row();
            // Delete the post
            $db->setQuery('DELETE FROM ' . $db->tablePrefix . 'posts WHERE id=' . $post_id)->execute() or error('Unable to delete post', __FILE__, __LINE__, $db->error());
            strip_search_index($post_id);
            // Count number of replies in the topic
            $db->setQuery('SELECT COUNT(id) FROM ' . $db->tablePrefix . 'posts WHERE topic_id=' . $topic_id) or error('Unable to fetch post count for topic', __FILE__, __LINE__, $db->error());
            $num_replies = $db->result($result, 0) - 1;
            // If the message we deleted is the most recent in the topic (at the end of the topic)
            if ($last_id == $post_id)
            {
                // If there is a $second_last_id there is more than 1 reply to the topic
                if (!empty($second_last_id))
                    $db->setQuery('UPDATE ' . $db->tablePrefix . 'topics SET last_post=' . $second_posted . ', last_post_id=' . $second_last_id . ', last_poster=\'' . $db->escape($second_poster) . '\', num_replies=' . $num_replies . ' WHERE id=' . $topic_id)->execute() or error('Unable to update topic', __FILE__, __LINE__, $db->error());
                else
                    // We deleted the only reply, so now last_post/last_post_id/last_poster is posted/id/poster from the topic itself
                    $db->setQuery('UPDATE ' . $db->tablePrefix . 'topics SET last_post=posted, last_post_id=id, last_poster=poster, num_replies=' . $num_replies . ' WHERE id=' . $topic_id)->execute() or error('Unable to update topic', __FILE__, __LINE__, $db->error());
            }
            else
                // Otherwise we just decrement the reply counter
                $db->setQuery('UPDATE ' . $db->tablePrefix . 'topics SET num_replies=' . $num_replies . ' WHERE id=' . $topic_id)->execute() or error('Unable to update topic', __FILE__, __LINE__, $db->error());
        }
        // Delete every .php file in the forum's cache directory
        function forum_clear_cache()
        {
            $d = dir(FORUM_CACHE_DIR);
            while (($entry = $d->read()) !== false)
            {
                if (substr($entry, strlen($entry) - 4) == '.php')
                    @unlink(FORUM_CACHE_DIR . $entry);
            }
            $d->close();
        }
        // Replace censored words in $text
        function censor_words($text)
        {
            global $db;
            static $search_for, $replace_with;
            // If not already built in a previous call, build an array of censor words and their replacement text
            if (!isset($search_for))
            {
                $db->setQuery('SELECT search_for, replace_with FROM ' . $db->tablePrefix . 'censoring') or error('Unable to fetch censor word list', __FILE__, __LINE__, $db->error());
                $num_words = $db->num_rows();
                $search_for = array();
                for ($i = 0; $i < $num_words; ++$i)
                {
                    list($search_for[$i], $replace_with[$i]) = $db->fetch_row();
                    $search_for[$i] = '/\b(' . str_replace('\*', '\w*?', preg_quote($search_for[$i], '/')) . ')\b/i';
                }
            }

            if (!empty($search_for))
                $text = substr(preg_replace($search_for, $replace_with, ' ' . $text . ' '), 1, - 1);
            return $text;
        }
        // Determines the correct title for $user
        // $user must contain the elements 'username', 'title', 'posts', 'g_id' and 'g_user_title'
        function get_title($user)
        {
            global $db, $pun_config, $pun_bans, $lang_common;
            static $ban_list, $pun_ranks;
            // If not already built in a previous call, build an array of lowercase banned usernames
            if (empty($ban_list))
            {
                $ban_list = array();
                foreach ($pun_bans as $cur_ban)
                $ban_list[] = strtolower($cur_ban['username']);
            }
            // If not already loaded in a previous call, load the cached ranks
            if ($pun_config['o_ranks'] == '1' && empty($pun_ranks))
            {
                if (file_exists(FORUM_CACHE_DIR . 'cache_ranks.php'))
                    include FORUM_CACHE_DIR . 'cache_ranks.php';
                if (!defined('PUN_RANKS_LOADED'))
                {
                    if (!defined('FORUM_CACHE_FUNCTIONS_LOADED'))
                        require SHELL_PATH . 'include/cache.php';
                    generate_ranks_cache();
                    require FORUM_CACHE_DIR . 'cache_ranks.php';
                }
            }
            // If the user has a custom title
            if ($user['title'] != '')
                $user_title = pun_htmlspecialchars($user['title']);
            // If the user is banned
            else if (in_array(strtolower($user['username']), $ban_list))
                $user_title = $lang_common['Banned'];
            // If the user group has a default user title
            else if ($user['g_user_title'] != '')
                $user_title = pun_htmlspecialchars($user['g_user_title']);
            // If the user is a guest
            else if ($user['g_id'] == PUN_GUEST)
                $user_title = $lang_common['Guest'];
            else
            {
                // Are there any ranks?
                if ($pun_config['o_ranks'] == '1' && !empty($pun_ranks))
                {
                    @reset($pun_ranks);
                    while (list(, $cur_rank) = @each($pun_ranks))
                    {
                        if (intval($user['num_posts']) >= $cur_rank['min_posts'])
                            $user_title = pun_htmlspecialchars($cur_rank['rank']);
                    }
                }
                // If the user didn't "reach" any rank (or if ranks are disabled), we assign the default
                if (!isset($user_title))
                    $user_title = $lang_common['Member'];
            }

            return $user_title;
        }
        // Generate a string with numbered links (for multipage scripts)
        function paginate($num_pages, $cur_page, $link)
        {
            global $lang_common;
            $pages = array();
            $link_to_all = false;
            // If $cur_page == -1, we link to all pages (used in viewforum.php)
            if ($cur_page == - 1)
            {
                $cur_page = 1;
                $link_to_all = true;
            }

            if ($num_pages <= 1)
                $pages = array('<strong class="item1">1</strong>');
            else
            {
                // Add a previous page link
                if ($num_pages > 1 && $cur_page > 1)
                    $pages[] = '<a' . (empty($pages) ? ' class="item1"' : '') . ' href="' . $link . '&amp;p=' . ($cur_page - 1) . '">' . $lang_common['Previous'] . '</a>';
                if ($cur_page > 3)
                {
                    $pages[] = '<a' . (empty($pages) ? ' class="item1"' : '') . ' href="' . $link . '&amp;p=1">1</a>';
                    if ($cur_page > 5)
                        $pages[] = '<span>' . $lang_common['Spacer'] . '</span>';
                }
                // Don't ask me how the following works. It just does, OK? :-)
                for ($current = ($cur_page == 5) ? $cur_page - 3 : $cur_page - 2, $stop = ($cur_page + 4 == $num_pages) ? $cur_page + 4 : $cur_page + 3; $current < $stop; ++$current)
                {
                    if ($current < 1 || $current > $num_pages)
                        continue;
                    else if ($current != $cur_page || $link_to_all)
                        $pages[] = '<a' . (empty($pages) ? ' class="item1"' : '') . ' href="' . $link . '&amp;p=' . $current . '">' . forum_number_format($current) . '</a>';
                    else
                        $pages[] = '<strong' . (empty($pages) ? ' class="item1"' : '') . '>' . forum_number_format($current) . '</strong>';
                }

                if ($cur_page <= ($num_pages - 3))
                {
                    if ($cur_page != ($num_pages - 3) && $cur_page != ($num_pages - 4))
                        $pages[] = '<span>' . $lang_common['Spacer'] . '</span>';
                    $pages[] = '<a' . (empty($pages) ? ' class="item1"' : '') . ' href="' . $link . '&amp;p=' . $num_pages . '">' . forum_number_format($num_pages) . '</a>';
                }
                // Add a next page link
                if ($num_pages > 1 && !$link_to_all && $cur_page < $num_pages)
                    $pages[] = '<a' . (empty($pages) ? ' class="item1"' : '') . ' href="' . $link . '&amp;p=' . ($cur_page + 1) . '">' . $lang_common['Next'] . '</a>';
            }

            return implode(' ', $pages);
        }
        // Display a message
        function message($message, $no_back_link = false)
        {
            global $db, $lang_common, $pun_config, $pun_start, $tpl_main;
            if (!defined('PUN_HEADER'))
            {
                global $pun_user;
                $page_title = pun_htmlspecialchars($pun_config['o_board_title']) . ' / ' . $lang_common['Info'];
                require SHELL_PATH . 'header.php';
            }

            ?>

<div id="msg" class="block">
	<h2><span><?php echo $lang_common['Info'] ?></span></h2>
	<div class="box">
		<div class="inbox">
			<p><?php echo $message ?></p>
<?php if (!$no_back_link): ?>			<p><?php echo CHtml::link($lang_common['Go back'], 'javascript: history.go(-1);');?></p>
<?php endif; ?>		</div>
	</div>
</div>
<?php

            require SHELL_PATH . 'footer.php';
        }
        // Format a time string according to $time_format and time zones
        function format_time($timestamp, $date_only = false, $date_format = null, $time_format = null, $time_only = false, $no_text = false)
        {
            global $pun_config, $lang_common, $pun_user, $forum_date_formats, $forum_time_formats;
            if ($timestamp == '')
                return $lang_common['Never'];
            $diff = ($pun_user['timezone'] + $pun_user['dst']) * 3600;
            $timestamp += $diff;
            $now = time();
            if ($date_format == null)
                $date_format = $forum_date_formats[$pun_user['date_format']];
            if ($time_format == null)
                $time_format = $forum_time_formats[$pun_user['time_format']];
            $date = gmdate($date_format, $timestamp);
            $today = gmdate($date_format, $now + $diff);
            $yesterday = gmdate($date_format, $now + $diff - 86400);
            if (!$no_text)
            {
                if ($date == $today)
                    $date = $lang_common['Today'];
                else if ($date == $yesterday)
                    $date = $lang_common['Yesterday'];
            }

            if ($date_only)
                return $date;
            else if ($time_only)
                return gmdate($time_format, $timestamp);
            else
                return $date . ' ' . gmdate($time_format, $timestamp);
        }
        // A wrapper for PHP's number_format function
        function forum_number_format($number, $decimals = 0)
        {
            global $lang_common;
            return number_format($number, $decimals, $lang_common['lang_decimal_point'], $lang_common['lang_thousands_sep']);
        }
        // Generate a random key of length $len
        function random_key($len, $readable = false, $hash = false)
        {
            $key = '';
            if ($hash)
                $key = substr(pun_hash(uniqid(rand(), true)), 0, $len);
            else if ($readable)
            {
                $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
                for ($i = 0; $i < $len; ++$i)
                $key .= substr($chars, (mt_rand() % strlen($chars)), 1);
            }
            else
            {
                for ($i = 0; $i < $len; ++$i)
                $key .= chr(mt_rand(33, 126));
            }

            return $key;
        }
        // If we are running pre PHP 4.3.0, we add our own implementation of file_get_contents
        if (!function_exists('file_get_contents'))
        {
            function file_get_contents($filename, $use_include_path = 0)
            {
                $data = '';
                if ($fh = fopen($filename, 'rb', $use_include_path))
                {
                    $data = fread($fh, filesize($filename));
                    fclose($fh);
                }

                return $data;
            }
        }
        // Make sure that HTTP_REFERER matches $pun_config['o_base_url']/$script
        function confirm_referrer($script)
        {
            global $pun_config, $lang_common;
            if (!preg_match('#^' . preg_quote(str_replace('www.', '', $pun_config['o_base_url']) . '/' . $script, '#') . '#i', str_replace('www.', '', (isset($_SERVER['HTTP_REFERER']) ? urldecode($_SERVER['HTTP_REFERER']) : ''))))
                message($lang_common['Bad referrer']);
        }
        // Generate a random password of length $len
        // Compatibility wrapper for random_key
        function random_pass($len)
        {
            return random_key($len, true);
        }
        // Compute a hash of $str
        // Uses sha1() if available. If not, SHA1 through mhash() if available. If not, fall back on md5()
        function pun_hash($str)
        {
            if (function_exists('sha1')) // Only in PHP 4.3.0+
                return sha1($str);
            else if (function_exists('mhash')) // Only if Mhash library is loaded
                return bin2hex(mhash(MHASH_SHA1, $str));
            else
                return md5($str);
        }
        // Try to determine the correct remote IP-address
        function get_remote_address()
        {
            return $_SERVER['REMOTE_ADDR'];
        }
        // Calls htmlspecialchars with a few options already set
        function pun_htmlspecialchars($str)
        {
            return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
        }
        // A wrapper for utf8_strlen for compatibility
        function pun_strlen($str)
        {
            return utf8_strlen($str);
        }
        // Convert \r\n and \r to \n
        function pun_linebreaks($str)
        {
            return str_replace("\r", "\n", str_replace("\r\n", "\n", $str));
        }
        // A wrapper for utf8_trim for compatibility
        function pun_trim($str)
        {
            return utf8_trim($str);
        }
        // Checks if a string is in all uppercase
        function is_all_uppercase($string)
        {
            return utf8_strtoupper($string) == $string && utf8_strtolower($string) != $string;
        }
        // Inserts $element into $input at $offset
        // $offset can be either a numerical offset to insert at (eg: 0 inserts at the beginning of the array)
        // or a string, which is the key that the new element should be inserted before
        // $key is optional: it's used when inserting a new key/value pair into an associative array
        function array_insert(&$input, $offset, $element, $key = null)
        {
            if ($key == null)
                $key = $offset;
            // Determine the proper offset if we're using a string
            if (!is_int($offset))
                $offset = array_search($offset, array_keys($input), true);
            // Out of bounds checks
            if ($offset > count($input))
                $offset = count($input);
            else if ($offset < 0)
                $offset = 0;
            $input = array_merge(array_slice($input, 0, $offset), array($key => $element), array_slice($input, $offset));
        }
        // Display a message when board is in maintenance mode
        function maintenance_message()
        {
            global $db, $pun_config, $lang_common, $pun_user;
            // Deal with newlines, tabs and multiple spaces
            $pattern = array("\t", '  ', '  ');
            $replace = array('&nbsp; &nbsp; ', '&nbsp; ', ' &nbsp;');
            $message = str_replace($pattern, $replace, $pun_config['o_maintenance_message']);
            // Load the maintenance template
            if (file_exists(SHELL_PATH . 'style/' . $pun_user['style'] . '/maintenance.tpl'))
                $tpl_maint = trim(file_get_contents(SHELL_PATH . 'style/' . $pun_user['style'] . '/maintenance.tpl'));
            else
                $tpl_maint = trim(file_get_contents(SHELL_PATH . 'include/template/maintenance.tpl'));
            // START SUBST - <pun_include "*">
            while (preg_match('#<pun_include "([^/\\\\]*?)\.(php[45]?|inc|html?|txt)">#', $tpl_maint, $cur_include))
            {
                if (!file_exists(SHELL_PATH . 'include/user/' . $cur_include[1] . '.' . $cur_include[2]))
                    error('Unable to process user include ' . htmlspecialchars($cur_include[0]) . ' from template maintenance.tpl. There is no such file in folder /include/user/');
                ob_start();
                include SHELL_PATH . 'include/user/' . $cur_include[1] . '.' . $cur_include[2];
                $tpl_temp = ob_get_contents();
                $tpl_maint = str_replace($cur_include[0], $tpl_temp, $tpl_maint);
                ob_end_clean();
            }
            // END SUBST - <pun_include "*">
            // START SUBST - <pun_language>
            $tpl_maint = str_replace('<pun_language>', $lang_common['lang_identifier'], $tpl_maint);
            // END SUBST - <pun_language>
            // START SUBST - <pun_content_direction>
            $tpl_maint = str_replace('<pun_content_direction>', $lang_common['lang_direction'], $tpl_maint);
            // END SUBST - <pun_content_direction>
            // START SUBST - <pun_head>
            ob_start();

            ?>
<title><?php echo pun_htmlspecialchars($pun_config['o_board_title']) . ' / ' . $lang_common['Maintenance'] ?></title>
<link rel="stylesheet" type="text/css" href="style/<?php echo $pun_user['style'] . '.css' ?>" />
<?php

            $tpl_temp = trim(ob_get_contents());
            $tpl_maint = str_replace('<pun_head>', $tpl_temp, $tpl_maint);
            ob_end_clean();
            // END SUBST - <pun_head>
            // START SUBST - <pun_maint_heading>
            $tpl_maint = str_replace('<pun_maint_heading>', $lang_common['Maintenance'], $tpl_maint);
            // END SUBST - <pun_maint_heading>
            // START SUBST - <pun_maint_message>
            $tpl_maint = str_replace('<pun_maint_message>', $message, $tpl_maint);
            // END SUBST - <pun_maint_message>
            // Close the db connection
            $db->close();
            echo $tpl_maint;
        }
        // Display $message and redirect user to $destination_url
        function redirect($destination_url, $message)
        {
            global $db, $pun_config, $lang_common, $pun_user;
            // Prefix with o_base_url (unless there's already a valid URI)
            if (strpos($destination_url, 'http://') !== 0 && strpos($destination_url, 'https://') !== 0 && strpos($destination_url, '/') !== 0)
                $destination_url = $pun_config['o_base_url'] . '/' . $destination_url;
            // Do a little spring cleaning
            $destination_url = preg_replace('/([\r\n])|(%0[ad])|(;[\s]*data[\s]*:)/i', '', $destination_url);
            // If the delay is 0 seconds, we might as well skip the redirect all together
            if ($pun_config['o_redirect_delay'] == '0')
            	header('Location: ' . str_replace('&amp;', '&', $destination_url));
            // Load the redirect template
            if (function_exists(SHELL_PATH . 'style/' . $pun_user['style'] . '/redirect.tpl'))
                $tpl_redir = trim(file_get_contents(SHELL_PATH . 'style/' . $pun_user['style'] . '/redirect.tpl'));
            else
                $tpl_redir = trim(file_get_contents(SHELL_PATH . 'include/template/redirect.tpl'));
            // START SUBST - <pun_include "*">
            while (preg_match('#<pun_include "([^/\\\\]*?)\.(php[45]?|inc|html?|txt)">#', $tpl_redir, $cur_include))
            {
                if (!file_exists(SHELL_PATH . 'include/user/' . $cur_include[1] . '.' . $cur_include[2]))
                    error('Unable to process user include ' . htmlspecialchars($cur_include[0]) . ' from template redirect.tpl. There is no such file in folder /include/user/');
                ob_start();
                include SHELL_PATH . 'include/user/' . $cur_include[1] . '.' . $cur_include[2];
                $tpl_temp = ob_get_contents();
                $tpl_redir = str_replace($cur_include[0], $tpl_temp, $tpl_redir);
                ob_end_clean();
            }
            // END SUBST - <pun_include "*">
            // START SUBST - <pun_language>
            $tpl_redir = str_replace('<pun_language>', $lang_common['lang_identifier'], $tpl_redir);
            // END SUBST - <pun_language>
            // START SUBST - <pun_content_direction>
            $tpl_redir = str_replace('<pun_content_direction>', $lang_common['lang_direction'], $tpl_redir);
            // END SUBST - <pun_content_direction>
            // START SUBST - <pun_head>
            ob_start();

            ?>
<meta http-equiv="refresh" content="<?php echo $pun_config['o_redirect_delay'] ?>;URL=<?php echo str_replace(array('<', '>', '"'), array('&lt;', '&gt;', '&quot;'), $destination_url) ?>" />
<title><?php echo pun_htmlspecialchars($pun_config['o_board_title']) . ' / ' . $lang_common['Redirecting'] ?></title>
<link rel="stylesheet" type="text/css" href="style/<?php echo $pun_user['style'] . '.css' ?>" />
<?php

            $tpl_temp = trim(ob_get_contents());
            $tpl_redir = str_replace('<pun_head>', $tpl_temp, $tpl_redir);
            ob_end_clean();
            // END SUBST - <pun_head>
            // START SUBST - <pun_redir_heading>
            $tpl_redir = str_replace('<pun_redir_heading>', $lang_common['Redirecting'], $tpl_redir);
            // END SUBST - <pun_redir_heading>
            // START SUBST - <pun_redir_text>
            $tpl_temp = $message . '<br /><br />' . CHtml::link($lang_common['Click redirect'], $destination_url); ;
            $tpl_redir = str_replace('<pun_redir_text>', $tpl_temp, $tpl_redir);
            // END SUBST - <pun_redir_text>
            // START SUBST - <pun_footer>
            ob_start();
            // Display executed queries (if enabled)
            if (defined('PUN_SHOW_QUERIES'))
                display_saved_queries();

            $tpl_temp = trim(ob_get_contents());
            $tpl_redir = str_replace('<pun_footer>', $tpl_temp, $tpl_redir);
            ob_end_clean();
            // END SUBST - <pun_footer>
            // Close the db connection (and free up any result data)
            $db->close();

            echo $tpl_redir;
        }
        // Display a simple error message
        function error($message, $file, $line, $db_error = false)
        {
           ?>

<title><?php echo pun_htmlspecialchars($pun_config['o_board_title']) ?> / Error</title>

<div id="errorbox">
	<h2>An error was encountered</h2>
	<div>
<?php

            if (defined('PUN_DEBUG'))
            {
                echo "\t\t" . '<strong>File:</strong> ' . $file . '<br />' . "\n\t\t" . '<strong>Line:</strong> ' . $line . '<br /><br />' . "\n\t\t" . '<strong>FluxBB reported</strong>: ' . $message . "\n";
                if ($db_error)
                {
                    echo "\t\t" . '<br /><br /><strong>Database reported:</strong> ' . pun_htmlspecialchars($db_error['error_msg']) . (($db_error['error_no']) ? ' (Errno: ' . $db_error['error_no'] . ')' : '') . "\n";
                    if ($db_error['error_sql'] != '')
                        echo "\t\t" . '<br /><br /><strong>Failed query:</strong> ' . pun_htmlspecialchars($db_error['error_sql']) . "\n";
                }
            }
            else
                echo "\t\t" . 'Error: <strong>' . $message . '.</strong>' . "\n";

            ?>
	</div>
</div>
<?php
            // If a database connection was established (before this error) we close it
            if ($db_error)
                $GLOBALS['db']->close();
        }
        // Unset any variables instantiated as a result of register_globals being enabled
        function forum_unregister_globals()
        {
            $register_globals = @ini_get('register_globals');
            if ($register_globals === "" || $register_globals === "0" || strtolower($register_globals) === "off")
                return;
            // Prevent script.php?GLOBALS[foo]=bar
            if (isset($_REQUEST['GLOBALS']) || isset($_FILES['GLOBALS']))
                exit('I\'ll have a steak sandwich and... a steak sandwich.');
            // Variables that shouldn't be unset
            $no_unset = array('GLOBALS', '_GET', '_POST', '_COOKIE', '_REQUEST', '_SERVER', '_ENV', '_FILES');
            // Remove elements in $GLOBALS that are present in any of the superglobals
            $input = array_merge($_GET, $_POST, $_COOKIE, $_SERVER, $_ENV, $_FILES, isset($_SESSION) && is_array($_SESSION) ? $_SESSION : array());
            foreach ($input as $k => $v)
            {
                if (!in_array($k, $no_unset) && isset($GLOBALS[$k]))
                {
                    unset($GLOBALS[$k]);
                    unset($GLOBALS[$k]); // Double unset to circumvent the zend_hash_del_key_or_index hole in PHP <4.4.3 and <5.1.4
                }
            }
        }
        // Removes any "bad" characters (characters which mess with the display of a page, are invisible, etc) from user input
        function forum_remove_bad_characters()
        {
            global $bad_utf8_chars;
            $bad_utf8_chars = array("\0", "\xc2\xad", "\xcc\xb7", "\xcc\xb8", "\xe1\x85\x9F", "\xe1\x85\xA0", "\xe2\x80\x80", "\xe2\x80\x81", "\xe2\x80\x82", "\xe2\x80\x83", "\xe2\x80\x84", "\xe2\x80\x85", "\xe2\x80\x86", "\xe2\x80\x87", "\xe2\x80\x88", "\xe2\x80\x89", "\xe2\x80\x8a", "\xe2\x80\x8b", "\xe2\x80\x8e", "\xe2\x80\x8f", "\xe2\x80\xaa", "\xe2\x80\xab", "\xe2\x80\xac", "\xe2\x80\xad", "\xe2\x80\xae", "\xe2\x80\xaf", "\xe2\x81\x9f", "\xe3\x80\x80", "\xe3\x85\xa4", "\xef\xbb\xbf", "\xef\xbe\xa0", "\xef\xbf\xb9", "\xef\xbf\xba", "\xef\xbf\xbb", "\xE2\x80\x8D");
            function _forum_remove_bad_characters($array)
            {
                global $bad_utf8_chars;
                return is_array($array) ? array_map('_forum_remove_bad_characters', $array) : str_replace($bad_utf8_chars, '', $array);
            }

            $_GET = _forum_remove_bad_characters($_GET);
            $_POST = _forum_remove_bad_characters($_POST);
            $_COOKIE = _forum_remove_bad_characters($_COOKIE);
            $_REQUEST = _forum_remove_bad_characters($_REQUEST);
        }
        // DEBUG FUNCTIONS BELOW
        // Display executed queries (if enabled)
        function display_saved_queries()
        {
            global $db, $lang_common;
            // Get the queries so that we can print them out
            $saved_queries = $db->get_saved_queries();

            ?>

<div id="debug" class="blocktable">
	<h2><span><?php echo $lang_common['Debug table'] ?></span></h2>
	<div class="box">
		<div class="inbox">
			<table cellspacing="0">
			<thead>
				<tr>
					<th class="tcl" scope="col"><?php echo $lang_common['Query times'] ?></th>
					<th class="tcr" scope="col"><?php echo $lang_common['Query'] ?></th>
				</tr>
			</thead>
			<tbody>
<?php

            $query_time_total = 0.0;
            foreach ($saved_queries as $cur_query)
            {
                $query_time_total += $cur_query[1];

                ?>
				<tr>
					<td class="tcl"><?php echo ($cur_query[1] != 0) ? $cur_query[1] : '&nbsp;' ?></td>
					<td class="tcr"><?php echo pun_htmlspecialchars($cur_query[0]) ?></td>
				</tr>
<?php

            }

            ?>
				<tr>
					<td class="tcl" colspan="2"><?php printf($lang_common['Total query time'], $query_time_total . ' s') ?></td>
				</tr>
			</tbody>
			</table>
		</div>
	</div>
</div>
<?php

        }
        // Dump contents of variable(s)
        function dump()
        {
            echo '<pre>';
            $num_args = func_num_args();
            for ($i = 0; $i < $num_args; ++$i)
            {
                print_r(func_get_arg($i));
                echo "\n\n";
            }

            echo '</pre>';
            exit;
        }