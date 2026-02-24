<?php
$baseUrl  = App::baseUrl();
$siteName = App::setting('site_name', 'ShopXPao');
$user     = $user     ?? Session::user();
$isClient = $isClient ?? ($user && ($user['role'] ?? null) === 'client');

// Afficher le mini-dashboard client si c'est un client connecté
if ($isClient) {
    require __DIR__ . '/_client-panel.php';
}
?>

<section class="sx-hero">
  <div class="container py-5">
    <div class="row align-items-center g-4">
      <div class="col-lg-7">
        <span class="sx-pill mb-3 d-inline-flex align-items-center gap-2">
          <i class="bi bi-shield-check"></i> Paiements 100% sécurisés via Moncash, Natcash et aussi par Cartes!
        </span>
        <h1 class="display-5 fw-bold text-white mb-3">
          Achetez et vendez sur <span class="sx-text-accent"><?= Security::escape($siteName) ?></span>
        </h1>
        <p class="text-white-50 fs-5 mb-4">
          Une plateforme ecommerce multi-boutiques où chaque vendeur a sa propre boutique indépendante.
        </p>

        <div class="d-flex flex-wrap gap-2">
          <a href="<?= $baseUrl ?>/boutiques" class="btn btn-light btn-lg">
            <i class="bi bi-shop me-1"></i> Explorer les boutiques
          </a>
          <a href="<?= $baseUrl ?>/register" class="btn btn-outline-light btn-lg">
            <i class="bi bi-plus-circle me-1"></i> Devenir vendeur
          </a>
        </div>
      </div>

      <div class="col-lg-5">
        <div class="sx-hero-card p-4">
          <div class="d-flex align-items-center justify-content-between">
            <div>
              <div class="text-white fw-semibold">Promos & nouveautés</div>
              <div class="text-white-50 small">Fraiche sur le site</div>
            </div>
            <i class="bi bi-lightning-charge-fill fs-2 text-white"></i>
          </div>

          <div class="row g-3 mt-2">
            <div class="col-6">
              <div class="sx-mini-card p-3">
                <div class="fw-semibold">Flash Deals</div>
                <div class="small text-muted">Produits en promo</div>
              </div>
            </div>
            <div class="col-6">
              <div class="sx-mini-card p-3">
                <div class="fw-semibold">Boutiques</div>
                <div class="small text-muted">Vendeurs vérifiés</div>
              </div>
            </div>
            <div class="col-12">
              <div class="sx-mini-card p-3">
                <div class="fw-semibold">Livraison</div>
                <div class="small text-muted">Partout en Haïti</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="container my-5">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h4 fw-bold mb-0">Boutiques en vedette</h2>
    <a href="<?= $baseUrl ?>/boutiques" class="link-sx">Voir tout <i class="bi bi-arrow-right"></i></a>
  </div>

  <?php if (empty($boutiques)): ?>
    <div class="alert alert-info">Aucune boutique active pour le moment.</div>
  <?php else: ?>
    <div class="row g-3">
      <?php foreach ($boutiques as $b): ?>
        <div class="col-12 col-md-6 col-lg-3">
          <a class="sx-shop-card d-block" href="<?= $baseUrl ?>/boutique/<?= Security::escape($b['slugBoutique']) ?>">
            <div class="sx-shop-thumb">
              <?php if (!empty($b['logo'])): ?>
                <img src="<?= $baseUrl . $b['logo'] ?>" alt="Logo" class="img-fluid">
              <?php else: ?>
                <div class="sx-shop-thumb-placeholder"><i class="bi bi-shop"></i></div>
              <?php endif; ?>
            </div>
            <div class="p-3">
              <div class="fw-semibold text-dark"><?= Security::escape($b['nomBoutique']) ?></div>
              <div class="text-muted small sx-2lines"><?= Security::escape($b['description'] ?? '') ?></div>
            </div>
          </a>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<section class="container my-5">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h4 fw-bold mb-0">Produits en vedette</h2>
  </div>

<?php if (empty($produitsVedette)): ?>
    <div class="alert alert-warning">Aucun produit en vedette pour le moment.</div>
<?php else: ?>
    <div class="row g-3">
      <?php foreach ($produitsVedette as $p): ?>
        <div class="col-12 col-md-6 col-lg-3">
          <div class="card sx-product-card h-100">
            <a href="<?= $baseUrl ?>/boutique/produit?id=<?= (int)$p['idProduit'] ?>">
              <?php if (!empty($p['image'])): ?>
                <img src="<?= $baseUrl . $p['image'] ?>" class="card-img-top sx-product-img" alt="">
              <?php else: ?>
                <div class="sx-product-img-placeholder"><i class="bi bi-image"></i></div>
              <?php endif; ?>
            </a>
            <div class="card-body">
              <div class="small text-muted mb-1"><?= Security::escape($p['nomBoutique']) ?></div>
              <div class="fw-semibold sx-2lines"><?= Security::escape($p['nomProduit']) ?></div>

              <div class="mt-2">
                <?php if (!empty($p['prixPromo'])): ?>
                  <span class="fw-bold text-danger"><?= number_format($p['prixPromo'], 0, ',', ' ') ?> G</span>
                  <span class="text-muted text-decoration-line-through small ms-2"><?= number_format($p['prix'], 0, ',', ' ') ?> G</span>
                <?php else: ?>
                  <span class="fw-bold"><?= number_format($p['prix'], 0, ',', ' ') ?> G</span>
                <?php endif; ?>
              </div>
            </div>
            <div class="card-footer bg-white border-0 p-3 pt-0">
              <button class="btn btn-sx-primary w-100" 
                      onclick="addToCart(<?= $p['idProduit'] ?>, 
                                        '<?= addslashes($p['nomProduit']) ?>', 
                                        <?= $p['prixPromo'] ?: $p['prix'] ?>,
                                        '<?= addslashes($p['image'] ?? '') ?>',
                                        '<?= addslashes($p['nomBoutique']) ?>')">
                <i class="bi bi-cart-plus me-1"></i> Ajouter au panier
              </button>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<section class="container my-5">
  <div class="row g-3">
    <div class="col-12 col-md-6 col-lg-3">
      <div class="sx-feature p-4">
        <i class="bi bi-shield-lock fs-2"></i>
        <div class="fw-bold mt-2">Paiement sécurisé</div>
        <div class="text-muted small">Moncash, Natcash, Cartes</div>
      </div>
    </div>
    <div class="col-12 col-md-6 col-lg-3">
      <div class="sx-feature p-4">
        <i class="bi bi-truck fs-2"></i>
        <div class="fw-bold mt-2">Livraison</div>
        <div class="text-muted small">Partout en Haïti</div>
      </div>
    </div>
    <div class="col-12 col-md-6 col-lg-3">
      <div class="sx-feature p-4">
        <i class="bi bi-headset fs-2"></i>
        <div class="fw-bold mt-2">Support</div>
        <div class="text-muted small">Assistance rapide</div>
      </div>
    </div>
    <div class="col-12 col-md-6 col-lg-3">
      <div class="sx-feature p-4">
        <i class="bi bi-shop-window fs-2"></i>
        <div class="fw-bold mt-2">Multi-boutiques</div>
        <div class="text-muted small">Plus de flexibilité</div>
      </div>
    </div>
  </div>
</section>