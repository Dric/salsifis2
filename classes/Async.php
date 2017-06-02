<?php

/**
 * Creator: Dric
 * Date: 24/05/2017
 * Time: 16:45
 */
class Async {
	static $requests = array(

	);
	public static function getAsyncRequest(){
		if (!isset($_REQUEST['aSync'])){
			return false;
		}
		foreach ($_REQUEST as $request=>$args){
			if ($request == 'diskUsage'){
				self::viewDiskUsage();
			}elseif ($request == 'externalIP'){
				self::viewExternalIP();
			}
		}
		return true;
	}

	public static function viewDiskUsage(){
		?>
		<div class="uk-container uk-container-small uk-text-center">
			<?php
			$dataPart = Components::getDiskStatus(Settings::DATA_PARTITION);
			if ($dataPart) {
				?>
				Espace disque occupé sur <code><?php echo Settings::DATA_PARTITION;?></code> (<?php echo Sanitize::readableFileSize($dataPart['free']); ?> libres) :<br>
				<progress class="uk-progress uk-border-rounded uk-box-shadow-medium" title="Occupation : <?php echo Sanitize::readableFileSize($dataPart['load']) .'/'. Sanitize::readableFileSize($dataPart['total']); ?>" value="<?php echo $dataPart['load']; ?>" max="<?php echo $dataPart['total']; ?>" uk-tooltip><?php echo Sanitize::readableFileSize($dataPart['load']) .'/'. Sanitize::readableFileSize($dataPart['total']); ?></progress>
				<?php
			} else {
				?><div uk-alert class="uk-alert-danger">Impossible de récupérer l'occupation du disque</div><?php
			}
			?>
		</div>
		<?php
	}

	public static function viewExternalIP(){
		?>
		<div class="uk-container uk-container-small uk-text-center">
		IP externe :<br><span class="uk-h1">
		<?php
		echo Components::getExternalIPAddress();
		?>
		</span></div>
		<?php
	}
}