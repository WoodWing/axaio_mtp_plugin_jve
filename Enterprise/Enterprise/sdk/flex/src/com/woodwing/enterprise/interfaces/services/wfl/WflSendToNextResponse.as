/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.WflSendToNextResponse")]

	public class WflSendToNextResponse
	{
		private var _RoutingMetaDatas:Array;
		private var _Reports:Array;

		public function WflSendToNextResponse() {
		}

		public function get RoutingMetaDatas():Array {
			return this._RoutingMetaDatas;
		}
		public function set RoutingMetaDatas(RoutingMetaDatas:Array):void {
			this._RoutingMetaDatas = RoutingMetaDatas;
		}

		public function get Reports():Array {
			return this._Reports;
		}
		public function set Reports(Reports:Array):void {
			this._Reports = Reports;
		}

	}
}
