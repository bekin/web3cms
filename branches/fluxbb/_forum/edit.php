<?php

/*---

	Copyright (C) 2008-2009 FluxBB.org
	based on code copyright (C) 2002-2005 Rickard Andersson
	License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher

---*/

require SHELL_PATH . 'include/common.php';

if ($pun_user['g_read_board'] == '0')
    message($lang_common['No view']);

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id < 1)
    message($lang_common['Bad request']);
// Fetch some info about the post, the topic and the forum
$db->setQuery('SELECT f.id AS fid, f.forum_name, f.moderators, f.redirect_url, fp.post_replies, fp.post_topics, t.id AS tid, t.subject, t.posted, t.first_post_id, t.closed, p.poster, p.poster_id, p.message, p.hide_smilies FROM ' . $db->db_prefix . 'posts AS p INNER JOIN ' . $db->db_prefix . 'topics AS t ON t.id=p.topic_id INNER JOIN ' . $db->db_prefix . 'forums AS f ON f.id=t.forum_id LEFT JOIN ' . $db->db_prefix . 'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id=' . $pun_user['g_id'] . ') WHERE (fp.read_forum IS NULL OR fp.read_forum=1) AND p.id=' . $id) or error('Unable to fetch post info', __FILE__, __LINE__, $db->error());
if (!$db->num_rows())
    message($lang_common['Bad request']);

$cur_post = $db->fetch_assoc();
// Sort out who the moderators are and if we are currently a moderator (or an admin)
$mods_array = ($cur_post['moderators'] != '') ? unserialize($cur_post['moderators']) : array();
$is_admmod = ($pun_user['g_id'] == PUN_ADMIN || ($pun_user['g_moderator'] == '1' && array_key_exists($pun_user['username'], $mods_array))) ? true : false;

$can_edit_subject = $id == $cur_post['first_post_id'];
// Do we have permission to edit this post?
if (($pun_user['g_edit_posts'] == '0' || $cur_post['poster_id'] != $pun_user['id'] || $cur_post['closed'] == '1') && !$is_admmod)
    message($lang_common['No permission']);
// Load the post.php/edit.php language file
require SHELL_PATH . 'lang/' . $pun_user['language'] . '/post.php';
// Start with a clean slate
$errors = array();

if (isset($_POST['form_sent']))
{
    if ($is_admmod)
        confirm_referrer('edit.php');
    // If it's a topic it must contain a subject
    if ($can_edit_subject)
    {
        $subject = pun_trim($_POST['req_subject']);

        if ($subject == '')
            $errors[] = $lang_post['No subject'];
        else if (pun_strlen($subject) > 70)
            $errors[] = $lang_post['Too long subject'];
        else if ($pun_config['p_subject_all_caps'] == '0' && is_all_uppercase($subject) && !$pun_user['is_admmod'])
            $errors[] = $lang_post['All caps subject'];
    }
    // Clean up message from POST
    $message = pun_linebreaks(pun_trim($_POST['req_message']));

    if ($message == '')
        $errors[] = $lang_post['No message'];
    else if (strlen($message) > 65535)
        $errors[] = $lang_post['Too long message'];
    else if ($pun_config['p_message_all_caps'] == '0' && is_all_uppercase($message) && !$pun_user['is_admmod'])
        $errors[] = $lang_post['All caps message'];
    // Validate BBCode syntax
    if ($pun_config['p_message_bbcode'] == '1')
    {
        require SHELL_PATH . 'include/parser.php';
        $message = preparse_bbcode($message, $errors);
    }

    $hide_smilies = isset($_POST['hide_smilies']) ? '1' : '0';
    // Did everything go according to plan?
    if (empty($errors) && !isset($_POST['preview']))
    {
        $edited_sql = (!isset($_POST['silent']) || !$is_admmod) ? $edited_sql = ', edited=' . time() . ', edited_by=\'' . $db->escape($pun_user['username']) . '\'' : '';

        require SHELL_PATH . 'include/search_idx.php';

        if ($can_edit_subject)
        {
            // Update the topic and any redirect topics
            $db->setQuery('UPDATE ' . $db->db_prefix . 'topics SET subject=\'' . $db->escape($subject) . '\' WHERE id=' . $cur_post['tid'] . ' OR moved_to=' . $cur_post['tid']) or error('Unable to update topic', __FILE__, __LINE__, $db->error());
            // We changed the subject, so we need to take that into account when we update the search words
            update_search_index('edit', $id, $message, $subject);
        }
        else
            update_search_index('edit', $id, $message);
        // Update the post
        $db->setQuery('UPDATE ' . $db->db_prefix . 'posts SET message=\'' . $db->escape($message) . '\', hide_smilies=' . $hide_smilies . $edited_sql . ' WHERE id=' . $id) or error('Unable to update post', __FILE__, __LINE__, $db->error());

        redirect('viewtopic.php?pid=' . $id . '#p' . $id, $lang_post['Edit redirect']);
    }
}

$page_title = pun_htmlspecialchars($pun_config['o_board_title']) . ' / ' . $lang_post['Edit post'];
$required_fields = array('req_subject' => $lang_common['Subject'], 'req_message' => $lang_common['Message']);
$focus_element = array('edit', 'req_message');
require SHELL_PATH . 'header.php';

$cur_index = 1;

?>
<div class="linkst">
	<div class="inbox">
		<ul class="crumbs">
			<li><?php echo CHtml::link($lang_common['Index'], array('forum/'));?></li>
			<li>&raquo;&nbsp;<?php
