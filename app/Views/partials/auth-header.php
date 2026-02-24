<?php
$baseUrl  = App::baseUrl();
$siteName = App::setting('site_name', 'ShopXPao');
$authLogo = $baseUrl . '/assets/images/shupxpaologo.png';
?>
<header class="auth-header-bar">
  <div class="container py-3 d-flex align-items-center">
    <a href="<?= $baseUrl ?>/" class="d-flex align-items-center text-decoration-none">
      <img src="<?= $authLogo ?>" alt="Logo <?= Security::escape($siteName) ?>" class="auth-header-logo-img">
      <span class="auth-header-name ms-2"><?= Security::escape($siteName) ?></span>
    </a>
  </div>
</header>