<?php
use FileSystem\Fs;

/**
 * Creator: Dric
 * Date: 07/06/2017
 * Time: 13:53
 */
class Admin {
	protected static function readSettings() {
		$fs = new \FileSystem\Fs('classes/');
		$settingsFile = $fs->readFile( 'DefaultSettings.php', 'array', true, true);
		$constants = array();
		$constantName = null;
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
				  'value'     => null
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
						$constants[$constantName]['type'] = 'null';
						$value = null;
					}
					$constants[$constantName]['defaultValue'] = $value;
				}
			} elseif (preg_match('/(?:\'|")(.+?)(?:\'|")\s+=> (?:\'|")(.+?)(?:\'|")/i', $line, $matchesLine)) {
				// On traite les items d'un tableau
				$constants[$constantName]['defaultValue'][$matchesLine[1]] = $matchesLine[2];
			}
		}
		$constantName = null;
		if ($fs->fileExists('Settings.php')) {
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
							$constants[$constantName]['type'] = 'null';
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

	public static function displayServerSettings() {
		$constants = self::readSettings();
		$related = array(
			'pwd'   => array(
				'USE_AUTH',
			  'PASSWORD'
			),
		  'adv'   => array(
			  'DEBUG',
			  'DATA_PARTITION'
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
					<input type="hidden" name="action" value="saveServerSettings">
					<button class="uk-button uk-button-primary" type="submit">Sauvegarder</button>
				</div>
			</form>
		</div>
		<?php
	}

	protected static function createFormInputs($constants){
		foreach ($constants as $constant => $tab) {
			switch ($tab['type']) {
				case 'string':
				case 'null':
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
		}
	}

	public static function saveServerSettings(){
		$constants = self::readSettings();
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
		echo Get::varDump($constants);
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
		$isArray = false;
		foreach ($settingsFile as $key => $line){
			if ($isArray){
				if (preg_match('/\);/', $line)){
					$isArray = false;
				}
				unset($settingsFile[$key]);
			} else {
				preg_match('/const (.+?) = (.*)/', $line, $matchesLine);
				$constantName = trim($matchesLine[1], " ");

				if (!is_null($constants[$constantName]['value'])) {
					list($value, $isArray) = self::varToText($constantName, $constants);
					preg_replace('/const (.+?) = (.*)/', 'const $1 = ' . $value, $settingsFile[$key]);
					unset($constants[$constantName]);
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
					$settingsFile[] = '  const ' . $constant . '   => ' . $value;
				}
			}
			$settingsFile[] = '}';
		}
		echo Get::varDump($settingsFile);
	}

	protected static function varToText($constantName, $constants){
		$isArray = false;
		switch ($constants[$constantName]['type']) {
			case 'string':
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
}