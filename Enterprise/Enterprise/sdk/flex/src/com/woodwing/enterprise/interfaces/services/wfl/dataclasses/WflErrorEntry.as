/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl.dataclasses
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflErrorEntry")]

	public class WflErrorEntry
	{
		private var _Entity:String;
		private var _ID:String;
		private var _Message:String;
		private var _Details:String;
		private var _ErrorCode:String;
		private var _MessageLevel:String;

		public function WflErrorEntry() {
		}

		public function get Entity():String {
			return this._Entity;
		}
		public function set Entity(Entity:String):void {
			this._Entity = Entity;
		}

		public function get ID():String {
			return this._ID;
		}
		public function set ID(ID:String):void {
			this._ID = ID;
		}

		public function get Message():String {
			return this._Message;
		}
		public function set Message(Message:String):void {
			this._Message = Message;
		}

		public function get Details():String {
			return this._Details;
		}
		public function set Details(Details:String):void {
			this._Details = Details;
		}

		public function get ErrorCode():String {
			return this._ErrorCode;
		}
		public function set ErrorCode(ErrorCode:String):void {
			this._ErrorCode = ErrorCode;
		}

		public function get MessageLevel():String {
			return this._MessageLevel;
		}
		public function set MessageLevel(MessageLevel:String):void {
			this._MessageLevel = MessageLevel;
		}

	}
}
