<?php
/**
 * Controlador de bonds
 */
require dirname(__FILE__).'/../class/controller/BondsController.php';
$_SESSION['bonds-screen-ok'] = false;

if (isset($_SESSION["o"])) {
	$_POST['operator'] = $_SESSION["o"];
	unset($_SESSION["o"]);
}

new BondsController('bonds');