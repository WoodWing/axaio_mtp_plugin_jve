/*
	Enterprise DataSource Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.dat.dataclasses
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.dat.dataclasses.DatPlacedQuery")]

	public class DatPlacedQuery
	{
		private var _QueryID:String;
		private var _FamilyValues:Array;

		public function DatPlacedQuery() {
		}

		public function get QueryID():String {
			return this._QueryID;
		}
		public function set QueryID(QueryID:String):void {
			this._QueryID = QueryID;
		}

		public function get FamilyValues():Array {
			return this._FamilyValues;
		}
		public function set FamilyValues(FamilyValues:Array):void {
			this._FamilyValues = FamilyValues;
		}

	}
}
