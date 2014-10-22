<?php
//
//   Create a spotify playlist from the recently played tracks on Gold radio.
//
//   http://blog.ab5w.com/gold-radio-spotify-playlist/
//
//   Copyright (C) 2014 Craig Parker <craig@ab5w.com>
//
//   This program is free software; you can redistribute it and/or modify
//   it under the terms of the GNU General Public License as published by
//   the Free Software Foundation; either version 3 of the License, or
//   (at your option) any later version.
//
//   This program is distributed in the hope that it will be useful,
//   but WITHOUT ANY WARRANTY; without even the implied warranty of
//   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//   GNU General Public License for more details.
//
//   You should have received a copy of the GNU General Public License
//   along with this program; If not, see <http://www.gnu.org/licenses/>.
//
//

function spotify_trackid($track) {

    //Set the base URL for searching.
    $base = "ws.spotify.com/search/1/track.json";

    //Turn the track into a '+' seperated string.
    $track = explode(" ", $track);
    $track = implode("+", $track);

    //Talk to the spotify API to return some json yumminess.
    $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $base . "?q=" . $track);

    $output = curl_exec($ch);

    //Decode the json into an array.
    $output = json_decode($output,true);

    //If there are results for the track.
    if (!$output['info']['num_results'] == '0') {

        //Grab the first result in the array (the most popular).
        $output = $output['tracks'][0];
        $trackid = $output['href'];

        //Return the trackid and a true value.
        $trackout = array("$trackid", "true");

        return $trackout;

    }

}

function spotify_addtrack($trackid) {

    //Playlist to add the tracks to.
    $playlist = "spotify:user:absw:playlist:0Ya2uK3J5YzCN5Uy23JD7f";

    $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,"localhost:1337/playlist/" . $playlist . "/add?index");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "[\"" . $trackid . "\"]");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $output = curl_exec ($ch);

    curl_close ($ch);

    return $output;

}

//Set a log file for checking if the track has already been added.
$tracklog = "/home/ab5w/dev/gold/trackadd.log";
if (!file_exists($tracklog)) {

    exec("touch $tracklog");

}

//Grab the list of last played tracks/artists from the gold website and create the arrays.
$artists = shell_exec("wget -qO- http://www.mygoldmusic.co.uk/playlist.asp | grep artist | grep \"</div>\" | awk -F \">\" '{print $2}' | awk -F \"<\" '{print $1}' | grep -v artist");
$artists = explode("\n", $artists);

$tracks = shell_exec("wget -qO- http://www.mygoldmusic.co.uk/playlist.asp | grep title | grep \"</div>\" | awk -F \">\" '{print $2}' | awk -F \"<\" '{print $1}' | grep -v title");
$tracks = explode("\n", $tracks);

//Combine the arrays and do a foreach on each track.
foreach (array_filter(array_combine($artists, $tracks)) as $artist => $track) {

    //Make the track string from the artist/track, space seperated.
    $track = $artist . ' ' . $track;

    //Get the trackid from the spotify_trackid function.
    $trackout = spotify_trackid($track);
    $trackid = $trackout[0];

    echo $track . " - " . $trackid ."\n";
    //If the track exists in spotify.
    if ($trackout[1] == "true") {
        //If the track isn't in the log, add it to the playlist.
        if (!exec("grep $trackid $tracklog")) {

            //Add the track to the playlist and to the log file.
            $trackadd = spotify_addtrack($trackid);
            file_put_contents($tracklog, $trackid, FILE_APPEND);
            echo $trackadd . "\n";

        }

    }

}