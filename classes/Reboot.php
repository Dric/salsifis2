<?php
use FileSystem\Fs;

/**
 * Creator: Dric
 * Date: 30/05/2017
 * Time: 14:43
 */

/**
 * Class Reboot
 *
 * Page de redémarrage du serveur
 *
 * Il faut permettre à l'interface web de gérer les redémarrages à l'aide de fichiers contenus dans le répertoire `scripts`
 *
 */
class Reboot extends Page{

	protected $title = 'Redémarrage';

	public function main() {
		global $absolutePath, $isGuest;
		if ($isGuest) {
			header('Location: .');
		}
		$disabled = !$this->checkFiles();
		if (!$disabled and isset($_REQUEST['action']) and $_REQUEST['action'] == 'reboot'){
			$this->runReboot();
		} elseif (!$disabled and isset($_REQUEST['action']) and $_REQUEST['action'] == 'shutdown') {
			$this->runShutdown();
		} else {
			?>
			<h2>Redémarrage/Arrêt du serveur</h2>
			<p>
				Vous pouvez redémarrer le serveur à partir d'ici.<br>
				Si vous souhaitez seulement l'arrêter, le plus simple est d'appuyer directement sur le bouton d'alimentation du serveur.
			</p>
			<div class="uk-alert-danger uk-padding-small uk-box-shadow-medium" uk-alert>
				<?php if (!$disabled){ ?>
					Vérifiez qu'aucune opération n'est en cours sur ce serveur, sans quoi vous risquez fortement de perdre des données !
				<?php }else{ ?>
					Vous devez exécuter quelques manipulations avant que cette fonction soit utilisable.<br><br>Connectez-vous sur la console de votre serveur et effectuez les quelques manpulations suivantes :
					<ol>
						<li>Déplacez les fichiers <code>shutdown_suid</code> et <code>reboot_suid</code> dans <code>/usr/local/bin</code> : <br><code>sudo mv <?php echo $absolutePath; ?>/scripts/*_suid /usr/local/bin</code></li>
						<li>Changez le propriétaire des fichiers <code>shutdown_suid</code> et <code>reboot_suid</code> par <code>root</code> : <br><code>sudo chown root:root /usr/local/bin/*_suid</code></li>
						<li>Changez les permissions sur ces fichiers :<br><code>sudo chmod 4755 /usr/local/bin/*_suid</code></li>
					</ol>
				<?php } ?>
			</div>
			<div class="uk-text-center">
				<form action="?page=reboot" method="post">
					<button name="action" value="shutdown" class="uk-button uk-button-danger uk-button-large" <?php if ($disabled) { echo 'disabled'; } ?> type="submit">Éteindre</button>
					<button name="action" value="reboot" class="uk-button uk-button-danger uk-button-large" <?php if ($disabled) { echo 'disabled'; } ?> type="submit">Redémarrer</button>
				</form>
			</div>
			<?php
		}
	}

	/**
	 * Vérifie que tout est en place pour permettre l'arrêt ou le redémarrage du serveur
	 * @return bool
	 */
	protected function checkFiles(){
		$fs = new Fs('/usr/local/bin/');
		if ($fs->fileExists('shutdown_suid') === false){
			Components::Alert('danger', 'Le fichier permettant l\'arrêt du serveur est introuvable !');
			return false;
		}
		if ($fs->fileExists('reboot_suid') === false){
			Components::Alert('danger', 'Le fichier permettant le redémarrage du serveur est introuvable !');
			return false;
		}
		$shutdownMeta = $fs->getFileMeta('shutdown_suid', array('chmod', 'owner'));
		if ($shutdownMeta->advChmod != 4755){
			Components::Alert('danger', 'Le fichier permettant l\'arrêt du serveur n\'a pas les bonnes permissions : <code>'.$shutdownMeta->advChmod.'</code> au lieu de <code>4755</code> !');
			return false;
		}
		$rebootMeta = $fs->getFileMeta('reboot_suid', array('chmod', 'owner'));
		if ($rebootMeta->advChmod != 4755){
			Components::Alert('danger', 'Le fichier permettant le redémarrage du serveur n\'a pas les bonnes permissions : <code>'.$rebootMeta->advChmod.'</code> au lieu de <code>4755</code> !');
			return false;
		}
		if ($shutdownMeta->owner != 'root'){
			Components::Alert('danger', 'Le fichier permettant l\'arrêt du du serveur n\'a pas le bon propriétaire : <code>'.$shutdownMeta->owner.'</code> au lieu de <code>root</code> !');
			return false;
		}
		if ($rebootMeta->owner != 'root'){
			Components::Alert('danger', 'Le fichier permettant le redémarrage du serveur n\'a pas le bon propriétaire : <code>'.$rebootMeta->owner.'</code> au lieu de <code>root</code> !');
			return false;
		}
		return true;
	}

	/**
	 * Redémarrage du serveur
	 */
	protected function runReboot(){
		?>
		<div class="uk-heading-hero uk-text-center">
			<h2 class="uk-alert-danger uk-padding uk-box-shadow-medium">Redémarrage en cours !</h2>
		</div>
		<div class="uk-box-shadow-medium uk-overlay-default uk-padding-small">
			<p>Le redémarrage ne devrait pas excéder 5 minutes.<br />Si ce délai est dépassé et que vous n'arrivez toujours pas à accéder à votre serveur, il y a de fortes chances pour que quelque chose cloche.</p>
			<p>Cliquez sur le lien ci-dessous pour retourner à la page d'accueil. Vous aurez une erreur tant que le serveur n'aura pas redémarré.<br />
				Il vous suffit d'actualiser la page (<kbd>F5</kbd> sur un PC) régulièrement pour que l'interface apparaisse une fois le serveur redémarré.</p>
			<div class="uk-text-center">
				<a class="uk-button uk-button-secondary uk-button-large" title="Cliquez ici et soyez patient !" href=".">Revenir à la page d'accueil</a>
			</div>
		</div>
		<?php
		exec('/usr/local/bin/reboot_suid');
		return true;
	}

	/**
	 * Arrêt du serveur
	 */
	protected function moduleShutdown(){
		$server = rtrim($_SERVER['HTTP_HOST'], '/');
		?>
		<div class="uk-heading-hero uk-text-center">
			<h2 class="uk-alert-danger uk-padding uk-box-shadow-medium">Le serveur est en cours d'arrêt !</h2>
		</div>
		<div class="uk-box-shadow-medium uk-overlay-default uk-padding-small">
			<p>Votre serveur va s'arrêter. Vous devrez appuyer sur le bouton d'alimentation de la machine pour la redémarrer.</p>
			<p>Vous pourrez ensuite vous connecter sur l'interface <?php echo Get::getTitleWithArticle(); ?> avec ce lien : </p>
			<div class="uk-text-center"><h2><strong>http://<?php echo $server; ?></strong></h2></div>
			<p class="uk-text-center">Vous pouvez fermer cette fenêtre.</p>
		</div>
		<?php
		exec('/usr/local/bin/shutdown_suid');
		return true;
	}

	public function menu() {
		?>
		<ul class="uk-nav uk-nav-default uk-margin-auto-vertical">
			<li><a href=".">Retour à l'accueil</a></li>
		</ul>
		<?php
	}
}