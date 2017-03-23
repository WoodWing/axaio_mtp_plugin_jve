/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl.dataclasses
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflUser")]

	public class WflUser
	{
		private var _UserID:String;
		private var _FullName:String;
		private var _TrackChangesColor:String;
		private var _EmailAddress:String;

		public function WflUser() {
		}

		public function get UserID():String {
			return this._UserID;
		}
		public function set UserID(UserID:String):void {
			this._UserID = UserID;
		}

		public function get FullName():String {
			return this._FullName;
		}
		public function set FullName(FullName:String):void {
			this._FullName = FullName;
		}

		public function get TrackChangesColor():String {
			return this._TrackChangesColor;
		}
		public function set TrackChangesColor(TrackChangesColor:String):void {
			this._TrackChangesColor = TrackChangesColor;
		}

		public function get EmailAddress():String {
			return this._EmailAddress;
		}
		public function set EmailAddress(EmailAddress:String):void {
			this._EmailAddress = EmailAddress;
		}

	}
}
