/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl.dataclasses
{
	import com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflState;

	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflRoutingMetaData")]

	public class WflRoutingMetaData
	{
		private var _ID:String;
		private var _State:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflState;
		private var _RouteTo:String;

		public function WflRoutingMetaData() {
		}

		public function get ID():String {
			return this._ID;
		}
		public function set ID(ID:String):void {
			this._ID = ID;
		}

		public function get State():com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflState {
			return this._State;
		}
		public function set State(State:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflState):void {
			this._State = State;
		}

		public function get RouteTo():String {
			return this._RouteTo;
		}
		public function set RouteTo(RouteTo:String):void {
			this._RouteTo = RouteTo;
		}

	}
}