echo CHtml::link(pun_htmlspecialchars($cur_post['forum_name']), array('forum/viewforum', 'id' => $cur_post['fid']));
?></li>
			<li>&raquo;&nbsp;<?php echo pun_htmlspecialchars($cur_post['subject']) ?></li>
		</ul>
	</div>
</div>

<?php
// If there are errors, we display them
if (!empty($errors))
{?>
<div id="posterror" class="block">
	<h2><span><?php echo $lang_post['Post errors'] ?></span></h2>
	<div class="box">
		<div class="inbox">
			<p><?php echo $lang_post['Post errors info'] ?></p>
			<ul>
<?php

    while (list(, $cur_error) = each($errors))
    echo "\t\t\t\t" . '<li><strong>' . $cur_error . '</strong></li>' . "\n";

    ?>
			</ul>
		</div>
	</div>
</div>

<?php

}
else if (isset($_POST['preview']))
{
    require_once SHELL_PATH . 'include/parser.php';
    $preview_message = parse_message($message, $hide_smilies);

    ?>
<div id="postpreview" class="blockpost">
	<h2><span><?php echo $lang_post['Post preview'] ?></span></h2>
	<div class="box">
		<div class="inbox">
			<div class="postbody">
				<div class="postright">
					<div class="postmsg">
						<?php echo $preview_message . "\n" ?>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<?php

}

?>
<div class="blockform">
	<h2><span><?php echo $lang_post['Edit post'] ?></span></h2>
	<div class="box">
		<?php echo CHtml::form(array('edit','id'=>$id,'action'=>'edit'), 'POST', array('id'=>'edit','onsubmit'=>'return process_form(this);'));?>
			<div class="inform">
				<fieldset>
					<legend><?php echo $lang_post['Edit post legend'] ?></legend>
					<input type="hidden" name="form_sent" value="1" />
					<div class="infldset txtarea">
<?php if ($can_edit_subject): ?>						<label><?php echo $lang_common['Subject'] ?><br />
						<input class="longinput" type="text" name="req_subject" size="80" maxlength="70" tabindex="<?php echo $cur_index++ ?>" value="<?php echo pun_htmlspecialchars(isset($_POST['req_subject']) ? $_POST['req_subject'] : $cur_post['subject']) ?>" /><br /></label>
<?php endif; ?>						<label><?php echo $lang_common['Message'] ?><br />
						<textarea name="req_message" rows="20" cols="95" tabindex="<?php echo $cur_index++ ?>"><?php echo pun_htmlspecialchars(isset($_POST['req_message']) ? $message : $cur_post['message']) ?></textarea><br /></label>
						<ul class="bblinks">
							<li>
							<?php echo CHtml::link($lang_common['BBCode'], array('forum/help#bbcode'), array('onclick' => 'window.open(this.href); return false;'));?>: <?php echo ($pun_config['p_message_bbcode'] == '1') ? $lang_common['on'] : $lang_common['off']; ?></li>
							<li>
							<?php echo CHtml::link($lang_common['img tag'], array('forum/help#img'), array('onclick' => 'window.open(this.href); return false;'));?>: <?php echo ($pun_config['p_message_img_tag'] == '1') ? $lang_common['on'] : $lang_common['off']; ?></li>
							<li><?php echo CHtml::link($lang_common['Smilies'], array('forum/help#smilies'), array('onclick' => 'window.open(this.href); return false;'));?>: <?php echo ($pun_config['o_smilies'] == '1') ? $lang_common['on'] : $lang_common['off']; ?></li>
						</ul>
					</div>
				</fieldset>
<?php

$checkboxes = array();
if ($pun_config['o_smilies'] == '1')
{
    if (isset($_POST['hide_smilies']) || $cur_post['hide_smilies'] == '1')
        $checkboxes[] = '<label><input type="checkbox" name="hide_smilies" value="1" checked="checked" tabindex="' . ($cur_index++) . '" />&nbsp;' . $lang_post['Hide smilies'];
    else
        $checkboxes[] = '<label><input type="checkbox" name="hide_smilies" value="1" tabindex="' . ($cur_index++) . '" />&nbsp;' . $lang_post['Hide smilies'];
}

if ($is_admmod)
{
    if ((isset($_POST['form_sent']) && isset($_POST['silent'])) || !isset($_POST['form_sent']))
        $checkboxes[] = '<label><input type="checkbox" name="silent" value="1" tabindex="' . ($cur_index++) . '" checked="checked" />&nbsp;' . $lang_post['Silent edit'];
    else
        $checkboxes[] = '<label><input type="checkbox" name="silent" value="1" tabindex="' . ($cur_index++) . '" />&nbsp;' . $lang_post['Silent edit'];
}

if (!empty($checkboxes))
{?>
			</div>
			<div class="inform">
				<fieldset>
					<legend><?php echo $lang_common['Options'] ?></legend>
					<div class="infldset">
						<div class="rbox">
							<?php echo implode('</label>' . "\n\t\t\t\t\t\t\t", $checkboxes) . '</label>' . "\n" ?>
						</div>
					</div>
				</fieldset>
<?php

}

?>
			</div>
			<p class="buttons"><input type="submit" name="submit" value="<?php echo $lang_common['Submit'] ?>" tabindex="<?php echo $cur_index++ ?>" accesskey="s" /> <input type="submit" name="preview" value="<?php echo $lang_post['Preview'] ?>" tabindex="<?php echo $cur_index++ ?>" accesskey="p" />
			<?php echo CHtml::link($lang_common['Go back'], 'javascript:history.go(-1);');?></p>
		</form>
	</div>
</div>
<?php

require SHELL_PATH . 'footer.php';