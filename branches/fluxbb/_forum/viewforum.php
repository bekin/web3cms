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
// Load the viewforum.php language file
require SHELL_PATH . 'lang/' . $pun_user['language'] . '/forum.php';
// Fetch some info about the forum
$db->setQuery('SELECT f.forum_name, f.redirect_url, f.moderators, f.num_topics, f.sort_by, fp.post_topics FROM ' . $db->db_prefix . 'forums AS f LEFT JOIN ' . $db->db_prefix . 'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id=' . $pun_user['g_id'] . ') WHERE (fp.read_forum IS NULL OR fp.read_forum=1) AND f.id=' . $id) or error('Unable to fetch forum info', __FILE__, __LINE__, $db->error());
if (!$db->num_rows())
    message($lang_common['Bad request']);

$cur_forum = $db->fetch_assoc();
// Is this a redirect forum? In that case, redirect!
if ($cur_forum['redirect_url'] != '')
{
    Yii::app()->request->redirect($cur_forum['redirect_url']);
}
// Sort out who the moderators are and if we are currently a moderator (or an admin)
$mods_array = array();
if ($cur_forum['moderators'] != '')
    $mods_array = unserialize($cur_forum['moderators']);

$is_admmod = ($pun_user['g_id'] == PUN_ADMIN || ($pun_user['g_moderator'] == '1' && array_key_exists($pun_user['username'], $mods_array))) ? true : false;
// Can we or can we not post new topics?
if (($cur_forum['post_topics'] == '' && $pun_user['g_post_topics'] == '1') || $cur_forum['post_topics'] == '1' || $is_admmod)
    $post_link = "\t\t" . '<p class="postlink conr">' . CHtml::link($lang_forum['Post topic'], array('forum/post', 'fid' => $id)) . '</p>' . "\n";
else
    $post_link = '';
// Get topic/forum tracking data
if (!$pun_user['is_guest'])
    $tracked_topics = get_tracked_topics();
// Determine the topic offset (based on $_GET['p'])
$num_pages = ceil($cur_forum['num_topics'] / $pun_user['disp_topics']);

$p = (!isset($_GET['p']) || $_GET['p'] <= 1 || $_GET['p'] > $num_pages) ? 1 : intval($_GET['p']);
$start_from = $pun_user['disp_topics'] * ($p - 1);
// Generate paging links
$paging_links = $lang_common['Pages'] . ': ' . paginate($num_pages, $p, 'viewforum.php?id=' . $id);

$page_title = pun_htmlspecialchars($pun_config['o_board_title'] . ' / ' . $cur_forum['forum_name']);
define('PUN_ALLOW_INDEX', 1);
require SHELL_PATH . 'header.php';

?>
<div class="linkst">
	<div class="inbox">
		<ul class="crumbs">
			<li><?php echo CHtml::link($lang_common['Index'], array('forum/'));?></li>
			<li>&raquo;&nbsp;<?php echo pun_htmlspecialchars($cur_forum['forum_name']) ?></li>
		</ul>
		<p class="pagelink conl"><?php echo $paging_links ?></p>
<?php echo $post_link ?>
		<div class="clearer"></div>
	</div>
</div>

<div id="vf" class="blocktable">
	<h2><span><?php echo pun_htmlspecialchars($cur_forum['forum_name']) ?></span></h2>
	<div class="box">
		<div class="inbox">
			<table cellspacing="0">
			<thead>
				<tr>
					<th class="tcl" scope="col"><?php echo $lang_common['Topic'] ?></th>
					<th class="tc2" scope="col"><?php echo $lang_common['Replies'] ?></th>
<?php if ($pun_config['o_topic_views'] == '1'): ?>					<th class="tc3" scope="col"><?php echo $lang_forum['Views'] ?></th>
<?php endif; ?>					<th class="tcr" scope="col"><?php echo $lang_common['Last post'] ?></th>
				</tr>
			</thead>
			<tbody>
