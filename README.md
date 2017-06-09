# Salsifis²

Salsifis² est une interface web pour gérer un petit serveur de media sous Linux.

Les interactions sont limitées à :

- une interface basique pour Transmission-daemon
- un explorateur de fichiers
- une interface d'arrêt/redémarrage du serveur

L'arrêt/redémarrage du serveur nécessite un accès à la console du serveur pour la mise en place de deux fichiers exécutables.

### Captures d'écran

![Accueil](https://github.com/Dric/salsifis2/master/img/screenshots/home.jpg "Accueil")

![Fichiers](https://github.com/Dric/salsifis2/master/img/screenshots/files.jpg "Explorateur de fichiers")

![Téléchargements](https://github.com/Dric/salsifis2/master/img/screenshots/downloads.jpg "Téléchargements")

### Composants utilisés :

- UIKit
- jQuery
- DataTables
- Font-Awesome

## Installation

Il est conseillé d'installer Salsifis² à la racine du serveur web.

### Via git (recommandé)

Ouvrez une session en ligne de commande sur le serveur (en SSH c'est plus simple pour les copier/coller).

Si vous avez déjà des scripts ou des pages stockées sur votre hébergement, n'exécutez pas la deuxième commande ci-dessous, elle efface le contenu de votre racine web.

puis saisissez les commandes suivantes :

    cd /var/www
    sudo rm -R html/*
    git clone https://github.com/Dric/salsifis2.git html

Mettez en place les fichiers nécessaires au redémarrage du serveur :

    sudo mv /var/www/html/scripts/*_suid /usr/local/bin
    sudo chown root:root /usr/local/bin/*_suid
    sudo chmod 4755 /usr/local/bin/*_suid

### Via FTP

- Téléchargez Salsifis² ici : https://github.com/Dric/salsifis2/archive/master.zip
- Décompressez les fichiers et envoyez-le via FTP sur votre serveur web (dans `/var/www/html`)

Vous ne pourrez pas redémarrer ou éteindre votre serveur via l'interface web tant que vous n'aurez pas mis en place les fichiers nécessaires au redémarrage du serveur.

