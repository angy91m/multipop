<?php
defined( 'ABSPATH' ) || exit;

// YOU CAN DISPOSE OF $user as WP_User Instance

if ($this->current_user_is_admin()) {
    $mail_to_confirm = get_user_meta( $user->ID, 'mpop_mail_to_confirm', true );
    $mail_changing = get_user_meta( $user->ID, '_new_email', true );
    $card_active = get_user_meta( $user->ID, 'mpop_card_active', true );
    ?>
        <link rel="stylesheet" href="<?=plugins_url()?>/multipop/css/main.css">
        <style type="text/css">
            #fieldset-billing + h2,
            #fieldset-shipping {
                display: none;
            }
        </style>
        <br>
        <h2>Multipopolare</h2>
        <table class="form-table">
            <tr>
                <th><label for="mpop_mail_confirmed"></label> E-mail confermata</th>
                <td id="mpop_mail_confirmed"><?= $mail_to_confirm ? $this->dashicon('no') : $this->dashicon('yes') ?></td>
            </tr>
            <tr>
                <th>Cambio e-mail in attesa di conferma</th>
                <td><?= $mail_changing ? $this->dashicon('yes') . 'Indirizzo precedente: ' . $mail_changing : $this->dashicon('no') ?></td>
            </tr>
            <tr>
                <th>Tessera attiva</th>
                <td><?= $card_active ? $this->dashicon('yes') : $this->dashicon('no') ?></td>
            </tr>
            <?php
            if (in_array($user->roles[0], ['administrator', 'multipopolare_resp']) && $this->user_has_master_key() ) { ?>
                <tr>
                    <th>Master key</th>
                    <td><?=$this->user_has_master_key($user->ID) ? $this->dashicon('yes') . '<br><button class="button" id="revoke_master_key_button" name="revoke_master_key" value="1">Revoca master key</button>' : $this->dashicon('no') . '<br><button class="button" id="set_master_key_button">Imposta master key</button>
                    <span id="set_master_key_container" style="display:none">
                        <span id="master_key_error" style="color:#f00; display:none;">Le master key non combaciano<br></span>
                        <label for="master_key">Nuova master key utente</label><br>
                        <input type="password" id="master_key" name="master_key" disabled/><br><br>
                        <label for="master_key">Conferma nuova master key utente</label><br>
                        <input type="password" id="master_key_confirmation" /><br><br>
                        <label for="master_key">La tua master key</label><br>
                        <input type="password" id="current_user_master_key" name="current_user_master_key" disabled/><br><br>
                        <button class="button" id="cancel_set_master_key_button">Annulla</button>
                    </span>'?></td>
                </tr>
                <?php
            }
            ?>
        </table>
        <script id="__MULTIPOP_DATA__" type="application/json"><?=json_encode([
            'mailConfirmed' => !($mail_to_confirm || $mail_changing),
            'userRole' => $user->roles[0],
            'currentUserHasMasterKey' => $this->user_has_master_key(),
            'userHasMasterKey' => $this->user_has_master_key($user->ID)
        ])?></script>
        <script type="text/javascript" src="<?=plugins_url()?>/multipop/js/user-edit.js"></script>
    <?php
}