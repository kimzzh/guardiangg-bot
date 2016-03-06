<?php
/*
    GuardianGGBot
    Copyright © 2016, Slava slavikus Karpenko <slavikus@gmail.com>

    All rights reserved, and whatnot. Be brave, guardian!

    If you improve this app, please consider making a pull request: https://github.com/slavikus/guardiangg-bot
*/

require_once("../../config.inc.php");

if (isset($_REQUEST['code']))
{
    $r = callOAuth($_REQUEST['code']);
    die("You're all set!");
}

function callOAuth($code) {
    $opts = array(
        'http' => array(
            'method' => 'POST',
            'content' => http_build_query(array('client_id' => SLACK_CLIENT_ID, 'client_secret' => SLACK_CLIENT_SECRET, 'code' => $code)),
            'header' => 'Content-Type: application/x-www-form-urlencoded'
        )
    );
    
    $ctx = stream_context_create($opts);
    
    $fp = fopen('https://slack.com/api/oauth.access', 'r', false, $ctx);
    $result = stream_get_contents($fp);
    fclose($fp);
    
    return json_decode($result, true);
}
