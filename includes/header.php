<?php
/**Site header - included on every page
 * Contains navigation and page structure opening tags*/
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' | ' : ''; ?>Construction Dashboard</title>
    <link rel="stylesheet" href="/Construction-dashboard/css/style.css">
</head>
<body>
    <header class="site-header">
        <div class="container">
            <h1 class="site-title">🏗️ Construction Dashboard</h1>
            <nav class="main-nav">
    <a href="<?php echo str_repeat('../', substr_count($_SERVER['PHP_SELF'], '/') - 2); ?>index.php">Projects</a>
    <a href="<?php echo str_repeat('../', substr_count($_SERVER['PHP_SELF'], '/') - 2); ?>about.php">About</a>
</nav>
        </div>
    </header>
    <main class="container">