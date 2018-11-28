<?php
use FileSystem\Fs;
use Git\Git;

/**
 * Creator: Dric
 * Date: 07/06/2017
 * Time: 13:53
 */
class Admin {

	/**
	 * Lit le fichier de paramétrage du serveur
	 *
	 * @param bool $defaultOnly
	 *
	 * @return array
	 */
	protected static function readSettings($defaultOnly = false) {
		$fs = new \FileSystem\Fs('classes/');
		$settingsFile = $fs->readFile( 'DefaultSettings.php', 'array', true, true);
		$constants = array();
		$constantName = null;
		$selectIn = self::selectIn();
		foreach ($settingsFile as $key => $line){
			if (stristr(strtolower($line), 'const ')){
				$keyExplain = $key-1; //Il faut reculer de deux pointeurs car le foreach affecte la valeur de la ligne à $line et avance d'un pointeur'
				preg_match('/\/\*\* (.*) \*\//i', $settingsFile[$keyExplain], $match);
				$explain = $match[1]; //On récupère la ligne du dessus (définition de la constante)
				//$ret = preg_match_all('/\(.+?\)/', $line, $define); //On récupère les chaînes entre guillemets
				preg_match('/const (.+?) = (.*)/', $line, $matchesLine);
				$constantName = trim($matchesLine[1], " ");
				$constants[$constantName] = array(
					'explain'   => $explain,
					'value'     => null,
					'selectIn'  => null
				);
				if (preg_match('/array\(/i', $matchesLine[2])){
					$constants[$constantName]['type'] = 'array'; // On a affaire à un tableau
				}	elseif (preg_match('/(?:\'|")(.+?)(?:\'|")/i', $matchesLine[2], $matchValue)) {
					$constants[$constantName]['type'] = 'string';
					$constants[$constantName]['defaultValue'] = $matchValue[1];
				} else{
					$value = trim($matchesLine[2], ' ;');
					if (is_int($value)){
						$constants[$constantName]['type'] = 'int';
						$value = (int)$value;
					}elseif(is_float($value)){
						$constants[$constantName]['type'] = 'float';
						$value = (float)$value;
					}elseif(in_array($value, array('true', 'false'))){
						$constants[$constantName]['type'] = 'bool';
						$value = ($value == 'true') ? true : false;
					} else {
						$constants[$constantName]['type'] = 'string';
						$value = null;
					}
					$constants[$constantName]['defaultValue'] = $value;
				}
				if (isset($selectIn[$constantName])){
					$constants[$constantName]['selectIn'] = $selectIn[$constantName];
				}
			} elseif (preg_match('/(?:\'|")(.+?)(?:\'|")\s+=> (?:\'|")(.+?)(?:\'|")/i', $line, $matchesLine)) {
				// On traite les items d'un tableau
				$constants[$constantName]['defaultValue'][$matchesLine[1]] = $matchesLine[2];
			}
		}
		$constantName = null;
		if ($fs->fileExists('Settings.php') and !$defaultOnly) {
			$settingsFile = $fs->readFile( 'Settings.php', 'array', true, true);
			foreach ($settingsFile as $key => $line){
				if (stristr(strtolower($line), 'const ')){
					preg_match('/const (.+?) = (.*)/', $line, $matchesLine);
					$constantName = trim($matchesLine[1], " ");
					if (preg_match('/array\(/i', $matchesLine[2])){
						$constants[$constantName]['type'] = 'array'; // On a affaire à un tableau
					}	elseif (preg_match('/(?:\'|")(.+?)(?:\'|")/i', $matchesLine[2], $matchValue)) {
						$constants[$constantName]['type'] = 'string';
						$constants[$constantName]['value'] = $matchValue[1];
					} else{
						$value = trim($matchesLine[2], ' ;');
						if (is_int($value)){
							$constants[$constantName]['type'] = 'int';
							$value = (int)$value;
						}elseif(is_float($value)){
							$constants[$constantName]['type'] = 'float';
							$value = (float)$value;
						}elseif(in_array($value, array('true', 'false'))){
							$constants[$constantName]['type'] = 'bool';
							$value = ($value == 'true') ? true : false;
						} else {
							$constants[$constantName]['type'] = 'string';
							$value = null;
						}
						$constants[$constantName]['value'] = $value;
					}
				} elseif (preg_match('/(?:\'|")(.+?)(?:\'|")\s+=> (?:\'|")(.+?)(?:\'|")/i', $line, $matchesLine)) {
					// On traite les items d'un tableau
					$constants[$constantName]['value'][$matchesLine[1]] = $matchesLine[2];
				}
			}
		}
		return $constants;
	}

