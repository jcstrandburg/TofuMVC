<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
	<meta name="description" content="">
	<meta name="author" content="">
	<link rel="icon" href="../../favicon.ico">

	<title>Tofu Demonstration</title>
</head>

<body>
<?php $this->style('bootstrap.css'); ?> <!--Marker1-->
<?php $this->script('https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js'); ?> <!--Marker2-->
<?php $this->script('bootstrap.min.js'); ?> <!--Marker3-->

<div class="container">
  <div class="starter-template">
	<h1>Tofu muthafucka</h1>
	<p class="lead">This is the standard header for tofu bitch.</p>
  </div>
</div><!-- /.container -->

<div class='container'>
	<?php foreach (Tofu::getErrorStrings() as $errstr):?>
		<div class='alert alert-danger'><?=$errstr?></div>
	<?php endforeach;?>
	<?php foreach (Tofu::getWarningStrings() as $warnstr):?>
		<div class='alert alert-warning'><?=$warnstr?></div>
	<?php endforeach;?>    	 
</div>
