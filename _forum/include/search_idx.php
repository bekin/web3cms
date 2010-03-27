<?php
// The contents of this file are very much inspired by the file functions_search.php
// from the phpBB Group forum software phpBB2 (http://www.phpbb.com)
// Make sure no one attempts to run this script "directly"
if (!defined('PUN'))
    exit;
// "Cleans up" a text string and returns an array of unique words
// This function depends on the current locale setting
function split_words($text)
{
    global $_user;
    static $noise_match, $noise_replace, $stopwords;
    if (empty($noise_match)) {
        $noise_match = array('[quote', '[code', '[url', '[img', '[email', '[color', '[colour', 'quote]', 'code]', 'url]', 'img]', 'email]', 'color]', 'colour]', '^', '$', '&', '(', ')', '<', '>', '`', '\'', '"', '|', ',', '@', '_', '?', '%', '~', '+', '[', ']', '{', '}', ':', '\\', '/', '=', '#', ';', '!', '*');
        $noise_replace = array('', '', '', '', '', '', '', '', '', '', '', '', '', '', ' ', ' ', ' ', ' ', ' ', ' ', ' ', '', '', ' ', ' ', ' ', ' ', '', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', '' , ' ', ' ', ' ', ' ', ' ', ' ');
        $stopwords = (array)@file(SHELL_PATH . 'lang/' . $_user['language'] . '/stopwords.txt');
        $stopwords = array_map('_trim', $stopwords);
    }
    // Clean up
    $patterns[] = '#&[\#a-z0-9]+?;#i';
    $patterns[] = '#\b[\w]+:\/\/[a-z0-9\.\-]+(\/[a-z0-9\?\.%_\-\+=&\/~]+)?#';
    $patterns[] = '#\[\/?[a-z\*=\+\-]+(\:?[0-9a-z]+)?:[a-z0-9]{10,}(\:[a-z0-9]+)?=?.*?\]#';
    $text = preg_replace($patterns, ' ', ' ' . strtolower($text) . ' ');
    // Filter out junk
    $text = str_replace($noise_match, $noise_replace, $text);
    // Strip out extra whitespace between words
    $text = trim(preg_replace('#\s+#', ' ', $text));
    // Fill an array with all the words
    $words = explode(' ', $text);
    if (!empty($words)) {
        while (list($i, $word) = @each($words)) {
            $words[$i] = trim($word, '.');
            $num_chars = _strlen($word);
            if ($num_chars < 3 || $num_chars > 20 || in_array($word, $stopwords))
                unset($words[$i]);
        }
    }
    return array_unique($words);
}
// Updates the search index with the contents of $post_id (and $subject)
function update_search_index($mode, $post_id, $message, $subject = null)
{
    global $db;
    // Split old and new post/subject to obtain array of 'words'
    $words_message = split_words($message);
    $words_subject = ($subject) ? split_words($subject) : array();
    if ($mode == 'edit') {
        $db->setQuery('SELECT w.id, w.word, m.subject_match FROM forum_search_words AS w INNER JOIN forum_search_matches AS m ON w.id=m.word_id WHERE m.post_id=' . $post_id, true) or error('Unable to fetch search index words', __FILE__, __LINE__, $db->error());
        // Declare here to stop array_keys() and array_diff() from complaining if not set
        $cur_words['post'] = array();
        $cur_words['subject'] = array();
        while ($row = $db->fetch_row()) {
            $match_in = ($row[2]) ? 'subject' : 'post';
            $cur_words[$match_in][$row[1]] = $row[0];
        }
        $words['add']['post'] = array_diff($words_message, array_keys($cur_words['post']));
        $words['add']['subject'] = array_diff($words_subject, array_keys($cur_words['subject']));
        $words['del']['post'] = array_diff(array_keys($cur_words['post']), $words_message);
        $words['del']['subject'] = array_diff(array_keys($cur_words['subject']), $words_subject);
    }else {
        $words['add']['post'] = $words_message;
        $words['add']['subject'] = $words_subject;
        $words['del']['post'] = array();
        $words['del']['subject'] = array();
    }
    unset($words_message);
    unset($words_subject);
    // Get unique words from the above arrays
    $unique_words = array_unique(array_merge($words['add']['post'], $words['add']['subject']));
    if (!empty($unique_words)) {
        $db->setQuery('SELECT id, word FROM forum_search_words WHERE word IN(' . implode(',', preg_replace('#^(.*)$#', '\'\1\'', $unique_words)) . ')', true) or error('Unable to fetch search index words', __FILE__, __LINE__, $db->error());
        $word_ids = array();
        while ($row = $db->fetch_row())
        $word_ids[$row[1]] = $row[0];
        $new_words = array_diff($unique_words, array_keys($word_ids));
        unset($unique_words);
        if (!empty($new_words)) {
            switch ($db->type) {
                case 'mysql':
                case 'mysqli':
                case 'mysql_innodb':
                case 'mysqli_innodb':
                    $db->setQuery('INSERT INTO forum_search_words (word) VALUES' . implode(',', preg_replace('#^(.*)$#', '(\'\1\')', $new_words)))->execute() or error('Unable to insert search index words', __FILE__, __LINE__, $db->error());
                    break;
                default:
                    while (list(, $word) = @each($new_words))
                    $db->setQuery('INSERT INTO forum_search_words (word) VALUES(\'' . $word . '\')')->execute() or error('Unable to insert search index words', __FILE__, __LINE__, $db->error());
                    break;
            }
        }
        unset($new_words);
    }
    // Delete matches (only if editing a post)
    while (list($match_in, $wordlist) = @each($words['del'])) {
        $subject_match = ($match_in == 'subject') ? 1 : 0;
        if (!empty($wordlist)) {
            $sql = '';
            while (list(, $word) = @each($wordlist))
            $sql .= (($sql != '') ? ',' : '') . $cur_words[$match_in][$word];
            $db->setQuery('DELETE FROM forum_search_matches WHERE word_id IN(' . $sql . ') AND post_id=' . $post_id . ' AND subject_match=' . $subject_match)->execute() or error('Unable to delete search index word matches', __FILE__, __LINE__, $db->error());
        }
    }
    // Add new matches
    while (list($match_in, $wordlist) = @each($words['add'])) {
        $subject_match = ($match_in == 'subject') ? 1 : 0;
        if (!empty($wordlist))
            $db->setQuery('INSERT INTO forum_search_matches (post_id, word_id, subject_match) SELECT ' . $post_id . ', id, ' . $subject_match . ' FROM forum_search_words WHERE word IN(' . implode(',', preg_replace('#^(.*)$#', '\'\1\'', $wordlist)) . ')')->execute() or error('Unable to insert search index word matches', __FILE__, __LINE__, $db->error());
    }
    unset($words);
}
// Strip search index of indexed words in $post_ids
function strip_search_index($post_ids)
{
    global $db;
    switch ($db->type) {
        case 'mysql':
        case 'mysqli':
        case 'mysql_innodb':
        case 'mysqli_innodb': {
                $db->setQuery('SELECT word_id FROM forum_search_matches WHERE post_id IN(' . $post_ids . ') GROUP BY word_id') or error('Unable to fetch search index word match', __FILE__, __LINE__, $db->error());
                if ($db->num_rows()) {
                    $word_ids = '';
                    while ($row = $db->fetch_row())
                    $word_ids .= ($word_ids != '') ? ',' . $row[0] : $row[0];
                    $db->setQuery('SELECT word_id FROM forum_search_matches WHERE word_id IN(' . $word_ids . ') GROUP BY word_id HAVING COUNT(word_id)=1') or error('Unable to fetch search index word match', __FILE__, __LINE__, $db->error());
                    if ($db->num_rows()) {
                        $word_ids = '';
                        while ($row = $db->fetch_row())
                        $word_ids .= ($word_ids != '') ? ',' . $row[0] : $row[0];
                        $db->setQuery('DELETE FROM forum_search_words WHERE id IN(' . $word_ids . ')')->execute() or error('Unable to delete search index word', __FILE__, __LINE__, $db->error());
                    }
                }
                break;
            }
        default:
            $db->setQuery('DELETE FROM forum_search_words WHERE id IN(SELECT word_id FROM forum_search_matches WHERE word_id IN(SELECT word_id FROM forum_search_matches WHERE post_id IN(' . $post_ids . ') GROUP BY word_id) GROUP BY word_id HAVING COUNT(word_id)=1)')->execute() or error('Unable to delete from search index', __FILE__, __LINE__, $db->error());
            break;
    }
    $db->setQuery('DELETE FROM forum_search_matches WHERE post_id IN(' . $post_ids . ')')->execute() or error('Unable to delete search index word match', __FILE__, __LINE__, $db->error());
}