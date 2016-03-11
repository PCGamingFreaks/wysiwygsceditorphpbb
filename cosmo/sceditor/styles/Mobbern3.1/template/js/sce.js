var sizes = ['25', '50', '75', '100', '150', '175', '200'],
	textarea;
$.sceditor.plugins.bbcode.bbcode.set('size', {
	format: function ($elem, content) {
		var fontSize,
			sizesIdx = 0,
			size = $elem.data('scefontsize');

		if (!size) {
			fontSize = $elem.css('fontSize');

			// Most browsers return px value but IE returns 1-7
			if (fontSize.indexOf('px') > -1) {
				// convert size to an int
				fontSize = ~~(fontSize.replace('px', ''));

				if (fontSize > 31) {
					sizesIdx = 6;
				}
				else if (fontSize > 23) {
					sizesIdx = 5;
				}
				else if (fontSize > 17) {
					sizesIdx = 4;
				}
				else if (fontSize > 15) {
					sizesIdx = 3;
				}
				else if (fontSize > 12) {
					sizesIdx = 2;
				}
				else if (fontSize > 9) {
					sizesIdx = 1;
				}
			}
			else {
				sizesIdx = ~~fontSize;
			}

			if (sizesIdx > 6) {
				sizesIdx = 6;
			}
			else if (sizesIdx < 0) {
				sizesIdx = 0;
			}

			size = sizes[sizesIdx];
		}

		return '[size=' + size + ']' + content + '[/size]';
	},
	html: function (token, attrs, content) {
		return '<span data-scefontsize="' + attrs.defaultattr + '" style="font-size:' + attrs.defaultattr + '%">' + content + '</span>';
	}
});

$.sceditor.command.set('size', {
	_dropDown: function (editor, caller, callback) {
		var content = $('<div />'),
			clickFunc = function (e) {
				callback($(this).data('size'));
				editor.closeDropDown(true);
				e.preventDefault();
			},
			size;

		for (var i = 1; i < 7; i++) {
			// Only consider maxsize when set greater 0
			if (sceController.isMaxFontsizeSet && sizes[i - 1] > sceController.getMaxFontsize) {
				break;
			}
			content.append($('<a class="sceditor-fontsize-option" data-size="' + i + '" href="#"><font size="' + i + '">' + i + '</font></a>').click(clickFunc));
		}

		editor.createDropDown(caller, 'fontsize-picker', content);
	},
	txtExec: function (caller) {
		var editor = this;

		$.sceditor.command.get('size')._dropDown(
			editor,
			caller,
			function (sizesIdx) {
				sizesIdx = ~~sizesIdx;
				if (sizesIdx > 6) {
					sizesIdx = 6;
				}
				else if (sizesIdx < 0) {
					sizesIdx = 0;
				}

				editor.insertText('[size=' + sizes[sizesIdx] + ']', '[/size]');
			}
		);
	}
});

$.sceditor.plugins.bbcode.bbcode.set('quote', {
	format: function (element, content) {
		var author = '',
			$element = $(element),
			$cite = $element.children('cite').first();

		if (1 === $cite.length || $element.data('author')) {
			author = $element.data('author') || $cite.text().replace(/(^\s+|\s+$)/g, '').replace(/:$/, '');

			$element.data('author', author);
			$cite.remove();

			content = this.elementToBbcode($element);
			author = '=' + author;

			$element.prepend($cite);
		}

		return '[quote' + author + ']' + content + '[/quote]';
	},
	html: function (token, attrs, content) {
		var addition = '';

		if ("undefined" !== typeof attrs.defaultattr) {
			content = '<cite>' + attrs.defaultattr + ':</cite>' + content;
			addition = ' data-author="' + attrs.defaultattr + '"';
		}
		else {
			addition = ' class="uncited"'
		}

		return '<blockquote' + addition + '>' + content + '</blockquote>';
	},
	quoteType: function (val, name) {
		return '"' + val.replace('"', '\\"') + '"';
	},
	breakStart: false,
	breakEnd: false
});

// This is needed for the smilies popup
function setSmilie(tag) {
	textarea.data('sceditor').insert(' ' + tag + ' ');
}

$(function () {
	sceController.init();
	// Don't need to select the node again and again
	textarea = sceController.getTextarea();
	// Hide the normal BBCode Buttons
	$('#format-buttons').hide();
	$('#smiley-box a img').each(function () {

		$(this).click(function () {
			setSmilie($(this).attr('alt'));
			return false;
		});
	});
});