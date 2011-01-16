<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ja" lang="ja">
<?php

// ******************************************************
// Software name : WebSVN Administrator
//
// Copyright (C) INOUE Hirokazu, All Rights Reserved
//     http://oasis.halfmoon.jp/
//
// version 0.1 (2011/01/16)
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

?>
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

require_once('include/config.php');	// ディレクトリなどの設定
require_once('include/auth.php');		// ユーザ認証

// このスクリプトのファイル名
$strFilenameThis = htmlspecialchars(basename($_SERVER['PHP_SELF']));

// config.php で設定が行われているか確認する
if(!isset($strSvnCmdPath) || !isset($strBaseDir)){
	print("<p class=\"error\">include/config.php の初期設定が行われていません</p>\n");
	print("</body>\n</html>\n");
	die;
}

$nResult = CheckAuthDataFile();
if($nResult == 0){
?>
	<p class="info">初期ユーザ名：user, パスワード：password です</p>
	<p><a href="<?php echo $strFilenameThis;?>">ログオン画面を表示する</a><p>
	</body>
	</html>
<?php
	die;
}
elseif($nResult < 0){
	print("<p class=\"error\">認証用データファイルが作成できません<br />dataディレクトリに書き込み権限が無い可能性があります</p>\n");
	print("</body>\n</html>\n");
	die;
}

// ユーザ認証を行う
$strReturn = CheckAuth($strFilenameThis, 'svnadmin-create');

if(!$strReturn)
{
	print("<p>認証が行われていません。またはCookieが使えない状況です。</p>\n");
	print("<a href=\"".$strFilenameThis."\">再度ログオン画面を表示する</a>\n");

	print("</body>\n</html>\n");
	die;
}

?>
<div id="main_content_left">
<h2>Menu</h2>
<ul>
<li><a href="<?php echo $strFilenameThis;?>">Home</a></li>
<li><a href="<?php echo $strFilenameThis; ?>?mode=chgpasswd">Change Password</a></li>
<li><a href="<?php echo $strFilenameThis; ?>?mode=logout">Logout</a></li>
</ul>
<h2>Repositories</h2>
<ul>
<?php
	if ($dir = opendir($strBaseDir)) {
		while (($file = readdir($dir)) !== false) {
			if ($file != "." && $file != ".." && is_dir($strBaseDir.$file)) {
				print "<li class=\"repo\"><a class=\"repo\">".htmlspecialchars($file)."</a></li>\n";
			}
		} 
		closedir($dir);
	}
?>
</ul>
</div>	<!-- id="main_content_left" -->
<div id="main_content_right">

<?php

//print("<p>Subversion 新リポジトリ作成 （svnadmin create ~/var/svn/[repo]）</p>\n");

if(isset($_POST['newrepo']) && strlen($_POST['newrepo'])>0){
	// 新規リポジトリ作成（リポジトリ名が与えられた場合）
	$strNewRepo = trim($_POST['newrepo']);
	print("<h1>Create New Repository (リポジトリ作成)</h1>\n");
	print("<p class=\"info\">新しいリポジトリ『".htmlspecialchars($strNewRepo)."』が有効なディテクトリ名かチェック中 ...</p>\n");

	if(preg_match("/^[A-Za-z0-9\-]+$/", $strNewRepo) && $strNewRepo[0] != '-' && $strNewRepo[strlen($strNewRepo)-1] != '-' && strlen($strNewRepo) <= 20){
		if(file_exists($strBaseDir.$strNewRepo) || is_dir($strBaseDir.$strNewRepo)){
			print("<p class=\"error\">指定されたリポジトリ名はすでに存在するディレクトリかファイル名です</p>");
		}
		else{
			print("<p class=\"info\">リポジトリ作成コマンド実行中 (svnamin create ".htmlspecialchars($strNewRepo).") ...</p>");
//			$strResult = system("/home/tmg1136-inue/local/bin/svnadmin create ".$strBaseDir.$strNewRepo." 2>&1", $nResult);
			exec($strSvnCmdPath."svnadmin create ".$strBaseDir.$strNewRepo." 2>&1", $arrStdout, $nResult);

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
	}
	else{
		print("<p class=\"error\">指定されたリポジトリ名が、命名規則から外れています。<br />20文字を越える、許容文字(A-Z,a-z,0-9,-)以外、先頭末尾に - など</p>");
	}

}
elseif(isset($_GET['mode']) && $_GET['mode'] === 'logout'){
	LogoffAuth();
?>
<h1>Logout</h1>
<p>ログアウトしました</p>
<?php
}
elseif(isset($_GET['mode']) && $_GET['mode'] === 'chgpasswd'){
	print("<h1>Change User and Password (ユーザ名、パスワード変更)</h1>\n");
	print("<p>".ChangePassword($strFilenameThis, 'svnadmin-create')."</p>\n");
}
else{
	// 引数が何もなかった場合、新規リポジトリ名の入力画面を表示
?>
<h1>Create New Repository (リポジトリ作成)</h1>
<p>svnadmin create コマンドを実行して新しいリポジトリを作成します。</p>
<p>&nbsp;</p>
<form method="post" action="./<?php echo $strFilenameThis; ?>" name="form1">
	<p>作成するリポジトリ名&nbsp;&nbsp;&nbsp;<input name="newrepo" type="text" size="25" />&nbsp;&nbsp;<input type="submit" value="新規作成" /></p>
<p>&nbsp;</p>
<p style="color:gray;">リポジトリ名には半角アルファベット・数字・横線（A-Z, a-z, 0-9, -）のみ利用できます。<br />
また、既存のリポジトリ名と同じリポジトリは作成できません。</p>
</form>
<?php
}
?>

</div>	<!-- id="main_content_right" -->
<p>&nbsp;</p>

</body>
</html>

