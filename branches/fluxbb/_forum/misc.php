<?php

/*---

	Copyright (C) 2008-2009 FluxBB.org
	based on code copyright (C) 2002-2005 Rickard Andersson
	License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher

---*/

if (isset($_GET['action']))
    define('PUN_QUIET_VISIT', 1);

require SHELL_PATH . 'include/common.php';
// Load the misc.php language file
require SHELL_PATH . 'lang/' . $pun_user['language'] . '/misc.php';

$action = isset($_GET['action']) ? $_GET['action'] : null;

if ($action == 'rules')
{
    if ($pun_config['o_rules'] == '0' || ($pun_user['is_guest'] && $pun_user['g_read_board'] == '0' && $pun_config['o_regs_allow'] == '0'))
        message($lang_common['Bad request']);
    // Load the register.php language file
    require SHELL_PATH . 'lang/' . $pun_user['language'] . '/register.php';

    $page_title = pun_htmlspecialchars($pun_config['o_board_title']) . ' / ' . $lang_register['Forum rules'];
    require SHELL_PATH . 'header.php';

    ?>
<div class="block">
	<h2><span><?php echo $lang_register['Forum rules'] ?></span></h2>
	<div class="box">
		<div id="rules-block" class="inbox">
			<div class="usercontent"><?php echo $pun_config['o_rules_message'] ?></div>
		</div>
	</div>
</div>
<?php

    require SHELL_PATH . 'footer.php';
}

else if ($action == 'markread')
{
    if ($pun_user['is_guest'])
        message($lang_common['No permission']);

    $db->setQuery('UPDATE ' . $db->tablePrefix . 'users SET last_visit=' . $pun_user['logged'] . ' WHERE id=' . $pun_user['id']) or error('Unable to update user last visit data', __FILE__, __LINE__, $db->error());
    // Reset tracked topics
    set_tracked_topics(null);

    redirect('index.php', $lang_misc['Mark read redirect']);
}
// Mark the topics/posts in a forum as read?
else if ($action == 'markforumread')
{
    if ($pun_user['is_guest'])
        message($lang_common['No permission']);

    $fid = isset($_GET['fid']) ? intval($_GET['fid']) : 0;
    if ($fid < 1)
        message($lang_common['Bad request']);

    $tracked_topics = get_tracked_topics();
    $tracked_topics['forums'][$fid] = time();
    set_tracked_topics($tracked_topics);

    redirect('viewforum.php?id=' . $fid, $lang_misc['Mark forum read redirect']);
}

