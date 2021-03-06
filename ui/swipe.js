/*
---
description: Left and right swipe events for touch devices.

license: MIT-style.

authors:
- Caleb Troughton

requires:
  core/1.2.4:
  - Element.Event
  - Class
  - Class.Extras

provides:
- MooSwipe

...
*/
var MooSwipe = MooSwipe || new Class({
	Implements: [Options, Events],

	options: {
		//onSwipeLeft: $empty,
		//onSwipeRight: $empty,
		//onSwipe: $empty,
		tolerance: 20,
		preventDefaults: true
	},

	element: null,
	startX: null,
	isMoving: false,

	initialize: function(el, options) {
		this.setOptions(options);
		this.element = $(el);
		this.element.addListener('touchstart', this.onTouchStart.bind(this));
	},

	cancelTouch: function() {
		this.element.removeListener('touchmove', this.onTouchMove);
		this.startX = this.startY = null;
		this.isMoving = false;
	},

	onTouchMove: function(e) {

      this.options.preventDefaults && e.preventDefault();

      if (this.isMoving) {
        var x = e.touches[0].pageX;
        var y = e.touches[0].pageY;
        var dx = this.startX - x;
        var dy = this.startY - y;
        if (Math.abs(dx) >= this.options.tolerance) {
          this.cancelTouch();
          this.fireEvent(dx > 0 ? 'swipeLeft' : 'swipeRight');
        }

        if (Math.abs(dy) >= this.options.tolerance) {
          this.cancelTouch();
          this.fireEvent(dy > 0 ? 'swipeTop' : 'swipeBottom');
        }
      }
	},

	onTouchStart: function(e) {
		if (e.touches.length == 1) {
			this.startX = e.touches[0].pageX;
			this.startY = e.touches[0].pageY;
			this.isMoving = true;
			this.element.addListener('touchmove', this.onTouchMove.bind(this));
		}
	}
});