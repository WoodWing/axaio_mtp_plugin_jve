/*
	Enterprise Admin Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.adm
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.adm.AdmCreateAccessProfilesRequest")]

	public class AdmCreateAccessProfilesRequest
	{
		private var _Ticket:String;
		private var _RequestModes:Array;
		private var _AccessProfiles:Array;

		public function AdmCreateAccessProfilesRequest() {
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

		public function get AccessProfiles():Array {
			return this._AccessProfiles;
		}
		public function set AccessProfiles(AccessProfiles:Array):void {
			this._AccessProfiles = AccessProfiles;
		}

	}
}
