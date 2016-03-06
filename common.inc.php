<?php
/*
    GuardianGGBot
    Copyright © 2016, Slava slavikus Karpenko <slavikus@gmail.com>

    All rights reserved, and whatnot. Be brave, guardian!

    If you improve this app, please consider making a pull request: https://github.com/slavikus/guardiangg-bot
*/

if (!file_exists("config.inc.php")) {
  die("Please copy config.dist.inc.php to config.inc.php and edit it to suit your needs first.");
}  

require_once("config.inc.php");

print BUNGIENET_API_KEY;
