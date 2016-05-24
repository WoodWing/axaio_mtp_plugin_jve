/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl.dataclasses
{
	import com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflMetaData;
	import com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflMessageList;

	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflObject")]

	public class WflObject
	{
		private var _MetaData:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflMetaData;
		private var _Relations:Array;
		private var _Pages:Array;
		private var _Files:Array;
		private var _Messages:Array;
		private var _Elements:Array;
		private var _Targets:Array;
		private var _Renditions:Array;
		private var _MessageList:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflMessageList;
		private var _ObjectLabels:Array;
		private var _InDesignArticles:Array;
		private var _Placements:Array;
		private var _Operations:Array;

		public function WflObject() {
		}

		public function get MetaData():com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflMetaData {
			return this._MetaData;
		}
		public function set MetaData(MetaData:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflMetaData):void {
			this._MetaData = MetaData;
		}

		public function get Relations():Array {
			return this._Relations;
		}
		public function set Relations(Relations:Array):void {
			this._Relations = Relations;
		}

		public function get Pages():Array {
			return this._Pages;
		}
		public function set Pages(Pages:Array):void {
			this._Pages = Pages;
		}

		public function get Files():Array {
			return this._Files;
		}
		public function set Files(Files:Array):void {
			this._Files = Files;
		}

		public function get Messages():Array {
			return this._Messages;
		}
		public function set Messages(Messages:Array):void {
			this._Messages = Messages;
		}

		public function get Elements():Array {
			return this._Elements;
		}
		public function set Elements(Elements:Array):void {
			this._Elements = Elements;
		}

		public function get Targets():Array {
			return this._Targets;
		}
		public function set Targets(Targets:Array):void {
			this._Targets = Targets;
		}

		public function get Renditions():Array {
			return this._Renditions;
		}
		public function set Renditions(Renditions:Array):void {
			this._Renditions = Renditions;
		}

		public function get MessageList():com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflMessageList {
			return this._MessageList;
		}
		public function set MessageList(MessageList:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflMessageList):void {
			this._MessageList = MessageList;
		}

		public function get ObjectLabels():Array {
			return this._ObjectLabels;
		}
		public function set ObjectLabels(ObjectLabels:Array):void {
			this._ObjectLabels = ObjectLabels;
		}

		public function get InDesignArticles():Array {
			return this._InDesignArticles;
		}
		public function set InDesignArticles(InDesignArticles:Array):void {
			this._InDesignArticles = InDesignArticles;
		}

		public function get Placements():Array {
			return this._Placements;
		}
		public function set Placements(Placements:Array):void {
			this._Placements = Placements;
		}

		public function get Operations():Array {
			return this._Operations;
		}
		public function set Operations(Operations:Array):void {
			this._Operations = Operations;
		}

	}
}
