<?php
/**
 * Opening HTML. Set before including:
 *   $page_title (string)  – text for <title>
 *   $page_css   (array)   – stylesheet paths relative to public/
 *   $body_class (string)  – optional class for <body>
 */
$page_css   = $page_css ?? ['assets/css/dashboard.css', 'assets/css/chat.css'];
$body_class = $body_class ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e($page_title ?? 'Aatman') ?> | Aatman</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <?php foreach ($page_css as $css): ?>
        <link rel="stylesheet" href="<?= e(url($css)) ?>">
    <?php endforeach; ?>
</head>
<body class="<?= e($body_class) ?>">
