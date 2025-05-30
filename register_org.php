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
    <title>Organisatie Registreren - School Infoscherm</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            max-width: 600px; 
            margin: 20px auto; 
            padding: 20px; 
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 { color: #333; text-align: center; margin-bottom: 30px; }
        fieldset { 
            border: 2px solid #007cba; 
            border-radius: 8px; 
            padding: 20px; 
            margin-bottom: 20px;
            background-color: #f9f9f9;
        }
        legend { 
            font-weight: bold; 
            color: #007cba; 
            padding: 0 10px;
            font-size: 1.1em;
        }
        label { 
            display: block; 
            margin-bottom: 15px; 
            font-weight: 500;
        }
        input[type="text"], input[type="email"], input[type="tel"], input[type="password"], textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            margin-top: 5px;
            box-sizing: border-box;
        }
        input[type="file"] {
            margin-top: 5px;
            padding: 5px;
        }
        textarea {
            height: 60px;
            resize: vertical;
        }
        button {
            background: #007cba;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
            margin-top: 20px;
        }
        button:hover { background: #005a8b; }
        .required { color: red; }
        .info {
            background: #e7f3ff;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            border-left: 4px solid #007cba;
        }
        .links {
            text-align: center;
            margin-top: 20px;
        }
        .links a {
            color: #007cba;
            text-decoration: none;
        }
        .form-row {
            display: flex;
            gap: 15px;
        }
        .form-row > div {
            flex: 1;
        }
        @media (max-width: 600px) {
            .form-row {
                flex-direction: column;
                gap: 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Organisatie Registreren</h1>
        
        <div class="info">
            <strong>Welkom bij IT4VO School Infoscherm!</strong><br>
            Registreer je organisatie om direct te starten met het beheren van je infoschermen.
            Na registratie ontvang je een verificatie email.
        </div>

        <form id="registerForm" action="api/register.php" method="post" enctype="multipart/form-data">
            
            <fieldset>
                <legend>Organisatie Gegevens <span class="required">*</span></legend>
                
                <label>
                    Organisatie Naam <span class="required">*</span>
                    <input type="text" name="org_name" required placeholder="bijv. Gymnasium Leiden">
                </label>

                <div class="form-row">
                    <div>
                        <label>
                            Telefoon <span class="required">*</span>
                            <input type="tel" name="org_phone" required placeholder="071-1234567">
                        </label>
                    </div>
                    <div>
                        <label>
                            E-mailadres <span class="required">*</span>
                            <input type="email" name="org_email" required placeholder="info@school.nl">
                        </label>
                    </div>
                </div>

                <label>
                    Adres Hoofdlocatie <span class="required">*</span>
                    <textarea name="org_address" required placeholder="Straatnaam 123&#10;1234 AB Plaatsnaam"></textarea>
                </label>

                <div class="form-row">
                    <div>
                        <label>
                            BRIN Code (optioneel)
                            <input type="text" name="brin_code" placeholder="12AB" maxlength="4" style="text-transform: uppercase;">
                        </label>
                    </div>
                    <div>
                        <label>
                            Logo (optioneel)
                            <input type="file" name="logo" accept="image/*">
                        </label>
                    </div>
                </div>
            </fieldset>

            <fieldset>
                <legend>Eerste Beheerder <span class="required">*</span></legend>
                
                <label>
                    Naam <span class="required">*</span>
                    <input type="text" name="admin_name" required placeholder="Jan de Vries">
                </label>

                <div class="form-row">
                    <div>
                        <label>
                            Telefoon <span class="required">*</span>
                            <input type="tel" name="admin_phone" required placeholder="071-1234567">
                        </label>
                    </div>
                    <div>
                        <label>
                            E-mailadres <span class="required">*</span>
                            <input type="email" name="admin_email" required placeholder="j.devries@school.nl">
                        </label>
                    </div>
                </div>

                <div class="form-row">
                    <div>
                        <label>
                            Wachtwoord <span class="required">*</span>
                            <input type="password" name="admin_password" required minlength="6">
                        </label>
                    </div>
                    <div>
                        <label>
                            Herhaal Wachtwoord <span class="required">*</span>
                            <input type="password" name="admin_password2" required minlength="6">
                        </label>
                    </div>
                </div>
            </fieldset>

            <button type="submit">Organisatie Registreren</button>
        </form>

        <div class="links">
            <a href="index.php">← Terug naar home</a> | 
            <a href="login.php">Al een account? Inloggen</a>
        </div>
    </div>

    <script>
        // Wachtwoord validatie
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const pass1 = document.querySelector('input[name="admin_password"]').value;
            const pass2 = document.querySelector('input[name="admin_password2"]').value;
            
            if (pass1 !== pass2) {
                e.preventDefault();
                alert('Wachtwoorden komen niet overeen!');
                return false;
            }
            
            if (pass1.length < 6) {
                e.preventDefault();
                alert('Wachtwoord moet minimaal 6 karakters zijn!');
                return false;
            }
        });

        // BRIN code uppercase
        document.querySelector('input[name="brin_code"]').addEventListener('input', function(e) {
            e.target.value = e.target.value.toUpperCase();
        });
    </script>
</body>
</html>