	/**
	 * Crée un ensemble de listes de choix pour le paramétrage du serveur
	 *
	 * @return array
	 */
	protected static function selectIn(){
		global $absolutePath;
		$selectIn    = array();
		$fs          = new Fs($absolutePath);
		$bgFiles     = $fs->getFilesInDir('img/backgrounds');
		$backgrounds = array();
		/** @var \FileSystem\File $file */
		foreach ($bgFiles as $file){
			if (in_array($file->extension, array('png', 'jpg'))){
				$backgrounds[] = $file->name;
			}
		}
		$selectIn['BG_IMG'] = $backgrounds;
		return $selectIn;
	}

	/**
	 * Affiche les paramètres du serveur
	 */
	public static function displayServerSettings() {
		$constants = self::readSettings();
		$related = array(
			'pwd'   => array(
				'USE_AUTH',
				'PASSWORD',
				'GUEST_PASSWORD'
			),
		  'adv'   => array(
			  'DEBUG',
			  'DATA_PARTITION',
			  'GUEST_DATA_PARTITION',
			  'DISPLAY_EXTERNAL_IP'
		  ),
		  'trans' => array(
			  'TRANSMISSION_WEB_URL',
			  'TRANSMISSION_RPC_URL',
			  'DOWNLOAD_DIRS'
		  )
		);
		$constants = array(
			'general' => $constants
		);

		foreach ($related as $cat => $item){
			foreach ($item as $setting){
				$constants[$cat][$setting] = $constants['general'][$setting];
				unset($constants['general'][$setting]);
			}
		}

		?>
		<div class="uk-modal-dialog">
			<button class="uk-modal-close-default" type="button" uk-close></button>
			<div class="uk-modal-header">
				<h2 class="uk-modal-title">Paramètres <?php echo Get::getTitleWithArticle(); ?></h2>
			</div>
			<form method="post" action="">
				<div class="uk-modal-body">
					<ul uk-tab="animation: uk-animation-fade">
						<li class="uk-active"><a href="#">Général</a></li>
						<li><a href="#">Mot de passe</a></li>
						<li><a href="#">Téléchargements</a></li>
						<li><a href="#">Avancé</a></li>
					</ul>
					<ul class="uk-switcher uk-margin">
						<li>
							<?php
							self::createFormInputs($constants['general']);
							?>
						</li>
						<li>
							<?php
							self::createFormInputs($constants['pwd']);
							?>
						</li>
						<li>
							<?php
							self::createFormInputs($constants['trans']);
							?>
						</li>
						<li>
							<?php
							self::createFormInputs($constants['adv']);
							?>
						</li>
					</ul>
				</div>
				<div class="uk-modal-footer uk-text-right">
					<button class="uk-button uk-button-default uk-modal-close" type="button">Annuler</button>
					<button name="action" value="saveServerSettings" class="uk-button uk-button-primary" type="submit">Sauvegarder</button>
				</div>
			</form>
		</div>
		<?php
	}

