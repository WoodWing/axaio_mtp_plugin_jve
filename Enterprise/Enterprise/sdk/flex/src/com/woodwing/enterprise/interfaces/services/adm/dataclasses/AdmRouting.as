/*
	Enterprise Admin Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.adm.dataclasses
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.adm.dataclasses.AdmRouting")]

	public class AdmRouting
	{
		private var _Id:Number;
		private var _SectionId:Number;
		private var _StatusId:Number;
		private var _RouteTo:String;

		public function AdmRouting() {
		}

		public function get Id():Number {
			return this._Id;
		}
		public function set Id(Id:Number):void {
			this._Id = Id;
		}

		public function get SectionId():Number {
			return this._SectionId;
		}
		public function set SectionId(SectionId:Number):void {
			this._SectionId = SectionId;
		}

		public function get StatusId():Number {
			return this._StatusId;
		}
		public function set StatusId(StatusId:Number):void {
			this._StatusId = StatusId;
		}

		public function get RouteTo():String {
			return this._RouteTo;
		}
		public function set RouteTo(RouteTo:String):void {
			this._RouteTo = RouteTo;
		}

	}
}
