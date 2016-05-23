/*
	Enterprise DataSource Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.dat.dataclasses
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.dat.dataclasses.DatPlacement")]

	public class DatPlacement
	{
		private var _ObjectID:String;
		private var _PlacedQueries:Array;

		public function DatPlacement() {
		}

		public function get ObjectID():String {
			return this._ObjectID;
		}
		public function set ObjectID(ObjectID:String):void {
			this._ObjectID = ObjectID;
		}

		public function get PlacedQueries():Array {
			return this._PlacedQueries;
		}
		public function set PlacedQueries(PlacedQueries:Array):void {
			this._PlacedQueries = PlacedQueries;
		}

	}
}
