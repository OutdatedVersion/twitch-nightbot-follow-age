<?php
// OutdatedVersion | 8/10/16 | Don't judge me! I don't PHP often.
require_once 'vendor/autoload.php';

use Carbon\Carbon;

$BASE = "/twitch/api/";
$DEV = true;

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
    return json_encode(array('service' => 'Twitch API Proxy',
                             'version' => 0,
                             'last_update' => '8-10-16',
                             'author' => 'OutdatedVersion',
                             'last_cried' => 'now'));
});


$klein->respond('/twitch/api/[:streamer]/following_since/[:user]/[:human_readable]?', function ($request)
{
    $twitchResponse = Unirest\Request::get('https://api.twitch.tv/kraken/users/' . $request->user .  '/follows/channels/' . $request->streamer);

    if ($twitchResponse->code == 404)
    {
        // The Twitch API returns a 404 if a
        // user is not following the target

        if ($request->human_readable)
            return $request->user . ' is not following ' . $request->streamer;

        return json_encode(array('state' => 'not_following',
                                 'streamer' => $request->streamer,
                                 'user' => $request->user,
                                 'request_length' => microtime() - $_['REQUEST_TIME']));
    }

    $date;

    foreach (json_decode($twitchResponse->raw_body) as $var)
            $date = $var->created_at;

    $humanReadableDate = Carbon::parse($date)->toDayDateTimeString();

    if ($request->human_readable)
        return $humanReadableDate;

    return json_encode(array('state' => 'following',
                             'following_since' => $date,
                             'following_since_human' => $humanReadableDate,
                             'streamer' => $request->streamer,
                             'user' => $request->user,
                             'request_length' => microtime() - $_['REQUEST_TIME']));
});


$klein->dispatch();
