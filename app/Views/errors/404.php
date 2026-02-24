<?php
$baseUrl = App::baseUrl();
$siteName = App::setting('site_name', 'ShopXPao');
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page non trouvée | <?= Security::escape($siteName) ?></title>
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px;
        }

        .error-container {
            max-width: 800px;
            width: 100%;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 30px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            overflow: hidden;
            animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .error-header {
            background: linear-gradient(135deg, #04BF9D, #027373);
            padding: 40px 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .error-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.2) 0%, transparent 70%);
            animation: rotate 20s linear infinite;
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .error-code {
            font-size: 8rem;
            font-weight: 900;
            color: white;
            text-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
            line-height: 1;
            margin-bottom: 10px;
            position: relative;
            z-index: 2;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .error-message {
            font-size: 1.5rem;
            font-weight: 600;
            color: white;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: relative;
            z-index: 2;
        }

        .error-body {
            padding: 50px 40px;
            text-align: center;
            background: white;
        }

        .error-icon {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, #04BF9D20, #02737320);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            font-size: 4rem;
            color: #04BF9D;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.1); opacity: 0.8; }
        }

        .error-title {
            font-size: 2rem;
            font-weight: 800;
            color: #1e293b;
            margin-bottom: 15px;
        }

        .error-description {
            font-size: 1.1rem;
            color: #64748b;
            line-height: 1.6;
            margin-bottom: 30px;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }

        .search-box {
            max-width: 400px;
            margin: 0 auto 30px;
            position: relative;
        }

        .search-box input {
            width: 100%;
            padding: 15px 20px;
            padding-right: 60px;
            border: 2px solid #e2e8f0;
            border-radius: 50px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .search-box input:focus {
            outline: none;
            border-color: #04BF9D;
            box-shadow: 0 0 0 3px rgba(4, 191, 157, 0.1);
        }

        .search-box button {
            position: absolute;
            right: 5px;
            top: 5px;
            bottom: 5px;
            width: 50px;
            background: #04BF9D;
            border: none;
            border-radius: 50px;
            color: white;
            font-size: 1.2rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .search-box button:hover {
            background: #027373;
            transform: scale(1.05);
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn-home {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 30px;
            background: linear-gradient(135deg, #04BF9D, #027373);
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 10px 20px -5px rgba(4, 191, 157, 0.4);
        }

        .btn-home:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px -5px rgba(4, 191, 157, 0.6);
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 30px;
            background: white;
            color: #1e293b;
            text-decoration: none;
            border: 2px solid #e2e8f0;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-back:hover {
            border-color: #04BF9D;
            color: #04BF9D;
            transform: translateX(-5px);
        }

        .suggestions {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px dashed #e2e8f0;
        }

        .suggestions-title {
            font-size: 0.9rem;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 15px;
        }

        .suggestions-links {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .suggestions-links a {
            color: #64748b;
            text-decoration: none;
            font-size: 0.95rem;
            transition: color 0.3s ease;
        }

        .suggestions-links a:hover {
            color: #04BF9D;
        }

        .suggestions-links a i {
            margin-right: 5px;
            font-size: 0.8rem;
        }

        .error-footer {
            padding: 20px;
            background: #f8fafc;
            text-align: center;
            color: #94a3b8;
            font-size: 0.9rem;
            border-top: 1px solid #e2e8f0;
        }

        .error-footer a {
            color: #04BF9D;
            text-decoration: none;
            font-weight: 600;
        }

        .error-footer a:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .error-code {
                font-size: 6rem;
            }
            
            .error-message {
                font-size: 1.2rem;
            }
            
            .error-body {
                padding: 30px 20px;
            }
            
            .error-title {
                font-size: 1.5rem;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .btn-home, .btn-back {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-header">
            <div class="error-code">404</div>
            <div class="error-message">Page non trouvée</div>
        </div>
        
        <div class="error-body">
            <div class="error-icon">
                <i class="bi bi-compass"></i>
            </div>
            
            <h1 class="error-title">Oups ! On s'est perdu ?</h1>
            
            <p class="error-description">
                La page que vous cherchez a été déplacée, supprimée, ou n'existe pas. 
                Vérifiez l'URL ou utilisez la recherche ci-dessous.
            </p>
            
            <div class="search-box">
                <form action="<?= $baseUrl ?>/recherche" method="GET">
                    <input type="text" name="q" placeholder="Rechercher un produit, une boutique..." autocomplete="off">
                    <button type="submit">
                        <i class="bi bi-search"></i>
                    </button>
                </form>
            </div>
            
            <div class="action-buttons">
                <a href="<?= $baseUrl ?>/" class="btn-home">
                    <i class="bi bi-house-door"></i>
                    Accueil
                </a>
                <a href="javascript:history.back()" class="btn-back">
                    <i class="bi bi-arrow-left"></i>
                    Page précédente
                </a>
            </div>
            
            <div class="suggestions">
                <div class="suggestions-title">Suggestions</div>
                <div class="suggestions-links">
                    <a href="<?= $baseUrl ?>/boutiques">
                        <i class="bi bi-shop"></i> Boutiques
                    </a>
                    <a href="<?= $baseUrl ?>/catalogue">
                        <i class="bi bi-grid"></i> Catalogue
                    </a>
                    <a href="<?= $baseUrl ?>/contact">
                        <i class="bi bi-envelope"></i> Contact
                    </a>
                    <a href="<?= $baseUrl ?>/aide">
                        <i class="bi bi-question-circle"></i> Aide
                    </a>
                </div>
            </div>
        </div>
        
        <div class="error-footer">
            <p>
                © <?= date('Y') ?> <?= Security::escape($siteName) ?> - 
                <a href="<?= $baseUrl ?>/">Retour au site</a>
            </p>
        </div>
    </div>
</body>
</html>