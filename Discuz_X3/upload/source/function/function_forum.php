<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: function_forum.php 33660 2013-07-29 07:51:05Z nemohou $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

function loadforum($fid = null, $tid = null) {
	global $_G;
	$tid = intval(isset($tid) ? $tid : getgpc('tid'));
	if(isset($fid)) {
		$fid = intval($fid);
	} else {
		$fid = getgpc('fid');
		if(!$fid && getgpc('gid')) {
			$fid = intval(getgpc('gid'));
		}
	}
	if(isset($_G['forum']['fid']) && $_G['forum']['fid'] == $fid || isset($_G['thread']['tid']) && $_G['thread']['tid'] == $tid){
		return null;
	}
	if(!empty($_GET['archiver'])) {//X1.5的Archiver兼容
		if($fid) {
			dheader('location: archiver/?fid-'.$fid.'.html');
		} elseif($tid) {
			dheader('location: archiver/?tid-'.$tid.'.html');
		} else {
			dheader('location: archiver/');
		}
	}
	if(defined('IN_ARCHIVER') && $_G['setting']['archiverredirect'] && !IS_ROBOT) {
		dheader('location: ../forum.php'.($_G['mod'] ? '?mod='.$_G['mod'].(!empty($_GET['fid']) ? '&fid='.$_GET['fid'] : (!empty($_GET['tid']) ? '&tid='.$_GET['tid'] : '')) : ''));
	}
	if($_G['setting']['forumpicstyle']) {
		$_G['setting']['forumpicstyle'] = dunserialize($_G['setting']['forumpicstyle']);
		empty($_G['setting']['forumpicstyle']['thumbwidth']) && $_G['setting']['forumpicstyle']['thumbwidth'] = 203;
		empty($_G['setting']['forumpicstyle']['thumbheight']) && $_G['setting']['forumpicstyle']['thumbheight'] = 999;
	} else {
		$_G['setting']['forumpicstyle'] = array('thumbwidth' => 203, 'thumbheight' => 999);
	}
	if($fid) {
		$fid = is_numeric($fid) ? intval($fid) : (!empty($_G['setting']['forumfids'][$fid]) ? $_G['setting']['forumfids'][$fid] : 0);
	}

	$modthreadkey = isset($_GET['modthreadkey']) && $_GET['modthreadkey'] == modauthkey($tid) ? $_GET['modthreadkey'] : '';
	$_G['forum_auditstatuson'] = $modthreadkey ? true : false;

	$metadescription = $hookscriptmessage = '';
	$adminid = $_G['adminid'];

	if(!empty($tid) || !empty($fid)) {

		if(!empty ($tid)) {
			$archiveid = !empty($_GET['archiveid']) ? intval($_GET['archiveid']) : null;
			$_G['thread'] = get_thread_by_tid($tid, $archiveid);
			$_G['thread']['allreplies'] = $_G['thread']['replies'] + $_G['thread']['comments'];
			if(!$_G['forum_auditstatuson'] && !empty($_G['thread'])
					&& !($_G['thread']['displayorder'] >= 0 || (in_array($_G['thread']['displayorder'], array(-4,-3,-2)) && $_G['uid'] && $_G['thread']['authorid'] == $_G['uid']))) {
				$_G['thread'] = null;
			}

			$_G['forum_thread'] = & $_G['thread'];

			if(empty($_G['thread'])) {
				$fid = $tid = 0;
			} else {
				$fid = $_G['thread']['fid'];
				$tid = $_G['thread']['tid'];
			}
		}

		if($fid) {
			$forum = C::t('forum_forum')->fetch_info_by_fid($fid);
		}

		if($forum) {
			if($_G['uid']) {
				if($_G['member']['accessmasks']) {
					$query = C::t('forum_access')->fetch_all_by_fid_uid($fid, $_G['uid']);
					$forum['allowview'] = $query[0]['allowview'];
					$forum['allowpost'] = $query[0]['allowpost'];
					$forum['allowreply'] = $query[0]['allowreply'];
					$forum['allowgetattach'] = $query[0]['allowgetattach'];
					$forum['allowgetimage'] = $query[0]['allowgetimage'];
					$forum['allowpostattach'] = $query[0]['allowpostattach'];
					$forum['allowpostimage'] = $query[0]['allowpostimage'];
				}
				if($adminid == 3) {
					$forum['ismoderator'] = C::t('forum_moderator')->fetch_uid_by_fid_uid($fid, $_G['uid']);
				}
			}
			$forum['ismoderator'] = !empty($forum['ismoderator']) || $adminid == 1 || $adminid == 2 ? 1 : 0;
			$fid = $forum['fid'];
			$gorup_admingroupids = $_G['setting']['group_admingroupids'] ? dunserialize($_G['setting']['group_admingroupids']) : array('1' => '1');

			if($forum['status'] == 3) {
				if(!empty($forum['moderators'])) {
					$forum['moderators'] = dunserialize($forum['moderators']);
				} else {
					require_once libfile('function/group');
					$forum['moderators'] = update_groupmoderators($fid);
				}
				if($_G['uid'] && $_G['adminid'] != 1) {
					$forum['ismoderator'] = !empty($forum['moderators'][$_G['uid']]) ? 1 : 0;
					$_G['adminid'] = 0;
					if($forum['ismoderator'] || $gorup_admingroupids[$_G['groupid']]) {
						$_G['adminid'] = $_G['adminid'] ? $_G['adminid'] : 3;
						if(!empty($gorup_admingroupids[$_G['groupid']])) {
							$forum['ismoderator'] = 1;
							$_G['adminid'] = 2;
						}

						$group_userperm = dunserialize($_G['setting']['group_userperm']);
						if(is_array($group_userperm)) {
							$_G['group'] = array_merge($_G['group'], $group_userperm);
							$_G['group']['allowmovethread'] = $_G['group']['allowcopythread'] = $_G['group']['allowedittypethread']= 0;
						}
					}
				}
			}
			foreach(array('threadtypes', 'threadsorts', 'creditspolicy', 'modrecommend') as $key) {
				$forum[$key] = !empty($forum[$key]) ? dunserialize($forum[$key]) : array();
				if(!is_array($forum[$key])) {
					$forum[$key] = array();
				}
			}

			if($forum['status'] == 3) {
				$_G['isgroupuser'] = 0;
				$_G['basescript'] = 'group';
				if($forum['level'] == 0) {
					$levelinfo = C::t('forum_grouplevel')->fetch_by_credits($forum['commoncredits']);
					$levelid = $levelinfo['levelid'];
					$forum['level'] = $levelid;
					C::t('forum_forum')->update_group_level($levelid, $fid);
				}
				if($forum['level'] != -1) {
					loadcache('grouplevels');
					$grouplevel = $_G['grouplevels'][$forum['level']];
					if(!empty($grouplevel['icon'])) {
						$valueparse = parse_url($grouplevel['icon']);
						if(!isset($valueparse['host'])) {
							$grouplevel['icon'] = $_G['setting']['attachurl'].'common/'.$grouplevel['icon'];
						}
					}
				}

				$group_postpolicy = $grouplevel['postpolicy'];
				if(is_array($group_postpolicy)) {
					$forum = array_merge($forum, $group_postpolicy);
				}
				$forum['allowfeed'] = $_G['setting']['group_allowfeed'];
				if($_G['uid']) {
					if(!empty($forum['moderators'][$_G['uid']])) {
						$_G['isgroupuser'] = 1;
					} else {
						$groupuserinfo = C::t('forum_groupuser')->fetch_userinfo($_G['uid'], $fid);
						$_G['isgroupuser'] = $groupuserinfo['level'];
						if($_G['isgroupuser'] <= 0 && empty($forum['ismoderator'])) {
							$_G['group']['allowrecommend'] = $_G['cache']['usergroup_'.$_G['groupid']]['allowrecommend'] = 0;
							$_G['group']['allowcommentpost'] = $_G['cache']['usergroup_'.$_G['groupid']]['allowcommentpost'] = 0;
							$_G['group']['allowcommentitem'] = $_G['cache']['usergroup_'.$_G['groupid']]['allowcommentitem'] = 0;
							$_G['group']['raterange'] = $_G['cache']['usergroup_'.$_G['groupid']]['raterange'] = array();
							$_G['group']['allowvote'] = $_G['cache']['usergroup_'.$_G['groupid']]['allowvote'] = 0;
						} else {
							$_G['isgroupuser'] = 1;
						}
					}
				}
			}
		} else {
			$fid = 0;
		}
	}

	$_G['fid'] = $fid;
	$_G['tid'] = $tid;
	$_G['forum'] = &$forum;
	$_G['current_grouplevel'] = &$grouplevel;

	if(empty($_G['uid'])) {
		$_G['group']['allowpostactivity'] = $_G['group']['allowpostpoll'] = $_G['group']['allowvote'] = $_G['group']['allowpostreward'] = $_G['group']['allowposttrade'] = $_G['group']['allowpostdebate'] = $_G['group']['allowpostrushreply'] = 0;
	}
	if(!empty($_G['forum']['widthauto'])) {
		$_G['widthauto'] = $_G['forum']['widthauto'];
	}
}