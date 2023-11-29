<?php

use OCA\Stt\AppInfo\Application;
use OCP\Util;

$appId = Application::APP_ID;
Util::addScript($appId, $appId . '-resultPage');

?>

<div id="stt_helper-content"></div>