<?php
// Fetch list of topics to display on this page
if ($pun_user['is_guest'] || $pun_config['o_show_dot'] == '0')
{
    // Without "the dot"
    $sql = 'SELECT id, poster, subject, posted, last_post, last_post_id, last_poster, num_views, num_replies, closed, sticky, moved_to FROM ' . $db->db_prefix . 'topics WHERE forum_id=' . $id . ' ORDER BY sticky DESC, ' . (($cur_forum['sort_by'] == '1') ? 'posted' : 'last_post') . ' DESC LIMIT ' . $start_from . ', ' . $pun_user['disp_topics'];
}
else
{
    // With "the dot"
    switch ($db_type)
    {
        case 'mysql':
        case 'mysqli':
            $sql = 'SELECT p.poster_id AS has_posted, t.id, t.subject, t.poster, t.posted, t.last_post, t.last_post_id, t.last_poster, t.num_views, t.num_replies, t.closed, t.sticky, t.moved_to FROM ' . $db->db_prefix . 'topics AS t LEFT JOIN ' . $db->db_prefix . 'posts AS p ON t.id=p.topic_id AND p.poster_id=' . $pun_user['id'] . ' WHERE t.forum_id=' . $id . ' GROUP BY t.id ORDER BY sticky DESC, ' . (($cur_forum['sort_by'] == '1') ? 'posted' : 'last_post') . ' DESC LIMIT ' . $start_from . ', ' . $pun_user['disp_topics'];
            break;

        case 'sqlite':
            $sql = 'SELECT p.poster_id AS has_posted, t.id, t.subject, t.poster, t.posted, t.last_post, t.last_post_id, t.last_poster, t.num_views, t.num_replies, t.closed, t.sticky, t.moved_to FROM ' . $db->db_prefix . 'topics AS t LEFT JOIN ' . $db->db_prefix . 'posts AS p ON t.id=p.topic_id AND p.poster_id=' . $pun_user['id'] . ' WHERE t.id IN(SELECT id FROM ' . $db->db_prefix . 'topics WHERE forum_id=' . $id . ' ORDER BY sticky DESC, ' . (($cur_forum['sort_by'] == '1') ? 'posted' : 'last_post') . ' DESC LIMIT ' . $start_from . ', ' . $pun_user['disp_topics'] . ') GROUP BY t.id ORDER BY t.sticky DESC, t.last_post DESC';
            break;

        default:
            $sql = 'SELECT p.poster_id AS has_posted, t.id, t.subject, t.poster, t.posted, t.last_post, t.last_post_id, t.last_poster, t.num_views, t.num_replies, t.closed, t.sticky, t.moved_to FROM ' . $db->db_prefix . 'topics AS t LEFT JOIN ' . $db->db_prefix . 'posts AS p ON t.id=p.topic_id AND p.poster_id=' . $pun_user['id'] . ' WHERE t.forum_id=' . $id . ' GROUP BY t.id, t.subject, t.poster, t.posted, t.last_post, t.last_post_id, t.last_poster, t.num_views, t.num_replies, t.closed, t.sticky, t.moved_to, p.poster_id ORDER BY sticky DESC, ' . (($cur_forum['sort_by'] == '1') ? 'posted' : 'last_post') . ' DESC LIMIT ' . $start_from . ', ' . $pun_user['disp_topics'];
            break;
    }
}

