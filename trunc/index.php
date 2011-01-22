<?php

$strVersion = '0.2';	// 画面に表示するバージョン
// ******************************************************
// Software name : WebSVN Administrator
//
// Copyright (C) INOUE Hirokazu, All Rights Reserved
//     http://oasis.halfmoon.jp/
//
// version 0.1 (2011/01/16)
// version 0.2 (2011/01/20)
//
// GNU GPL Free Software
//
// このプログラムはフリーソフトウェアです。あなたはこれを、フリーソフトウェア財
// 団によって発行された GNU 一般公衆利用許諾契約書(バージョン2か、希望によっては
// それ以降のバージョンのうちどれか)の定める条件の下で再頒布または改変することが
// できます。
// 
// このプログラムは有用であることを願って頒布されますが、*全くの無保証* です。
// 商業可能性の保証や特定の目的への適合性は、言外に示されたものも含め全く存在し
// ません。詳しくはGNU 一般公衆利用許諾契約書をご覧ください。
// 
// あなたはこのプログラムと共に、GNU 一般公衆利用許諾契約書の複製物を一部受け取
// ったはずです。もし受け取っていなければ、フリーソフトウェア財団まで請求してく
// ださい(宛先は the Free Software Foundation, Inc., 59 Temple Place, Suite 330
// , Boston, MA 02111-1307 USA)。
//
// http://www.opensource.jp/gpl/gpl.ja.html
// ******************************************************

require_once('include/config.php');	// ディレクトリなどの設定
require_once('include/auth.php');		// ユーザ認証

// このスクリプトのファイル名
$strFilenameThis = htmlspecialchars(basename($_SERVER['PHP_SELF']));

// 認証ファイルの確認
// auth.datの存在確認 1:OK, 0:Create, -1:Create Error
$flag_auth_file_exist = CheckAuthDataFile();

// ファイルのダウンロード（ヘッダを表示する前に行い、終了する）
if(isset($_GET['mode']) && $_GET['mode'] === 'download' && isset($_GET['dumptext'])){
	$strRepo = $_GET['dumptext'];
	download_dump_text($strRepo);
}

// HTML構文を開始し、ヘッダー部分を表示する
print_html_header();

// include/config.php 内のグローバル変数が設定されているかチェック
if(!check_config_file(1)){
	print_html_footer();
	die;
}

// 認証ファイルの確認結果を表示（必要な場合）
if($flag_auth_file_exist == 0){
?>
	<p class="info">初期ユーザ名：user, パスワード：password です</p>
	<p><a href="<?php echo $strFilenameThis;?>">ログオン画面を表示する</a><p>
	</body>
	</html>
<?php
	die;
}
elseif($flag_auth_file_exist < 0){
	print("<p class=\"error\">認証用データファイルが作成できません<br />dataディレクトリに書き込み権限が無い可能性があります</p>\n");
	print_html_footer();
	die;
}

// ユーザ認証を行う
if(!CheckAuth($strFilenameThis, 0))
{
	print("<p>認証が行われていません。またはCookieが使えない状況です。</p>\n");
	print("<a href=\"".$strFilenameThis."\">再度ログオン画面を表示する</a>\n");

	print("</body>\n</html>\n");
	die;
}

?>
<div id="main_content_left">
<h2>System</h2>
<p><?php echo date('Y/m/d  H:i:s', time()) ; ?><br />
Subversion &nbsp;<?php echo get_svn_version(); ?></p>
<h2>Menu</h2>
<ul>
<li><a href="<?php echo $strFilenameThis;?>">Home</a></li>
<li><a href="<?php echo $strFilenameThis;?>?mode=backuplist">Backup Catalog</a></li>
<li><a href="<?php echo $strFilenameThis; ?>?mode=chgpasswd">Change Password</a></li>
<li><a href="<?php echo $strFilenameThis; ?>?mode=logout">Logout</a></li>
</ul>
<h2>Repositories</h2>
<?php

// 既存リポジトリ一覧を表示（左側ペイン）
display_repositories();

?>
</div>	<!-- id="main_content_left" -->
<div id="main_content_right">

<?php

// *********************
// プログラム引数によって処理分岐
// *********************

