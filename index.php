<?php
session_start();
if (isset($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IT4VO School Infoscherm - Gratis voor het Onderwijs</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
            padding: 40px 0;
        }

        .logo {
            background: #007cba;
            color: white;
            padding: 20px 30px;
            border-radius: 12px;
            display: inline-block;
            margin-bottom: 20px;
            font-size: 1.8em;
            font-weight: bold;
            box-shadow: 0 4px 15px rgba(0, 124, 186, 0.3);
        }

        h1 {
            font-size: 2.5em;
            color: #2c3e50;
            margin-bottom: 15px;
            font-weight: 300;
        }

        .subtitle {
            font-size: 1.2em;
            color: #7f8c8d;
            margin-bottom: 30px;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .hero-section {
            background: white;
            border-radius: 15px;
            padding: 40px;
            margin-bottom: 40px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            text-align: center;
        }

        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin: 40px 0;
        }

        .feature {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .feature:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }

        .feature-icon {
            font-size: 3em;
            margin-bottom: 15px;
            color: #007cba;
        }

        .feature h3 {
            color: #2c3e50;
            margin-bottom: 10px;
            font-size: 1.3em;
        }

        .feature p {
            color: #7f8c8d;
            line-height: 1.5;
        }

        .cta-section {
            background: white;
            border-radius: 15px;
            padding: 40px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .cta-buttons {
            display: flex;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
            margin-top: 30px;
        }

        .btn {
            display: inline-block;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1.1em;
            transition: all 0.3s ease;
            min-width: 200px;
        }

        .btn-primary {
            background: #007cba;
            color: white;
            box-shadow: 0 4px 15px rgba(0, 124, 186, 0.3);
        }

        .btn-primary:hover {
            background: #005a8b;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 124, 186, 0.4);
        }

        .btn-secondary {
            background: #34495e;
            color: white;
            box-shadow: 0 4px 15px rgba(52, 73, 94, 0.3);
        }

        .btn-secondary:hover {
            background: #2c3e50;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(52, 73, 94, 0.4);
        }

        .btn-whatsapp {
            background: #25d366;
            color: white;
            box-shadow: 0 4px 15px rgba(37, 211, 102, 0.3);
        }

        .btn-whatsapp:hover {
            background: #20b358;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(37, 211, 102, 0.4);
        }

        .footer {
            text-align: center;
            margin-top: 50px;
            padding: 30px;
            background: rgba(255,255,255,0.9);
            border-radius: 15px;
            color: #7f8c8d;
        }

        .pricing-highlight {
            background: linear-gradient(45deg, #f39c12, #e67e22);
            color: white;
            padding: 15px 25px;
            border-radius: 50px;
            display: inline-block;
            margin: 20px 0;
            font-weight: bold;
            font-size: 1.1em;
            box-shadow: 0 4px 15px rgba(243, 156, 18, 0.3);
        }

        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            
            h1 {
                font-size: 2em;
            }
            
            .hero-section, .cta-section {
                padding: 25px;
            }
            
            .cta-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .btn {
                width: 100%;
                max-width: 300px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">IT4VO School Infoscherm</div>
            <h1>Gratis Infoschermen voor het Onderwijs</h1>
            <p class="subtitle">
                Beheer eenvoudig digitale infoschermen op Android TV en Chromecast. 
                Deel nieuws, afwezigheid en roosterwijzigingen met je hele school.
            </p>
        </div>

        <div class="hero-section">
            <div class="pricing-highlight">
                🎓 100% Gratis voor Non-Profit Onderwijs
            </div>
            
            <p style="font-size: 1.2em; color: #2c3e50; margin: 20px 0;">
                <strong>Klaar in 5 minuten!</strong> Registreer je organisatie, 
                claim je schermen en begin direct met het delen van belangrijke informatie.
            </p>

            <div class="features">
                <div class="feature">
                    <div class="feature-icon">📺</div>
                    <h3>Android TV & Chromecast</h3>
                    <p>Gebruik bestaande TV's met Android TV of Chromecast dongles. Geen dure hardware nodig.</p>
                </div>
                
                <div class="feature">
                    <div class="feature-icon">⚡</div>
                    <h3>Supersnel Instellen</h3>
                    <p>Ga naar de player URL, krijg een code, claim het scherm. Klaar!</p>
                </div>
                
                <div class="feature">
                    <div class="feature-icon">📝</div>
                    <h3>Drie Soorten Content</h3>
                    <p>Nieuws, afwezigheid/verlof en roosterwijzigingen. Perfect voor scholen.</p>
                </div>
                
                <div class="feature">
                    <div class="feature-icon">🎯</div>
                    <h3>Gerichte Weergave</h3>
                    <p>Groepeer schermen per locatie, klas of verdieping. Toon relevante info per groep.</p>
                </div>
                
                <div class="feature">
                    <div class="feature-icon">⏰</div>
                    <h3>Geplande Publicatie</h3>
                    <p>Plan wanneer slides getoond worden. Automatische start en stop tijden.</p>
                </div>
                
                <div class="feature">
                    <div class="feature-icon">🔔</div>
                    <h3>Schoolbel Systeem</h3>
                    <p>Binnenkort: Automatische schoolbel functionaliteit geïntegreerd in je schermen.</p>
                </div>
            </div>
        </div>

        <div class="cta-section">
            <h2 style="color: #2c3e50; margin-bottom: 15px;">Aan de Slag</h2>
            <p style="font-size: 1.1em; color: #7f8c8d; margin-bottom: 30px;">
                Voor roostermakers, IT-coördinatoren en schoolleiding. Geen technische kennis vereist.
            </p>
            
            <div class="cta-buttons">
                <a href="register_org.php" class="btn btn-primary">
                    🚀 Registreer je Organisatie
                </a>
                
                <a href="login.php" class="btn btn-secondary">
                    🔐 Inloggen
                </a>
                
                <a href="https://wa.me/31624645787" class="btn btn-whatsapp" target="_blank">
                    💬 Vragen? WhatsApp Ons
                </a>
            </div>
        </div>

        <div class="footer">
            <p><strong>IT4VO.nl</strong> - Open Source Oplossingen voor het Onderwijs</p>
            <p>Gratis beschikbaar onder de Non-Profit Open Software License 3.0</p>
            <p style="margin-top: 15px;">
                <a href="https://github.com/IT4VO-nl/school-infoscherm" style="color: #007cba; text-decoration: none;">
                    📂 Bekijk de Broncode op GitHub
                </a>
            </p>
        </div>
    </div>
</body>
</html>