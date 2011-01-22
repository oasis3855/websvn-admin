<?php

// ******************************************************
// Software name : WebSVN Administrator用 認証関数
//
// Copyright (C) INOUE Hirokazu, All Rights Reserved
//     http://oasis.halfmoon.jp/
//
// version 1.0 (2010/02/21)
// version 1.1 (2011/01/16)
// version 1.2 (2011/01/22)
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

// 認証用データファイル
$strAuthDataFile = 'data/auth.dat';


// 認証メイン関数
// 認証されていない場合、ユーザ・パスワード入力ボックスを表示
// ユーザ・パスワードが送られてきた場合は、認証を行う
// 既に認証されている場合は、何もしない
//
// 認証状態はセッションに格納される（認証ログオフは、LogoffAuth 関数）
//
// 認証されている場合 1 を返す
// 認証されていない場合 0 を返す
function CheckAuth($strReloadPage, $flag_check_only)
{
	if(!isset($_SESSION)){ session_start(); }
	$strAuthUser = '';	// 認証されたユーザ名
	
	if (isset($_SESSION['svnadmin-user']))
	{
		// 既にログオンしてている場合
		return(1);		// 認証成功
	}
	elseif(isset($flag_check_only) && $flag_check_only == 1){
		// ログオンしていない場合で、結果のみ返す場合
		return(0);
	}
	elseif(!isset($_POST['user']) || !isset($_POST['password'])){
		// 新規入力画面を表示する

		print("<form method=\"post\" action=\"./$strReloadPage\" name=\"form2\">\n");
		print("\t<table border=\"0\" cellpadding=\"2\" cellspacing=\"0\" align=\"center\">\n");
		print("\t<tr><td colspan=\"2\"><strong>ログオン送信データ</strong></td></tr>");
		print("\t<tr><td>ユーザ</td><td><input name=\"user\" size=\"40\" /></td></tr>\n");
		print("\t<tr><td>パスワード</td><td><input name=\"password\" type=\"password\" size=\"40\" /></td></tr>\n");
		print("\t<tr><td colspan=\"2\"><input type=\"submit\" value=\"ログオンする\" /></td></tr>\n");
		print("\t</table>\n");
		print("</form>\n");

		return(0);		// 認証失敗
	}
	else {
		// DBを参照して、認証チェックを行う
		$strAuthUser = CheckUser($_POST['user'], $_POST['password']);
		if($strAuthUser != '') {
			// 認証OK
			$_SESSION['svnadmin-user'] = $strAuthUser;
			return(1);		// 認証成功
		}
	}

	// 認証失敗
	return(0);

}


// 認証状態をログオフする
function LogoffAuth()
{
	if(!isset($_SESSION)){ session_start(); }
	$_SESSION = array(); // セッション変数を全てクリア
	session_destroy(); // セッションファイルを削除

}


function ChangePassword($strReloadPage, $strLogPage)
{
	if(!isset($_SESSION)){ session_start(); }
	
	if (isset($_SESSION['svnadmin-user']))
	{
		// 既にログオンしてている場合
		if(isset($_POST['newuser']) && isset($_POST['password1']) && isset($_POST['password2'])){
			// パスワードの変更
			if($_POST['password1'] === $_POST['password2']){
				// 禁止文字、文字列長さのチェック
				if(!preg_match("/^[A-Za-z0-9\-]+$/", $_POST['newuser']) || !preg_match("/^[A-Za-z0-9\-]+$/", $_POST['password1']) ||
						strlen($_POST['newuser']) > 20 || strlen($_POST['password1']) > 20){
					return('Error : ユーザ名とパスワードは20文字以内、A-Z,a-z,0-9の文字しか許容されません');
				}
				else{
					// ユーザ名・パスワードの変更
					if(ChangeAuthFile($_POST['newuser'], $_POST['password1']) > 0){
						return('ユーザ名とパスワードを変更しました');
					}
					else{
						return('Error : 認証ファイル '.$strAuthDataFile.' に書き込めません');
					}
				}
			}
			return('Error : パスワードが一致しません');
		}
		// パスワード変更画面を表示する

		print("<form method=\"post\" action=\"./$strReloadPage?mode=chgpasswd\" name=\"form2\">\n");
		print("\t<table border=\"0\" cellpadding=\"2\" cellspacing=\"0\" align=\"center\">\n");
		print("\t<tr><td colspan=\"2\"><strong>ユーザ名とパスワードの変更</strong></td></tr>");
		print("\t<tr><td>新ユーザ</td><td><input name=\"newuser\" size=\"40\" /></td></tr>\n");
		print("\t<tr><td>新パスワード</td><td><input name=\"password1\" type=\"password\" size=\"40\" /></td></tr>\n");
		print("\t<tr><td>もう一度新パスワードを入力</td><td><input name=\"password2\" type=\"password\"size=\"40\" /></td></tr>\n");
		print("\t<tr><td colspan=\"2\"><input type=\"submit\" value=\"変更する\" /></td></tr>\n");
		print("\t</table>\n");
		print("</form>\n");

		return('');
	}

	// 認証失敗
	return('エラー：ログオンしていませんので、パスワードの変更は出来ません');

}

// 認証用データファイルが存在するか確認し、存在しなければ新規作成する
function CheckAuthDataFile(){
	global $strAuthDataFile;
	if(file_exists($strAuthDataFile)){ return(1); }	// 認証ファイルが存在する

	// 新しい認証ファイルを作成する
	$fh = fopen($strAuthDataFile, 'w');
	if($fh){
		fwrite($fh, "# user, password\n");
		fwrite($fh, "test,".md5('password')."\n");
		fclose($fh);
	}
	else{
		return(-1);	// 認証ファイルが作成できない
	}

	return(0);		// 認証ファイルを新規作成した
}

// ユーザ名、パスワードをチェック
function CheckUser($strNewUser, $strNewPassword)
{
	global $strAuthDataFile;
	$strUser = '';
	$strPassword = '';

	// 禁止文字、文字列長さのチェック
	if(!preg_match("/^[A-Za-z0-9\-]+$/", $strNewUser) || !preg_match("/^[A-Za-z0-9\-]+$/", $strNewPassword) ||
			strlen($strNewUser) > 20 || strlen($strNewPassword) > 20){
		return('');
	}
	
	$flag_ok = 0;

	$fh = fopen($strAuthDataFile, 'r');
	if($fh){
		while (!feof($fh)){
			$line = fgets($fh);
			if(strlen($line)<=0 || $line[0] == '#'){ continue; }
			# コンマで切り分ける
			$arr = split(',', $line);
			if(count($arr)<2){ continue; }
			# 文字列の前後に空白,改行,TAB があれば取り除く
			$strUser = trim($arr[0]);
			$strPassword = trim($arr[1]);
			# アカウント確認されれば終了
			if($strUser === $strNewUser && $strPassword === md5($strNewPassword)){
				$flag_ok = 1;
				break;
			}
		}
		fclose($fh);
	}
	else{
			print("<p>Error : Authorization data file read error.</p>\n");
	}


	if($flag_ok == 0){ return(''); }
	
	// 認証成功（ユーザ名を返す）
	return($strUser);
}


// 認証ファイルに新ユーザ名、パスワードをセットする
function ChangeAuthFile($strNewUser, $strNewPassword)
{
	global $strAuthDataFile;
	$fh = fopen($strAuthDataFile, 'w');
	if($fh){
		fwrite($fh, "# user, password\n");
		fwrite($fh, $strNewUser.",".md5($strNewPassword)."\n");
		fclose($fh);
		return(1);		// 成功
	}
	else{
		return(-1);	// 失敗
	}

}


?>