// バックアップディレクトリの一覧
if(isset($_GET['mode']) && $_GET['mode'] === 'backuplist'){
	display_backup_list();
}
// 新規リポジトリ作成
elseif(isset($_GET['mode']) && $_GET['mode'] === 'makerepo' && isset($_POST['newrepo']) && strlen($_POST['newrepo'])>0){
	$strRepo = trim($_POST['newrepo']);
	create_new_repository($strRepo);
}
// ログアウト
elseif(isset($_GET['mode']) && $_GET['mode'] === 'logout'){
	LogoffAuth();
	print("<h1>Logout</h1>\n<p>ログアウトしました</p>\n");
}
// ユーザ名・パスワード変更
elseif(isset($_GET['mode']) && $_GET['mode'] === 'chgpasswd'){
	print("<h1>Change User and Password (ユーザ名、パスワード変更)</h1>\n");
	print("<p>".ChangePassword($strFilenameThis, 'svnadmin-create')."</p>\n");
}
// 既存リポジトリの情報表示（バックアップ、削除サブメニュー表示）
elseif(isset($_GET['mode']) && $_GET['mode'] === 'inforepo' && isset($_GET['reponame'])){
	$strRepo = $_GET['reponame'];
	info_repository($strRepo);
}
// 既存リポジトリのベリファイ
elseif(isset($_GET['mode']) && $_GET['mode'] === 'verify' && isset($_GET['reponame'])){
	$strRepo = $_GET['reponame'];
	verify_repository($strRepo);
}
// 既存リポジトリのバックアップ（hotcopy）
elseif(isset($_GET['mode']) && $_GET['mode'] === 'hotcopy' && isset($_GET['reponame'])){
	$strRepo = $_GET['reponame'];
	hotcopy_repository($strRepo, 0);
}
// 既存リポジトリのバックアップ（dump）
elseif(isset($_GET['mode']) && $_GET['mode'] === 'dump' && isset($_GET['reponame'])){
	$strRepo = $_GET['reponame'];
	dump_repository($strRepo);
}
// リポジトリの削除
elseif(isset($_GET['mode']) && $_GET['mode'] === 'remove' && isset($_GET['reponame'])){
	$strRepo = $_GET['reponame'];
	remove_repository($strRepo);
}
// 既存リポジトリの情報表示（バックアップ、削除サブメニュー表示）
elseif(isset($_GET['mode']) && $_GET['mode'] === 'viewbackupdir' && isset($_GET['dirname'])){
	$strRepo = $_GET['dirname'];
	view_backup_dir($strRepo);
}
// 新規リポジトリ作成 入力画面
else{
	// 引数が何もなかった場合、新規リポジトリ名の入力画面を表示
	input_new_repository();
}

print('</div>	<!-- id="main_content_right" -->'."\n");

// HTMLのフッターを表示する(HTML構文を閉じる)
print_html_footer();

exit();

// *********************
// 既存リポジトリ一覧を表示（左側ペイン）
// *********************
function display_repositories() {
	global $strBaseDir;
	global $strFilenameThis;

	$arrDirs = array();
	if ($dir = opendir($strBaseDir)) {
		while (($file = readdir($dir)) !== false) {
			if ($file != "." && $file != ".." && is_dir($strBaseDir.$file)) {
				array_push($arrDirs, $file);
			}
		} 
		closedir($dir);
	}

	sort($arrDirs);

	print("<ul>\n");
	foreach($arrDirs as $val){
		print "<li class=\"repo\"><a class=\"repo\" href=\"$strFilenameThis?mode=inforepo&amp;reponame=".htmlspecialchars($val)."\">".htmlspecialchars($val)."</a></li>\n";
	}
	print("</ul>\n");

}


