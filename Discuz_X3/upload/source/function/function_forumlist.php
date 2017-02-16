<?php



if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

function forum(&$forum) {
	global $_G;
	$lastvisit = $_G['member']['lastvisit'];
	if(!$forum['viewperm'] || ($forum['viewperm'] && forumperm($forum['viewperm'])) || !empty($forum['allowview']) || (isset($forum['users']) && strstr($forum['users'], "\t$_G[uid]\t"))) {
		$forum['permission'] = 2;
	} elseif(!$_G['setting']['hideprivate']) {
		$forum['permission'] = 1;
	} else {
		return FALSE;
	}

	if($forum['icon']) {
		$forum['icon'] = get_forumimg($forum['icon']);
		$forum['icon'] = '<a href="forum.php?mod=forumdisplay&fid='.$forum['fid'].'"><img src="'.$forum['icon'].'" align="left" alt="" /></a>';
	}

	$lastpost = array(0, 0, '', '');

	$forum['lastpost'] = is_string($forum['lastpost']) ? explode("\t", $forum['lastpost']) : $forum['lastpost'];

	$forum['lastpost'] =count($forum['lastpost']) != 4 ? $lastpost : $forum['lastpost'];

	list($lastpost['tid'], $lastpost['subject'], $lastpost['dateline'], $lastpost['author']) = $forum['lastpost'];
	$thisforumlastvisit = array();
	if($_G['cookie']['forum_lastvisit']) {
		preg_match("/D\_".$forum['fid']."\_(\d+)/", $_G['cookie']['forum_lastvisit'], $thisforumlastvisit);
	}

	$forum['folder'] = ($thisforumlastvisit && $thisforumlastvisit[1] > $lastvisit ? $thisforumlastvisit[1] : $lastvisit) < $lastpost['dateline'] ? ' class="new"' : '';

	if($lastpost['tid']) {
		$lastpost['dateline'] = dgmdate($lastpost['dateline'], 'u');
		$lastpost['authorusername'] = $lastpost['author'];
		if($lastpost['author']) {
			$lastpost['author'] = '<a href="home.php?mod=space&username='.rawurlencode($lastpost['author']).'">'.$lastpost['author'].'</a>';
		}
		$forum['lastpost'] = $lastpost;
	} else {
		$forum['lastpost'] = $lastpost['authorusername'] = '';
	}

	$forum['moderators'] = moddisplay($forum['moderators'], $_G['setting']['moddisplay'], !empty($forum['inheritedmod']));

	if(isset($forum['subforums'])) {
		$forum['subforums'] = implode(', ', $forum['subforums']);
	}

	return TRUE;
}

function moddisplay($moderators, $type, $inherit = 0) {
    if($moderators) {
        $modlist = $comma = '';
        foreach(explode("\t", $moderators) as $moderator) {
            $modlist .= $comma.'<a href="home.php?mod=space&username='.rawurlencode($moderator).'" class="notabs" c="1">'.($inherit ? '<strong>'.$moderator.'</strong>' : $moderator).'</a>';
            $comma = ', ';
        }
    } else {
        $modlist = '';
    }
    return $modlist;
}

function set_rssauth() {
    global $_G;
    if($_G['setting']['rssstatus'] && $_G['uid']) {
        $auth = authcode($_G['uid']."\t".($_G['fid'] ? $_G['fid'] : '').
            "\t".substr(md5($_G['member']['password']), 0, 8), 'ENCODE', md5($_G['config']['security']['authkey']));
    } else {
        $auth = '0';
    }
    $_G['rssauth'] = rawurlencode($auth);
}