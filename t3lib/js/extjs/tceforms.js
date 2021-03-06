/***************************************************************
 * extJS for TCEforms
 *
 * $Id$
 *
 * Copyright notice
 *
 * (c) 2009-2011 Steffen Kamper <info@sk-typo3.de>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

Ext.ns('TYPO3');

	// class to manipulate TCEFORMS
TYPO3.TCEFORMS = {
	contextId: 't3-tceforms-context',

	records: {},

	init: function() {
		Ext.QuickTips.init();

		this.convertDateFieldsToDatePicker();
		this.convertTextareasResizable();

		// initialize validation
		Ext.get(this.contextId).on('submit', function(evt) {
			if (!this.Validation.isValidForm()) {
				evt.stopEvent();
			}
		}, this);
	},

	convertDateFieldsToDatePicker: function() {
		var dateFields = Ext.select("input[id^=tceforms-date]"), minDate, maxDate, lowerMatch, upperMatch;
		dateFields.each(function(element) {
			var index = element.dom.id.match(/tceforms-datefield-/) ? 0 : 1;
			var format = TYPO3.settings.datePickerUSmode ? TYPO3.settings.dateFormatUS : TYPO3.settings.dateFormat;

			var datepicker = element.next('span');

			// check for daterange
			var lowerMatch = element.dom.className.match(/lower-(\d+)\b/);
			minDate = Ext.isArray(lowerMatch) ? new Date(lowerMatch[1] * 1000) : null;
			var upperMatch = element.dom.className.match(/upper-(\d+)\b/);
			maxDate = Ext.isArray(upperMatch) ? new Date(upperMatch[1] * 1000) : null;

			var menu = new Ext.menu.DateMenu({
				id:			'p' + element.dom.id,
				format:		format[index],
				value:		Date.parseDate(element.dom.value, format[index]),
				minDate:	minDate,
				maxDate:	maxDate,
				handler: 	function(picker, date){
					var relElement = Ext.getDom(picker.ownerCt.id.substring(1));
					relElement.value = date.format(format[index]);
					if (Ext.isFunction(relElement.onchange)) {
						relElement.onchange.call(relElement);
					}
				},
				listeners:	{
					beforeshow:	function(obj) {
						var relElement = Ext.getDom(obj.picker.ownerCt.id.substring(1));
						if (relElement.value) {
							obj.picker.setValue(Date.parseDate(relElement.value, format[index]));
						}
					}
				}
			});

			datepicker.on('click', function(){
				menu.show(datepicker);
			});
		});
	},
	
	convertTextareasResizable: function() {
		var textAreas = Ext.select("textarea[id^=tceforms-textarea-]");
		textAreas.each(function(element) {
			if (TYPO3.settings.textareaFlexible) {
				var elasticTextarea = new Ext.ux.elasticTextArea().applyTo(element.dom.id, {
					minHeight: 50,
					maxHeight: TYPO3.settings.textareaMaxHeight
				});
			}
			if (TYPO3.settings.textareaResize) {
				element.addClass('resizable');
				var dwrapped = new Ext.Resizable(element.dom.id, {
					minWidth:  300,
					minHeight: 50,
					maxHeight: TYPO3.settings.textareaMaxHeight,
					dynamic:   true
				});
			}
		});
	},

	registerRecord: function(table, uid, sheetIdentifier) {
		this.records[sheetIdentifier] = {
			'table': table,
			'uid': uid,
			'sheetIdentifier': sheetIdentifier
		};
	},

	createTabMenu: function(identifier) {
		return new Ext.TabPanel({
			resizeTabs:true, // turn on tab resizing
			applyTo: 'record-' + identifier,
			enableTabScroll:true,
			autoHeight:true,
			defaults: {autoScroll:true},
			id: identifier,
			autoTabs: true,
			autoTabSelector: "div[id^=" + identifier + "-]",
			activeTab: 0,
			border:false,
			deferredRender: false,
			autoWidth: true,
			monitorResize: true
		});
	},


	initRecords: function() {
		Ext.iterate(this.records, function(recordIdentifier, recordInfo) {
			this.records[recordIdentifier].tabMenu = this.createTabMenu(recordIdentifier);
		}, this);
	},

	findRecordInfoFromElement: function(element) {
		var parentElement = Ext.get(element).parent('.x-tab-panel');
		if (parentElement) {
			return this.records[parentElement.id.substr(7)];
		} else {
			return false;
		}
	}
	
};
Ext.onReady(TYPO3.TCEFORMS.init, TYPO3.TCEFORMS);




/** our validation manager and registration handler **/
TYPO3.TCEFORMS.Validation = {
	init: function() {
		Ext.iterate(this.Validators, function(validatorName, validatorObject) {
			new validatorObject();
		});
	},

		/** should be triggered on trying to submit the form **/
	isValidForm: function() {
		var isValidForm = true;
		Ext.iterate(this.Validators, function(validatorName, validatorObject) {
			if (Ext.type(validatorObject.validateOnFormSubmission)) {
				if (validatorObject.validateOnFormSubmission() == false) {
					isValidForm = false;
				}
			}
		});
		return isValidForm;
	},

		// will be populated below and through extensions
	Validators: {}
};


