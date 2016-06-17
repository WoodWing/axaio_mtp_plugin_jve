/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl.dataclasses
{
	import com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflState;

	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflPlacedObject")]

	public class WflPlacedObject
	{
		private var _Id:String;
		private var _Name:String;
		private var _Type:String;
		private var _State:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflState;
		private var _Version:String;
		private var _LockedBy:String;
		private var _Format:String;

		public function WflPlacedObject() {
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

		public function get Type():String {
			return this._Type;
		}
		public function set Type(Type:String):void {
			this._Type = Type;
		}

		public function get State():com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflState {
			return this._State;
		}
		public function set State(State:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflState):void {
			this._State = State;
		}

		public function get Version():String {
			return this._Version;
		}
		public function set Version(Version:String):void {
			this._Version = Version;
		}

		public function get LockedBy():String {
			return this._LockedBy;
		}
		public function set LockedBy(LockedBy:String):void {
			this._LockedBy = LockedBy;
		}

		public function get Format():String {
			return this._Format;
		}
		public function set Format(Format:String):void {
			this._Format = Format;
		}

	}
}