else if (isset($_GET['email']))
{
    if ($pun_user['is_guest'] || $pun_user['g_send_email'] == '0')
        message($lang_common['No permission']);

    $recipient_id = intval($_GET['email']);
    if ($recipient_id < 2)
        message($lang_common['Bad request']);

    $db->setQuery('SELECT username, email, email_setting FROM ' . $db->tablePrefix . 'users WHERE id=' . $recipient_id) or error('Unable to fetch user info', __FILE__, __LINE__, $db->error());
    if (!$db->num_rows())
        message($lang_common['Bad request']);

    list($recipient, $recipient_email, $email_setting) = $db->fetch_row();

    if ($email_setting == 2 && !$pun_user['is_admmod'])
        message($lang_misc['Form email disabled']);

    if (isset($_POST['form_sent']))
    {
        // Clean up message and subject from POST
        $subject = pun_trim($_POST['req_subject']);
        $message = pun_trim($_POST['req_message']);

        if ($subject == '')
            message($lang_misc['No email subject']);
        else if ($message == '')
            message($lang_misc['No email message']);
        else if (strlen($message) > 65535)
            message($lang_misc['Too long email message']);

        if ($pun_user['last_email_sent'] != '' && (time() - $pun_user['last_email_sent']) < $pun_user['g_email_flood'] && (time() - $pun_user['last_email_sent']) >= 0)
            message(sprintf($lang_misc['Email flood'], $pun_user['g_email_flood']));
        // Load the "form email" template
        $mail_tpl = trim(file_get_contents(SHELL_PATH . 'lang/' . $pun_user['language'] . '/mail_templates/form_email.tpl'));
        // The first row contains the subject
        $first_crlf = strpos($mail_tpl, "\n");
        $mail_subject = trim(substr($mail_tpl, 8, $first_crlf - 8));
        $mail_message = trim(substr($mail_tpl, $first_crlf));

        $mail_subject = str_replace('<mail_subject>', $subject, $mail_subject);
        $mail_message = str_replace('<sender>', $pun_user['username'], $mail_message);
        $mail_message = str_replace('<board_title>', $pun_config['o_board_title'], $mail_message);
        $mail_message = str_replace('<mail_message>', $message, $mail_message);
        $mail_message = str_replace('<board_mailer>', $pun_config['o_board_title'] . ' ' . $lang_common['Mailer'], $mail_message);

        require_once SHELL_PATH . 'include/email.php';

        pun_mail($recipient_email, $mail_subject, $mail_message, $pun_user['email'], $pun_user['username']);

        $db->setQuery('UPDATE ' . $db->tablePrefix . 'users SET last_email_sent=' . time() . ' WHERE id=' . $pun_user['id']) or error('Unable to update user', __FILE__, __LINE__, $db->error());

        redirect(htmlspecialchars($_POST['redirect_url']), $lang_misc['Email sent redirect']);
    }
    // Try to determine if the data in HTTP_REFERER is valid (if not, we redirect to the users profile after the email is sent)
    $redirect_url = (isset($_SERVER['HTTP_REFERER']) && preg_match('#^' . preg_quote($pun_config['o_WEB_PATH']) . '/(.*?)\.php#i', $_SERVER['HTTP_REFERER'])) ? htmlspecialchars($_SERVER['HTTP_REFERER']) : 'index.php';

    $page_title = pun_htmlspecialchars($pun_config['o_board_title']) . ' / ' . $lang_misc['Send email to'] . ' ' . pun_htmlspecialchars($recipient);
    $required_fields = array('req_subject' => $lang_misc['Email subject'], 'req_message' => $lang_misc['Email message']);
    $focus_element = array('email', 'req_subject');
    require SHELL_PATH . 'header.php';

    ?>
<div class="blockform">
	<h2><span><?php echo $lang_misc['Send email to'] ?> <?php echo pun_htmlspecialchars($recipient) ?></span></h2>
	<div class="box">
		<?php echo CHtml::form(array('misc','email'=>$recipient_id), 'POST', array('id'=>'email','onsubmit'=>'this.submit.disabled=true;if(process_form(this)){return true;}else{this.submit.disabled=false;return false;}'));?>
			<div class="inform">
				<fieldset>
					<legend><?php echo $lang_misc['Write email'] ?></legend>
					<div class="infldset txtarea">
						<input type="hidden" name="form_sent" value="1" />
						<input type="hidden" name="redirect_url" value="<?php echo $redirect_url ?>" />
						<label><strong><?php echo $lang_misc['Email subject'] ?></strong><br />
						<input class="longinput" type="text" name="req_subject" size="75" maxlength="70" tabindex="1" /><br /></label>
						<label><strong><?php echo $lang_misc['Email message'] ?></strong><br />
						<textarea name="req_message" rows="10" cols="75" tabindex="2"></textarea><br /></label>
						<p><?php echo $lang_misc['Email disclosure note'] ?></p>
					</div>
				</fieldset>
			</div>
			<p class="buttons"><input type="submit" name="submit" value="<?php echo $lang_common['Submit'] ?>" tabindex="3" accesskey="s" />
			<?php echo CHtml::link($lang_common['Go back'], 'javascript:history.go(-1);');?></p>
		</form>
	</div>
</div>
<?php

    require SHELL_PATH . 'footer.php';
}