// *********************
// HTML構文を開始し、ヘッダー部分を表示する
// *********************
function print_html_header() {
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ja" lang="ja">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta http-equiv="Content-Language" content="ja" />
	<link rel="stylesheet" href="style.css" type="text/css" />

	<title> </title>

	<script type="text/javascript" src="../utf.js"></script>
	<script type="text/javascript" src="../md5.js"></script>
	<script type="text/javascript" src="../authpage_form_md5.js"></script>

</head>
<body>

<div style="height:100px; width:100%; padding:0px; margin:0px;">
<p><img src="./logo-svn.png" width="109" height="93" alt="Subversion" style="vertical-align:middle;" /><span style="margin:0px 20px; font-size:30px; font-weight:lighter;">WebSVN-Admin</span><span style="margin:0px 0px; font-size:25px; font-weight:lighter; color:lightgray;">Subversion Administration</span></p>
</div>
<?php
}

// *********************
// フッター部分を表示した後、HTML構文を閉じる
// *********************
function print_html_footer() {
	global $strVersion;
?>
<p>&nbsp;</p>
<div class="clear"></div>
<div id="footer">
<p><a href="http://sourceforge.jp/projects/websvn-admin/">WebSVN-Admin</a> version <?php echo $strVersion; ?> &nbsp;&nbsp; GNU GPL free software</p>
</div>	<!-- id="footer" -->

</body>
</html>
<?php
}


// *********************
// 新規リポジトリ名入力画面
// *********************
function input_new_repository() {
	global $strFilenameThis;
?>

<h1>Create New Repository (リポジトリ作成)</h1>
<p>svnadmin create コマンドを実行して新しいリポジトリを作成します。</p>
<p>&nbsp;</p>
<form method="post" action="./<?php echo $strFilenameThis; ?>?mode=makerepo" name="form1">
	<p>作成するリポジトリ名&nbsp;&nbsp;&nbsp;<input name="newrepo" type="text" size="25" />&nbsp;&nbsp;<input type="submit" value="新規作成" /></p>
<p>&nbsp;</p>
<p style="color:gray;">リポジトリ名には半角アルファベット・数字・横線（A-Z, a-z, 0-9, -）のみ利用できます。<br />
また、既存のリポジトリ名と同じリポジトリは作成できません。</p>
</form>

<?php
}


// *********************
// 新規リポジトリ作成
// *********************
function create_new_repository($strRepo) {
	global $strSvnCmdPath;
	global $strBaseDir;

	print("<h1>Create New Repository (リポジトリ作成)</h1>\n");
	print("<p class=\"info\">新しいリポジトリ『".htmlspecialchars($strRepo)."』が有効なディテクトリ名かチェック中 ...</p>\n");

	// リポジトリ名に不正な文字が入っていないか検査
	if(!preg_match("/^[A-Za-z0-9\-]+$/", $strRepo) || $strRepo[0] == '-' || $strRepo[strlen($strRepo)-1] == '-' || strlen($strRepo) > 20 || strlen($strRepo) <= 0){
		print("<p class=\"error\">指定されたリポジトリ名が、命名規則から外れています。<br />20文字を越える、許容文字(A-Z,a-z,0-9,-)以外、先頭末尾に - など</p>");
		return;
	}

	// すでに存在するディレクトリ名は却下
	if(file_exists($strBaseDir.$strRepo)){
		print("<p class=\"error\">指定されたリポジトリ名はすでに存在するディレクトリかファイル名です</p>");
		return;
	}

	// 新規リポジトリ作成
	print("<p class=\"info\">リポジトリ作成コマンド実行中 (svnamin create ".htmlspecialchars($strRepo).") ...</p>");
	exec($strSvnCmdPath."svnadmin create ".$strBaseDir.$strRepo." 2>&1", $arrStdout, $nResult);

	// 結果判定
	if($nResult == 0){ print("<p class=\"ok\">コマンドが正しく実行されました</p>\n"); }
	else{ print("<p class=\"error\">実行エラー</p>\n"); }

	// コマンドのStdout出力がある場合
	if(count($arrStdout)>0){
		print("<pre>\n\n");
		foreach($arrStdout as $str){
			print($str."\n");
		}
		print("</pre>\n");
	}

}

