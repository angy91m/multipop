<?php
defined( 'ABSPATH' ) || exit;
?>
<div v-if="selectedTab.name == 'summary'">
    <?php require __DIR__ . '/tabs/summary.php' ?>
</div>
<div v-if="selectedTab.name == 'passwordChange'">
    <?php require __DIR__ . '/tabs/password-change.php' ?>
</div>
<div v-if="selectedTab.name == 'masterkeyChange'">
    <?php require __DIR__ . '/tabs/masterkey-change.php' ?>
</div>
<div v-if="selectedTab.name == 'card'">
    <?php require __DIR__ . '/tabs/card.php' ?>
</div>
<div v-if="selectedTab.name == 'moduleUpload' && moduleUploadData.sub">
    <?php require __DIR__ . '/tabs/module-upload.php' ?>
</div>
<div v-if="selectedTab.name == 'users'" id="mpop-user-search">
    <?php require __DIR__ . '/tabs/users.php' ?>
</div>
<div v-if="selectedTab.name == 'userAdd'" id="mpop-sub-view">
    <?php require __DIR__ . '/tabs/user-add.php' ?>
</div>
<div v-if="selectedTab.name == 'userView'" id="mpop-user-view">
    <?php require __DIR__ . '/tabs/user-view.php' ?>
</div>
<div v-if="selectedTab.name == 'subAdd'">
    <?php require __DIR__ . '/tabs/sub-add.php' ?>
</div>
<div v-if="selectedTab.name == 'subView'" id="mpop-sub-view">
    <?php require __DIR__ . '/tabs/sub-view.php' ?>
</div>
<div v-if="selectedTab.name == 'uploadUserCsv'">
    <?php require __DIR__ . '/tabs/upload-user-csv.php' ?>
</div>