else if (isset($_GET['report']))
{
    if ($pun_user['is_guest'])
        message($lang_common['No permission']);

    $post_id = intval($_GET['report']);
    if ($post_id < 1)
        message($lang_common['Bad request']);

    if (isset($_POST['form_sent']))
    {
        // Clean up reason from POST
        $reason = pun_linebreaks(pun_trim($_POST['req_reason']));
        if ($reason == '')
            message($lang_misc['No reason']);

        if ($pun_user['last_email_sent'] != '' && (time() - $pun_user['last_email_sent']) < $pun_user['g_email_flood'] && (time() - $pun_user['last_email_sent']) >= 0)
            message(sprintf($lang_misc['Report flood'], $pun_user['g_email_flood']));
        // Get the topic ID
        $db->setQuery('SELECT topic_id FROM ' . $db->tablePrefix . 'posts WHERE id=' . $post_id) or error('Unable to fetch post info', __FILE__, __LINE__, $db->error());
        if (!$db->num_rows())
            message($lang_common['Bad request']);

        $topic_id = $db->result($result);
        // Get the subject and forum ID
        $db->setQuery('SELECT subject, forum_id FROM ' . $db->tablePrefix . 'topics WHERE id=' . $topic_id) or error('Unable to fetch topic info', __FILE__, __LINE__, $db->error());
        if (!$db->num_rows())
            message($lang_common['Bad request']);

        list($subject, $forum_id) = $db->fetch_row();
        // Should we use the internal report handling?
        if ($pun_config['o_report_method'] == 0 || $pun_config['o_report_method'] == 2)
            $db->setQuery('INSERT INTO ' . $db->tablePrefix . 'reports (post_id, topic_id, forum_id, reported_by, created, message) VALUES(' . $post_id . ', ' . $topic_id . ', ' . $forum_id . ', ' . $pun_user['id'] . ', ' . time() . ', \'' . $db->escape($reason) . '\')') or error('Unable to create report', __FILE__, __LINE__, $db->error());
        // Should we email the report?
        if ($pun_config['o_report_method'] == 1 || $pun_config['o_report_method'] == 2)
        {
            // We send it to the complete mailing-list in one swoop
            if ($pun_config['o_mailing_list'] != '')
            {
                $mail_subject = sprintf($lang_common['Report notification'], $forum_id, $subject);
                $mail_message = sprintf($lang_common['Report message 1'], $pun_user['username'], $pun_config['o_WEB_PATH'] . '/viewtopic.php?pid=' . $post_id . '#p' . $post_id) . "\n";
                $mail_message .= sprintf($lang_common['Report message 2'], $reason) . "\n";
                $mail_message .= "\n" . '--' . "\n" . $lang_common['Email signature'];

                require SHELL_PATH . 'include/email.php';

                pun_mail($pun_config['o_mailing_list'], $mail_subject, $mail_message);
            }
        }

        $db->setQuery('UPDATE ' . $db->tablePrefix . 'users SET last_email_sent=' . time() . ' WHERE id=' . $pun_user['id']) or error('Unable to update user', __FILE__, __LINE__, $db->error());

        redirect('viewtopic.php?pid=' . $post_id . '#p' . $post_id, $lang_misc['Report redirect']);
    }

    $page_title = pun_htmlspecialchars($pun_config['o_board_title']) . ' / ' . $lang_misc['Report post'];
    $required_fields = array('req_reason' => $lang_misc['Reason']);
    $focus_element = array('report', 'req_reason');
    require SHELL_PATH . 'header.php';

    ?>
<div class="blockform">
	<h2><span><?php echo $lang_misc['Report post'] ?></span></h2>
	<div class="box">
		<?php echo CHtml::form(array('misc','report'=> $post_id), 'POST', array('id'=>'report','onsubmit'=>'this.submit.disabled=true;if(process_form(this)){return true;}else{this.submit.disabled=false;return false;}'));?>
			<div class="inform">
				<fieldset>
					<legend><?php echo $lang_misc['Reason desc'] ?></legend>
					<div class="infldset txtarea">
						<input type="hidden" name="form_sent" value="1" />
						<label><strong><?php echo $lang_misc['Reason'] ?></strong><br /><textarea name="req_reason" rows="5" cols="60"></textarea><br /></label>
					</div>
				</fieldset>
			</div>
			<p class="buttons"><input type="submit" name="submit" value="<?php echo $lang_common['Submit'] ?>" accesskey="s" />
			<?php echo CHtml::link($lang_common['Go back'], 'javascript:history.go(-1)');?></p>
		</form>
	</div>
</div>
<?php

    require SHELL_PATH . 'footer.php';
}

else if (isset($_GET['subscribe']))
{
    if ($pun_user['is_guest'] || $pun_config['o_subscriptions'] != '1')
        message($lang_common['No permission']);

    $topic_id = intval($_GET['subscribe']);
    if ($topic_id < 1)
        message($lang_common['Bad request']);
    // Make sure the user can view the topic
    $db->setQuery('SELECT 1 FROM ' . $db->tablePrefix . 'topics AS t LEFT JOIN ' . $db->tablePrefix . 'forum_perms AS fp ON (fp.forum_id=t.forum_id AND fp.group_id=' . $pun_user['g_id'] . ') WHERE (fp.read_forum IS NULL OR fp.read_forum=1) AND t.id=' . $topic_id . ' AND t.moved_to IS NULL') or error('Unable to fetch topic info', __FILE__, __LINE__, $db->error());
    if (!$db->num_rows())
        message($lang_common['Bad request']);

    $db->setQuery('SELECT 1 FROM ' . $db->tablePrefix . 'subscriptions WHERE user_id=' . $pun_user['id'] . ' AND topic_id=' . $topic_id) or error('Unable to fetch subscription info', __FILE__, __LINE__, $db->error());
    if ($db->num_rows())
        message($lang_misc['Already subscribed']);

    $db->setQuery('INSERT INTO ' . $db->tablePrefix . 'subscriptions (user_id, topic_id) VALUES(' . $pun_user['id'] . ' ,' . $topic_id . ')') or error('Unable to add subscription', __FILE__, __LINE__, $db->error());

    redirect('viewtopic.php?id=' . $topic_id, $lang_misc['Subscribe redirect']);
}

else if (isset($_GET['unsubscribe']))
{
    if ($pun_user['is_guest'] || $pun_config['o_subscriptions'] != '1')
        message($lang_common['No permission']);

    $topic_id = intval($_GET['unsubscribe']);
    if ($topic_id < 1)
        message($lang_common['Bad request']);

    $db->setQuery('SELECT 1 FROM ' . $db->tablePrefix . 'subscriptions WHERE user_id=' . $pun_user['id'] . ' AND topic_id=' . $topic_id) or error('Unable to fetch subscription info', __FILE__, __LINE__, $db->error());
    if (!$db->num_rows())
        message($lang_misc['Not subscribed']);

    $db->setQuery('DELETE FROM ' . $db->tablePrefix . 'subscriptions WHERE user_id=' . $pun_user['id'] . ' AND topic_id=' . $topic_id) or error('Unable to remove subscription', __FILE__, __LINE__, $db->error());

    redirect('viewtopic.php?id=' . $topic_id, $lang_misc['Unsubscribe redirect']);
}

else
    message($lang_common['Bad request']);