$db->setQuery($sql) or error('Unable to fetch topic list', __FILE__, __LINE__, $db->error());
// If there are topics in this forum
if ($db->num_rows())
{
    while ($cur_topic = $db->fetch_assoc())
    {
        $icon_text = $lang_common['Normal icon'];
        $item_status = '';
        $icon_type = 'icon';

        if ($cur_topic['moved_to'] == null)
            $last_post = CHtml::link(format_time($cur_topic['last_post']), array('forum/viewtopic', 'pid' => $cur_topic['last_post_id'] . '#p' . $cur_topic['last_post_id'])) . '<span class="byuser">' . $lang_common['by'] . '&nbsp;' . pun_htmlspecialchars($cur_topic['last_poster']) . '</span>';
        else
            $last_post = '&nbsp;';

        if ($pun_config['o_censoring'] == '1')
            $cur_topic['subject'] = censor_words($cur_topic['subject']);

        if ($cur_topic['moved_to'] != 0)
            $subject = $lang_forum['Moved'] . ': ' . CHtml::link(pun_htmlspecialchars($cur_topic['subject']), array('forum/viewtopic', 'id' => $cur_topic['moved_to'])) . ' <span class="byuser">' . $lang_common['by'] . '&nbsp;' . pun_htmlspecialchars($cur_topic['poster']) . '</span>';
        else if ($cur_topic['closed'] == '0')
            $subject = CHtml::link(pun_htmlspecialchars($cur_topic['subject']), array('forum/viewtopic', 'id' => $cur_topic['id'])) . ' <span class="byuser">' . $lang_common['by'] . '&nbsp;' . pun_htmlspecialchars($cur_topic['poster']) . '</span>';
        else
        {
            $subject = CHtml::link(pun_htmlspecialchars($cur_topic['subject']), array('forum/viewtopic', 'id' => $cur_topic['id'])) . ' <span class="byuser">' . $lang_common['by'] . '&nbsp;' . pun_htmlspecialchars($cur_topic['poster']) . '</span>';
            $icon_text = $lang_common['Closed icon'];
            $item_status = 'iclosed';
        }

        if (!$pun_user['is_guest'] && $cur_topic['last_post'] > $pun_user['last_visit'] && (!isset($tracked_topics['topics'][$cur_topic['id']]) || $tracked_topics['topics'][$cur_topic['id']] < $cur_topic['last_post']) && (!isset($tracked_topics['forums'][$id]) || $tracked_topics['forums'][$id] < $cur_topic['last_post']))
        {
            $icon_text .= ' ' . $lang_common['New icon'];
            $item_status .= ' inew';
            $icon_type = 'icon inew';
            $subject = '<strong>' . $subject . '</strong>';
            $subject_new_posts = '<span class="newtext">[ ' . CHtml::link($lang_common['New posts'], array('forum/viewtopic', 'id' => $cur_topic['id'], 'action' => 'new'), array('title' => $lang_common['New posts info'])) . ']</span>';
        }
        else
            $subject_new_posts = null;
        // Should we display the dot or not? :)
        if (!$pun_user['is_guest'] && $pun_config['o_show_dot'] == '1')
        {
            if ($cur_topic['has_posted'] == $pun_user['id'])
            {
                $subject = '<strong class="ipost">&middot;&nbsp;</strong>' . $subject;
                $item_status .= ' iposted';
            }
        }

        if ($cur_topic['sticky'] == '1')
        {
            $subject = '<span class="stickytext">' . $lang_forum['Sticky'] . ': </span>' . $subject;
            $item_status .= ' isticky';
            $icon_text .= ' ' . $lang_forum['Sticky'];
        }

        $num_pages_topic = ceil(($cur_topic['num_replies'] + 1) / $pun_user['disp_posts']);

        if ($num_pages_topic > 1)
            $subject_multipage = '<span class="pagestext">[ ' . paginate($num_pages_topic, - 1, 'viewtopic.php?id=' . $cur_topic['id']) . ' ]</span>';
        else
            $subject_multipage = null;
        // Should we show the "New posts" and/or the multipage links?
        if (!empty($subject_new_posts) || !empty($subject_multipage))
        {
            $subject .= !empty($subject_new_posts) ? ' ' . $subject_new_posts : '';
            $subject .= !empty($subject_multipage) ? ' ' . $subject_multipage : '';
        }

        ?>
				<tr<?php if ($item_status != '') echo ' class="' . trim($item_status) . '"'; ?>>
					<td class="tcl">
						<div class="<?php echo $icon_type ?>"><div class="nosize"><?php echo trim($icon_text) ?></div></div>
						<div class="tclcon">
							<div>
								<?php echo $subject . "\n" ?>
							</div>
						</div>
					</td>
					<td class="tc2"><?php echo ($cur_topic['moved_to'] == null) ? forum_number_format($cur_topic['num_replies']) : '&nbsp;' ?></td>
<?php if ($pun_config['o_topic_views'] == '1'): ?>					<td class="tc3"><?php echo ($cur_topic['moved_to'] == null) ? forum_number_format($cur_topic['num_views']) : '&nbsp;' ?></td>
<?php endif; ?>					<td class="tcr"><?php echo $last_post ?></td>
				</tr>
<?php

    }
}
else
{
    $colspan = ($pun_config['o_topic_views'] == '1') ? 4 : 3;

    ?>
				<tr>
					<td class="tcl" colspan="<?php echo $colspan ?>"><?php echo $lang_forum['Empty forum'] ?></td>
				</tr>
<?php

}

?>
			</tbody>
			</table>
		</div>
	</div>
</div>

<div class="linksb">
	<div class="inbox">
		<p class="pagelink conl"><?php echo $paging_links ?></p>
<?php echo $post_link ?>
		<ul class="crumbs">
			<li><?php echo CHtml::link($lang_common['Index'], array('forum/'));?></li>
			<li>&raquo;&nbsp;<?php echo pun_htmlspecialchars($cur_forum['forum_name']) ?></li>
		</ul>
		<div class="clearer"></div>
	</div>
</div>
<?php

$forum_id = $id;
$footer_style = 'viewforum';
require SHELL_PATH . 'footer.php';