// *********************
// 既存リポジトリの情報表示（バックアップ、削除サブメニュー表示）
// *********************
function info_repository($strRepo) {
	global $strSvnCmdPath;
	global $strBaseDir;
	global $strFilenameThis;

	print("<h1>Repository Administration (リポジトリ管理)</h1>\n");
	print("<p>リポジトリ名 : ".htmlspecialchars($strRepo)."</p>\n");

	// リポジトリ名に不正な文字が入っていないか検査
	if(!preg_match("/^[A-Za-z0-9\-]+$/", $strRepo) || $strRepo[0] == '-' || $strRepo[strlen($strRepo)-1] == '-' || strlen($strRepo) > 20 || strlen($strRepo) <= 0){
		print("<p class=\"error\">不正なリポジトリ名が指定されました</p>\n");
		return;
	}

	// リポジトリの最終リビジョン番号を読み取る
	$strRevNo = '';
	exec($strSvnCmdPath."svnlook youngest ".$strBaseDir.$strRepo." 2>&1", $arrStdout, $nResult);
	if($nResult != 0){
		print("<p class=\"error\">svnlook youngestコマンドが実行できません</p>\n");
	}
	if(count($arrStdout) >= 1){ $strRevNo = $arrStdout[0]; }
	$arrStdout = array();

	// リポジトリの最終変更ユーザ名を読み取る
	$strAuthor = '';
	exec($strSvnCmdPath."svnlook author ".$strBaseDir.$strRepo." 2>&1", $arrStdout, $nResult);
	if($nResult != 0){
		print("<p class=\"error\">svnlook authorコマンドが実行できません</p>\n");
	}
	if(count($arrStdout) >= 1){ $strAuthor = $arrStdout[0]; }
	$arrStdout = array();

	// リポジトリの最終変更日時を読み取る
	$strDate = '';
	exec($strSvnCmdPath."svnlook date ".$strBaseDir.$strRepo." 2>&1", $arrStdout, $nResult);
	if($nResult != 0){
		print("<p class=\"error\">svnlook dateコマンドが実行できません</p>\n");
	}
	if(count($arrStdout) >= 1){ $strDate = $arrStdout[0]; }
	
	print("<p>直近にコミットしたユーザ : ".$strAuthor."</p>\n");
	print("<p>直近のコミット日時 : ".$strDate."</p>\n");
	print("<p>リビジョン no : ".$strRevNo."</p>\n");

	$arrCmd = array(
		array('verify', 'ベリファイ'),
//		array('recover', 'エラー回復'),
		array('hotcopy', 'バックアップ（Hotcopy）'),
		array('dump', 'バックアップ（Dump）'),
		array('remove', '削除'),
	);
	
	foreach($arrCmd as $val){
		print("<form method=\"post\" action=\"./".$strFilenameThis."?mode=".$val[0]."&amp;reponame=".htmlspecialchars($strRepo)."\" name=\"form1\" class=\"horiz\"><input type=\"submit\" value=\"".$val[1]."\" /></form>\n");
	}
	
}

// *********************
// 既存リポジトリのベリファイ
// *********************
function verify_repository($strRepo) {
	global $strSvnCmdPath;
	global $strBaseDir;

	print("<h1>Verify Repository (ベリファイ)</h1>\n");
	print("<p>リポジトリ名 : ".htmlspecialchars($strRepo)."</p>\n");

	// リポジトリ名に不正な文字が入っていないか検査
	if(!preg_match("/^[A-Za-z0-9\-]+$/", $strRepo) || $strRepo[0] == '-' || $strRepo[strlen($strRepo)-1] == '-' || strlen($strRepo) > 20 || strlen($strRepo) <= 0){
		print("<p class=\"error\">不正なリポジトリ名が指定されました</p>\n");
		return;
	}

	// ベリファイコマンドを実行
	exec($strSvnCmdPath."svnadmin verify ".$strBaseDir.$strRepo." 2>&1", $arrStdout, $nResult);
	if($nResult != 0){
		print("<p class=\"error\">svnadmin verifyコマンドが実行できません</p>\n");
		return;
	}

	// コマンドのStdout出力がある場合
	if(count($arrStdout)>0){
		print("<pre>\n\n");
		foreach($arrStdout as $str){
			print($str."\n");
		}
		print("</pre>\n");
		print("<p class=\"info\">ベリファイが完了しました</p>\n");
	}
	else{
		print("<p class=\"info\">svnadminコマンドの出力無し。エラーの可能性あり</p>\n");
	}
}

