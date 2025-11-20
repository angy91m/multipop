<?php
defined( 'ABSPATH' ) || exit;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "https://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html lang="it" xmlns="https://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0 " />
    <title><?=$mail['subject']?></title>
    <?php
        if (isset($mail['style'])) {
            echo '<style type="text/css">' . $mail['style'] . '</style>' . "\n";
        }
    ?>
</head>
<body>
    <?=$mail['body']?>
</body>
</html>
<?php