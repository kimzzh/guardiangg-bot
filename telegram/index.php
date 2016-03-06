<?php
/*
    GuardianGGBot
    Copyright © 2016, Slava slavikus Karpenko <slavikus@gmail.com>

    All rights reserved, and whatnot. Be brave, guardian!

    If you improve this app, please consider making a pull request: https://github.com/slavikus/guardiangg-bot
*/

require_once("../common.inc.php");

$req = json_decode(@file_get_contents('php://input'), true);

if (!$req or !$req['update_id'])
    return;

header("Content-Type: application/json");

if (isset($req['inline_query'])) {
    handle_inline_query($req);
} else if (isset($req['message'])) {
    handle_message($req);
}

function handle_inline_query($req) {
    $q = @$req['inline_query']['query'];
    $id = @$req['inline_query']['id'];
    
    if (!$q)
        return;
    
    $res = array();
    
    $elo = get_elo($q);
    if ($elo)
    {
        $item = array('id' => md5($elo['user_id']), 'type' => 'article', 'title' => $elo['handle'], 'description' => elo_text($elo, 14, false), 'url' => 'http://guardian.gg/en/profile/'.$elo['system'].'/'.rawurlencode($elo['handle']).'/5', 'message_text' => elo_text($elo), 'parse_mode' => 'Markdown');
    
        $item['thumb_url'] = $elo['system'] == 1 ? 'http://www.bungie.net/img/theme/destiny/icons/icon_xbl.png' : 'http://www.bungie.net/img/theme/destiny/icons/icon_psn.png';
        $item['thumb_width'] = 42;
        $item['thumb_height'] = 42;
        
        $res[] = $item;
        
        record_hit($elo['handle'], 'elo');
    } else {
        record_hit($q, '404');
    }
    
    $answer = array('method' => 'answerInlineQuery', 'inline_query_id' => $id, 'cache_time' => 10, 'results' => $res);
     
    print json_encode($answer);
}

function handle_message($req) {
    $text = @$req['message']['text'];
    
    if (!$text or substr($text, 0, 1) != '/')
        return;
    
    $cmd = array_shift(split(' ', $text));
    $args = array_splice(split(' ', $text), 1);
    
    // split off the bot name from cmd, if any
    @list($cmd, $bot_name) = @split('@', $cmd, 2);
    
    switch (strtolower($cmd)) {
        case "/about":
        $rnd = array("Симпы, лаффки, обнимашки!","Жаркие стычки и горячие питерские парни, вот это всё.","Чмоке фсем в этом чати!", "Привет Triplewipe!", "Обнимашки Guardian.FM!", "@mishookoo — няша!", "Стотыщ галахорнов вам на голову.", "Ультуй на лицо!", "Будете в Питере - пишите, поите пивом.");
        send_msg($req, "*Guardian.GG Bot*\n\nCreated by *Slava* [@slavikus](https://telegram.me/slavikus) *Karpenko*\nData credit goes to [Guardian.GG](http://guardian.gg)\nCheck out Slava's [Loadouts for Destiny](http://LoadoutsApp.com) for iOS, too!\n\n".$rnd[rand(0, count($rnd)-1)]);
        break;
        
        case '/elo':
        handle_elo($req, $args);
        break;
        
        case '/too':
        handle_too($req, $args, 14);
        break;
        
        case '/skirmish':
        handle_too($req, $args, 9);
        break;
        
        /*
        case '/test':
        handle_test($req, $args, 14);
        break;
        */
        
        default:
        if (strtolower($bot_name) == 'guardianggbot')
            send_msg($req, "Unknown command: $cmd");
        break;
    }
}

function handle_too($req, $args, $mode = 14) {
    $handle = join(' ', $args);
    
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
        $txt .= "[".$elo['name']."](http://guardian.gg/en/profile/".$elo['membershipType']."/".rawurlencode($elo['name'])."/$mode): ";
            
            $txt .= "Elo *".round($elo['elo'], 0)."*";
            $txt .= ", K/D: _".round(floatval($elo['kills'])/floatval($elo['deaths']), 2)."_";
            $txt .= ", K/D/A: _".round(floatval($elo['kills']+$elo['assists'])/floatval($elo['deaths']), 2)."_";
            
            if ($elo['elo'] >= 2200 or round(floatval($elo['kills'])/floatval($elo['deaths']), 2) >= 2.5) $txt .= " [💀]";
            else if ($elo['elo'] >= 2000 or round(floatval($elo['kills'])/floatval($elo['deaths']), 2) >= 2.0) $txt .= " [👻]";
            else if ($elo['elo'] >= 1800 or round(floatval($elo['kills'])/floatval($elo['deaths']), 2) >= 1.8) $txt .= " [😎]";
            
            $txt .= "\n";
    }
    
    send_msg($req, $txt);
    record_hit($handle, 'too');
}

function handle_elo($req, $args, $mode = 0) {
    $handle = join(' ', $args);
    
    if (!$args || !count($args)) {
        send_msg($req, "Usage: */elo* _PSN_ or _XBL_");
    } else {
        $elo = get_elo($handle);
        if ($elo) {
            send_msg($req, elo_text($elo, $mode));
            record_hit($elo['handle'], 'elo');
        } else {
            send_msg($req, "Sorry, *".$handle."* was not found.");
            record_hit($handle, '404');
        }
    }
}

function send_msg($req, $text) {
    $answer = array('method' => 'sendMessage', 'chat_id' => $req['message']['chat']['id'], 'reply_to_message_id' => $req['message']['message_id'], 'parse_mode' => 'Markdown', 'text' => $text, 'disable_web_page_preview' => true);
    
    print json_encode($answer);
}

function elo_text($elo, $mode = 0, $include_nick = true) {
    $txt = '';
    
    if ($include_nick) {
        $txt = "[".$elo['handle']."](http://guardian.gg/en/profile/".$elo['system']."/".rawurlencode($elo['handle'])."/5) (";
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
