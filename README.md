# awkwin's Streamer 2.0

This is streamer used to stream anime on [madoka.whs.in.th](http://madoka.whs.in.th/streaming/). It supports any video URL (even that hosted on other origin server) and Flash Live Streaming server (requires additional hackery)

This is a product of quick 'n dirty hack in two days plus few other patches. Don't expect much from it.

## Dependencies

1. A PHP capable server (no database is required)
2. PHP `curl` module
3. [nginx-push-stream-module](https://github.com/wandenberg/nginx-push-stream-module)

## Installation

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
2. Edit config.php. Specify your given `FB_ID` and `FB_SECRET`. Optionally set `FB_REQUIRE_EVENT` to Facebook event id only allow users who have RSVP to that 
event to login.
3. Test it out. Make sure the push server is running and accessible from the internet

## Hosting stream

1. Go to `http://yoururl/path/to/streaming/?master=1`. If required, log in with menome and retry the URL.
2. You'll see 4 buttons on top right, Open file, Open YouTube, Open stream and Set Announce.

- Open file is to open video from arbitary URL. The video must be supported by client browsers' HTML5 video. MP4 usually works fine.
- Open YouTube is to open YouTube video. This plugin is quite buggy at this time.
- Open stream is to use the code in the script tag. The default is to receive stream from http://stream.whs.in.th/live/anime. This must be changed in code, not in run-time.
- Set Announce is to set chat's pinned message.

## Notes

- No master authentication is performed. Do not attempt to use multiple masters.
- If the master lags everyone loop. If a client lags, it skip.
- If you pause the file player, clients will loop.
- If you seek the file player, clients seek.
- Client can click at the lag meter below the chat bar to seek to the current keyframe time. (Needs to wait until next keyframe arrived)

## Behind the scene

Streamer use a very similar technique from [Noke](https://github.com/whs/Noke) to keep client updated.

The master sends a "keyframe" every 3 seconds. The keyframe tells that what second in the video is now playing and also the file name and other state.

A client after connecting to the push server will wait for a keyframe which is if missing for 3 seconds will throw an error that master is missing. After receiving a keyframe, it loads the media and seeks to the specified time.

When the keyframe time and current video time of a client is over 3 seconds apart, the client corrects this by skipping to the keyframe time. (This also creates the loop effect as noted in the notes section, and also use to implement seeking)

In clients, there're "lag" and "ping" meters. Lag show how much that client's video time is apart from the server. Positive indicates lag behind. Ping show how long does keyframe stopped appearing at expected time. The ping meter starts counting 3 seconds after last keyframe arrived.

A keyframe may contains a "force" flag. Forced keyframes appear when server have manually changed media. Clients encoutering force flag will try to reload the media.

## Acknowledgements

- Flash video player from [Flash Media Playback](http://www.adobe.com/products/flashmediaplayback) (yeah, I should've used Strobe)
- Chatbox suggestion from @WolfKungz
- Nicochat inspiration from [Nico Nico Douga](http://nicovideo.jp)
- Additional Nicochat testing from @zennnzonbolt @nonene_desu @WolfKungz
- OZView inspiration from [Summer Wars](http://menome.in.th/anime/summerwars)
- Code portions from [project menome](http://menome.in.th) and twitteroauth.php
- The keyframe approach is inspired from [Synchtube](http://synchtube.com).
- Anime streaming is inspired from [/r/clannaddiscussion](http://www.reddit.com/r/clannaddiscussion).