// *********************
// 既存リポジトリのバックアップ（hotcopy）
// *********************
// 引数 $flag_mode : 0のとき、処理のタイトルを表示する
// 戻り値 0:失敗, 1:成功
function hotcopy_repository($strRepo, $flag_mode) {
	global $strSvnCmdPath;
	global $strBaseDir;
	global $strBackupDir;

	if($flag_mode == 0){
		print("<h1>Backup (hotcopy) Repository (バックアップ : hotcopy)</h1>\n");
		print("<p>リポジトリ名 : ".htmlspecialchars($strRepo)."</p>\n");
	}

	// リポジトリ名に不正な文字が入っていないか検査
	if(!preg_match("/^[A-Za-z0-9\-]+$/", $strRepo) || $strRepo[0] == '-' || $strRepo[strlen($strRepo)-1] == '-' || strlen($strRepo) > 20 || strlen($strRepo) <= 0){
		print("<p class=\"error\">不正なリポジトリ名が指定されました</p>\n");
		return(0);
	}

	// バックアップ先ディレクトリが既存でないか検査
	$strBackupBasename = $strRepo.'-'.date('Ymd-Hi', time());
	if(file_exists($strBackupDir.$strBackupBasename)){
		print("<p class=\"error\">バックアップ先に同じ名称のディレクトリがあります</p>\n");
		return(0);
	}

	print("<p>コマンド実行中 ... (svnadmin hotcopy ".htmlspecialchars($strRepo)." ".htmlspecialchars($strBackupBasename).")</p>\n");

	// バックアップコマンドを実行
	exec($strSvnCmdPath."svnadmin hotcopy ".$strBaseDir.$strRepo." ".$strBackupDir.$strBackupBasename." 2>&1", $arrStdout, $nResult);
	if($nResult != 0){
		print("<p class=\"info\">バックアップに失敗しました。単純コピーを試します</p>\n");
		print("<p>コマンド実行中 ... (cp -Rv ".htmlspecialchars($strRepo)." ".htmlspecialchars($strBackupBasename).")</p>\n");
		$arrStdout = array();
		exec("cp -Rv ".$strBaseDir.$strRepo." ".$strBackupDir.$strBackupBasename." 2>&1", $arrStdout, $nResult);
		// コマンドのStdout出力がある場合
		if(count($arrStdout)>0){
			print("<pre>\n\n");
			foreach($arrStdout as $str){
				print($str."\n");
			}
			print("</pre>\n");
		}
		if($nResult != 0){
			print("<p class=\"error\">単純コピーも出来ません。バックアップに失敗しました</p>\n");
			return(0);
		}
		print("<p class=\"ok\">hotcopyは失敗しましたが、単純コピーは完了しました</p>\n");
		return(1);
	}
	// コマンドのStdout出力がある場合
	if(count($arrStdout)>0){
		print("<pre>\n\n");
		foreach($arrStdout as $str){
			print($str."\n");
		}
		print("</pre>\n");
	}
	else{
		print("<p class=\"ok\">バックアップが完了しました</p>\n");
	}
	return(1);
}

// *********************
// 既存リポジトリのバックアップ（dump）
// *********************
function dump_repository($strRepo) {
	global $strSvnCmdPath;
	global $strBaseDir;
	global $strBackupDir;

	print("<h1>Backup (dump) Repository (バックアップ : dump)</h1>\n");
	print("<p>リポジトリ名 : ".htmlspecialchars($strRepo)."</p>\n");

	// リポジトリ名に不正な文字が入っていないか検査
	if(!preg_match("/^[A-Za-z0-9\-]+$/", $strRepo) || $strRepo[0] == '-' || $strRepo[strlen($strRepo)-1] == '-' || strlen($strRepo) > 20 || strlen($strRepo) <= 0){
		print("<p class=\"error\">不正なリポジトリ名が指定されました</p>\n");
		return;
	}

	// バックアップ先ディレクトリが既存でないか検査
	$strBackupBasename = $strRepo.'-'.date('Ymd-Hi', time()).'.txt';
	if(file_exists($strBackupDir.$strBackupBasename)){
		print("<p class=\"error\">バックアップ先に同じ名称のファイルがあります</p>\n");
		return;
	}

	print("<p>コマンド実行中 ... (svnadmin dump ".htmlspecialchars($strRepo)." &gt; ".htmlspecialchars($strBackupBasename).")</p>\n");

	// バックアップコマンドを実行
	exec($strSvnCmdPath."svnadmin dump ".$strBaseDir.$strRepo." > ".$strBackupDir.$strBackupBasename." 2>&1", $arrStdout, $nResult);
	if($nResult != 0){
		print("<p class=\"error\">バックアップに失敗しました</p>\n");
		return;
	}
	// コマンドのStdout出力がある場合
	if(count($arrStdout)>0){
		print("<pre>\n\n");
		foreach($arrStdout as $str){
			print($str."\n");
		}
		print("</pre>\n");
	}
	else{
		print("<p class=\"ok\">バックアップが完了しました</p>\n");
	}
	return;
}

