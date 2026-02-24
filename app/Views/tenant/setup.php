<?php $baseUrl = App::baseUrl(); ?>

<style>
    .tenant-main { margin-left: 0 !important; width: 100% !important; padding: 0 !important; }
    
    /* PALETTE COULEURS */
    :root {
        --sx-primary: #04BF9D;
        --sx-light: #04BFAD;
        --sx-dark: #027373;
        --sx-accent: #5FCDD9;
        --sx-black: #172026;
    }

    /* BACKGROUND PREMIUM */
    .setup-page {
        min-height: 100vh;
        background: radial-gradient(circle at top right, var(--sx-primary), var(--sx-dark));
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        overflow: hidden;
    }

    /* Cercles décoratifs en fond */
    .setup-page::before {
        content: ''; position: absolute; top: -100px; left: -100px;
        width: 400px; height: 400px; border-radius: 50%;
        background: rgba(255,255,255,0.05); filter: blur(60px);
    }
    .setup-page::after {
        content: ''; position: absolute; bottom: -50px; right: -50px;
        width: 300px; height: 300px; border-radius: 50%;
        background: rgba(4, 191, 157, 0.2); filter: blur(40px);
    }

    /* CARTE PRINCIPALE */
    .setup-card {
        background: #ffffff;
        border-radius: 24px;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        overflow: hidden;
        max-width: 950px;
        width: 95%;
        display: flex;
        position: relative;
        z-index: 10;
        min-height: 550px;
    }

    /* COLONNE GAUCHE (Visuel) */
    .setup-visual {
        flex: 1;
        background: linear-gradient(135deg, var(--sx-dark) 0%, var(--sx-black) 100%);
        padding: 3rem;
        display: flex;
        flex-direction: column;
        justify-content: center;
        color: white;
        position: relative;
    }

    .setup-visual::before {
        content: ''; position: absolute; inset: 0;
        background-image: url('data:image/svg+xml,%3Csvg width="20" height="20" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"%3E%3Cg fill="%23ffffff" fill-opacity="0.03"%3E%3Cpath d="M0 0h20L0 20z"/%3E%3C/g%3E%3C/svg%3E');
    }

    /* COLONNE DROITE (Formulaire) */
    .setup-form {
        flex: 1.2;
        padding: 3.5rem;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    /* TYPOGRAPHIE & INPUTS */
    h1 { font-size: 2.2rem; font-weight: 800; line-height: 1.1; margin-bottom: 1rem; }
    p.lead { font-size: 1rem; opacity: 0.8; font-weight: 300; }

    .form-label {
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        color: var(--sx-dark);
        margin-bottom: 0.5rem;
    }

    .form-control {
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        padding: 0.8rem 1rem;
        font-size: 1rem;
        transition: all 0.3s;
        background: #f8fafc;
    }

    .form-control:focus {
        border-color: var(--sx-primary);
        background: #fff;
        box-shadow: 0 0 0 4px rgba(4, 191, 157, 0.1);
    }

    .input-group-text {
        background: #fff;
        border: 2px solid #e2e8f0;
        border-right: none;
        border-top-left-radius: 12px;
        border-bottom-left-radius: 12px;
        color: var(--sx-dark);
        font-weight: 600;
    }
    
    .input-group .form-control { border-left: none; }

    /* BOUTON D'ACTION */
    .btn-submit {
        background: linear-gradient(90deg, var(--sx-primary), var(--sx-light));
        border: none;
        color: white;
        padding: 1rem;
        border-radius: 12px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        width: 100%;
        transition: transform 0.2s, box-shadow 0.2s;
        margin-top: 1rem;
    }

    .btn-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px -5px rgba(4, 191, 157, 0.4);
        color: #fff;
    }

    /* LOGO BOX */
    .logo-box {
        background: rgba(255,255,255,0.1);
        backdrop-filter: blur(10px);
        border-radius: 16px;
        padding: 1rem;
        display: inline-flex;
        align-items: center;
        gap: 1rem;
        margin-top: auto;
        border: 1px solid rgba(255,255,255,0.1);
    }
    .logo-img { width: 40px; height: auto; }

    /* RESPONSIVE */
    @media (max-width: 991px) {
        .setup-card { flex-direction: column; max-width: 500px; }
        .setup-visual { padding: 2rem; min-height: 200px; }
        .setup-form { padding: 2rem; }
    }
</style>

<div class="setup-page">
    <div class="setup-card">
        
        <!-- VISUEL -->
        <div class="setup-visual">
            <h1>Propulsez<br>votre business.</h1>
            <p class="lead">Créez votre boutique en quelques secondes et commencez à vendre sur la marketplace n°1.</p>
            
            <div class="logo-box">
                <img src="<?= $baseUrl ?>/assets/images/shupxpaologo.png" alt="ShopXPao" class="logo-img">
                <div>
                    <div class="fw-bold text-white">Partenaire ShopXPao</div>
                    <div class="small text-white-50">Solution certifiée</div>
                </div>
            </div>
        </div>

        <!-- FORMULAIRE -->
        <div class="setup-form">
            <div class="mb-4">
                <h3 class="fw-bold text-dark m-0">Configuration</h3>
                <p class="text-muted small">Informations de votre nouvelle boutique</p>
            </div>

            <form action="<?= $baseUrl ?>/vendeur/creer" method="POST">
                <?= CSRF::field() ?>
                
                <!-- Nom -->
                <div class="mb-3">
                    <label class="form-label">Nom de l'enseigne</label>
                    <input type="text" name="nomBoutique" class="form-control" 
                           placeholder="Ex: Mode Haïti" required 
                           oninput="generateSlug(this.value)">
                </div>

                <!-- Slug -->
                <div class="mb-3">
                    <label class="form-label">Adresse Web (Slug)</label>
                    <div class="input-group">
                        <span class="input-group-text">@</span>
                        <input type="text" name="slugBoutique" id="shopSlug" class="form-control" 
                               placeholder="mode-haiti" required readonly style="background:#f1f5f9; cursor:not-allowed;">
                    </div>
                </div>

                <!-- Description -->
                <div class="mb-4">
                    <label class="form-label">Description courte</label>
                    <textarea name="description" class="form-control" rows="3" 
                              placeholder="Que vendez-vous ? (Vêtements, Tech, Art...)"></textarea>
                </div>

                <button type="submit" class="btn-submit">
                    Lancer ma boutique <i class="bi bi-rocket-takeoff-fill ms-2"></i>
                </button>
            </form>
        </div>

    </div>
</div>

<script>
    function generateSlug(text) {
        const slug = text.toLowerCase()
            .normalize("NFD").replace(/[\u0300-\u036f]/g, "") 
            .replace(/^-+|-+$/g, ''); 
        
        document.getElementById('shopSlug').value = slug;
    }
</script>