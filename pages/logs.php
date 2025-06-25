<?php
defined( 'ABSPATH' ) || exit;
if ( !$this->current_user_is_admin() ) {
    echo '<p>Accesso non consentito</p>';
    exit;
}
$log_page = isset($_GET['log_page']) ? intval($_GET['log_page']) : 1;
$log_action = isset($_GET['log_action']) ? strval($_GET['log_action']) : '';
$log_user = isset($_GET['log_user']) ? intval($_GET['log_user']) : null;
$log_author = isset($_GET['log_author']) ? intval($_GET['log_author']) : null;
$results = $this->get_logs($log_page, $log_action, $log_user, $log_author);
?>
<style type="text/css">
    #mpop-logs-table {
        border-collapse: collapse;
        width: 100%;
    }
    #mpop-logs-table td,
    #mpop-logs-table th {
        border: 1px solid #dddddd;
        text-align: left;
        padding: 8px;
    }
</style>
<form method="GET">
    <label>
        Action:&nbsp;
        <input type="text" name="log_action" style="text-transform: uppercase" />
        <input type="hidden" name="page" value="multipop_logs" />
    </label>
</form>
<table id="mpop-logs-table">
    <thead>
        <tr>
            <th>Azione</th>
            <th>Dati</th>
            <th>Utente impattato</th>
            <th>Authore</th>
            <th>Timestamp</th>
        </tr>
    </thead>
    <tbody>
        <?php
        foreach ($results as $r) {
            ?>
            <tr>
                <td><?=$r['action']?></td>
                <td><?=json_encode(json_decode($r['data'], true), JSON_PRETTY_PRINT)?></td>
                <td><?=$r['user']?></td>
                <td><?=$r['author']?></td>
                <td><?=$this::show_date_time(intval($r['ts']))?></td>
            </tr>
            <?php
        }
        ?>
    </tbody>
</table>
<?php