	/**
	 * Crée des champs de formulaire à partir des constantes d'une classe de paramétrage
	 * @param $constants
	 */
	protected static function createFormInputs($constants){
		foreach ($constants as $constant => $tab) {
			if (!isset($tab['selectIn'])){
				switch ($tab['type']) {
					case 'string':
						?>
						<div class="uk-margin">
							<label class="uk-form-label"><?php echo $tab['explain']; ?></label>
							<input name="<?php echo $constant; ?>" class="uk-input" type="text" value="<?php echo $tab['value']; ?>" placeholder="<?php echo $tab['defaultValue']; ?>">
						</div>
						<?php
						break;
					case 'int':
						?>
						<div class="uk-margin">
							<label class="uk-form-label"><?php echo $tab['explain']; ?></label>
							<input name="<?php echo $constant; ?>" class="uk-input" type="number" step="1" min="0" value="<?php echo $tab['value']; ?>" placeholder="<?php echo $tab['defaultValue']; ?>">
						</div>
						<?php
						break;
					case 'float':
						?>
						<div class="uk-margin">
							<label class="uk-form-label"><?php echo $tab['explain']; ?></label>
							<input name="<?php echo $constant; ?>" class="uk-input" type="number" step="0.1" min="0" value="<?php echo $tab['value']; ?>" placeholder="<?php echo $tab['defaultValue']; ?>">
						</div>
						<?php
						break;
					case 'bool':
						?>
						<label>
							<input name="<?php echo $constant; ?>" class="uk-checkbox" type="checkbox" <?php if ($tab['value'] or (empty($tab['value']) and $tab['defaultValue'])) {
								echo 'checked';
							} ?>> <?php echo $tab['explain']; ?>
						</label>
						<?php
						break;
					case 'array':
						$count = count((empty($tab['value'])) ? $tab['defaultValue'] : $tab['value']);
						?>
						<div class="uk-margin">
							<label class="uk-form-label" for="<?php echo $constant; ?>"><?php echo $tab['explain']; ?></label>
							<textarea id="<?php echo $constant; ?>" name="<?php echo $constant; ?>" class="uk-textarea" rows="<?php echo $count; ?>"><?php echo (!empty($tab['value'])) ? implode("\n", $tab['value']) : implode("\n", $tab['defaultValue']) ; ?></textarea>
						</div>
						<?php
						break;
				}
			} else {
				?>
				<div class="uk-margin">
					<label class="uk-form-label" for="<?php echo $constant; ?>-field"><?php echo $tab['explain']; ?></label>
					<select class="uk-select" id="<?php echo $constant; ?>-field" name="<?php echo $constant; ?>">
						<?php
						foreach ($tab['selectIn'] as $selectValue){
							?><option value="<?php echo $selectValue; ?>" <?php if (($tab['value'] == $selectValue) or (empty($tab['value']) and $tab['defaultValue'] == $selectValue)) {echo 'selected';} ?>><?php echo $selectValue; ?></option><?php
						}
						?>
					</select>
				</div>
				<?php
			}
		}
	}

