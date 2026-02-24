<?php
$baseUrl = App::baseUrl();
$pageIcon = $pageIcon ?? 'bi-tools';
$pageMessage = $pageMessage ?? 'Cette page est en cours de développement';
?>

<div class="admin-layout">
    <?php require __DIR__ . '/partials/sidebar.php'; ?>
    
    <div class="admin-main">
        <?php require __DIR__ . '/partials/topbar.php'; ?>
        
        <div class="coming-soon-container">
            <div class="coming-soon-card">
                <div class="coming-soon-icon">
                    <i class="bi <?= $pageIcon ?>"></i>
                </div>
                <h1 class="coming-soon-title">Page en construction</h1>
                <p class="coming-soon-message"><?= $pageMessage ?></p>
                <div class="coming-soon-progress">
                    <div class="progress-bar" style="width: 75%"></div>
                </div>
                <p class="coming-soon-eta">Disponible prochainement</p>
                <a href="<?= $baseUrl ?>/admin" class="coming-soon-btn">
                    <i class="bi bi-arrow-left"></i>
                    Retour au tableau de bord
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.coming-soon-container {
    min-height: calc(100vh - 140px);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem;
}

.coming-soon-card {
    max-width: 500px;
    width: 100%;
    background: white;
    border-radius: 30px;
    padding: 3rem 2rem;
    text-align: center;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    border: 1px solid #e2e8f0;
    animation: slideUp 0.5s ease;
}

.coming-soon-icon {
    width: 100px;
    height: 100px;
    background: linear-gradient(135deg, #04BF9D15, #02737315);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 2rem;
    font-size: 3rem;
    color: #04BF9D;
}

.coming-soon-title {
    font-size: 2rem;
    font-weight: 800;
    color: #1e293b;
    margin-bottom: 1rem;
}

.coming-soon-message {
    color: #64748b;
    font-size: 1.1rem;
    margin-bottom: 2rem;
    line-height: 1.6;
}

.coming-soon-progress {
    height: 8px;
    background: #f1f5f9;
    border-radius: 4px;
    margin-bottom: 1rem;
    overflow: hidden;
}

.coming-soon-progress .progress-bar {
    height: 100%;
    background: linear-gradient(90deg, #04BF9D, #027373);
    border-radius: 4px;
    animation: progressPulse 2s infinite;
}

.coming-soon-eta {
    color: #94a3b8;
    font-size: 0.9rem;
    margin-bottom: 2rem;
}

.coming-soon-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.8rem 2rem;
    background: linear-gradient(135deg, #04BF9D, #027373);
    color: white;
    border-radius: 50px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
}

.coming-soon-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 20px -5px rgba(4, 191, 157, 0.4);
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes progressPulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.8; }
}
</style>