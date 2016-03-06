<?php
/*
    GuardianGGBot
    Copyright © 2016, Slava slavikus Karpenko <slavikus@gmail.com>

    All rights reserved, and whatnot. Be brave, guardian!

    If you improve this app, please consider making a pull request: https://github.com/slavikus/guardiangg-bot
*/

require_once("../common.inc.php");

if (!isset($_REQUEST['command']))
{
    die("Unknown command.");
}

header("Content-Type: application/json");

switch (strtolower($_REQUEST['command'])) {
    case "/about":
    $rnd = array("Симпы, лаффки, обнимашки!","Жаркие стычки и горячие питерские парни, вот это всё.","Чмоке фсем в этом чати!", "Привет Triplewipe!", "Обнимашки Guardian.FM!", "@mishookoo — няша!", "Стотыщ галахорнов вам на голову.", "Ультуй на лицо!", "Будете в Питере - пишите, поите пивом.");
    send_msg($req, "*Guardian.GG Bot*\n\nCreated by *Slava* [@slavikus](https://telegram.me/slavikus) *Karpenko*\nData credit goes to [Guardian.GG](http://guardian.gg)\nCheck out Slava's [Loadouts for Destiny](http://LoadoutsApp.com) for iOS, too!\n\n".$rnd[rand(0, count($rnd)-1)]);
    break;

    case '/elo':
    handle_elo($_REQUEST['command'], $_REQUEST['text']);
    break;

    case '/too':
    handle_too($_REQUEST['command'], $_REQUEST['text'], 14);
    break;

    case '/skirmish':
    handle_too($_REQUEST['command'], $_REQUEST['text'], 9);
    break;

    default:
    send_msg($req, "Unknown command: ".$_REQUEST['command']);
    break;
}

function handle_too($req, $handle, $mode = 14) {
    $cmd_name = '/too';
    $mode_name = 'Trials of Osiris';
    
    if ($mode == 9) {
        $cmd_name = '/skirmish';
        $mode_name = 'Skirmish';
        
    }
    
    if (!$handle) {
        send_msg($req, "Usage: *$cmd_name* _PSN_ or _XBL_");
        return;
    }
    
    $out_system = 0;
    $out_handle = '';
    
    $user_id = lookup_user($handle, 0, $out_system, $out_handle);
    
    if ($user_id == null)
    {
        send_msg($req, "Sorry, *".$handle."* was not found.");
        record_hit($handle, '404');
        return;
    }
    
    $elos = callGuardianGGFireteam($user_id, $mode);
    if ($elos == null)
    {
        send_msg($req, "Sorry, $mode_name fireteam data for *".$out_handle."* was not found. Try _/elo ".$out_handle."_");
        return;
    }
    
    $txt = '';
    foreach ($elos as $elo) {
        $txt .= "<http://guardian.gg/en/profile/".$elo['membershipType']."/".rawurlencode($elo['name'])."/$mode|".$elo['name'].">: ";
            
            $txt .= "Elo *".round($elo['elo'], 0)."*";
            $txt .= ", K/D: _".round(floatval($elo['kills'])/floatval($elo['deaths']), 2)."_";
            $txt .= ", K/D/A: _".round(floatval($elo['kills']+$elo['assists'])/floatval($elo['deaths']), 2)."_";
            
            if ($elo['elo'] >= 2200 or round(floatval($elo['kills'])/floatval($elo['deaths']), 2) >= 2.5) $txt .= " 💀";
            else if ($elo['elo'] >= 2000 or round(floatval($elo['kills'])/floatval($elo['deaths']), 2) >= 2.0) $txt .= " 👻";
            else if ($elo['elo'] >= 1800 or round(floatval($elo['kills'])/floatval($elo['deaths']), 2) >= 1.8) $txt .= " 😎";
            
            $txt .= "\n";
    }
    
    send_msg($req, $txt);
    record_hit($handle, 'too');
}

function handle_elo($req, $handle, $mode = 0) {
    $elo = get_elo($handle);
    if ($elo) {
        send_msg($req, elo_text($elo, $mode));
        record_hit($elo['handle'], 'elo');
    } else {
        send_msg($req, "Sorry, *".$handle."* was not found.");
        record_hit($handle, '404');
    }
}

function send_msg($req, $text) {
    $answer = array('text' => $text, 'mrkdwn' => true, 'response_type' => 'in_channel');
    
    print json_encode($answer);
}

function elo_text($elo, $mode = 0, $include_nick = true) {
    $txt = '';
    
    if ($include_nick) {
        $txt = "<http://guardian.gg/en/profile/".$elo['system']."/".rawurlencode($elo['handle'])."/5|".$elo['handle']."> (";
        if ($elo['system'] == 1) {
            $txt .= "_XBL_";
        } else {
            $txt .= "_PSN_";
        }
    
        $txt .= "):\n\n";
    }
    
    foreach ($elo['elo'] as $arr) {
        if ($mode == 0 or intval($arr['mode']) == $mode)
        {
            $line = '';
            
            if (intval($arr['rank']) > 0) {
                $line .= mode_name($arr['mode']).": *".round(floatval($arr['elo']), 2)."*";
                if (intval($arr['rank']) < 5000)
                    $line .= " _#".$arr['rank']."_";
            }
            
            if (intval($arr['gamesPlayedSolo']) > 0) {
                if (intval($arr['rank']) > 0)
                    $line .= " (solo: ".round(floatval($arr['eloSolo']), 2).")";
                else
                    $line .= mode_name($arr['mode']).": *".round(floatval($arr['eloSolo']), 2)."* (solo only)";
            }
            
            if ($line)
                $txt .= $line."\n";
        }
    }
    
    return $txt;
}

