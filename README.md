# 🏫 IT4VO School Infoscherm

**Gratis digitale infoschermen voor het Nederlandse onderwijs**

Een complete, open-source oplossing voor het beheren van digitale infoschermen via Android TV en Chromecast. Speciaal ontwikkeld voor scholen met focus op gebruiksgemak en privacy.

[![License: NPOSL-3.0](https://img.shields.io/badge/License-NPOSL--3.0-blue.svg)](LICENSE.md)
[![PHP](https://img.shields.io/badge/PHP-8.0%2B-blue.svg)](https://php.net)
[![PWA](https://img.shields.io/badge/PWA-Enabled-green.svg)](https://web.dev/progressive-web-apps/)

## ✨ Features

### 🎯 **Voor Scholen**
- **100% Gratis** voor non-profit onderwijsinstellingen
- **Plug & Play** - Klaar in 5 minuten
- **Geen dure hardware** - Gebruik bestaande Android TV's of Chromecast
- **BRIN Verificatie** - Automatische validatie met DUO database
- **Privacy First** - Jouw data blijft bij jou

### 📺 **Player Functionaliteit** 
- **PWA Support** - Installeerbaar op Android TV/Chromecast
- **Offline Werking** - Cached content blijft beschikbaar
- **Auto-Update** - Content refresht automatisch
- **Responsive Design** - Optimaal voor TV-schermen
- **Real-time Status** - Online/offline monitoring

### 🛠️ **Beheer Interface**
- **Multi-tenant** - Meerdere scholen op één systeem
- **Gebruiksvriendelijk** - Gemaakt voor roostermakers, niet technici
- **Drie Content Types**: Nieuws, Ziekte/Verlof, Roosterwijzigingen
- **Groepen Targeting** - Toon relevante content per locatie
- **Geplande Publicatie** - Automatische start/stop tijden

## 🚀 Quick Start

### **Stap 1: Registreer je School**
1. Ga naar de registratie pagina
2. Voer je **BRIN-nummer** in voor automatische validatie
3. Vul organisatie- en contactgegevens aan
4. **Verificeer** je email adres

### **Stap 2: Claim je Eerste Scherm**
1. Ga op een Android TV/Chromecast naar de player URL
2. Noteer de **4-character Player ID** (bijv. `A1B2`)
3. Log in op het admin dashboard
4. **Claim** het scherm met de Player ID

### **Stap 3: Voeg Content Toe**
1. Maak je eerste **slide** aan
2. Kies het **type**: Nieuws, Ziekte/Verlof, of Roosterwijziging  
3. Stel **publicatieperiode** in
4. Content verschijnt **automatisch** op je schermen!

## 💻 Technische Requirements

### **Server Requirements**
- **PHP 8.0+** met PDO MySQL support
- **MySQL 5.7+** of MariaDB 10.3+
- **Apache/Nginx** webserver
- **HTTPS** (vereist voor PWA functionaliteit)
- **Email** (SMTP voor verificaties)

### **Client Requirements** 
- **Android TV 6.0+** of **Google TV**
- **Chromecast** (2e generatie of nieuwer)
- **Chrome browser** voor beheer interface
- **Stabiele internetverbinding**

## 🛠️ Installatie

### **Hosted Oplossing (Aanbevolen)**
Voor kleine scholen bieden we gratis hosting aan:
1. Registreer op [IT4VO.nl/school-infoscherm](https://IT4VO.nl/school-infoscherm)
2. Verificeer je organisatie 
3. Begin direct met het toevoegen van schermen

### **Self-Hosted Installatie**

#### **Stap 1: Download & Upload**
```bash
git clone https://github.com/IT4VO-nl/school-infoscherm.git
```

#### **Stap 2: Database Setup**
```sql
# Importeer het database schema
mysql -u root -p school_infoscherm < database-schema.sql
```

#### **Stap 3: Configuratie**
```bash
# Kopieer configuratie templates
cp db_config.php.example db_config.php
cp config.php.example config.php

# Pas aan naar jouw omgeving
nano db_config.php
nano config.php
```

#### **Stap 4: Webserver Setup**
```apache
# Apache Virtual Host example
<VirtualHost *:443>
    ServerName infoscherm.jouwschool.nl
    DocumentRoot /var/www/school-infoscherm
    
    SSLEngine on
    SSLCertificateFile /path/to/cert.pem
    SSLCertificateKeyFile /path/to/private.key
</VirtualHost>
```

#### **Stap 5: BRIN Database (Optioneel)**
Download de actuele DUO schoolgegevens voor automatische verificatie:
1. Download van [DUO Open Data](https://duo.nl/open_onderwijsdata/)
2. Plaats als `checks/schoolgegevens.csv`
3. Pas `includes/SchoolVerifier.php` aan indien nodig

## 📱 PWA Installatie

### **Android TV**
1. Open **Chrome** op je Android TV
2. Navigeer naar: `jouw-domein.nl/player.php?id=XXXX`
3. Klik **"Add to Home Screen"** in het Chrome menu
4. App start **automatisch** na reboot

### **Chromecast**
1. **Cast tabblad** vanuit Chrome desktop
2. URL: `jouw-domein.nl/player.php?id=XXXX` 
3. **Fullscreen mode** voor optimale weergave

## 🏗️ Architectuur

### **Multi-Tenant Database Design**
```
organisations → admins
             → players → player_groups
             → slides → slide_group_map
```

### **RESTful API Structure**
```
/api/
├── register.php          # Organisatie registratie
├── organisations.php     # Organisatie CRUD
├── admins.php           # Admin gebruikers CRUD  
├── players.php          # Player/scherm CRUD
├── slides.php           # Content CRUD
├── player_groups.php    # Groepen CRUD
├── player_content.php   # Content delivery voor players
├── player_heartbeat.php # Status monitoring
├── system_slides.php    # Systeem berichten
└── verify_school.php    # BRIN/DUO verificatie
```

### **Security Features**
- **Session-based** authenticatie
- **CSRF** bescherming via SameSite cookies
- **Input sanitization** voor alle user data
- **Prepared statements** tegen SQL injection
- **Rate limiting** voor API calls
- **HTTPS-only** cookies en session data

## 🤝 Contributing

We verwelkomen bijdragen van de onderwijscommunity!

### **Voor Scholen**
- **Bug reports** - Meld problemen via GitHub Issues
- **Feature requests** - Deel je wensen en ideeën
- **Gebruikerservaringen** - Help anderen met tips & tricks

### **Voor Developers**
- **Fork** het project
- **Maak** een feature branch (`git checkout -b feature/nieuwe-functie`)
- **Commit** je wijzigingen (`git commit -am 'Voeg nieuwe functie toe'`)
- **Push** naar branch (`git push origin feature/nieuwe-functie`)
- **Open** een Pull Request

### **Development Setup**
```bash
# Clone repository
git clone https://github.com/IT4VO-nl/school-infoscherm.git
cd school-infoscherm

# Setup lokale database
mysql -u root -p -e "CREATE DATABASE school_infoscherm_dev"
mysql -u root -p school_infoscherm_dev < database-schema.sql

# Copy configs
cp db_config.php.example db_config.php
cp config.php.example config.php

# Start lokale server
php -S localhost:8000
```

## 📋 Roadmap

### **v1.1 - NEXT**
- [ ] **Docker containers** voor eenvoudige deployment
- [ ] **Schoolbel systeem** geïntegreerd in players
- [ ] **Multi-media support** (afbeeldingen, video)
- [ ] **Template systeem** voor consistente branding

### **v1.2 - LATER**  
- [ ] **Mobile app** for content management
- [ ] **Bulk import** van roostersystemen (Zermelo, Magister)
- [ ] **Analytics dashboard** voor content performance
- [ ] **Multi-language** support (Engels, Duits)

### **v2.0 - PERHAPS**
- [ ] **Federatie support** - Verbind meerdere scholen
- [ ] **Advanced scheduling** met recurring events
- [ ] **Integration APIs** voor SIS/LMS systemen
- [ ] **White-label** oplossing voor leveranciers

## 🆘 Support & Community

### HIER WORDT NOG AAN GEWERKT

## 📄 Licentie

Dit project valt onder de **Non-Profit Open Software License 3.0** (NPOSL-3.0).

**Voor Non-Profit Onderwijs:**
✅ **Volledig gratis** gebruik  
✅ **Modificatie** toegestaan  
✅ **Distributie** toegestaan  
✅ **Commercieel gebruik** binnen onderwijs

**Voor Commerciële Doeleinden:**
❌ **Niet toegestaan** zonder expliciete toestemming  
📧 **Contact** ons voor commerciële licenties

[Lees de volledige licentie](LICENSE.md)

## 🏢 Over IT4VO

**IT4VO** is een nog op te richten Nederlandse stichting die **gratis IT-oplossingen** ontwikkelt voor het onderwijs. Onze missie is het toegankelijk maken van moderne technologie voor alle scholen, zonder hoge kosten of noodzakelijke consultants.

### **Onze Waarden**
- **Open Source First** - Transparantie en samenwerking
- **Privacy by Design** - Jouw data blijft van jou  
- **Education Focus** - Speciaal gemaakt voor onderwijs
- **Community Driven** - Gebouwd met en voor scholen

### **Contact**
- **Website**: [IT4VO.nl](https://IT4VO.nl - under construction)
- **Email**: info@it4vo.nl
- **GitHub**: [@IT4VO-nl](https://github.com/IT4VO-nl)

---

**Gemaakt met ❤️ voor het Nederlandse onderwijs met Claude Sonnet 4**

*Geef dit project een ⭐ als het nuttig is voor jouw school!*
