<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Response;

class GameTunnelAPI extends Controller
{
    public function in(Request $request)
    {

    }

    public function out(Request $request)
    {
       
    }

    public function mixed(Request $request)
    {
        $command = $request->command;
        $urlFullUrl = $request->fullUrl();
        $urlReplaceToReal = str_replace('http://localhost/api/game_tunnel/mixed/booongo/', 'https://box7-stage.betsrv.com/gate-stage1/gs/', $urlFullUrl);
        $url = $urlReplaceToReal;

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $headers = array(
           "Host: box7-stage.betsrv.com",
           "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:100.0) Gecko/20100101 Firefox/100.0",
           "Accept: */*",
           "Accept-Language: en-US,en;q=0.5",
           "Accept-Encoding: gzip, deflate, br",
           "Content-Type: application/json",
           "DNT: 1",
           "Connection: keep-alive",
           "Sec-Fetch-Dest: empty",
           "Sec-Fetch-Mode: cors",
           "Sec-Fetch-Site: cross-site",
        );
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        $resp = curl_exec($curl);
        curl_close($curl);

        return response()->json($resp);
    }

    public function bgamingMixed(Request $request)
    {
        // Add small validator though all should be ok, as most can be considered safe from side of bgaming/ss (with exceptions x)

        // fully working still jlust need go on ssl or remote host to test, currency etc can simply be changed from bottom, would need just to collect the tokens/ssh as players enter, they are valid for 60 minutes in bgaming - which is more then sufficient i've felt 

        // u can recontinue these below extremely easy, overly easy, more over balance amount dont matter, can play in minus etc. dont matter 

        // STILL NEEDS to be edited per game type (provably fair, older games from bgaming (with configurable per line bets) and newwer) 
        // and configured, in rare case games have own configu diff to the above - mainly the very very old may have a slight diff per win bet, but if u take last 50-70 games u will have no issues with just 3 configs

        $game = $request->game_slug;
        $realToken = 'e9bd5acb-98df-4538-8694-1a68c70447b4'; //temp manual added token, simply use demo generator link for bgaming
        $command = $request->command;

        $urlFullUrl = $request->fullUrl();
        $urlReplaceToReal = str_replace('http://localhost/api/game_tunnel/bgaming/', 'https://bgaming-network.com/api/', $urlFullUrl);
        $url = $urlReplaceToReal;

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            
        $headers = array(
           "Referer: https://bgaming-network.com/games/JokerQueen/FUN?play_token=".$realToken,
           "Origin: https://bgaming-network.com",
           "Alt-Used: bgaming-network.com",
           "Connection: keep-alive",
           "Sec-Fetch-Mode: cors",
           "Sec-Fetch-Site: same-origin",
        );
        $data = $request->all();
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Accept: application/json'));
        curl_setopt($curl, CURLOPT_POST, 1); 
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        $resp = curl_exec($curl);
        curl_close($curl);

        // Line below the response is basically where u can edit, skip wins, do whatever, u just put balance there (based on the auth of user direct, ill start on this tomorrow i just started tonight with basics in systems to make mass abuses or different takes on the existing ones)

        // u can combine this with ur frontend fraud & sentry frauds pretty easy basically all methods seem easy in general im gonna hide tomorrow the sentry if u will in the balance counter to see if that works better because of soon all will be public anyway 
        // but tyty  for let me finish my things i had some fun sinc e2 months again 
        return $resp;
    }
}
