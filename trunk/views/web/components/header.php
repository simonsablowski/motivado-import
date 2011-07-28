<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
		<meta http-equiv="Content-Language" content="en"/>
		<title><? echo $this->localize($title); ?></title>
		<base href="<? echo $this->getApplication()->getConfiguration('baseUrl'); ?>"/>
		<link href="<? echo $this->getApplication()->getConfiguration('cheeseUrl'); ?>web/css/style.css" rel="stylesheet" title="Default" type="text/css" />
		<link href="web/css/style.css" rel="stylesheet" title="Default" type="text/css" />
		<script type="text/javascript" src="<? echo $this->getApplication()->getConfiguration('cheeseUrl'); ?>web/js/jquery-1.4.2.min.js"></script>
		<script type="text/javascript" src="<? echo $this->getApplication()->getConfiguration('cheeseUrl'); ?>web/js/cheese.js"></script>
	</head>
	<body>
<? if (isset($message) && !empty($message)): ?>
			<div id="message">
				<? echo $this->localize($message); ?>
			</div>
<? endif; ?>
		<div id="document">
