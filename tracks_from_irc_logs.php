<?php
//
//   Script to create a playlist from spotity links/uri's found in my IRC logs.
//   Requires 'spotify-api-server' - https://github.com/liesen/spotify-api-server
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

//Config bits.

//Log for preventing duplicates.
$tracklog = "/home/ab5w/dev/irctracks/trackadd.log";
//Location of the IRC log(s).
$irclogdir = "/home/ab5w/irclogs/*";
//Playlist to add the tracks to.
$playlist = "spotify:user:absw:playlist:2LwUdMPblahpr0pTSotk5r";


//Our add track function.
function spotify_addtrack($trackid,$playlist) {

    //Post the trackid to the api server listening on localhost.
    $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,"localhost:1337/playlist/" . $playlist . "/add?index");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "[\"" . $trackid . "\"]");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $output = curl_exec ($ch);

    curl_close ($ch);

    return $output;

}

//Set the log for preventing duplicates.
if (!file_exists($tracklog)) { exec("touch $tracklog"); }

//Grep /all/ the logs.
exec("grep -Rno \" spotify:track.*\" $irclogdir | awk '{print $2}'", $uris);
exec("grep -Rno \" http://open.spotify.com/track/.*\" $irclogdir | awk '{print $2}' | awk -F\"/\" '{print $5}'", $http_urls);

//Turn each spotify http link into a spotify uri.
foreach ($http_urls as $http_url) {

    $uri = "spotify:track:" . $http_url;
    $httpuris[] = $uri; 

}

//Merge the two arrays for ease.
$idarray = array_merge($uris, $httpuris);

//Do the necessary.
foreach ($idarray as $spotifyid) {

    //If the ID doesn't already exist in the log.
    if (!exec("grep $spotifyid $tracklog")) {

        //Add the track to the playlist.
        $trackadd = spotify_addtrack($spotifyid,$playlist);
        //Add the trackid to the log.
        exec("echo \"$spotifyid\" >> $tracklog");
        //Echo the result.
        echo $trackadd . "\n";

    }

}