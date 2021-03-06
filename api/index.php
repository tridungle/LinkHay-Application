<?php 

include '../api/curl.php';
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$cc = new cURL();
$linkhay = 'http://linkhay.com';
$vietid = 'http://vietid.net';
$username = (isset($_GET['username']) ? $_GET['username'] : '');
$password = (isset($_GET['password']) ? $_GET['password'] : '');
$type = (isset($_GET['type']) ? $_GET['type'] : 'index');
$page = (isset($_GET['page']) ? $_GET['page'] : '1');
$category = (isset($_GET['category']) ? $_GET['category'] : '');
$login = (isset($_GET['login']) ? $_GET['login'] : 'false');


/*-----------------------------
 LOGIN
-----------------------------*/
$login_status = '';
if ($login == 'true') {
	$oauth = $cc->get($linkhay . '/auth/request?type=modal');
	$oauth = str_replace(array("\r\n", "\r", "\n"), "", $oauth);
	preg_match('/var oauth_token		=	"(.*?)"/', $oauth, $token_key);
	if (!$token_key) {
		$oauth = $cc->get($linkhay . '/auth/request?type=modal');
		$oauth = str_replace(array("\r\n", "\r", "\n"), "", $oauth);
		preg_match('/var oauth_token		=	"(.*?)"/', $oauth, $token_key);
	}
	
	if ($token_key) {
		$data_login = array(
			'email' => $username,
			'password' => $password,
			'login_btn' => 'Đăng nhập',
			'oauth_token' => $token_key[1],
			'processlogin' => 1,
			'username' => ''
		);

		$login = $cc->post($vietid . '/Authentication/Authenticate/', http_build_query($data_login));
		//echo $login;
		$login = str_replace(array("\r\n", "\r", "\n"), "", $login);
		preg_match('/&oauth_verifier=(.+?)&confirm=1&confirm=1/', $login, $confirm_key);
		if ($confirm_key) {
			$login_status = true;
		} else {
			$login_status = false;
		}
	} else {
		$login_status = false;
	}
}


/*-----------------------------
 LOAD CONTENT 
-----------------------------*/

$subject = $cc->get($linkhay . '/ajaxHomepageGetMore?pagename=' . $type . '&category=' . $category . '&page=' . $page . '&'); 
$subject = json_decode($subject);
$subject = $subject->lh_more;
//echo $subject;

preg_match_all('/src="(.*?)" width="15" height="15"/', $subject, $avatar);
preg_match_all('/"\/>(.*?)<\/a><\/strong>/', $subject, $username);

// items
$subject = str_replace(array("\r\n", "\r", "\n"), "", $subject);
preg_match_all('/<li id="news_item_(.*?)<\/div><!-- end news-item -->            <\/li>/', $subject, $items);

$links = array();