// *********************
// リポジトリの削除
// *********************
function remove_repository($strRepo) {
	global $strSvnCmdPath;
	global $strBaseDir;
	global $strBackupDir;

	print("<h1>Remove Repository (リポジトリ削除)</h1>\n");
	print("<p>リポジトリ名 : ".htmlspecialchars($strRepo)."</p>\n");

	// リポジトリ名に不正な文字が入っていないか検査
	if(!preg_match("/^[A-Za-z0-9\-]+$/", $strRepo) || $strRepo[0] == '-' || $strRepo[strlen($strRepo)-1] == '-' || strlen($strRepo) > 20 || strlen($strRepo) <= 0){
		print("<p class=\"error\">不正なリポジトリ名が指定されました</p>\n");
		return;
	}

	// バックアップ（hotcopy）
	print("<p class=\"info\">削除前に、バックアップを行います</p>\n");
	if(!hotcopy_repository($strRepo, 1)){
		print("<p class=\"error\">対象リポジトリがバックアップできないため、削除を中止します</p>\n");
		return;
	}

	print("<p>コマンド実行中 ... (rm -rfv ".htmlspecialchars($strRepo).")</p>\n");
	// 削除
	exec("rm -rfv ".$strBaseDir.$strRepo." 2>&1", $arrStdout, $nResult);
	if($nResult != 0){
		print("<p class=\"error\">削除に失敗しました</p>\n");
		return;
	}
	// コマンドのStdout出力がある場合
	if(count($arrStdout)>0){
		print("<pre>\n\n");
		print("$ rm -rfv ".htmlspecialchars($strRepo)."\n\n");
		foreach($arrStdout as $str){
			print($str."\n");
		}
		print("</pre>\n");
	}
	print("<p class=\"ok\">削除が完了しました</p>\n");

}


// *********************
// バックアップ一覧を表示する
// *********************
function display_backup_list() {
	global $strBackupDir;
	global $strFilenameThis;

	print("<h1>Backup Catalog</h1>\n");

	$arrDirs = array();
	if ($dir = opendir($strBackupDir)) {
		while (($file = readdir($dir)) !== false) {
			if ($file != "." && $file != "..") {
				array_push($arrDirs, $file);
			}
		} 
		closedir($dir);
	}

	sort($arrDirs);

	print("<ul>\n");
	foreach($arrDirs as $val){
		if(is_dir($strBackupDir.$val)){
			print "<li><a href=\"".$strFilenameThis."?mode=viewbackupdir&amp;dirname=".htmlspecialchars($val)."\">".htmlspecialchars($val)."</a></li>\n";
		}
		else{
			print "<li><a href=\"".$strFilenameThis."?mode=download&amp;dumptext=".htmlspecialchars($val)."\">".htmlspecialchars($val)."</a></li>\n";
		}
	}
	print("</ul>\n");

}


