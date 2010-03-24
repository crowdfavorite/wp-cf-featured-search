;(function($) {
	$(function() {
		$.cf_result = function(input, options) {
			var $input, timeout, prevLength, cache, cacheSize;
			
			$input = $(input).attr("autocomplete", "off");
			
			timeout = false;
			prevLength = 0;
			cache = [];
			cacheSize = 0;
			
			if ($.browser.mozilla) {
				$input.keypress(process_key);
			}
			else {
				$input.keyup(process_key);
			}

			function process_key(e) {
				var _this = $(this);
				var value = _this.val();

				if (value.length != prevLength) {
					if (timeout)
						clearTimeout(timeout);
					timeout = setTimeout(search, options.delay);
					
					prevLength = value.length;
				}
			}
			
			function search() {
				var s = $.trim($input.val());
				
				$input.addClass(options.searchingClass);
				$input.removeClass(options.positiveClass);
				$input.removeClass(options.negativeClass);
				
				$.get(options.source, {
					s:s
				}, function(data) {
					$input.removeClass(options.searchingClass);
					if (data == 0) {
						$input.addClass(options.negativeClass);
					}
					else {
						$input.addClass(options.positiveClass);
					}
				});
			}
		}
		
		$.fn.cf_result = function(source, options) {
			if (!source) { return; }
			
			options = options || {};
			options.source = source;
			options.positiveClass = options.positiveClass || 'cfrs-positive';
			options.negativeClass = options.negativeClass || 'cfrs-negative';
			options.searchingClass = options.searchingClass || 'cfrs-searching';
			options.delay = options.delay || 500;
			
			this.each(function() {
				new $.cf_result(this, options);
			});
			return this;
		};
		
	});
})(jQuery);