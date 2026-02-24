<?php
$baseUrl  = App::baseUrl();
$siteName = App::setting('site_name', 'ShopXPao');

$fb  = App::setting('social_facebook', '#');
$ig  = App::setting('social_instagram', '#');
$x   = App::setting('social_twitter', '#');
$wa  = App::setting('social_whatsapp', '#');
$email   = App::setting('contact_email', 'contact@shopxpao.ht');
$phone   = App::setting('contact_phone', '+509 3995 07 54 / +509 4214 59 89');
$address = App::setting('contact_address', '#29 Gerardo, rue chavannes prolongée, Pétion-Ville, Port-au-Prince, Haïti');
$footerText = App::setting('footer_text', '© ' . date('Y') . ' ' . $siteName . ' - Tous droits réservés.');
?>

<style>
.sx-footer {
    background: #172026; 
    border-top: 1px solid rgba(4, 191, 157, 0.15);
}

/* Liens du footer */
.sx-footer-link {
    color: #94a3b8;
    text-decoration: none;
    transition: color 0.2s ease;
    line-height: 2;
    display: inline-block;
}

.sx-footer-link:hover {
    color: #04BF9D;
}

/* Titres */
.sx-footer h5, .sx-footer h6 {
    color: white;
    margin-bottom: 1.2rem;
    font-weight: 600;
    letter-spacing: 0.3px;
}

.sx-footer h6 {
    font-size: 1rem;
}

/* Réseaux sociaux - version plus discrète */
.sx-social {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: 8px; 
    font-size: 1.1rem;
    text-decoration: none;
    transition: all 0.2s ease;
    background: rgba(255, 255, 255, 0.05);
    color: #94a3b8 !important;
}

.sx-social:hover {
    background: #04BF9D;
    color: white !important;
    transform: translateY(-2px);
}

/* Paiements */
.sx-payment-item {
    display: flex;
    align-items: center;
    gap: 8px;
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 8px;
    padding: 8px 12px;
    transition: all 0.2s ease;
}

.sx-payment-item:hover {
    background: rgba(4, 191, 157, 0.1);
    border-color: #04BF9D;
}

.sx-payment-logo {
    height: 24px;
    width: auto;
    object-fit: contain;
}

.sx-payment-label {
    font-size: 0.8rem;
    color: #94a3b8;
}

/* Contact */
.sx-contact-item {
    display: flex;
    gap: 10px;
    margin-bottom: 1rem;
    color: #94a3b8;
    font-size: 0.9rem;
    line-height: 1.5;
}

.sx-contact-item i {
    color: #04BF9D;
    margin-top: 3px;
    min-width: 18px;
}

/* Séparateur */
.sx-footer hr {
    opacity: 0.1;
    margin: 1.5rem 0;
}

/* Copyright */
.sx-copyright {
    color: #64748b;
    font-size: 0.85rem;
    text-align: center;
}

/* Responsive */
@media (max-width: 768px) {
    .sx-footer .row {
        text-align: center;
    }
    
    .sx-contact-item {
        justify-content: center;
    }
    
    .sx-payment-item {
        justify-content: center;
    }
}
</style>

<footer class="sx-footer py-5">
    <div class="container">
        <div class="row g-4">
            
            <!-- Colonne 1 : À propos -->
            <div class="col-lg-4">
                <h5><?= Security::escape($siteName) ?></h5>
                <p class="text-muted mb-3" style="color: #94a3b8 !important;">
                    <?= Security::escape(App::setting('site_description', 'Plateforme e-commerce multi-boutiques.')) ?>
                </p>
                <div class="d-flex gap-2">
                    <a class="sx-social" href="<?= Security::escape($fb) ?>" target="_blank" title="Facebook">
                        <i class="bi bi-facebook"></i>
                    </a>
                    <a class="sx-social" href="<?= Security::escape($ig) ?>" target="_blank" title="Instagram">
                        <i class="bi bi-instagram"></i>
                    </a>
                    <a class="sx-social" href="<?= Security::escape($x) ?>" target="_blank" title="X (Twitter)">
                        <i class="bi bi-twitter-x"></i>
                    </a>
                    <?php if ($wa): ?>
                        <a class="sx-social" href="https://wa.me/<?= preg_replace('/\D+/', '', $wa) ?>" target="_blank" title="WhatsApp">
                            <i class="bi bi-whatsapp"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Colonne 2 : Liens rapides -->
            <div class="col-lg-2">
                <h6>Liens rapides</h6>
                <ul class="list-unstyled">
                    <li><a class="sx-footer-link" href="<?= $baseUrl ?>/boutiques">Boutiques</a></li>
                    <li><a class="sx-footer-link" href="<?= $baseUrl ?>/register">Créer un compte</a></li>
                    <li><a class="sx-footer-link" href="<?= $baseUrl ?>/login">Connexion</a></li>
                    <li><a class="sx-footer-link" href="<?= $baseUrl ?>/panier">Panier</a></li>
                </ul>
            </div>

            <!-- Colonne 3 : Paiements -->
            <div class="col-lg-3">
                <h6>Paiements acceptés</h6>
                <div class="d-flex flex-column gap-2">
                    <div class="sx-payment-item">
                        <img src="<?= $baseUrl ?>/assets/images/MonCash-logo.png" alt="MonCash" class="sx-payment-logo">
                        <span class="sx-payment-label">MonCash</span>
                    </div>
                    <div class="sx-payment-item">
                        <img src="<?= $baseUrl ?>/assets/images/NatCash-logo.png" alt="NatCash" class="sx-payment-logo">
                        <span class="sx-payment-label">NatCash</span>
                    </div>
                    <div class="sx-payment-item">
                        <img src="<?= $baseUrl ?>/assets/images/card.png" alt="Carte bancaire" class="sx-payment-logo">
                        <span class="sx-payment-label">Cartes bancaires</span>
                    </div>
                </div>
            </div>

            <!-- Colonne 4 : Contact -->
            <div class="col-lg-3">
                <h6>Contact</h6>
                <div class="sx-contact-item">
                    <i class="bi bi-geo-alt"></i>
                    <span><?= Security::escape($address) ?></span>
                </div>
                <div class="sx-contact-item">
                    <i class="bi bi-telephone"></i>
                    <span><?= Security::escape($phone) ?></span>
                </div>
                <div class="sx-contact-item">
                    <i class="bi bi-envelope"></i>
                    <span><?= Security::escape($email) ?></span>
                </div>
            </div>
        </div>

        <hr>

        <!-- Copyright -->
        <div class="sx-copyright">
            <?= Security::escape($footerText) ?>
        </div>
    </div>
</footer>