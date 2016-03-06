<?php
/*
    GuardianGGBot
    Copyright © 2016, Slava slavikus Karpenko <slavikus@gmail.com>

    All rights reserved, and whatnot. Be brave, guardian!

    If you improve this app, please consider making a pull request: https://github.com/slavikus/guardiangg-bot
*/

if (!file_exists(__DIR__ . "/config.inc.php")) {
  die("Please copy config.dist.inc.php to config.inc.php and edit it to suit your needs first.");
}  

require_once(__DIR__ . "/config.inc.php");

if (!ADMIN_PASSWORD) {
    die("Please set admin password to register the bot at appropriate services.");
}

/*
    Get Elo rating for a given handle. System should be 1 for XBL, 2 for PSN or 0 to try and auto-detect.
    Returns either:
            an array('handle' => <full user handle with proper capitalization and spacing>,
                    'system' => <detected system = 1/2>,
                    'user_id' => <Bungie.Net user id>,
                    'elo' => <array with Elo values>
                )
            null if lookup failed
*/
function get_elo($handle, $system = 0)
{
    $out_system = 0;
    $out_handle = '';
    
    $user_id = lookup_user($handle, $system, $out_system, $out_handle);
    
    if ($user_id == null)
        return null;
    
    $elo = callGuardianGG($user_id);
    if (!$elo)
        return null;
    
    return array('handle' => $out_handle, 'user_id' => $user_id, 'system' => intval($out_system), 'elo' => $elo);
}

/*
    Decode mode number into human readable mode name. Yes, I know this can be looked through Bungie.Net definitions, but I am lazy.
*/
function mode_name($mode_id) {
    $MODES = array(
        523 => "Crimson Doubles",
        14 => "ToO",
        19 => "IB",
        10 => "Control",
        12 => "Clash",
        24 => "Rift",
        13 => "Rumble",
        23 => "Elimination",
        11 => "Salvage",
        15 => "Doubles",
        28 => "Zone Control",
        29 => "SRL",
        9 => "Skirmish"
    );
    
    if (isset($MODES[intval($mode_id)]))
        return $MODES[intval($mode_id)];
        
    return "<".$mode_id.">";
}

/*
    Lookup user on Bungie.Net. System should be 1 for XBL, 2 for PSN or 0 to try and auto-detect.

    Returns:
        Bungie.Net user id or null if not found.
        out_system and out_handle will also be filled if user was found.
*/
function lookup_user($handle, $system, &$out_system, &$out_handle) {
    $autolookup = false;
    if ($system == 0) {
        // Try and guess. This will be a wild guess by checking whether user handle has a space in it (XBL) or not (PSN)
        if (strpos($handle, ' ') !== false) {
            return lookup_user($handle, 1, $out_system, $out_handle);
        }
        
        $autolookup = true;
        $system = 2;
    }
    
    $res = json_decode(callBungieNet("https://www.bungie.net/platform/destiny/SearchDestinyPlayer/".$system.'/'.rawurlencode($handle)), true);
    
    if ($res && is_array($res['Response']) && count($res['Response']) > 0)
    {
        $member = array_pop($res['Response']);
        
        $out_system = $member['membershipType'];
        $out_handle = $member['displayName'];
        return $member['membershipId'];
    }
    
    if ($autolookup)
        return lookup_user($handle, 1, $out_system, $out_handle); // try and find on XBL
    
    return null;
}

/*
    Call Bungie.Net API and return the result.
*/
function callBungieNet($url) {
    $opts = array(
        'http' => array(
            'method' => 'GET',
            'header' => 'X-API-Key: '.BUNGIENET_API_KEY
        )
    );
    
    $ctx = stream_context_create($opts);
    
    $fp = fopen($url, 'r', false, $ctx);
    $result = stream_get_contents($fp);
    fclose($fp);
    
    return $result;
}

/*
    Call Guardian.GG Elo endpoint and return the result.
*/
function callGuardianGG($user_id) {
    $opts = array(
        'http' => array(
            'method' => 'GET',
        )
    );
    
    $ctx = stream_context_create($opts);
    
    $fp = fopen('https://api.guardian.gg/elo/'.rawurlencode($user_id), 'r', false, $ctx);
    $result = stream_get_contents($fp);
    fclose($fp);
    
    return json_decode($result, true);
}

/*
    Call Guardian.GG fireteam endpoint for a given game mode and return the result. mode is a number like 14 (see mode_name() for a hint).
*/
function callGuardianGGFireteam($user_id, $mode) {
    $opts = array(
        'http' => array(
            'method' => 'GET',
        )
    );
    
    $ctx = stream_context_create($opts);
    
    $fp = fopen('https://api.guardian.gg/fireteam/'.rawurlencode($mode).'/'.rawurlencode($user_id), 'r', false, $ctx);
    $result = stream_get_contents($fp);
    fclose($fp);
    
    return json_decode($result, true);
}

// Record a hit on Google Analytics (if GOOGLE_ANALYTICS_ID is set in config.inc.php)
function record_hit($handle, $endpoint) {
    if (!GOOGLE_ANALYTICS_ID)
        return;
    
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    else 
        $ip = @$_SERVER['REMOTE_ADDR'];
    
    $host = parse_url(BOT_URL_BASE, PHP_URL_HOST);
    
    $data = array(
        'v' => 1,
        't' => 'pageview',
        'tid' => GOOGLE_ANALYTICS_ID,
        'cid' => uniqid(),
        'dh' => $host,
        'dt' => $handle,
        'uip' => $ip
    );
    
    $data['dp'] = '/'.rawurlencode($endpoint).'/'.rawurlencode($handle);
    
    $opts = array(
        'http' => array('method'=>'POST',
                        'content' => http_build_query($data),
                        'header' => 'Content-Type: application/x-www-form-urlencoded')
    );
     
     $ctx = stream_context_create($opts);
     $fp = fopen('https://www.google-analytics.com/collect', 'r', false, $ctx);
     fclose($fp);
}
