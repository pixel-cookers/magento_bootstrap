/**
 * Magento Owebia Shipping2 Module
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * @category   Owebia
 * @package    Owebia_Shipping2
 * @copyright  Copyright (c) 2008-10 Owebia (http://www.owebia.com/)
 * @author     Antoine Lemoine
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * @constructor
 */
OS2Editor = function (options) {
	this.options = options;

	this.jwindow = jQuery(window);
	this.jeditor = null;
	this.jeditor_content = null;
	this.jtextarea = null;
	this.jcontextualmenu = null;
	this.mouse_event_owner = null;
	this.dialog_v_padding = 10;
	this.dialog_h_padding = 15;
	this.opened = false;
	this.shipping_code = null;
}

OS2Editor.prototype = {
	/**
	 * @private
	 */
	_init: function () {
		var jeditor = this._dialog('os2-editor',"<div style=\"width:100%;height:100%;position:relative;\" id=\"os2-editor-content\"></div>");
		this.jeditor = jeditor;
		var jdialogbox = jeditor.find('.dialog-box');
		this.jeditor_content = jeditor.find('#os2-editor-content');
		this.jeditor_content.css({width: jdialogbox.innerWidth()-this.dialog_h_padding*2, height: jdialogbox.innerHeight()-this.dialog_v_padding*2, border: '0'});

		var self = this;
		jQuery('#os2-editor .preview-items-list > .preview-item').live('click',function (event) {
			var caller = jQuery(this);
			if (caller.hasClass('address-filter')) self._contextualMenu('address-filter',caller);
			else if (caller.hasClass('customer-group')) self._contextualMenu('customer-group',caller);
			jQuery('#os2-contextual-menu').css({position: 'absolute', 'z-index': 100, left: event.pageX-7, top: event.pageY}).show();
			event.preventDefault();
			event.stopPropagation();
		});
		jQuery('#os2-editor .property-container .field').live('blur',function (event) {
			self._updateProperty(this);
			event.preventDefault();
			event.stopPropagation();
		});
	},

	/**
	 * @private
	 */
	_dialog: function (id, content) {
		var w = this.jwindow.width();
		var h = this.jwindow.height();
		var margin = 50;
		var v_padding = this.dialog_v_padding;
		var h_padding = this.dialog_h_padding;
		var width = w-2*margin;
		var height = h-2*margin;
		var dialog_w = Math.max(width+2*h_padding,350);
		var dialog_h = Math.max(height+2*v_padding,250);
		var top = (h-dialog_h)/2;
		var left = (w-dialog_w)/2;
		var margin_top = (dialog_h-height)/2-v_padding;
		var margin_left = (dialog_w-width)/2-h_padding;
		var jdialog = jQuery("<div style=\"position:fixed;top:0;left:0;width:100%;height:100%;z-index:100;\" id=\""+id+"\">"
			+"<div class=\"dialog-bg\" style=\"position:fixed;top:0;left:0;z-index:100;width:100%;height:100%;background:#000;\"></div>"
			+"<div class=\"dialog-box\" style=\"position:fixed;background:#fff;-moz-box-shadow: #000 0 0 10px;top:"+top+"px;left:"+left+"px;width:"+dialog_w+"px;"
				+"height:"+dialog_h+"px;z-index:200;\"><div style=\"padding:"+v_padding+"px "+h_padding+"px;margin:"+margin_top+"px 0 0 "+margin_left+"px;\">"
			+content+'</div></div></div>');
		jdialog.find('.dialog-bg').click(function(event){
				jdialog.fadeOut(function(){jdialog.hide();});
			})
			.css({
				opacity: '0.7'
			})
		;
		jQuery('body').append(jdialog);
		return jdialog;
	},

	/**
	 * @private
	 */
	_contextualMenu: function (which, caller) {
		var self = this;
		this.mouse_event_owner = caller;
		
		if (this.jcontextualmenu==null) {
			var jcontextualmenu = jQuery('<ul id="os2-contextual-menu" style="display:none;"></ul>');
			jcontextualmenu.click(function (event) {
				jcontextualmenu.hide();
				event.preventDefault();
				event.stopPropagation();
			});
			jQuery('body').append(jcontextualmenu);
			jQuery(document).mouseup(function (event) {
				if (self.mouse_event_owner!=null && self.mouse_event_owner.hasClass('files-container')) {
					self.mouse_event_owner.trigger(event);
				}
				jcontextualmenu.hide();
			});
			this.jcontextualmenu = jcontextualmenu;
		}

		switch (which) {
			case 'address-filter':
				this.jcontextualmenu.html(
					"<li><span>"+caller.attr('full-value')+"</span></li>"
					+(caller.hasClass('preview-item-group') ?
							"<li><a id=\"ungroup-link\" href=\"#\">"+this.options.menu_item_dissociate_label+"</a></li>"
						:
							''
					)
					+"<li><a id=\"edit-link\" href=\"#\">"+this.options.menu_item_edit_label+"</a></li>"
					+"<li><a id=\"remove-link\" href=\"#\" onclick=\"\">"+this.options.menu_item_remove_label+"</a></li>"
				);
				jQuery('#remove-link').click(function (event) {
					self._remove_click(event,caller);
				});
				jQuery('#edit-link').click(function (event) {
					self._edit_click(event,caller,'get-address-filters');
				});
				jQuery('#ungroup-link').click(function (event) {
					self._dissociateAddressFiltersGroup(caller,100);
					event.preventDefault();
				});
				break;
			case 'customer-group':
				this.jcontextualmenu.html(
					"<li><span>"+caller.attr('full-value')+"</span></li>"
					+"<li><a id=\"edit-link\" href=\"#\">"+this.options.menu_item_edit_label+"</a></li>"
					+"<li><a id=\"remove-link\" href=\"#\" onclick=\"\">"+this.options.menu_item_remove_label+"</a></li>"
				);
				jQuery('#remove-link').click(function (event) {
					self._remove_click(event,caller);
				});
				jQuery('#edit-link').click(function (event) {
					self._edit_click(event,caller,'get-customer-groups');
				});
				break;
		}
	},

	/**
	 * @private
	 */
	_remove_click: function (event, caller) {
		var self = this;
		caller.fadeOut(null,function(){
			var parent = caller.parents('.property-container');
			caller.remove();
			self.updatePropertyFromPreview(parent);
		});
		event.preventDefault();
	},

	/**
	 * @private
	 */
	_edit_click: function (event, caller, what) {
		var self = this;
		var new_value = prompt(this.options.prompt_new_value_label,caller.attr('original-value'));
		if (new_value) {
			this._ajax({
				data: {
					what: what,
					input: new_value
				},
				success: function (msg) {
					var parent = caller.parents('.property-container');
					caller.replaceWith(msg);
					self.updatePropertyFromPreview(parent);
				}
			});
		}
		event.preventDefault();
	},

	/**
	 * @private
	 */
	_ajax: function (args) {
		args.data.form_key = this.options.form_key;
		jQuery.ajax({
			type: 'POST',
			url: this.options.ajax_url,
			data: args.data,
			success: args.success
		});
	},
	
	/**
	 * @private
	 */
	_download: function (data) {
		data.form_key = this.options.form_key;
		data = jQuery.param(data);
		var inputs = '';
		jQuery.each(data.split('&'),function(){ 
			var tmp = this.split('=');
			inputs += '<input type="hidden" name="'+tmp[0]+'" value="'+tmp[1]+'"/>'; 
		});
		jQuery('<form action="'+this.options.ajax_url+'" method="post">'+inputs+'</form>').appendTo('body').submit().remove();
	},
	
	/**
	 * @private
	 */
	_checkCountries: function (parent) {
		var output = '';
		parent.find('.address-filter').each(function () {
			var country_code = jQuery(this).attr('country-code');
			output += country_code+',';
			if (country_code!='') {
				var tmp = parent.find('.country-'+country_code);
				if (tmp.size()>1) {
					tmp.addClass('warning');
				} else {
					tmp.removeClass('warning');
				}
			}
		});
	},

	/**
	 * @private
	 */
	_getConfig: function () {
		var self = this;
		var config = '';
		this.jeditor_content.find('.row-container').each(function(){
			var jrowcontainer = jQuery(this);
			if (jrowcontainer.hasClass('ignored-lines')) {
				config += jrowcontainer.find('.field').val()+"\n";
			} else {
				var comment = jrowcontainer.find('.property-container[property-name="*comment"] .field').val();
				if (comment!='') {
					var lines = comment.replace(/(?:\r\n|\n|\r)/,"\n").split("\n");
					for (var i=0; i<lines.length; i++) {
						if (lines[i].substr(0,1)!='#') lines[i] = '# '+lines[i];
						else lines[i] = '#'+lines[i];
					}
					config += lines.join("\n")+"\n";
				}
				config += "{\n";
				jrowcontainer.find('.property-container').each(function(){
					var jpropertycontainer = jQuery(this);
					var property = jpropertycontainer.attr('property-name');
					if (property!='*comment') {
						var value = null;
						if (property=='destination' || property=='origin') {
							value = self._getCompleteValue('compact-value',this,true,true);
						}
						else value = jpropertycontainer.find('.field').val();
						var property = jpropertycontainer.attr('property-name');
						
						switch (property) {
							case 'enabled':
								if (value!='1') config += "\t"+property+': false,'+"\n";
								break;
							default:
								if (value!='') {
									config += "\t"+property+': "'+value.replace(/\"/g,"\\\"")+'",'+"\n";
								}
								break;
						}
						
					}
				});
				config += "}\n";
			}
		});
		return config;
	},

	/**
	 * @private
	 */
	_dissociateAddressFiltersGroup: function (caller, delay) {
		var self = this;
		var childs = caller.children('.address-filter');
		if (delay>0) {
			childs.each(function (i) {
				var child = jQuery(this);
				child.delay(i*delay).fadeOut(null,function(){
					child.insertBefore(caller);
					child.fadeIn();
					if (i==childs.size()-1) {
						caller.fadeOut(null,function(){
							caller.remove();
							self.updatePropertyFromPreview(parent);
						});
					}
				});
			});
		} else {
			childs.insertBefore(caller);
			caller.remove();
			self.updatePropertyFromPreview(parent);
		}
	},
	
	/**
	 * @private
	 */
	_updatePropertyPreview: function (object, what) {
		if (!(object instanceof jQuery)) object = jQuery(object);
		if (!object.hasClass('property-container')) object = object.parents('.property-container');

		var self = this;
		this._ajax({
			data: {
				what: what,
				input: object.find('textarea').val()
			},
			success: function (msg) {
				object.find('.preview-items-list').html(msg);
			}
		});
	},

	/**
	 * @private
	 */
	_updateProperty: function (object) {
		if (!(object instanceof jQuery)) object = jQuery(object);

		var property_container = object.parents('.property-container');
		var property_name = property_container.attr('property-name');
		switch (property_name) {
			case 'destination':
			case 'origin':
				this._updatePropertyPreview(object,'get-address-filters');
				break;
			case 'customer_groups':
				this._updatePropertyPreview(object,'get-customer-groups');
				break;
			case 'label':
				this._updateRowTitle(object,property_container);
				break;
		}
		this._ajax({
			data: {
				what: 'check-config',
				config: this._getConfig(),
			},
			success: function (msg) {
				jQuery('body').append(msg);
			}
		});
		var id = object.attr('id')+'-item';
		if (object.val().trim()=='') jQuery('#'+id).addClass('empty');
		else jQuery('#'+id).removeClass('empty');
	},

	/**
	 * @private
	 */
	_updateRowTitle: function (jtextarea) {
		var title = jtextarea.val().trim();
		if (title=='') title = this.options.default_row_label;
		jtextarea.parents('.row-container').find('.row-title').html(title);
	},

	/**
	 * @private
	 */
	_updateRowsUI: function () {
		var jrows = this.jeditor.find('.row-container');
		if (jrows.filter('.selected').size()==0) {
			jrows.eq(0).addClass('selected');
		}
		if (jrows.size()<=1) {
			jrows.find('.row-actions .delete').hide();
		} else {
			jrows.find('.row-actions .delete').show();
		}
	},

	/**
	 * @private
	 */
	/*_linearizeAddressFilters: function (object) {
		if (!(object instanceof jQuery)) object = jQuery(object);
		if (!object.hasClass('property-container')) object = object.parents('.property-container');

		var self = this;
		object.find('.address-filter-list .address-filter-group').each(function () {
			self._dissociateAddressFiltersGroup(jQuery(this),0);
		});
	},*/

	/**
	 * @public
	 */
	insertAtCaret: function (object, text_to_insert) {
		if (!(object instanceof jQuery)) {
			object = jQuery(object);
			if (object[0].nodeName!='textarea') object = object.parents('.property-container');
		}
		if (object.hasClass('property-container')) object = object.find('textarea');

		object.each(function (i) {
			if (document.selection) {
				this.focus();
				var sel = document.selection.createRange();
				sel.text = text_to_insert;
				this.focus();
			} else if (this.selectionStart || this.selectionStart=='0') {
				var start_index = this.selectionStart;
				var end_index = this.selectionEnd;
				var scroll_top = this.scrollTop;
				this.value = this.value.substring(0,start_index)+text_to_insert+this.value.substring(end_index,this.value.length);
				this.focus();
				this.selectionStart = start_index + text_to_insert.length;
				this.selectionEnd = start_index + text_to_insert.length;
				this.scrollTop = scroll_top;
			} else {
				this.value += text_to_insert;
				this.focus();
			}
		});
	},

	/**
	 * @public
	 */
	updatePropertyFromPreview: function (object) {
		if (!(object instanceof jQuery)) object = jQuery(object);
		if (!object.hasClass('property-container')) object = object.parents('.property-container');

		var list = object.find('.address-filter-list');
		var displayed_field = list.attr('displayed-field');
		if (displayed_field==null) displayed_field = 'original-value';
		var compact = list.attr('compact');
		if (compact==null) compact = false;
		else compact = compact=='1';
		this.updatePropertyValue(displayed_field,object,compact);
	},

	/**
	 * @private
	 */
	_getCompleteValue: function (field, object, compact, linearize_groups) {
		if (!(object instanceof jQuery)) object = jQuery(object);
		if (!object.hasClass('property-container')) object = object.parents('.property-container');

		var values = [];
		object.find('.preview-items-list').attr('displayed-field',field).attr('compact',compact?'1':'0');
		
		if (linearize_groups) {
			object.find('.preview-items-list .preview-item').each(function () {
				var jitem = jQuery(this);
				if (!jitem.hasClass('preview-item-group')) values.push(jitem.attr(field));
			});
		} else {
			object.find('.preview-items-list > .preview-item').each(function () {
				values.push(jQuery(this).attr(field));
			});
		}
		var excluding = object.find('.excluding:checked').val()=='1';
		return (excluding ? '* - (' : '')+values.join(','+(compact ? '' : ' '))+(excluding ? ')' : '');
	},

	/**
	 * @public
	 */
	updatePropertyValue: function (field, object, compact) {
		if (!(object instanceof jQuery)) object = jQuery(object);
		if (!object.hasClass('property-container')) object = object.parents('.property-container');

		object.find('.field').val(this._getCompleteValue(field,object,compact,false));
		var property_name = object.attr('property-name');
		if (property_name=='destination' || property_name=='origin') {
			this._checkCountries(object);
		}
	},

	/**
	 * @public
	 */
	selectProperty: function (code, property) {
		jQuery('#r-'+code+'-container .property-item, #r-'+code+'-container .property-container').removeClass('selected');
		jQuery('#r-'+code+'-p-'+property+'-item, #r-'+code+'-p-'+property+'-container').addClass('selected');
	},

	/**
	 * @public
	 */
	selectRow: function (code) {
		jQuery('#os2-editor .row-container').removeClass('selected');
		jQuery('#r-'+code+'-container').addClass('selected');
	},

	/**
	 * @public
	 */
	resetErrors: function (code, property, error) {
		this.jeditor.find('.has-error').not('.ignored-lines').removeClass('has-error');
		this.jeditor.find('div.error').remove();
	},

	/**
	 * @public
	 */
	setError: function (code, property, error) {
		if (property=='') {
			jQuery('#r-'+code+'-container .row-header').append("<div class=\"error\">"+error+"</div>");
		} else {
			jQuery('#r-'+code+'-container').addClass('has-error');
			jQuery('#r-'+code+'-p-'+property+'-item').addClass('has-error');
			jQuery('#r-'+code+'-p-'+property+'-container').prepend("<div class=\"error\">"+error+"</div>");
		}
	},

	/**
	 * @public
	 */
	correct: function (code, property, value) {
		var jfield = jQuery('#r-'+code+'-p-'+property);
		jfield.val(value);
		this._updateProperty(jfield);
	},

	/**
	 * @public
	 */
	removeRow: function (object) {
		var self = this;
		var jrow = jQuery(object).parents('.row-container');
		jrow.fadeOut(null,function(){
			jrow.remove();
			self._updateRowsUI();
		});
	},

	/**
	 * @public
	 */
	addRow: function () {
		var self = this;
		this._ajax({
			data: {
				what: 'add-row'
			},
			success: function (msg) {
				var jcontainer = self.jeditor.find('.config-container');
				jcontainer.find('.row-container.selected').removeClass('selected');
				jcontainer.append(msg);
				self._updateRowsUI();
			}
		});
	},

	/**
	 * @public
	 */
	saveToFile: function () {
		this._download({
			what: 'save-to-file',
			config: this._getConfig(),
		});
	},

	/**
	 * @public
	 */
	save: function () {
		var self = this;
		this._ajax({
			data: {
				what: 'save-config',
				config: this._getConfig(),
				shipping_code: this.shipping_code
			},
			success: function (msg) {
				self.jtextarea.val(msg);
				self.close();
			}
		});
		/*
		this.jtextarea.val(this._getConfig());
		this.close();
		*/
	},

	/**
	 * @public
	 */
	applyChanges: function () {
		this.loadConfig(this._getConfig());
	},

	/**
	 * @public
	 */
	loadConfig: function (config) {
		if (typeof config=='undefined') {
			config = jQuery('#os2-editor-config-loader textarea').val()
		}
		var self = this;
		this._ajax({
			data: {
				what: 'load-config',
				config: config
			},
			success: function (msg) {
				jQuery('#os2-editor-config-container').html(msg);
				self.jeditor.find('.address-filter-list').each(function(){
					self._checkCountries(jQuery(this));
				});
				self._updateRowsUI();
				jQuery('#os2-editor-config-loader').slideUp().find('textarea').val('');
			}
		});
	},

	/**
	 * @public
	 */
	showConfigLoader: function () {
		jQuery('#os2-editor-config-loader').slideDown();
	},

	/**
	 * @public
	 */
	hideConfigLoader: function () {
		jQuery('#os2-editor-config-loader').slideUp().find('textarea').val('');
	},

	/**
	 * @public
	 */
	open: function (object, shipping_code, callback) {
		this.shipping_code = shipping_code;
		this.opened = true;
		if (this.jeditor==null) this._init();

		var jcell = jQuery(object).parents('td.value');
		this.jtextarea = jcell.find('textarea');

		this.jeditor_content.html('<div class=\"loading rule-param-wait\">'+this.options.loading_label+'</div>');
		this.jeditor.fadeIn();

		var self = this;
		this._ajax({
			data: {
				what: 'open',
				input: this.jtextarea.val()
			},
			success: function (msg) {
				self.jeditor_content.html(msg);
				var jconfig_container = self.jeditor.find('#os2-editor-config-container');
				var height = self.jeditor_content.height() - jconfig_container.position().top - self.jeditor.find('.donate-container').height() - 15;
				jconfig_container.css({
					overflow: 'auto',
					height: height
				});
				self._updateRowsUI();
				if (typeof callback!='undefined') callback();
			}
		});
	},
	
	/**
	 * @public
	 */
	close: function () {
		this.opened = false;
		this.jeditor.fadeOut();
	},

	/**
	 * @public
	 */
	openPage: function (page) {
		jQuery('#os2editor-'+page+'-page').fadeIn();
	},

	/**
	 * @public
	 */
	closePage: function (object) {
		jQuery(object).parents('.os2editor-page').fadeOut();
	},

	/**
	 * @public
	 */
	help: function (help_section, object, shipping_code) {
		var self = this;
		if (!this.opened) {
			this.open(object,shipping_code,function(){self.help(help_section);});
			return;			
		}
		this._ajax({
			data: {
				what: 'help',
				input: help_section
			},
			success: function (msg) {
				var jhelp_page = jQuery('#os2editor-help-page');
				var jpage_content = jhelp_page.find('.page-content');
				var height = jhelp_page.height() - jpage_content.position().top - 15;
				jpage_content.css({
					overflow: 'auto',
					height: height
				});
				jpage_content.html(msg);
			}
		});
		jQuery('#os2editor-help-page .page-content').html('<div class=\"loading rule-param-wait\">'+this.options.loading_label+'</div>');
		this.openPage('help');
	}
}