foreach ($items[0] as $id => $item) {
	// URL link
	preg_match('/href="(.*?)"/', $item, $link);
	$link = $link[1];
	$link = str_replace($linkhay, '', $link);
	$link = $linkhay . $link;
	// Title
	preg_match('/class="Array"Array>(.*?)<\/a><span class="blur">/', $item, $title);
	if (!$title) {
		preg_match('/osc-insert-cb="preLinkDetailClick">(.*?)<\/a><span class="blur">/', $item, $title);
	} 
	if (!$title) {
		preg_match('/osc-insert-cb="preMediaDetailClick">(.*?)<\/a><span class="blur">/', $item, $title);
	}
	// Type
	$type = 'link';
	if ( preg_match('/<i class="link-icon fa fa-camera"><\/i>/', $title[1]) ) {
		$type = 'media';
	} else if ( preg_match('/<i class="link-icon lh-icon-quick-note"><\/i>/', $title[1]) ) {
		$type = 'quicknote';
	} else if ( preg_match('/<i class="link-icon fa fa-youtube-play"><\/i>/', $title[1]) ) {
		$type = 'video';
	}
	// Title
	$title = str_replace(array('<i class="link-icon fa fa-camera"></i> ', '<i class="link-icon lh-icon-quick-note"></i> ', '<i class="link-icon fa fa-youtube-play"></i> '), '', $title);
	// Subtitle
	preg_match('/<span class="author-mess"> <span class="dot">(.*?)<\/span> (.*?)<\/span>/', $item, $subtitle);
	// Original Link
	preg_match('/<a title="(.*?)"/', $item, $original_link);
	// Thumbnail
	preg_match('/src="(.*?)" width="60" height="60"/', $item, $thumbnail);
	$thumbnail = str_replace('60_', '120_', $thumbnail);
	// Vote
	preg_match('/<div class="boxvote"(.*?)<\/div>/', $item, $vote_html);
	preg_match('/href="(.*?)">(.*?)<\/a>/', $vote_html[0], $vote);
	preg_match('/<span class="(.*?)"><i><\/i>Hay<\/span>/', $vote_html[0], $vote_status);
	// Link ID
	preg_match('/id="vote_(.*?)">/', $vote_html[0], $link_id);
	// Reshare
	preg_match_all('/<a href="\/u\/(.*?)" title="/', $item, $reshare);
	// Domain
	preg_match('/<span class="domain"><a class="link" target="_blank" href="(.*?)">(.*?)<\/a><\/span>/', $item, $domain);
	// Channel
	preg_match('/<a rel="channel-(.*?)" class="link-channel channel-tip" href="(.*?)" title="(.*?)">(.*?)<\/a>/', $item, $channel);
	// Comments
	preg_match('/<a class="comment-count mrk-insert-trigger" osc-insert-cb="preLinkDetailClick" title="(.*?)" href="(.*?)">(.*?)<\/a>/', $item, $comments);
	if (!$comments) {
		preg_match('/<a class="comment-count mrk-insert-trigger" osc-insert-cb="preMediaDetailClick" title="(.*?)" href="(.*?)">(.*?)<\/a>/', $item, $comments);
	}
	if (!$comments) {
		preg_match('/<a class="comment-count" title="(.*?)" href="(.*?)">(.*?)<\/a>/', $item, $comments);
	}
	// Time Ago
	preg_match('/<a class="timeago mrk-insert-trigger" osc-insert-cb="preLinkDetailClick" href="(.*?)" title="(.*?)">(.*?)<\/a>/', $item, $timeago);
	
	if (!$timeago) {
		preg_match('/<a class="timeago mrk-insert-trigger" osc-insert-cb="preMediaDetailClick" href="(.*?)" title="(.*?)">(.*?)<\/a>/', $item, $timeago);
	}
	if (!$timeago) {
		preg_match('/<a class="timeago" href="(.*?)" title="(.*?)">(.*?)<\/a>/', $item, $timeago);
	}

	// Avatar
	$avatar_url = str_replace('_15', '_60', $avatar[1][$id]);

	$link_item = array(	
		'id' => ($link_id ? $link_id[1] : ''),
		'title' => ($title ? $title[1] : ''),
		'subtitle' => ($subtitle ? $subtitle[2] : ''),
		'url' => ($link ? $link : ''),
		'type' => ($type ? $type : ''),
		'link' => ($original_link ? $original_link[1] : ''),
		'thumbnail' => ($thumbnail ? $thumbnail[1] : 'img/thumbnail.svg'),
		'vote' => ($vote ? $vote[2] : ''),
		'status' => ($vote_status ? $vote_status[1] : ''),
		'username' => ($username ? $username[1][$id] : ''),
		'avatar' => ($avatar_url ? $avatar_url : ''),
		'domain_url' => ($domain ? $domain[1] : ''),
		'domain_name' => ($domain ? $domain[2] : ''),
		'channel_id' => ($channel ? $channel[1] : ''),
		'channel_name' => ($channel ? $channel[4] : ''),
		'comments' => ($comments ? $comments[3] : ''),
		'time' => ($timeago ? $timeago[3] : ''),
		'reshare' => ($reshare ? $reshare[1] : '')
	);

	/*
	echo 'Link ID: ' . $id . '<br/>';
	echo 'Title: ' . ($title ? $title[1] : '') . '<br/>';
	echo 'Link URL: ' . ($link ? $link : '') . '<br/>';
	echo 'Original: ' . ($original_link ? $original_link[1] : '') . '<br/>';
	echo 'Thumbnail: ' . ($thumbnail ? $thumbnail[1] : 'img/thumbnail.svg') . '<br/>';
	echo 'Vote: ' . ($vote ? $vote[2] : '') . '<br/>';
	echo 'Username: ' . ($username ? $username[1][$id] : '') . '<br/>';
	echo 'Avatar: ' . ($avatar ? $avatar[1][$id] : '') . '<br/>';
	echo 'Doamin URL: ' . ($domain ? $domain[1] : '') . '<br/>';
	echo 'Doamin name: ' . ($domain ? $domain[2] : '') . '<br/>';
	echo 'Channel ID: ' . ($channel ? $channel[1] : ''). '<br/>';
	echo 'Channel name: ' . ($channel ? $channel[4] : '') . '<br/>';
	echo 'Comments: ' . ($comments ? $comments[3] : '') . '<br/>';
	echo 'Time: ' . ($timeago ? $timeago[3] : '') . '<br/>';
	echo '<br/>--------------------------------<br/>';
	*/
	$link_item = (object) $link_item;
	array_push($links, $link_item);
}
$result = array('links' => $links, 'login_status' => $login_status);
$result = json_encode($result);
print_r($result);




/*-----------------------------
	LOGOUT
-----------------------------*/
$app_key = '057ee7c8032ed1faa1cfe6a3051785fb';
$call_back = 'http://linkhay.com/auth/logout';

$post = array(
	'app_key' => $app_key,
	'call_back' => $call_back
	);
//$logout_app = $cc->get($vietid . '/OauthServerV2/logout', http_build_query($post));
$delete_key = $cc->post($vietid . '/OauthServerV2/Plogout', http_build_query($post));
$logout = $cc->post($vietid . '/OauthServerV2/Plogout', http_build_query($post));

//echo $logout;

	


?>