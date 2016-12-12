(function($, videojs, PushStream){

var Streaming = function(settings){
	this.settings = Object.assign({
		socket: {
			host: window.location.hostname,
			port: window.location.port,
			useSSL: window.location.protocol === 'https:',
			modes: 'websocket|eventsource|stream'
		},
		videojs: {
			controls: true,
			autoplay: true,
			preload: true,
			techOrder: ['html5', 'youtube', 'flash'],
			controlBar: {
				playToggle: config.master,
			},
			forceSSL: window.location.protocol === 'https:',
		},
		chat: {
			updateOnlineInterval: 3000,
			onlineTimeout: 6000,
			maxNicoSpam: 5,
			nicoSpamInterval: 500,
			nicoRowSize: 30,
			nicoMinFontSize: 20,
			nicoMaxFontSize: 26,
			nicoPadding: 10,
			nicoAnimDuration: 4000,
			nicoAnimWidthScale: 2,
			ozDuration: 2000,
			ozWidthScale: 2,
		},
		syncInterval: 3000,
		maxLag: 3000,
		maxLagPaused: 100,
		pingTimerInterval: 500,
		seekImmuneTime: 3000,
	}, settings || {});

	this.source = {};
	this.announce = '';
	this.bindEvents();
	this.lastPacket = 0;
	this.lastSeek = 0;

	this.video = videojs('#player', this.settings.videojs).ready(() => {
		this.chat = new Chat(this.video, this.settings.chat);
		this.connect();
		if(config.master){
			this.masterInit();
		}else{
			this.startPingTimer();
		}
	});
};

Streaming.prototype.startPingTimer = function(){
	this.pingTimer = setInterval(() => {
		var ping = Math.max(
			this.ping,
			(new Date().getTime() - this.lastPacket) - this.settings.syncInterval
		);
		$('#lp').text(ping);
	}, this.settings.pingTimerInterval);
};

Streaming.prototype.connect = function(){
	this.socket = new PushStream(this.settings.socket);
	this.socket.addChannel('animestream_master', {backtrack: 1});
	this.socket.addChannel('animestream_chat');
	this.socket.addChannel('animestream_online');

	this.socket.onmessage = this.onMessage.bind(this);
	this.socket.onopen = this.onOpen.bind(this);

	this.socket.connect();
};

Streaming.prototype.onMessage = function(text, id, channel){
	channel = channel.replace(/^animestream_/, '');
	switch(channel){
		case 'master':
			this.onMasterPacket(text);
			break;
		case 'online':
			this.chat.onOnlinePacket(text);
			break;
		case 'chat':
			this.chat.onChatPacket(text);
			break;
		default:
			console.error('Unknown packet type', channel);
	}
};

Streaming.prototype.onOpen = function(text, id, channel){
	this.chat.onOpen(text, id, channel);
};

Streaming.prototype.onMasterPacket = function(data){
	this.ping = Math.max(
		0,
		(new Date().getTime() - this.lastPacket) - this.settings.syncInterval
	);
	$('#lp').text(this.ping);
	this.lastPacket = new Date().getTime();

	if(data.announce){
		$('#announce').text(data.announce).show();
	}else{
		$('#announce').text(data.announce).hide();
	}

	if(this._masterNew || data.source.type != this.source.type || data.source.src != this.source.src){
		console.log('changing source', data.source);
		this.video.src(data.source);
		this.source = data.source;
		this._masterNew = false;
	}

	if(!config.master){
		if(data.pause){
			this.video.pause();
		}else{
			this.video.play();
		}
		var buffered = this.video.bufferedPercent();
		var lag = (data.time - this.video.currentTime()) * 1000;
		if(
			(new Date().getTime() - this.lastSeek > this.settings.seekImmuneTime) && 
			(
				this._forceSeek ||
				(!data.pause && Math.abs(lag) > this.settings.maxLag) ||
				(data.pause && Math.abs(lag) > this.settings.maxLagPaused)
			)
		){
			console.log('too lag, seeking', lag);
			this.lastSeek = new Date().getTime();
			this.video.currentTime(data.time);
			this._forceSeek = false;
		}
		$('#lag').text(Math.ceil(lag));
	}
};

Streaming.prototype.bindEvents = function(){
	$('#lagmeter').click(() => {
		this._forceSeek = true;
	});
};

Streaming.prototype.masterInit = function(){
	if(config.master){
		$('body').addClass('master');
	}
	this.masterBind();
};

Streaming.prototype.masterSync = function(force){
	$.ajax({
		type: 'POST',
		url: config.chat_master,
		data: JSON.stringify({
			source: this.source,
			time: this.video.currentTime(),
			force: force,
			announce: this.announce,
			pause: this.video.paused(),
		}),
		contentType: 'application/json'
	});
	clearTimeout(this.masterSyncTimer);
	this.masterSyncTimer = setTimeout(this.masterSync.bind(this), this.settings.syncInterval);
};

Streaming.prototype.masterBind = function(){
	$('button[data-act=file]').click(() => {
		var file = prompt('File URL?', 'data/');
		if(!file) return false;
		this.source = {
			type: 'video/mp4',
			src: file
		};
		this._masterNew = true;
		this.masterSync(true);
		return false;
	});
	$('button[data-act=youtube]').click(() => {
		var file = prompt("YouTube URL?", "");
		if(!file) return false;
		this.source = {
			type: 'video/youtube',
			src: file
		};
		this._masterNew = true;
		this.masterSync(true);
		return false;
	});
	$('button[data-act=announce]').click(() => {
		var announce = prompt("Announce?", this.announce);
		if(announce !== null){
			this.announce = announce;
		}
		this._masterNew = true;
		this.masterSync();
		return false;
	});
};

var Chat = function(video, settings){
	this.settings = settings;
	this.video = video;
	this.user = {
		id: config.id,
		name: config.name,
		avatar: config.avatar,
		master: config.master,
		buffer: 0
	};
	this.nico = true;
	this.oz = false;
	this.nicoSlot = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
	this.bindEvents();
};

Chat.prototype.onOpen = function(text, id, channel){
	this.updateOnlineUsers();
};

Chat.prototype.updateOnlineUsers = function(){
	this.user.buffer = this.video.bufferedPercent();

	$.ajax({
		type: 'POST',
		url: config.chat_online,
		data: JSON.stringify({'user': this.user}),
		contentType: 'application/json'
	});

	this.removeInactiveUsers();
	setTimeout(this.updateOnlineUsers.bind(this), this.settings.updateOnlineInterval);
};

Chat.prototype.removeInactiveUsers = function(){
	var minOnlineTime = new Date().getTime() - this.settings.onlineTimeout;
	$('#onlineuser li').each(function(){
		if($(this).data('update') < minOnlineTime){
			$(this).remove();
		}
	});
};

Chat.prototype.onChatPacket = function(data){
	var ele = $('<li><a class="user" target="_blank"></a><div class="body"></div></li>');
	ele.find('.user')
		.text(` ${data.user.name}:`);
	$('<img />')
		.attr('src', data.user.avatar)
		.prependTo(ele.find('.user'));
	ele.find('.body')
		.text(data.message.replace(/\*([0-9]+)$/, '')+' ');
	$('<span class="meta" />')
		.text(new Date(data.time * 1000).toLocaleTimeString())
		.appendTo(ele.find('.body'));

	ele.insertAfter('#announce');

	// nicochat
	if(this.nico){
		var count = this.getNicoCount(data.message);
		var message = data.message.replace(/\*([0-9]+)$/, '');
		this.spamNico(message, count);
	}

	// ozview
	if(this.oz){
		this.sendOz(message);
	}
};

Chat.prototype.onOnlinePacket = function(data){
	data.user.id = parseInt(data.user.id, 10);

	var ele = $(`#onlineuser .user_${data.user.id}`);
	if(ele.length === 0){
		ele = $('<li><a class="user" target="_blank"><span></span></a></li>');
		$('<img>')
			.attr("src", data.user.avatar)
			.prependTo(ele.find('.user'));
		ele
			.addClass(`user_${data.user.id}`)
			.data('update', new Date().getTime());
		ele.find('.user span')
			.text(` ${data.user.name}`)
			.attr('title', data.user.name);

		ele.appendTo("#onlineuser");
	}
	
	ele.data('update', new Date().getTime());

	if(config.master){
		var buffered = Math.floor(parseFloat(data.user.buffer, 10) * 100);
		var lag = ` (${buffered}%)`;
		ele.find('.user span')
			.text(` ${data.user.name}${lag}`)
			.attr('title', data.user.name + lag);
	}
};

Chat.prototype.getNicoCount = function(message){
	var count = message.match(/\*([0-9]+)$/);
	if(!count){
		return 1;
	}
	return Math.max(1, Math.min(parseInt(count[1], 10), this.settings.maxNicoSpam));
};

Chat.prototype.getNicoSlot = function(){
	var time = new Date().getTime();
	return this.nicoSlot
		.map((item, index) => [index, Math.max(0, item - time)])
		.reduce((a, b) => {
			// find item which have lowest index
			// with lowest time left
			if(a[1] == b[1]){
				return a[0] < b[0] ? a : b;
			}
			return a[1] < b[1] ? a : b;
		})[0];
}

Chat.prototype.sendNico = function(message){
	var slot = this.getNicoSlot();
	var ele = $('<span class="nico" />').text(message);
	ele.css('top', slot * this.settings.nicoRowSize)
		.css('font-size', 
			Math.floor(
				Math.random() * (
					this.settings.nicoMaxFontSize - this.settings.nicoMinFontSize
				)
			) + this.settings.nicoMinFontSize
		);
	ele.appendTo('#nicochat');

	var width = ele.outerWidth() + this.settings.nicoPadding;
	var animDuration = this.settings.nicoAnimDuration + (width * this.settings.nicoAnimWidthScale);
	this.nicoSlot[slot] = new Date().getTime() + animDuration;
	ele.animate({left: width*-1}, animDuration);
};

Chat.prototype.spamNico = function(message, count){
	this.sendNico(message);
	count--;
	if(count > 0){
		setTimeout(() => {
			this.spamNico(message, count);
		}, Math.random() * this.settings.nicoSpamInterval);
	}
};

Chat.prototype.sendOz = function(message){
	var oz = $('<div class="ozbox" />')
		.text(message)
		.appendTo('#oz');
	oz.css({
		top: Math.floor(Math.random() * ($(window).height() - oz.outerHeight())),
		left: Math.floor(Math.random() * ($(window).width() - oz.outerWidth())),
		transform: 'rotateY(0deg)',
		webkitTransform: 'rotateY(0deg)',
	});
	setTimeout(function(){
		oz.css({
			transform: 'rotateY(90deg)',
			webkitTransform: 'rotateY(90deg)'
		});
	}, this.settings.ozDuration + (oz.width() * this.settings.ozWidthScale));
};

Chat.prototype.bindEvents = function(){
	$('#nicotoggle').click(() => {
		this.nico = !this.nico;
		if(this.nico){
			$('#nicotoggle').css('color', '#00ccff');
		}else{
			$('.nico').remove();
			$('#nicotoggle').css('color', 'red');
		}
	});
	$("#oztoggle").click(() => {
		this.oz = !this.oz;
		if(this.oz){
			$('#oztoggle').css('color', '#00ccff');
		}else{
			$('.ozbox').remove();
			$('#oztoggle').css('color', 'red');
		}
	});
	$('input[name=chat]').keyup(function(e){
		if(e.which == 13){
			this.disabled = true;
			$.post('chat.php', {'text': this.value}, () => {
				this.disabled = false;
				this.value = '';
				this.focus();
			});
			return false;
		}
	});
};

new Streaming();

})(jQuery, videojs, PushStream);