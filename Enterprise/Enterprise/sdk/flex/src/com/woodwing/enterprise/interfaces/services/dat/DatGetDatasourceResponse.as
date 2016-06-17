/*
	Enterprise DataSource Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.dat
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.dat.DatGetDatasourceResponse")]

	public class DatGetDatasourceResponse
	{
		private var _Queries:Array;
		private var _Bidirectional:String;

		public function DatGetDatasourceResponse() {
		}

		public function get Queries():Array {
			return this._Queries;
		}
		public function set Queries(Queries:Array):void {
			this._Queries = Queries;
		}

		public function get Bidirectional():String {
			return this._Bidirectional;
		}
		public function set Bidirectional(Bidirectional:String):void {
			this._Bidirectional = Bidirectional;
		}

	}
}
