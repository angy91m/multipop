<?php
defined( 'ABSPATH' ) || exit;
if ($this->current_user_is_admin() && $this->user_has_master_key()) { ?>
    <br>
    <h2>Multipopolare</h2>
    <table class="form-table">
        <tr>
            <th>Master key</th>
            <td>
                <button class="button" id="change_master_key_button">Cambia master key</button>
                <span id="change_master_key_container" style="display:none">
                    <span id="master_key_error" style="color:#f00; display:none;">Le master key non combaciano<br></span>
                    <label for="master_key">Nuova master key</label><br>
                    <input type="password" id="master_key" name="master_key" disabled/><br><br>
                    <label for="master_key">Conferma nuova master key</label><br>
                    <input type="password" id="master_key_confirmation" /><br><br>
                    <label for="master_key">Vecchia key</label><br>
                    <input type="password" id="current_user_master_key" name="current_user_master_key" disabled/><br><br>
                    <button class="button" id="cancel_change_master_key_button">Annulla</button>
                </span>
            </td>
        </tr>
    </table>

<?php
}