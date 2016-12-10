# awkwin's Streamer 3.0

This is streamer used to stream anime on [madoka.whs.in.th](http://madoka.whs.in.th/streaming/). It use [videojs](https://videojs.com) as the backend.

## Dependencies

1. A PHP capable server (no database is required)
2. PHP `curl` module
3. [nginx-push-stream-module](https://github.com/wandenberg/nginx-push-stream-module)

## Installation

### Docker setup

Streamer now comes with Dockerfile. To install with Docker,

```
docker run -p 80:80 -e FB_ID=<FBID> -e FB_SECRET=<FBSECRET> willwill/streaming
```

Replace `<FBID>` and `<FBSECRET>` by Facebook App ID and secret respectively (read the app setup section on how to register). You can add `-v "<datafolder>:/var/www/html/data/:ro"` to mount a data folder (eg. video files) to serve at /data.

Note that this will assume that you're running behind reverse proxy. You can use without one, but it is a security risk. Make sure your reverse proxy can forward websocket.

### nginx setup

In the same vhost that host the PHP pages, set

~~~~~~~
location /privpub/master {
	push_stream_publisher;
	push_stream_channels_path             animestream_master;
	push_stream_store_messages on;
}
location /privpub/chat {
	push_stream_publisher;
	push_stream_channels_path             animestream_chat;
}
location /pub/online {
	push_stream_publisher;
	push_stream_channels_path             animestream_online;
}
location ~ /sub/(.*) {
	push_stream_subscriber;
	push_stream_channels_path              $1;
	push_stream_message_template                "{\"id\":~id~,\"channel\":\"~channel~\",\"text\":~text~}";
	push_stream_ping_message_interval           10s;
}
location ~ /ev/(.*) {
	push_stream_subscriber eventsource;
	push_stream_channels_path              $1;
	push_stream_message_template                "{\"id\":~id~,\"channel\":\"~channel~\",\"text\":~text~}";
	push_stream_ping_message_interval           10s;
}
location ~ /lp/(.*) {
	push_stream_subscriber long-polling;
	push_stream_channels_path              $1;
	push_stream_message_template                "{\"id\":~id~,\"channel\":\"~channel~\",\"text\":~text~}";
	push_stream_longpolling_connection_ttl	30s;
}
location ~ /ws/(.*) {
	push_stream_subscriber websocket;
	push_stream_channels_path              $1;
	push_stream_message_template                "{\"id\":~id~,\"channel\":\"~channel~\",\"text\":~text~}";
	push_stream_ping_message_interval           10s;
}
~~~~~~~

### App setup

1. Register a [Facebook app](http://developers.facebook.com). Set app domain to link of your site.
2. Edit config.php. Specify your given `FB_ID` and `FB_SECRET`.
3. Test it out. Make sure the push server is running and accessible from the internet

## Hosting stream

1. Go to `http://yoururl/path/to/streaming/?master=1`. If required, log in and retry the URL.
2. You'll see 4 buttons on top right, Open file, Open YouTube and Set Announce.

- Open file is to open video from arbitary URL. The video must be supported by client browsers' HTML5 video. MP4 usually works fine.
- Open YouTube is to open YouTube video. **Known issue:** you cannot switch from YouTube back to file. ([videojs-youtube#347](https://github.com/eXon/videojs-youtube/issues/347))
- Set Announce is to set chat's pinned message.

## Notes

- No master authentication is performed. Do not attempt to use multiple masters.
- If the master lags everyone will loop. If a client lags, it will skip.
- If master pause the file player, clients will pause and attempt to seek to the same frame.
- If master seek the file player, clients will seek.
- Client can click at the lag meter below the chat bar to seek to the current keyframe time. (Needs to wait until next keyframe arrived)

## Behind the scene

Streamer use a very similar technique from [Noke](https://github.com/whs/Noke) to keep client updated.

The master sends a "keyframe" every 3 seconds. The keyframe tells that what second in the video is now playing and also the file name and other state.

A client after connecting to the push server will wait for a keyframe which is if missing for 3 seconds will throw an error that master is missing. After receiving a keyframe, it loads the media and seeks to the specified time.

When the keyframe time and current video time of a client is over 3 seconds apart, the client corrects this by skipping to the keyframe time. (This also creates the loop effect as noted in the notes section, and also use to implement seeking)

In clients, there're "lag" and "ping" meters. Lag show how much that client's video time is apart from the server. Positive indicates lag behind. Ping show how long does keyframe stopped appearing at expected time. The ping meter starts counting 3 seconds after last keyframe arrived.

A keyframe may contains a "force" flag. Forced keyframes appear when server have manually changed media. Clients encoutering force flag will try to reload the media.

## License

Licensed under [StealItPl 1.1](https://github.com/whs/whs.github.com/blob/master/LICENSE) or [AGPLv3](http://www.gnu.org/licenses/agpl.html). You can use this only for non commercial use with under 100 active users in a month and retain this license term in your derivatives or you can use it under the terms of AGPLv3.

## Acknowledgements

- Chatbox suggestion from @WolfKungz
- Nicochat inspiration from [Nico Nico Douga](http://nicovideo.jp)
- Additional Nicochat testing from @zennnzonbolt @nonene_desu @WolfKungz
- OZView inspiration from [Summer Wars](http://menome.in.th/anime/summerwars)
- Code portions from [project menome](http://menome.in.th) and [twitteroauth.php](https://github.com/abraham/twitteroauth)
- The keyframe approach is inspired from [Synchtube](http://synchtube.com).
- Anime streaming is inspired from [/r/clannaddiscussion](http://www.reddit.com/r/clannaddiscussion).
