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
		global $absolutePath;
		$disabled = !$this->checkFiles();
		if (!$disabled and isset($_REQUEST['action']) and $_REQUEST['action'] == 'reboot'){
			$this->runReboot();
		} else {
			?>
			<h2>Redémarrage du serveur</h2>
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
						<li>Changez le propriétaire des fichiers <code>shutdown_suid</code> et <code>reboot_suid</code> par <code>root</code> : <br><code>sudo chown root:root <?php echo $absolutePath; ?>/scripts/*_suid</code></li>
						<li>Changez les permissions sur ces fichiers :<br><code>sudo chmod 4755 <?php echo $absolutePath; ?>/scripts/*_suid</code></li>
					</ol>
				<?php } ?>
			</div>
			<div class="uk-text-center">
				<form action="?page=reboot" method="post">
					<input type="hidden" name="action" value="reboot">
					<button class="uk-button uk-button-danger uk-button-large" <?php if ($disabled) { echo 'disabled'; } ?> type="submit">Redémarrer</button>
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
		global $absolutePath;
		$fs = new Fs($absolutePath.'/scripts/');
		/*if ($fs->fileExists('shutdown_suid') === false){
			Components::Alert('danger', 'Le fichier permettant l\'arrêt du serveur est introuvable !');
			return false;
		}*/
		if ($fs->fileExists('reboot_suid') === false){
			Components::Alert('danger', 'Le fichier permettant le redémarrage du serveur est introuvable !');
			return false;
		}
		/*$shutdownMeta = $fs->getFileMeta('shutdown_suid', array('chmod', 'owner'));
		if ($shutdownMeta->advChmod != 4755){
			Components::Alert('danger', 'Le fichier permettant l\'arrêt du serveur n\'a pas les bonnes permissions : <code>'.$shutdownMeta->advChmod.'</code> au lieu de <code>4755</code> !');
			return false;
		}*/
		$rebootMeta = $fs->getFileMeta('reboot_suid', array('chmod', 'owner'));
		if ($rebootMeta->advChmod != 4755){
			Components::Alert('danger', 'Le fichier permettant le redémarrage du serveur n\'a pas les bonnes permissions : <code>'.$rebootMeta->advChmod.'</code> au lieu de <code>4755</code> !');
			return false;
		}
		/*if ($shutdownMeta->owner != 'root'){
			Components::Alert('danger', 'Le fichier permettant l\'arrêt du du serveur n\'a pas le bon propriétaire : <code>'.$shutdownMeta->owner.'</code> au lieu de <code>root</code> !');
			return false;
		}*/
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
		global $absolutePath;
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
		exec($absolutePath.'/scripts/reboot_suid');
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