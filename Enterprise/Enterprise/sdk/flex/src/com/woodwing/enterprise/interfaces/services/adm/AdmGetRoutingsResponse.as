/*
	Enterprise Admin Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.adm
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.adm.AdmGetRoutingsResponse")]

	public class AdmGetRoutingsResponse
	{
		private var _Routings:Array;
		private var _Sections:Array;
		private var _Statuses:Array;

		public function AdmGetRoutingsResponse() {
		}

		public function get Routings():Array {
			return this._Routings;
		}
		public function set Routings(Routings:Array):void {
			this._Routings = Routings;
		}

		public function get Sections():Array {
			return this._Sections;
		}
		public function set Sections(Sections:Array):void {
			this._Sections = Sections;
		}

		public function get Statuses():Array {
			return this._Statuses;
		}
		public function set Statuses(Statuses:Array):void {
			this._Statuses = Statuses;
		}

	}
}
