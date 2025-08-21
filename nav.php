<?php
// nav.php
// Это наш навигационный блок.
// Он будет вставляться на все страницы сайта.
?>
<!-- <nav style="margin-bottom: 20px; padding: 10px; background-color: #f0f0f0; border-radius: 8px;"> -->
<div class="nav-links">
    <?php
    // Получаем имя текущего файла
    $current_page = basename($_SERVER['PHP_SELF']);
    ?>
    <a href="index.php" class="<?php if ($current_page != 'index.php') { echo 'active'; } ?>">Добавить лид</a>
    <a href="about.php" class="<?php if ($current_page != 'about.php') { echo 'active'; } ?>">Статусы лидов</a>
</div>
    <!-- <a href="index.php" style="margin-right: 15px; font-size: 18px; text-decoration: none; color: #333;">Главная</a>
    <a href="about.php" style="font-size: 18px; text-decoration: none; color: #333;">О нас</a> -->
<!-- </nav> -->
<!-- <hr> -->
