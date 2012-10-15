# awkwin's Streamer

This is streamer used to stream anime on [madoka.whs.in.th](http://madoka.whs.in.th/streaming/). It supports any video URL (even that hosted on other origin server) and Flash Live Streaming server (requires additional hackery)

This is a product of quick 'n dirty hack in two days. Don't expect much from it.

## Dependencies

1. A PHP capable server (no database is required)
2. PHP `curl` module
3. PHP must allow `file_get_contents` to read from remote URL. (Actually not required, but need some more hackery)
4. menome-compatible socket.io server. [Sora push server](https://github.com/whs/Sora/blob/master/pushserver.js) should be working, but haven't been tested. The Sora push server requires node.js, express and socket.io.

## Installation

1. Define your authenticator at `auth.php`, or use the provided [menome](http://menome.in.th) sign in. The following steps is to setup the menome sign in.
2. Register for an API key at [menome Security Center](http://menome.in.th/user/@security#api). Enter `http://yoururl/path/to/streaming/auth.php` as the redirect URL. Use "Confidential client".
3. Edit config.php. Specify your given `API_KEY` and `API_SECRET`
4. Specify your `CHAT_SERVER` and `CHAT_SECRET` (chat server must be URL accessible from the internet and the web server, chat secret must be the same as the push server)
5. Test it out. Make sure the push server is running and accessible from the internet

## Hosting stream

1. Go to `http://yoururl/path/to/streaming/?master=1`. If required, log in with menome and retry the URL.
2. You'll see 3 buttons on top right, Open Video, Open Stream and Set Announce.

- Open Video is to open video from arbitary URL. The video must be supported by client browsers' HTML5 video. MP4 usually works fine.
- Open Stream is to use the code in the script tag. The default is to receive stream from http://stream.whs.in.th/live/anime. This must be changed in code, not in run-time.
- Set Announce is to set chat's pinned message.

## Notes

- No master authentication is performed. Don't attempt to use multiple masters
- For the file player, if the master lags everyone lags. If a client lags, it skip.
- If you pause the file player, the client will loop.
- If you seek the file player, the client seeks.
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
- Sora push server (and its spinoff the menome stream server) is developed under National Software Competition 2012 program under funding from NECTEC. NECTEC does not supports the usage of Sora or streaming.