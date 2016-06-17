/*
	Enterprise Admin Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.adm
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.adm.AdmGetUserGroupsRequest")]

	public class AdmGetUserGroupsRequest
	{
		private var _Ticket:String;
		private var _RequestModes:Array;
		private var _UserId:Number;
		private var _GroupIds:Array;

		public function AdmGetUserGroupsRequest() {
		}

		public function get Ticket():String {
			return this._Ticket;
		}
		public function set Ticket(Ticket:String):void {
			this._Ticket = Ticket;
		}

		public function get RequestModes():Array {
			return this._RequestModes;
		}
		public function set RequestModes(RequestModes:Array):void {
			this._RequestModes = RequestModes;
		}

		public function get UserId():Number {
			return this._UserId;
		}
		public function set UserId(UserId:Number):void {
			this._UserId = UserId;
		}

		public function get GroupIds():Array {
			return this._GroupIds;
		}
		public function set GroupIds(GroupIds:Array):void {
			this._GroupIds = GroupIds;
		}

	}
}
