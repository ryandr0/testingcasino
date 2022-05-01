<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Gamelist;

class GameUtillityFunctions extends Controller
{
    //
    public static $booongo_apikey = 'hj1yPYivJmIX4X1I1Z57494re';


    // Setup a normalized game_id format, for example for SEO reasons to have operator/aggregator name inside the game id that is used, please note
    // this does invalidate all previous games and what not, so leave like below as app only for testing purposes and to generate seo texts based on SS id's 

    // Also cause boongo just straight up was dogshit on their id's so yeh =P

    // It's adviseable if you end up changing below, to use a format that you can reproduce on a static ruleset, so that for example if your gamelist is not complete (think of live casino games or any other games with lobbies that allow player to switch around) you can still display same format game-list to player/ops.

    public static function createNormalizedGameIDFormat($name, $provider, $api_id) {

        //Below Format based on gamename (max 10 string length) + first two letter from API extension + provider (4 first letter)

        try { 
            $game_name = $name;
            $game_provider = $provider;
            $game_api_id = $api_id;

            if(!$game_name or $game_name === NULL || !$game_provider or $game_provider === NULL || !$game_api_id or $game_provider === NULL) {
                Log::debug('Incomplete origin id\'s provided: '.$game_name.$game_provider.$game_api_id);
            }

            //First removing spaces from name, and making lower capital letters, also removing several symbols - try to think properly of the order of deleting, as for example dleeting space bar first can cause issues for removing the 'and' word and so on.
            $formatGameNameReplace_lowered = strtolower($game_name);
            $formatGameNameReplace_andsymbol = str_replace("&", "", $formatGameNameReplace_lowered);
            $formatGameNameReplace_exclamation = str_replace("!", "", $formatGameNameReplace_andsymbol);
            $formatGameNameReplace_comma = str_replace(",", "", $formatGameNameReplace_exclamation);
            $formatGameNameReplace_nd = str_replace(" 'nd ", "-", $formatGameNameReplace_comma);
            $formatGameNameReplace_hyphen = str_replace("'", "", $formatGameNameReplace_nd);
            $formatGameNameReplace_and = str_replace(" and ", "-", $formatGameNameReplace_hyphen);
            $formatGameNameReplace_space = str_replace(" ", "", $formatGameNameReplace_and);

            $formatGameProviderNameReplace_lowered = strtolower($game_provider);
            $formatGameProviderNameReplace_nd = str_replace(" 'nd ", "n", $formatGameProviderNameReplace_lowered);
            $formatGameProviderNameReplace_hyphen = str_replace("'", "", $formatGameProviderNameReplace_nd);
            $formatGameProviderNameReplace_and = str_replace(" and ", "-", $formatGameProviderNameReplace_hyphen);
            $formatGameProviderNameReplac_spaces = str_replace(" ", "", $formatGameProviderNameReplace_and);

            $finalizedGameName = $formatGameNameReplace_space;
            if (strlen($finalizedGameName) > 16) {
                $finalizedGameName = substr($formatGameNameReplace_space, 0, 16);
            } 

            $finalizedGameProvider = $formatGameProviderNameReplac_spaces;
            if (strlen($finalizedGameProvider) > 8) {
                $finalizedGameProvider = substr($formatGameProviderNameReplac_spaces, 0, 8);
            } 

            $finalizedAPIid = substr($game_api_id, 0, 1); // 1 letter

            // Compacting the 3 parts together, split by the . symbol
            return $finalizedGameName.'.'.$finalizedGameProvider.'-'.$finalizedAPIid;

        } catch(Throwable $e) {
                Log::debug('Hard error on trying to set normalized game id name format: '.$e);
        }

    }


        // Using TIGER Mafia apikey

