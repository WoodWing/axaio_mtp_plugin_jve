/*
	Enterprise Admin Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.adm
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.adm.AdmAddUsersToGroupRequest")]

	public class AdmAddUsersToGroupRequest
	{
		private var _Ticket:String;
		private var _UserIds:Array;
		private var _GroupId:Number;

		public function AdmAddUsersToGroupRequest() {
		}

		public function get Ticket():String {
			return this._Ticket;
		}
		public function set Ticket(Ticket:String):void {
			this._Ticket = Ticket;
		}

		public function get UserIds():Array {
			return this._UserIds;
		}
		public function set UserIds(UserIds:Array):void {
			this._UserIds = UserIds;
		}

		public function get GroupId():Number {
			return this._GroupId;
		}
		public function set GroupId(GroupId:Number):void {
			this._GroupId = GroupId;
		}

	}
}
