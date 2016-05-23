/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.WflGetStatesResponse")]

	public class WflGetStatesResponse
	{
		private var _States:Array;
		private var _RouteToUsers:Array;
		private var _RouteToGroups:Array;

		public function WflGetStatesResponse() {
		}

		public function get States():Array {
			return this._States;
		}
		public function set States(States:Array):void {
			this._States = States;
		}

		public function get RouteToUsers():Array {
			return this._RouteToUsers;
		}
		public function set RouteToUsers(RouteToUsers:Array):void {
			this._RouteToUsers = RouteToUsers;
		}

		public function get RouteToGroups():Array {
			return this._RouteToGroups;
		}
		public function set RouteToGroups(RouteToGroups:Array):void {
			this._RouteToGroups = RouteToGroups;
		}

	}
}
