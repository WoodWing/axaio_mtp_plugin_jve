/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl.dataclasses
{
	import com.woodwing.enterprise.interfaces.services.WoodWingUtils;
	import com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflState;
	import com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflAttachment;

	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflVersionInfo")]

	public class WflVersionInfo
	{
		private var _Version:String;
		private var _User:String;
		private var _Comment:String;
		private var _Slugline:String;
		private var _Created:String;
		private var _Objects:String;
		private var _State:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflState;
		private var _File:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflAttachment;

		public function WflVersionInfo() {
		}

		public function get Version():String {
			return this._Version;
		}
		public function set Version(Version:String):void {
			this._Version = Version;
		}

		public function get User():String {
			return this._User;
		}
		public function set User(User:String):void {
			this._User = User;
		}

		public function get Comment():String {
			return this._Comment;
		}
		public function set Comment(Comment:String):void {
			this._Comment = Comment;
		}

		public function get Slugline():String {
			return this._Slugline;
		}
		public function set Slugline(Slugline:String):void {
			this._Slugline = Slugline;
		}

		public function get Created():String {
			return this._Created;
		}

		public function getCreatedAsDate():Date {
			return WoodWingUtils.stringToDate(this._Created);
		}

		public function set Created(Created:String):void {
			this._Created = Created;
		}


		public function setCreatedAsDate(Created:Date):void {
			this._Created = WoodWingUtils.dateToString(Created);
		}

		public function get Objects():String {
			return this._Objects;
		}
		public function set Objects(Objects:String):void {
			this._Objects = Objects;
		}

		public function get State():com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflState {
			return this._State;
		}
		public function set State(State:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflState):void {
			this._State = State;
		}

		public function get File():com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflAttachment {
			return this._File;
		}
		public function set File(File:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflAttachment):void {
			this._File = File;
		}

	}
}
