<?php
require('lib/common.php');

$id = isset($_GET['id']) ? $_GET['id'] : null;

$post = fetch("SELECT * FROM posts WHERE id = ?", [$id]);

if (!$post) error('404', "Invalid post ID.");

$twig = twigloader();
echo $twig->render('post.twig', [
	'post' => $post
]);
