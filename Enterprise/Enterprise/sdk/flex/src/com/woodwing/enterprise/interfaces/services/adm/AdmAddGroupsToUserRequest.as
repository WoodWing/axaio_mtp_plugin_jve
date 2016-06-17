/*
	Enterprise Admin Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.adm
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.adm.AdmAddGroupsToUserRequest")]

	public class AdmAddGroupsToUserRequest
	{
		private var _Ticket:String;
		private var _GroupIds:Array;
		private var _UserId:Number;

		public function AdmAddGroupsToUserRequest() {
		}

		public function get Ticket():String {
			return this._Ticket;
		}
		public function set Ticket(Ticket:String):void {
			this._Ticket = Ticket;
		}

		public function get GroupIds():Array {
			return this._GroupIds;
		}
		public function set GroupIds(GroupIds:Array):void {
			this._GroupIds = GroupIds;
		}

		public function get UserId():Number {
			return this._UserId;
		}
		public function set UserId(UserId:Number):void {
			this._UserId = UserId;
		}

	}
}
