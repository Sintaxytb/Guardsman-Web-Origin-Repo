<?php

namespace App\Console;

use Illuminate\Support\Facades\DB;

class StatisticsManager
{
    public static function shiftDataByIndex(int $value = -1, string $index) {
        clearstatcache();
        // write to statistics file new user count
        // and shift old values down by one
        // 0 = oldest data, 4 = newest data
        // (0-4)

        $appUrl = env('APP_URL');
        $file_name = "$appUrl/statistics.json?update=" . mt_rand();

        $statsContents = json_decode(file_get_contents($file_name), true);
        $statsFile = fopen(public_path() . "/statistics.json", "w");
        $highestIndex = 4;

        if ($statsContents == "")
        {
            $statsContents = array();
        }

        for ($i = 0; $i < $highestIndex; $i++)
        {
            $plusOneIndex = $i + 1;
            if (!array_key_exists($plusOneIndex, $statsContents)) continue;
            if (!array_key_exists($index, $statsContents[$plusOneIndex])) continue;
            $statsContents[$i][$index] = $statsContents[$plusOneIndex][$index];
        }

        /*if (!array_key_exists($highestIndex, $statsContents))
        {
            array_push($statsContents, array());
        }*/

        $lastArrayKey = count($statsContents) - 1;

        // check if nothing is in the array
        if (!array_key_exists($lastArrayKey, $statsContents))
        {
            array_push($statsContents, array());
            $lastArrayKey++;
        }
        // check if we have enough indexes, and only create a new one if the old value exists in the current highest array
        elseif (!array_key_exists($highestIndex, $statsContents) && array_key_exists($index, $statsContents[$lastArrayKey]))
        {
            array_push($statsContents, array());
            $lastArrayKey++;
        }

        $lastArray = $statsContents[$lastArrayKey];

        //if (!array_key_exists($index, $lastArray))
        //{
            $statsContents[count($statsContents) - 1][$index] = $value;
        //}

        fwrite($statsFile, json_encode($statsContents));
        fclose($statsFile);
    }
}