// *********************
// バックアップ一覧を表示する（ディレクトリの場合）
// *********************
function view_backup_dir($strRepo) {
	global $strBackupDir;

	print("<h1>View Backup Repository (hotcopy dir)</h1>\n");
	print("<p>リポジトリ名 : ".htmlspecialchars($strRepo)."</p>\n");

	// リポジトリ名に不正な文字が入っていないか検査
	if(!preg_match("/^[A-Za-z0-9\-]+$/", $strRepo) || $strRepo[0] == '-' || $strRepo[strlen($strRepo)-1] == '-' || strlen($strRepo) > 40 || strlen($strRepo) <= 0){
		print("<p class=\"error\">不正なリポジトリ名が指定されました</p>\n");
		return;
	}

	print("<p>コマンド実行中 ... (ls -laR ".htmlspecialchars($strRepo).")</p>\n");
	// ファイル一覧
	exec("ls -laR ".$strBackupDir.$strRepo." 2>&1", $arrStdout, $nResult);
	if($nResult != 0){
		print("<p class=\"error\">一覧取得に失敗しました</p>\n");
		return;
	}
	// コマンドのStdout出力がある場合
	if(count($arrStdout)>0){
		print("<pre>\n\n");
		print("$ ls -laR ".htmlspecialchars($strRepo)."\n\n");
		foreach($arrStdout as $str){
			print($str."\n");
		}
		print("</pre>\n");
	}

}


// *********************
// dump(バックアップ)ファイルをダウンロードする
// *********************
function download_dump_text($strRepo) {
	global $strFilenameThis;
	global $strBackupDir;

	// 認証確認
	if(!CheckAuth($strFilenameThis, 1)){
		die("Error : not logged on\n");
	}

	// include/config.php 内のグローバル変数が設定されているかチェック
	if(!check_config_file(0)){
		die("Error : config value\n");
	}

	// リポジトリ名に不正な文字が入っていないか検査
	if(!preg_match("/^[A-Za-z0-9\-\.]+$/", $strRepo) || $strRepo[0] == '-' || $strRepo[strlen($strRepo)-1] == '-' || strlen($strRepo) > 40 || strlen($strRepo) <= 0){
		die("Error : unsupported filename\n");
	}

	$strFilename = $strBackupDir.$strRepo;
	if(!is_file($strFilename)){
		die("file '$strFilename' not exist\n");
	}

	$file_length = filesize($strFilename);
	header("Content-Disposition: attachment; filename=$strFilename");
	header("Content-Length:$file_length");
	header("Content-Type: application/octet-stream");
	readfile ($strFilename);
	
	exit();
}


// *********************
// svnコマンドのバージョン番号（文字列）を返す関数
// *********************
function get_svn_version() {

	global $strSvnCmdPath;
	global $strBaseDir;
	$strSvnVersion = '';
	
	exec($strSvnCmdPath."svnlook --version 2>&1", $arrStdout, $nResult);
	if($nResult == 0){
		if(preg_match('~([0-9]+)\.([0-9]+)\.([0-9]+)~', $arrStdout[0], $matches)) {
			$strSvnVersion = $matches[0];
		}
	}
	return($strSvnVersion);
}


// *********************
// config.php が正しく設定されているかチェック
// *********************
function check_config_file($flag_in_html) {
	global $strSvnCmdPath;
	global $strBaseDir;
	global $strBackupDir;
	
	$flag_ok = 1;	// 正しくセットされていれば 1, 設定が足りなければ 0

	if(!isset($flag_in_html)){ $flag_in_html = 0; }

	// config.php で設定が行われているか確認する
	$arrVarName = array(
		array($strSvnCmdPath, '$strSvnCmdPath'),
		array($strBaseDir, '$strBaseDir'),
		array($strBackupDir, '$strBackupDir'),
	);
	
	foreach($arrVarName as $val){
		if(!isset($val[0])){
			if($flag_in_html){ print("<p class=\"error\">include/config.php に ".$val[1]." が設定されていません</p>\n");}
			else{print("Error : include/config.php - ".$val[1]." not exist\n");}
			$flag_ok = 0;
		}
	}

	if(!$flag_ok){ return(0); }

	// execコマンドに渡す変数の汚染除去（エスケープ）
	$strSvnCmdPath = escapeshellcmd($strSvnCmdPath);
	$strBaseDir = escapeshellcmd($strBaseDir);
	$strBackupDir = escapeshellcmd($strBackupDir);
	
	return(1);
}


?>


