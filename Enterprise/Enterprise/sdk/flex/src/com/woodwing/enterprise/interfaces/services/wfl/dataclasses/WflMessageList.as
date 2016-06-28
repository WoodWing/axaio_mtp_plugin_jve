/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl.dataclasses
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflMessageList")]

	public class WflMessageList
	{
		private var _Messages:Array;
		private var _ReadMessageIDs:Array;
		private var _DeleteMessageIDs:Array;

		public function WflMessageList() {
		}

		public function get Messages():Array {
			return this._Messages;
		}
		public function set Messages(Messages:Array):void {
			this._Messages = Messages;
		}

		public function get ReadMessageIDs():Array {
			return this._ReadMessageIDs;
		}
		public function set ReadMessageIDs(ReadMessageIDs:Array):void {
			this._ReadMessageIDs = ReadMessageIDs;
		}

		public function get DeleteMessageIDs():Array {
			return this._DeleteMessageIDs;
		}
		public function set DeleteMessageIDs(DeleteMessageIDs:Array):void {
			this._DeleteMessageIDs = DeleteMessageIDs;
		}

	}
}
