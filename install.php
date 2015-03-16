<?
	extract($_POST);

	function endPage() {
		echo "</body></html>";
		exit;
	}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html lang="ko" xml:lang="ko" xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="content-type" content="text/html;charset=utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0,minimum-scale=1.0,user-scalable=no,target-densitydpi=medium-dpi">
<meta name="apple-mobile-web-app-capable" content="no">
<meta name="apple-mobile-web-app-status-bar-style" content="black">
	<title>홈페이지 인스톨</title>
	<style>
	label span {width:80px; display:inline-block;}
	</style>
</head>
<body>

	<form name="frmWrite" method="post">
	<input type="hidden" name="rq" value="1">
	<div><b>설치관리자</b></div>
	<div><label><span>아이디</span> <input type="text" name="id" value="<?=$id?>"></label></div>
	<div><label><span>비밀번호</span> <input type="password" name="pw" value="<?=$pw?>"></label></div>
	<div><b>DB 정보</b></div>
	<div><label><span>HOST</span> <input type="text" name="db_host" value="<?=$db_host ? $db_host : 'localhost'?>"></label></div>
	<div><label><span>PORT</span> <input type="text" name="db_port" value="<?=$db_port ? $db_port : '3306'?>"></label></div>
	<div><label><span>DB 명</span> <input type="text" name="db_name" value="<?=$db_name?>"></label></div>
	<div><label><span>아이디</span> <input type="text" name="db_user" value="<?=$db_user?>"></label></div>
	<div><label><span>비밀번호</span> <input type="text" name="db_pass" value="<?=$db_pass?>"></label></div>
	<div><label><span>헤더</span> <input type="text" name="db_head" value="<?=$db_head?>"></label></div>
	<div><input type="submit" value="설치하기"></div>
	</form>
<?if(!$_POST['rq']) endPage(); ?>

<?
	$host_name = "nsone.cafe24.com";
	// 서버 인증...
	$fp = fsockopen($host_name, 80, $errno, $errstr, 30);
	if (!$fp){
		echo '서버가 응답하지 않습니다.<br/>';
		endPage();
	}

	$http_param = "id={$_POST['id']}&pw={$_POST['pw']}";
	$http_req = "POST /inst/?m=install&a=getfile HTTP/1.1\r\n";
	$http_req .= "Host: {$host_name}\r\n";
	$http_req .= "Content-Type: application/x-www-form-urlencoded\r\n";
	$http_req .= "Content-Length: ".strlen($http_param)."\r\n";
	$http_req .= "Connection: Close\r\n\r\n";

	fwrite($fp, $http_req);
	fwrite($fp, $http_param);

	$buf = "";
	while (!feof($fp)){
		$buf .= fgets($fp, 2048);
	}
	fclose($fp);

	$buf = trim($buf);
	$buf = substr($buf, strpos($buf,"\r\n\r\n"));

	// 응답결과
	$result = json_decode($buf);
	if($result->err_code) {
		echo $result->err_msg.'<br/>';
		endPage();
	}

	$fp = fsockopen($host_name, 80, $errno, $errstr, 30);
	if (!$fp){
		echo '서버가 응답하지 않습니다.<br/>';
		endPage();
	}

	$dest	= fopen($result->filename, 'wb');

	$http_req = "GET /_storage/{$result->filename} HTTP/1.1\r\n";
	$http_req .= "Host: {$host_name}\r\n";
	$http_req .= "Content-Type: application/x-www-form-urlencoded\r\n";
	$http_req .= "Connection: Close\r\n\r\n";

	fwrite($fp, $http_req);
	$buf = "";
	$header_check = false;
	while (!feof($fp)){
		if($header_check === false) {
			$buf .= fgets($fp, 2048);
			$pos	= strpos($buf, "\r\n\r\n");
			if($pos) {
				$header_check = true;
				fwrite($dest, substr($buf, $pos+4));
			}
			continue;
		}
		fwrite($dest, fgets($fp, 2048));
	}
	fclose($fp);
	fclose($dest);
	

	exec("tar -xvzf {$result->filename}");
	exec("chmod -R 707 *");
	unlink($result->filename);

	include_once '_tmp/backup/db_schema.php';
	
	unlink('install.php');
	header("location:/{$db_head}/");
?>

</body>
</html>
