/*
	Enterprise DataSource Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.dat
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.dat.DatQueryDatasourcesResponse")]

	public class DatQueryDatasourcesResponse
	{
		private var _Datasources:Array;

		public function DatQueryDatasourcesResponse() {
		}

		public function get Datasources():Array {
			return this._Datasources;
		}
		public function set Datasources(Datasources:Array):void {
			this._Datasources = Datasources;
		}

	}
}
