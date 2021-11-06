//
/* デバッグ用関数
function dump_size(method,obj) {
	alertDump({ method:method, w:obj.outerWidth(),h:obj.outerHeight() });
};
function ParentScroll(obj) {
	if (obj.prop('tagName') === 'BODY') return { x: 0, y: 0 };
	var pscroll = ParentScroll(obj.parent());
	var scroll = {
		x: obj.scrollLeft() + pscroll.x,
		y: obj.scrollTop() + pscroll.y,
	};
	return scroll;
};
function targetBox(obj) {
	var top = obj.offset().top;
	var left = obj.offset().left;
	return {
		left: left,
		top: top,
		right: left + obj.width(),
		bottom: top + obj.height(),
	};
};
*/
// バルーンヘルプの表示
// ターゲット位置を元に自身のポジションを決定する
function balloonPosition(target, onside, margin) {
	if (target.prop('tagName') === undefined) return;
	// ターゲットの中心位置
	this.pointX = parseInt(target.offset().left) + parseInt(target.outerWidth(true)/2);
	this.pointY = parseInt(target.offset().top) + parseInt(target.outerHeight(true) / 2);
	this.Box = { left: 0, top: 0, right: 0, bottom: 0 };
	// this.Onside = onside;
	// this.Margin = margin;
	var bBox = {
		width: 0, height: 0,
		left: 0, top: 0, right: 0, bottom: 0 ,
		LeftPos: function (x) {
			this.left = x;
			this.right = x + this.width;
		},
		TopPos: function (y) {
			this.top = y;
			this.bottom = y + this.height;
		},
		TopLeft: function (x, y) {
			this.LeftPos(x);
			this.TopPos(y);
			return true;
		},
		setBound: function (x, y, w, h) {
			this.width = w;
			this.height = h;
			this.TopPos(y);
			this.LeftPos(x);
		},
	};
	// スクロール量を考慮
	this.scrollPos = function () {
		var x = this.pointX - $(window).scrollLeft();
		var y = this.pointY - $(window).scrollTop();
		return { x: x, y: y };
	};
	this.calcPosition = function (obj) {
		var w = parseInt(obj.outerWidth());
		var hw = parseInt(w / 2);
		var h = parseInt(obj.outerHeight());
		var hh = parseInt(h / 2);
		var Pos = this.scrollPos();
		var parentBox = {
			left: 0,top: 0,
			right: $(window).width(),
			bottom: $(window).height(),
		};
		// default top-center default
		if (onside) {
			var hz = "left"; var vt = "";
			bBox.setBound(Pos.x, Pos.y - hh, w, h);
			if (bBox.right > parentBox.right || bBox.top < parentBox.top) {
				vt = "top-"; hz = "center";
				bBox.TopLeft(Pos.x - hw, Pos.y);
			};
			if (bBox.bottom > parentBox.bottom) {
				vt = "bottom-"; hz = "center";
				bBox.TopLeft(Pos.x - hw, Pos.y - h);
			};
			if (bBox.right > parentBox.right || bBox.top < parentBox.top) {
				vt = ""; hz = "right";
				bBox.TopLeft(Pos.x - w, Pos.y - hh);
			};
			if (bBox.left < parentBox.left) {
				vt = "top-"; hz = "left";
				bBox.TopLeft(Pos.x, Pos.y);
				if (bBox.bottom > parentBox.bottom) {
					vt = "bottom-";
					bBox.TopPos(Pos.y - h);
				};
			} else {
				if (bBox.bottom > parentBox.bottom) {
					vt = "bottom-";
					bBox.TopPos(Pos.y - h);
				};
				if (bBox.top < parentBox.top) {
					vt = "top-";
					bBox.TopPos(Pos.y);
				};
			};
		} else {
			var vt = "top-"; var hz = "center";
			bBox.setBound(Pos.x - hw, Pos.y, w, h);
			if (bBox.bottom > parentBox.bottom) {
				vt = "bottom-";
				bBox.TopPos(Pos.y - h);
			};
			if (bBox.right > parentBox.right) {
				hz = "right";
				bBox.LeftPos(Pos.x - w);
			};
			if (bBox.left < parentBox.left) {
				hz = "left";
				bBox.LeftPos(Pos.x);
			};
			if (bBox.top < parentBox.top) {
				vt = "";
				bBox.TopPos(Pos.y);
			};
		};
		this.Box = {
			left: bBox.left - margin,
			top: bBox.top - margin,
			right: bBox.right + margin,
			bottom: bBox.bottom + margin
		};
		this.balloon = 'balloon-' + vt + hz;
		obj.addClass(this.balloon);
		// マージン分移動する
		bBox.left = bBox.left - parseInt(obj.css('margin-right'));
		bBox.top = bBox.top - parseInt(obj.css('margin-bottom'));
		obj.css({'left': bBox.left + 'px','top': bBox.top + 'px'});
	};
	this.inBalloon = function (x, y) {
		return (x >= this.Box.left) && (x <= this.Box.right)
				&& (y >= this.Box.top) && (y <= this.Box.bottom);
	};
};
//==============================================================================================
// ポップアップバルーンセットアップ
$.fn.PopupBaloonSetup = function () {
// 旧バルーンヘルプ
// .popup-balloon.onside{@!item-id} => [
//		Balloon Message
// ]
	this.find(".popup-balloon").each(function () {
		var self = $(this); // jQueryオブジェクトを変数に代入しておく
		var onside = self.attr('class').existsWord('onside');
		var ref = self.attr("data-element");  // 紐付けるID
		if (ref === undefined) return true;	// continue
		var ev = 'click';
		if (ref.charAt(0) == '@') { 
			ref = ref.slice(1);
			ev = 'mouseover';
		};
		if (ref.charAt(0) == '!') {		// ヘルプを付けない
			ref = ref.slice(1);
			var icon = ref;
		} else {
			icon = ref + "-help";
			$('#' + ref).after('<span class="help_icon" id="' + icon + '"></span>')
							.css("margin-right", '2px');
		};
		var icon_obj = $('#' + icon);
		if (ev == "click") icon_obj.css("cursor", "help");
		icon_obj.off(ev).on(ev, function () {
			// 他要素の mouseover防止とバルーンを消すための領域設定
			var bk_panel = $('<div class="balloon-BK"></div>').appendTo('body');
			bk_panel.fadeIn('fast');
			var Balloon = new balloonPosition(icon_obj, onside, 5);
			icon_obj.addClass('active');
			self.fadeIn('fast');
			Balloon.calcPosition(self);
			// リサイズは処理完了後に位置移動する
			var resizeTimer = null;
			$(window).on('resize.balloon', function () {
				clearTimeout(resizeTimer);
				resizeTimer = setTimeout(function() {
					// リサイズ完了後の処理
					Balloon.calcPosition(self);
				}, 200);
			});
			// スクロールはリアルタイムで位置移動
			$(window).on('scroll.balloon', function () {
				Balloon.calcPosition(self);
			});
			bk_panel.off().on('mousemove',function (e) {
				e.stopPropagation();
				e.preventDefault();
				if (!Balloon.inBalloon(e.clientX, e.clientY)) {
					self.css('display','');	// fadeInで設定されたものを削除
					icon_obj.removeClass('active');
					self.removeClass(Balloon.balloon);
					$(window).off('scroll.balloon resize.balloon');
					bk_panel.remove();
				};
			});
		});
	});
// 新バルーンヘルプ: マルチ・バルーン
// .multi-balloon => [
//		.onside{center-item} => [	 Balloon Message	]
//		.{right-item} => [	//  #right-item には 'sw1','sw2' を data-value に定義する
//			#sw1 => [ 	Balloon Message	]
//			#sw2 => [ 	Balloon Message	]
//		]
// ]
	this.find('.multi-balloon').each(function () {
		$(this).children().each(function () {
			var self = $(this); // jQueryオブジェクトを変数に代入しておく
			var cls = self.attr('class');
			var onside = (cls == undefined) ? false : cls.existsWord('onside');
			var ref = self.attr("data-element");  // 紐付けるID
			if (ref === undefined) return true;	// continue
			var ev = 'click';
			if (ref.charAt(0) == '@') { 
				ref = ref.slice(1);
				ev = 'mouseover';
			};
			if (ref.charAt(0) == '!') {		// ヘルプを付けない
				ref = ref.slice(1);
				var icon = ref;
			} else {
				icon = ref + "-help";
				$('#' + ref).after('<span class="help_icon" id="' + icon + '"></span>')
								.css("margin-right", '2px');
			};
			var ref_obj = $('#' + ref);
			var icon_obj = $('#' + icon);
			if (ev == "click") icon_obj.css("cursor", "help");
			icon_obj.off(ev).on(ev, function () {
				// 他要素の mouseover防止とバルーンを消すための領域設定
				var bk_panel = $('<div class="balloon-BK"></div>').appendTo('body');
				bk_panel.fadeIn('fast');
				var Balloon = new balloonPosition(icon_obj,onside,5);
				var disp_class = ref_obj.attr('data-value');		// 表示するタグID
				// 選択タグがあればそれをバルーンにする、なければ自身がバルーン
				ballon_obj = (typeof disp_class === 'string') ? self.find('#' + disp_class) : self;
				ballon_obj.addClass('popup-balloon');		// popup-balloon のスタイルを適用する
				ballon_obj.fadeIn('fast');		// dusplay:block でないとサイズが取得できない
				icon_obj.addClass('active');
				Balloon.calcPosition(ballon_obj);
				// リサイズは処理完了後に位置移動する
				var resizeTimer = null;
				$(window).on('resize.mballoon', function () {
					clearTimeout(resizeTimer);
					resizeTimer = setTimeout(function() {
						// リサイズ完了後の処理
						Balloon.calcPosition(ballon_obj);
					}, 200);
				});
				// スクロールはリアルタイムで位置移動
				$(window).on('scroll.mballoon', function () {
					Balloon.calcPosition(ballon_obj);
				});
				bk_panel.off().on('mousemove',function (e) {
					e.stopPropagation();
					e.preventDefault();
					if (!Balloon.inBalloon(e.clientX, e.clientY)) {
						// popup-balloon と吹き出し用のクラスを削除
						ballon_obj.removeClass('popup-balloon ' + Balloon.balloon);
						ballon_obj.css('display','');	// fadeInで設定されたものを削除
						icon_obj.removeClass('active');
						$(window).off('scroll.mballoon resize.mballoon');
						bk_panel.remove();
					};
				});
			});
		});
	});
	return this;
};
