<?
/**
 * Provides URL shortening functionality, like tinyurl.com, bit.ly, ow.ly and other popular services.
 * (c) 2011, it-in, http://it-in.ru
 * @author Sergey Kovalev <kovalev_s@it-in.ru>
 * @version 0.1
 */

// Load library
require_once 'tinyurl.php';

/**
 * Checks if user is allowed to create short URLS.
 */
function checkPermissions()
{
	/*
	/ Uncomment if you need to allow URL shortening to specific user groups.
	*/
	/*if(!CSite::InGroup((array(1))))
	{
		ob_end_clean();
		header('HTTP/1.1 403 Forbidden');
		echo 'Access denied.';
		exit;
	}*/
}

// Redirect immediately if short code is passed
if(!empty($_GET['SHORT_CODE']))
{
	require_once $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php';
	TinyURL::redirect($_GET['SHORT_CODE']);
}

// AJAX-request
if($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest')
{
	require_once $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php';
	checkPermissions();
	echo TinyURL::shorten(trim($_REQUEST['url']));
	exit;
}

// Web-interface
require_once $_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php";
checkPermissions();
$APPLICATION->SetTitle("Сокращатель ссылок");
?>

<style type="text/css">
	#tinyurl-url
	{
		width:96%;
		font-size:200%;
		padding:0.5ex;
	}
	label[for=tinyurl-url]
	{
		opacity:0.5;
		font-size:70%;
	}
	#tinyurl-result
	{
		width:96%;
		margin-top:1em;
		font-size:200%;
		padding:0.5ex;
		background-color:#FFFF8E;
		display:none;
	}
</style>

<div id="tinyurl">
	<form>
		<input type='text' id='tinyurl-url' />
		<label for='tinyurl-url'>Введите URL и нажмите Enter</label>
	</form>
</div>

<div id="tinyurl-result"></div>

<script type="text/javascript">
$(function(){
	$('#tinyurl-url').focus();
	$('#tinyurl form').submit(function() {
		$.ajax({
		  type: "POST",
		  data: ({url : $('#tinyurl-url').val()}),
		  success: function(data){
			$('#tinyurl-result').hide().text(data).fadeIn('slow');
		  }
		});
		return false;
	});
});
</script>

<?require_once $_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php";?>
