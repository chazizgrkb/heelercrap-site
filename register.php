<?php
require('lib/common.php');

if ($log) redirect('./');

$error = '';

if (isset($_POST['action'])) {
	$name = (isset($_POST['name']) ? $_POST['name'] : null);
	$mail = (isset($_POST['mail']) ? $_POST['mail'] : null);
	$pass = (isset($_POST['pass']) ? $_POST['pass'] : null);
	$pass2 = (isset($_POST['pass2']) ? $_POST['pass2'] : null);

	if (!isset($name)) $error .= 'Blank username. ';
	if (!isset($mail)) $error .= 'Blank email. ';
	if (!isset($pass) || strlen($pass) < 6) $error .= 'Password is too short. ';
	if (!isset($pass2) || $pass != $pass2) $error .= "The passwords don't match. ";
	if (result("SELECT COUNT(*) FROM users WHERE name = ?", [$name])) $error .= "Username has already been taken. ";
	if (!preg_match('/[a-zA-Z0-9_]+$/', $name)) $error .= "Username contains invalid characters (Only alphanumeric and underscore allowed). ";
	if (!filter_var($mail, FILTER_VALIDATE_EMAIL)) $error .= "Email isn't valid. ";
	if (result("SELECT COUNT(*) FROM users WHERE email = ?", [$mail])) $error .= "You've already registered an account using this email address. ";
	if (result("SELECT COUNT(*) FROM users WHERE ip = ?", [$_SERVER['REMOTE_ADDR']])) $error .= "Creating multiple accounts (alts) aren't allowed. ";

	if ($error == '') {
		$token = register($name, $pass, $mail);

		setcookie($cookieName, $token, 2147483647);

		redirect('./?rd');
	}
}

$twig = twigloader();
echo $twig->render('register.twig', ['error' => $error]);