	/**
	 * Sauvegarde les paramètres du serveur dans un fichier de config php
	 *
	 * @return bool
	 */
	public static function saveServerSettings(){
		$constants = self::readSettings(true);
		foreach ($constants as $constant => $tab){
			if (isset($_REQUEST[$constant]) and !empty($_REQUEST[$constant])){
				$request = htmlspecialchars($_REQUEST[$constant]);
				if ($tab['type'] != 'array'){
					settype($request, $tab['type']);
					$value = $request;
					if ($value == $tab['defaultValue']){
						$value = null;
					}
				} else {
					if ($request == implode("\n", $tab['defaultValue'])){
						$value = explode("\n", $request);
					} else {
						$value = null;
					}
				}
				$constants[$constant]['value'] = $value;
			}
		}
		// Si l'authentification est activée mais que le mot de passe est vide, on désactive l'authentification
		if ($constants['USE_AUTH']['value'] === true and is_null($constants['PASSWORD']['value'])){
			$constants['USE_AUTH']['value'] = null;
			Components::setAlert('danger', 'L\'authentification a été désactivée car aucun mot de passe n\'a été saisi !');
		}
		if ($constants['PASSWORD']['value'] === $constants['GUEST_PASSWORD']['value']){
			Components::setAlert('danger', 'Le mot de passe des invités est le même que le mot de passe administrateur !');
			return false;
		}
		//echo Get::varDump($constants);
		$fs = new Fs('classes');
		if (!$fs->fileExists('Settings.php')){
			$content = '<?php
/**
 * Date: '.date('d/m/Y', time()).'
 * Time: '.date('H:i', time()).'
 */
class Settings extends DefaultSettings {
}';
			$ret = $fs->writeFile('Settings.php', $content);
			if (!$ret) {
				$_SESSION['alerts'][] = array('type'=>'danger','message'=>'Impossible de créer le fichier <code>Settings.php</code>');
				return false;
			}
		}
		$settingsFile = $fs->readFile('Settings.php', 'array', true, true);
		//echo Get::varDump($settingsFile);
		$isArray = false;
		$updateDone = false;
		foreach ($settingsFile as $key => $line){
			if ($isArray){
				if (preg_match('/\);/', $line)){
					$isArray = false;
				}
				unset($settingsFile[$key]);
			} else {
				if (preg_match('/const (.+?) = (.*)/', $line, $matchesLine)) {
					$constantName = trim($matchesLine[1], " ");
					if (!is_null($constants[$constantName]['value'])) {
						list($value, $isArray) = self::varToText($constantName, $constants);
						$settingsFile[$key] = ' const ' . $constantName . ' = ' . $value;
						unset($constants[$constantName]);
					} else {
						if ($constants[$constantName]['type'] == 'array') {
							$isArray = true;
						}
						unset($settingsFile[$key-1]);
						unset($settingsFile[$key]);
					}
				} elseif (!$updateDone) {
					if (strstr($line, 'Date: ')) {
						$settingsFile[$key] = ' * Date: '.date('d/m/Y', time());
					}
					if (strstr($line, 'Time: ')) {
						$settingsFile[$key] = ' * Time: '.date('H:i', time());
						// L'heure étant sous la date, si on l'a changé on a fini avec la date de mise à jour.
						$updateDone = true;
					}
				}
			}
		}
		if (!empty($constants)) {
			// On enlève la dernière accolade du fichier
			array_pop($settingsFile);
			foreach ($constants as $constant => $tab) {
				if (!is_null($tab['value'])){
					list($value, $isArray) = self::varToText($constant, $constants);
					$settingsFile[] = ' /** '.$tab['explain'].' */';
					$settingsFile[] = ' const ' . $constant . '   = ' . $value;
				}
			}
			$settingsFile[] = '}';
		}
		//echo Get::varDump($settingsFile);
		$ret = $fs->writeFile('Settings.php', $settingsFile, false, false, true);
		if ($ret){
			//$fs->setChmod('Settings.php', 777);
			$_SESSION['alerts'][] = array('type'=>'success','message'=>'Paramètres sauvegardés !');
		} else {
			$_SESSION['alerts'][] = array('type'=>'danger','message'=>'Impossible de sauvegarder les paramètres !');
		}
		return $ret;
	}