Ext.onReady(function() {

		// base object that will be used to "subclass"
TYPO3.TCEFORMS.Validation.DefaultValidator = Ext.extend(Ext.util.Observable, {
	errorMarkup: '<span class="t3-icon t3-icon-actions t3-icon-dialog-warning"></span>',
	errorClass: 't3-tceforms-field-error',

		// the elements that will be used, something like: "input[required=required]"
	elementSelector: '',
	elements: [],
	registeredEvents: ['blur', 'keyup'],

	/**
	 * register all fields that need to be validated
	 * @access public
	 */
	constructor: function() {
		this.elements = Ext.query(this.elementSelector);
			// callback function that is called when the event gets triggered
		var fn = function(evt, element) {
			this.validate(Ext.get(element));
		};

		Ext.each(this.elements, function(element) {
			var visibleElement = element;
			Ext.each(this.registeredEvents, function(eventName) {
				Ext.fly(visibleElement).on(eventName, fn, this);
			}, this);
		}, this);
	},

	/**
	 * loop through each field and see if it is valid
	 *
	 * part of the public API that does the magic when submitting the form
	 *
	 * @access public
	 * @return if this returns false, the form will not get submitted
	 */
	validateOnFormSubmission: function() {
		isValid = true;
		Ext.each(this.elements, function(element) {
			if (!this.validate(Ext.get(element))) {
				isValid = false;
			}
		}, this);
		return isValid;
	},

	/**
	 * validate function to check for a single element
	 *
	 * this method also takes the necessary actions on what to do
	 * when a field is invalid, valid or
	 *
	 * @param element
	 * @return if this returns false, an element is invalid
	 * @access private
	 */
	validate: function(element) {
		if (this.isValid(element)) {
			this.markAsValid(element);
			return true;
		} else {
			this.markAsInvalid(element);
			return false;
		}
	},

	/**
	 * validate function to validate a single element
	 * only lets you know if an element is valid or not, does nothing else
	 *
	 * @param element
	 * @return if this returns false, an element is invalid
	 * @access private
	 */
	isValid: function(element) {
		return true;
	},


		// various helper functions
	markAsInvalid: function(element) {
		element.addClass(this.errorClass);
		this.markContainerAsInvalid(element);
	},

	markAsValid: function(element) {
		element.removeClass(this.errorClass);
		this.markContainerAsValid(element);
	},

	findParentContainerPosition: function(container) {
		var containerPosition = 0;
		while (container = container.prev()) containerPosition++;
		return containerPosition;
	},

	markContainerAsValid: function(element) {
		// find parent tab for that element
		var recordInfo = TYPO3.TCEFORMS.findRecordInfoFromElement(element);
		var containerRecord = element.parent('.x-panel');
		var containerPosition = this.findParentContainerPosition(containerRecord);
		var tab = recordInfo.tabMenu.getTabEl(containerPosition);
		var tabtext = Ext.get(tab).child('.x-tab-strip-text');
		if (tabtext.child('.t3-icon')) {
			tabtext.last().remove();
		}
	},

	markContainerAsInvalid: function(element) {
		// find parent tab for that element
		var recordInfo = TYPO3.TCEFORMS.findRecordInfoFromElement(element);
		var containerRecord = element.parent('.x-panel');
		var containerPosition = this.findParentContainerPosition(containerRecord);
		var tab = recordInfo.tabMenu.getTabEl(containerPosition);
		var tabtext = Ext.get(tab).child('.x-tab-strip-text');
		if (!tabtext.child('.t3-icon')) {
			tabtext.createChild(this.errorMarkup);
		}
	}
});

/** Implementation of the Required Validator**/
TYPO3.TCEFORMS.Validation.Validators.Required = Ext.extend(TYPO3.TCEFORMS.Validation.DefaultValidator, {
	elementSelector: 'input[required=required]',

	/**
	 * validate function to see if an element is empty or not
	 *
	 * @param element
	 * @return if this returns false, an element is invalid
	 * @access private
	 */
	isValid: function(element) {
		return (element.getValue().length > 0);
	}
});


TYPO3.TCEFORMS.Validation.init();

});




/***********
 * Filter
 **********/

/**
 * a manager for filtering values within
 **/
TYPO3.TCEFORMS.Filters = {
		// will be populated below and through extensions
	Filters: {},

	init: function() {
		Ext.iterate(this.Filters, function(filterName, filterObject) {
			new filterObject();
		});
	}
}
Ext.onReady(TYPO3.TCEFORMS.init, TYPO3.TCEFORMS);
