<?php
// OutdatedVersion | 8/10/16
require_once 'vendor/autoload.php';

use Carbon\Carbon;

$BASE = "/twitch/api/";
$DEV = false;

if ($DEV)
{
    // Making errors look pretty is nice, right?
    $whoops = new \Whoops\Run;
    $whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);
    $whoops->register();
}

// Set the required Twitch API header
Unirest\Request::defaultHeader('Accept', 'application/vnd.twitchtv.v3+json');

// Fire up our request handler
$klein = new \Klein\Klein();

/* ==== Responses ==== */

$klein->respond($BASE, function ($request)
{
    return json_encode(array('service' => 'Twitch API Proxy'));
});


$klein->respond('/twitch/api/[:streamer]/follower_since/[:user]/[:human]?', function ($request)
{
    $twitchResponse = Unirest\Request::get('https://api.twitch.tv/kraken/users/' . $request->user .  '/follows/channels/' . $request->streamer);

    if ($twitchResponse->code == 404)
    {
        // The Twitch API returns a 404 if a
        // user is not following the target

        if ($request->human)
            return $request->user . ' is not following ' . $request->streamer;

        return json_encode(array('state' => 'not_following',
                                 'streamer' => $request->streamer,
                                 'user' => $request->user));
    }

    foreach (json_decode($twitchResponse->raw_body) as $var)
    {
        $humanReadableDate = Carbon::parse($var)->toDayDateTimeString();

        if ($request->human)
            return $humanReadableDate;

        return json_encode(array('state' => 'following',
                                 'following_since' => $date,
                                 'streamer' => $request->streamer,
                                 'user' => $request->user));
    }
});


$klein->dispatch();