	/**
	 * Retourne des variables translatées en déclaration dans un fichier de config
	 *
	 * @param $constantName
	 * @param $constants
	 *
	 * @return array
	 */
	protected static function varToText($constantName, $constants){
		$isArray = false;
		switch ($constants[$constantName]['type']) {
			case 'string':
				if (($constantName == 'PASSWORD' and $constants[$constantName]['value'] != Settings::PASSWORD) or ($constantName == 'GUEST_PASSWORD' and $constants[$constantName]['value'] != Settings::GUEST_PASSWORD)) {
						$constants[$constantName]['value'] = password_hash($constants[$constantName]['value'], PASSWORD_DEFAULT);
				}
				$value = '\'' . $constants[$constantName]['value'] . '\';';
				break;
			case 'int':
			case 'float':
				$value = $constants[$constantName]['value'] . ';';
				break;
			case 'bool':
				$value = ($constants[$constantName]['value']) ? 'true;' : 'false;';
				break;
			case 'array':
				$value = 'array(' . PHP_EOL;
				foreach ($constants[$constantName]['value'] as $item => $itemVal) {
					$value = '		' . $item . '   => ' . $itemVal . ',' . PHP_EOL;
				}
				$value = substr($value, 0, -2) . PHP_EOL . '  );';
				$isArray = true;
				break;
			default:
				$value = 'null;';
		}
		return array($value, $isArray);
	}

	/**
	 * Récupère les informations sur le commit en place ainsi que sur d'éventuelles mises à jour
	 * @return object
	 */
	protected static function getSalsifisVersion(){
		global $absolutePath;
		$gitRepo = Git::open($absolutePath);
		$lastCommit = $gitRepo->getLastCommit();
		$originUrl = $gitRepo->getOrigin();
		$gitRepo->fetch();
		$logs = mb_substr($gitRepo->logFileRevisionRange('master', 'origin/master', '+@@+%H+-+%h+-+%at+-+%B'), 4);

		preg_match('/http(?:s|):\/\/(.+?)\/(?:.*)\/(.+?)(?:\.git|)$/i', $originUrl, $matches);
		return (object)array(
				'lastCommit'      => $lastCommit->hash,
		    'lastCommitURL'   => $lastCommit->url,
		    'lastCommitDate'  => Sanitize::date($lastCommit->date, 'dateTime'),
		    'origin'          => $matches[1],
				'originURL'       => $originUrl,
		    'repo'            => $matches[2],
		    'repoURL'         => $originUrl,
		    'updates'         => explode('+@@+', $logs)
		);
	}

	/**
	 * Affiche le modal qui décrit la version du serveur
	 */
	public static function displayServerVersion(){
		$version = self::getSalsifisVersion();
		?>
		<div class="uk-modal-dialog">
			<button class="uk-modal-close-default" type="button" uk-close></button>
			<div class="uk-modal-header">
				<h2 class="uk-modal-title">Version <?php echo Get::getTitleWithArticle(); ?></h2>
			</div>
				<div class="uk-modal-body">
					<p>Salsifis² est une interface web pour gérer un petit serveur de media sous Linux.</p>
					<ul>
						<li>Version : <a href="<?php echo $version->lastCommitURL; ?>"><?php echo $version->lastCommit; ?></a> du <?php echo $version->lastCommitDate; ?></li>
						<li>Origine : <a href="<?php echo $version->originURL; ?>"><?php echo $version->origin; ?></a></li>
						<li>Nom du dépôt : <a href="<?php echo $version->repoURL ?>"><?php echo $version->repoURL; ?></a></li>
					</ul>
					<?php
					if (!empty($version->updates) and !empty($version->updates[0])){
						$alert = 'Une mise à jour '.Get::getTitleWithArticle().' est disponible !';
						$alert .= '<ul>';
						foreach ($version->updates as $updateRaw) {
							list($updateFullHash, $updateShortHash, $updateTimestamp, $updateBody) = explode('+-+', $updateRaw);
							$alert .= '<li>'.Sanitize::date($updateTimestamp, 'dateTime').' - <a href="'.$version->originURL . '/commit/' . $updateFullHash.'">'.$updateShortHash.'</a> : '.$updateBody.'</li>';
						}
						$alert .= '</ul>';
						Components::Alert('primary', $alert);
					}
					?>
				</div>
				<div class="uk-modal-footer uk-text-right">
					<button class="uk-button uk-button-default uk-modal-close" type="button">Quitter</button>
				</div>
		</div>
		<?php
	}
}