<?php

$shop_url = $_GET['shop'];

header('Location: install.php?shop=' . $shop_url);
exit();
