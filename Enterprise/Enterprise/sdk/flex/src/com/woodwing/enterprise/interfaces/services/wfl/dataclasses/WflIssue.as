/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl.dataclasses
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflIssue")]

	public class WflIssue
	{
		private var _Id:String;
		private var _Name:String;
		private var _OverrulePublication:String;

		public function WflIssue() {
		}

		public function get Id():String {
			return this._Id;
		}
		public function set Id(Id:String):void {
			this._Id = Id;
		}

		public function get Name():String {
			return this._Name;
		}
		public function set Name(Name:String):void {
			this._Name = Name;
		}


		// _OverrulePublication should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function get OverrulePublication():String {
			return this._OverrulePublication;
		}

		// _OverrulePublication should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function set OverrulePublication(OverrulePublication:String):void {
			this._OverrulePublication = OverrulePublication;
		}

	}
}
