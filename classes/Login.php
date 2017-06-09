<?php

/**
 * Created by PhpStorm.
 * User: Dric
 * Date: 05/06/2017
 * Time: 00:56
 */
class Login extends Page{
	protected $title = 'Connexion';

	public function main() {
		if (isset($_REQUEST['action']) and $_REQUEST['action'] == 'login'){
			Auth::tryLogin();
		}
		?>
		<div class="uk-margin-auto uk-width-large uk-text-center">
			<img class="uk-visible@l uk-box-shadow-medium uk-border-circle uk-padding-small" src="img/favicons/android-icon-72x72.png" alt="<?php echo Settings::TITLE; ?>">
		<h2 class="uk-margin-remove-top">Connexion sur <?php echo Settings::TITLE; ?></h2>

			<p>Vous devez vous connectez à l'aide d'un mot de passe pour pouvoir accéder à l'interface du serveur <?php echo Settings::TITLE; ?></p>
			<form action="?action=login" method="post">
				<input type="hidden" name="action" value="login">
				<div class="uk-margin uk-padding uk-padding-remove-bottom uk-margin-remove-bottom">
					<label class="">Mot de passe</label>
					<input name="loginPwd" class="uk-input uk-form-large" type="password" placeholder="Votre mot de passe très secret"><br>
				</div>
				<div class="uk-margin uk-padding uk-padding-remove-top uk-margin-remove-top uk-text-right">
					<label><input name="stayConnected" class="uk-checkbox" type="checkbox" checked> Rester connecté</label>
				</div>
				<button class="uk-button uk-button-default uk-button-large" type="submit">Connexion</button>
			</form>
		</div>
		<?php
	}
}