    public static function retrieveGamesBooongo() {

        $url = "https://gate-stage.betsrv.com/op/tigergames-stage/api/v1/game/list";

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $headers = array(
           "Content-Type: application/json",
        );
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        $data = json_encode(array(
            "api_token" => "hj1yPYivJmIX4X1I1Z57494re",
          ));

        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

        if(env('APP_ENV') === 'local') 
        {
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        }
        
        $resp = curl_exec($curl);
        $resp = json_decode($resp, true);

        foreach($resp['items'] as $gameItem)
        {
            if(isset($gameItem['game_id'])) {

                $api_extension = 'ttg_betsrv';
                $api_origin_id = $gameItem['game_id'].'++'.$gameItem['game_name'];

                $provider_name = $gameItem['provider_name'];
                $release_date = $gameItem['release_date'];

                //Generating custom game id on set format for seo and to really whitelabel customly per casino, see function for more info, using regular game_id 
                $ourOwnGameID = self::createNormalizedGameIDFormat($gameItem['i18n']['en']['title'], $provider_name, $api_extension) ?? $gameItem['game_name'];
                if($ourOwnGameID !== NULL) {

                //Transforming Booongo/Playson list to our format, we then check database based on the api_origin_id & the normalized game ID we created above //
                $transformInFormat[] = array(
                    'game_id' => $ourOwnGameID,
                    'fullName' => $gameItem['i18n']['en']['title'],
                    'thumbnail' => 'https:'.urldecode($gameItem['i18n']['en']['banner_path']),
                    'provider' => $provider_name,
                    'open' => 1,
                    'isRecommend' => 0,
                    'isNew' => 0,
                    'isHot' => 0,
                    'funplay' => 1,
                    'rtpDes' => 0,
                    'order_rating' => rand(0, 30),
                    'category' => 'slots',
                    'short_desc' => NULL,
                    'api_origin_id' => $api_origin_id,
                    'api_extension' => $api_extension,
                    'api_extra' => NULL,
                    'released_at' => $release_date,
                    'created_at' => now(),
                    'updated_at' => now(),
                );
            }
        }

        $getFullGamelist = Gamelist::all();
        $selectExistingGame = $getFullGamelist->where('api_origin_id', '=', $api_origin_id)->where('game_id', '=', $ourOwnGameID)->first();

        if(!$selectExistingGame) {
            $selectExistingGame = Gamelist::insert([
                    'game_id' => $ourOwnGameID,
                    'fullName' => $gameItem['i18n']['en']['title'],
                    'provider' => $provider_name,
                    'open' => 1,
                    'isRecommend' => 0,
                    'isNew' => 0,
                    'isHot' => 0,
                    'funplay' => 1,
                    'rtpDes' => 0,
                    'order_rating' => rand(0, 30),
                    'category' => 'slots',
                    'short_desc' => NULL,
                    'api_origin_id' => $api_origin_id,
                    'api_extension' => $api_extension,
                    'api_extra' => NULL,
                    'released_at' => \Carbon\Carbon::createFromDate($release_date),
                    'thumbnail' => 'https:'.urldecode($gameItem['i18n']['en']['banner_path']),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

        } else {

            // Put update function here if you wish to upsert/modify existing game records with updated info from the game-list retrieval.
            // Simply delete rows - probably you would need to create a main function for this to setup globally, depends  how many providers you plan on maintaining & updating

            $selectExistingGame->update([
                    'game_id' => $ourOwnGameID,
                    'fullName' => $gameItem['i18n']['en']['title'],
                    'provider' => $provider_name,
                    'open' => 1,
                    'isRecommend' => 0,
                    'isNew' => 0,
                    'isHot' => 0,
                    'funplay' => 1,
                    'rtpDes' => 0,
                    'order_rating' => rand(0, 30),
                    'category' => 'slots',
                    'short_desc' => NULL,
                    'api_origin_id' => $api_origin_id,
                    'api_extension' => $api_extension,
                    'api_extra' => NULL,
                    'released_at' => \Carbon\Carbon::createFromDate($release_date),
                    'thumbnail' => 'https:'.urldecode($gameItem['i18n']['en']['banner_path']),
                    'updated_at' => now(),
                ]);
        }




        }
        //dd($transformInFormat);

        //Log::notice($transformInFormat);

        return json_encode($transformInFormat);


    }


}
