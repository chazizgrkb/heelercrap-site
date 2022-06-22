<?php
require('lib/common.php');

$twig = twigloader();

$allSongs = query("SELECT * FROM posts");

echo $twig->render('index.twig', [
	'all_songs' => $allSongs
]);