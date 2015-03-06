<?php
$app = JFactory::getApplication();

if ($app->getName() != 'site') {
	require_once 'override.php';
}