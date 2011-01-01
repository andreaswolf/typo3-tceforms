/***************************************************************
*  Copyright notice
*
*  (c) 2010 TYPO3 Tree Team <http://forge.typo3.org/projects/typo3v4-extjstrees>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
Ext.namespace('TYPO3.Components.PageTree');

/**
 * @class TYPO3.Components.PageTree.TopPanel
 *
 * Top Panel
 *
 * @namespace TYPO3.Components.PageTree
 * @extends Ext.Panel
 * @author Stefan Galinski <stefan.galinski@gmail.com>
 */
TYPO3.Components.PageTree.TopPanel = Ext.extend(Ext.Panel, {
	/**
	 * Component Id
	 *
	 * @type {String}
	 */
	id: 'typo3-pagetree-topPanel',

	/**
	 * Border
	 *
	 * @type {Boolean}
	 */
	border: false,

	/**
	 * Toolbar Object
	 *
	 * @type {Ext.Toolbar}
	 */
	tbar: new Ext.Toolbar(),

	/**
	 * Currently Clicked Toolbar Button
	 *
	 * @type {Ext.Button}
	 */
	currentlyClickedButton: null,

	/**
	 * Currently Shown Panel
	 *
	 * @type {Ext.Component}
	 */
	currentlyShownPanel: null,

	/**
	 * Active tree (often used from outside, too)
	 *
	 * @type {TYPO3.Components.PageTree.Tree}
	 */
	activeTree: null,

	/**
	 * Filtering Indicator Item
	 *
	 * @type {Ext.Panel}
	 */
	filteringIndicator: null,

	/**
	 * Drag and Drop Group
	 *
	 * @cfg {String}
	 */
	ddGroup: '',

	/**
	 * Data Provider
	 *
	 * @cfg {Object}
	 */
	dataProvider: null,

	/**
	 * Filtering Tree
	 *
	 * @cfg {TYPO3.Components.PageTree.FilteringTree}
	 */
	filteringTree: null,

	/**
	 * Page Tree
	 *
	 * @cfg {TYPO3.Components.PageTree.Tree}
	 */
	tree: null,

	/**
	 * Initializes the component
	 *
	 * @return {void}
	 */
	initComponent: function() {
		this.activeTree = this.tree;

		this.currentlyShownPanel = new Ext.Panel({
			id: this.id + '-defaultPanel',
			cls: this.id + '-item',
			html: TYPO3.Components.PageTree.LLL.treeStructure
		});
		this.items = [this.currentlyShownPanel];

		TYPO3.Components.PageTree.TopPanel.superclass.initComponent.apply(this, arguments);

		this.addDragDropNodeInsertionFeature();
		
		if (!TYPO3.Components.PageTree.Configuration.hideFilter
			|| TYPO3.Components.PageTree.Configuration.hideFilter === '0'
		) {
			this.addFilterFeature();
		}

		this.getTopToolbar().addItem({xtype: 'tbfill'});
		this.addRefreshTreeFeature();
	},

	/**
	 * Returns a custom button template to fix some nasty webkit issues
	 * by removing some useless wrapping html code
	 *
	 * @return {void}
	 */
	getButtonTemplate: function() {
		return new Ext.Template(
			'<div id="{4}" class="x-btn {3}"><button type="{0}"">&nbsp;</button></div>'
		);
	},

	/**
	 * Adds a button to the components toolbar with a related component
	 *
	 * @param {Object} button
	 * @param {Object} connectedWidget
	 * @return {void}
	 */
	addButton: function(button, connectedWidget) {
		button.template = this.getButtonTemplate();
		if (!button.hasListener('click')) {
			button.on('click', this.topbarButtonCallback);
		}

		if (connectedWidget) {
			connectedWidget.hidden = true;
			button.connectedWidget = connectedWidget;
			this.add(connectedWidget);
		}

		this.getTopToolbar().addItem(button);
		this.doLayout();
	},

	/**
	 * Usual button callback method that triggers the assigned component of the
	 * clicked toolbar button
	 *
	 * @return {void}
	 */
	topbarButtonCallback: function() {
		var topPanel = this.ownerCt.ownerCt;

		topPanel.currentlyShownPanel.hide();
		if (topPanel.currentlyClickedButton) {
			topPanel.currentlyClickedButton.toggle(false);
		}

		if (topPanel.currentlyClickedButton === this) {
			topPanel.currentlyClickedButton = null;
			topPanel.currentlyShownPanel = topPanel.get(topPanel.id + '-defaultPanel');
		} else {
			this.toggle(true);
			topPanel.currentlyClickedButton = this;
			topPanel.currentlyShownPanel = this.connectedWidget;
		}

		topPanel.currentlyShownPanel.show();
	},

	/**
	 * Loads the filtering tree nodes with the given search word
	 *
	 * @param {Ext.form.TextField} textField
	 * @return {void}
	 */
	createFilterTree: function(textField) {
		var searchWord = textField.getValue();
		if ((searchWord.length <= 2 && searchWord.length > 0) || searchWord === this.filteringTree.searchWord) {
			return;
		}

		this.filteringTree.searchWord = searchWord;
		if (this.filteringTree.searchWord === '') {
			this.activeTree = this.tree;

			this.filteringTree.hide();
			this.tree.show().refreshTree(function() {
				textField.focus();
			}, this);

			if (this.filteringIndicator) {
				this.ownerCt.removeIndicator(this.filteringIndicator);
				this.filteringIndicator = null;
			}
		} else {
			var selectedNode = this.ownerCt.getSelected();
			this.activeTree = this.filteringTree;

			if (!this.filteringIndicator) {
				this.filteringIndicator = this.ownerCt.addIndicator(
					this.createIndicatorItem(textField)
				);
			}

			this.tree.hide();
			this.ownerCt.ownerCt.getEl().mask('', 'x-mask-loading-message');
			this.ownerCt.ownerCt.getEl().addClass('t3-mask-loading');
			this.filteringTree.show().refreshTree(function() {
				if (selectedNode) {
					this.ownerCt.select(selectedNode.attributes.nodeData.id, false);
				}
				textField.focus();
				this.ownerCt.ownerCt.getEl().unmask();
			}, this);
		}

		this.doLayout();
	},

	/**
	 * Adds an indicator item to the page tree application for the filtering feature
	 *
	 * @param {Ext.form.TextField} textField
	 * @return {void}
	 */
	createIndicatorItem: function(textField) {
		return {
			border: false,
			id: this.ownerCt.id + '-indicatorBar-filter',
			cls: this.ownerCt.id + '-indicatorBar-item',
			html: '<p>' +
					'<span id="' + this.ownerCt.id + '-indicatorBar-filter-info' + '" ' +
						'class="' + this.ownerCt.id + '-indicatorBar-item-leftIcon ' +
							TYPO3.Components.PageTree.Sprites.Info + '">' + '&nbsp;' +
					'</span>' +
					'<span id="' + this.ownerCt.id + '-indicatorBar-filter-clear' + '" ' +
						'class="' + this.ownerCt.id + '-indicatorBar-item-rightIcon ' +
							TYPO3.Components.PageTree.Sprites.InputClear + '">' + '&nbsp;' +
					'</span>' +
					TYPO3.Components.PageTree.LLL.activeFilterMode +
				'</p>',
			filteringTree: this.filteringTree,

			listeners: {
				afterrender: {
					scope: this,
					fn: function() {
						var element = Ext.fly(this.ownerCt.id + '-indicatorBar-filter-clear');
						element.on('click', function() {
							textField.setValue('');
							this.createFilterTree(textField);
						}, this);
					}
				}
			}
		};
	},

	/**
	 * Adds the necessary functionality and components for the filtering feature
	 *
	 * @return {void}
	 */
	addFilterFeature: function() {
		var topPanelButton = new Ext.Button({
			id: this.id + '-button-filter',
			cls: this.id + '-button',
			iconCls: TYPO3.Components.PageTree.Sprites.Filter
		});

		var textField = new Ext.form.TriggerField({
			id: this.id + '-filter',
			enableKeyEvents: true,
			triggerClass: TYPO3.Components.PageTree.Sprites.InputClear,

			listeners: {
				keydown: {
					fn: this.createFilterTree,
					scope: this,
					buffer: 1000
				}
			}
		});

		textField.onTriggerClick = function() {
			textField.setValue('');
			this.createFilterTree(textField);
		}.createDelegate(this);

		var topPanelWidget = new Ext.Panel({
			border: false,
			id: this.id + '-filterWrap',
			cls: this.id + '-item',
			items: [textField],

			listeners: {
				show: {
					scope: this,
					fn: function(panel) {
						panel.get(this.id + '-filter').focus();
					}
				}
			}
		});

		this.addButton(topPanelButton, topPanelWidget);
	},

	/**
	 * Creates the entries for the new node drag zone toolbar
	 *
	 * @return {void}
	 */
	createNewNodeToolbar: function() {
		(new Ext.dd.DragZone(this.getEl(), {
			ddGroup: this.ownerCt.ddGroup,

			getDragData: function(event) {
				this.proxyElement = document.createElement('div');

				var node = Ext.getCmp(event.getTarget('.x-btn').id);
				node.shouldCreateNewNode = true;

				return {
					ddel: this.proxyElement,
					item: node
				}
			},

			onInitDrag: function() {
				var clickedButton = this.dragData.item;
				var cls = clickedButton.initialConfig.iconCls;

				this.proxyElement.style.width = '150px';
				this.proxyElement.innerHTML = '<span class="' + cls + '"></span>'  + clickedButton.title;

				this.proxy.update(this.proxyElement);
			}
		}));
	},

	/**
	 * Creates the necessary components for new node drag and drop feature
	 *
	 * @return {void}
	 */
	addDragDropNodeInsertionFeature: function() {
		var newNodeToolbar = new Ext.Toolbar({
			border: false,
			id: this.id + '-item-newNode',
			cls: this.id + '-item',

			listeners: {
				render: {
					fn: this.createNewNodeToolbar
				}
			}
		});

		this.dataProvider.getNodeTypes(function(response) {
			for (var i = 0; i < response.length; ++i) {
				response[i].template = this.getButtonTemplate();
				newNodeToolbar.addItem(response[i]);
			}
			newNodeToolbar.doLayout();
		}, this);

		var topPanelButton = new Ext.Button({
			id: this.id + '-button-newNode',
			cls: this.id + '-button',
			iconCls: TYPO3.Components.PageTree.Sprites.NewNode
		});

		this.addButton(topPanelButton, newNodeToolbar);
	},

	/**
	 * Adds a button to the toolbar for the refreshing feature
	 *
	 * @return {void}
	 */
	addRefreshTreeFeature: function() {
		var topPanelButton = new Ext.Button({
			id: this.id + '-button-refresh',
			cls: this.id + '-button',
			iconCls: TYPO3.Components.PageTree.Sprites.Refresh,

			listeners: {
				click: {
					scope: this,
					fn: function() {
						this.activeTree.refreshTree();
					}
				}
			}
		});

		this.addButton(topPanelButton);
	}
});

// XTYPE Registration
Ext.reg('TYPO3.Components.PageTree.TopPanel', TYPO3.Components.PageTree.TopPanel);