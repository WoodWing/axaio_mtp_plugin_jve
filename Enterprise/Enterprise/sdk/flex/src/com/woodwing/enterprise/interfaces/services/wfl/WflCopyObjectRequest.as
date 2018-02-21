/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl
{
	import com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflMetaData;

	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.WflCopyObjectRequest")]

	public class WflCopyObjectRequest
	{
		private var _Ticket:String;
		private var _SourceID:String;
		private var _MetaData:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflMetaData;
		private var _Relations:Array;
		private var _Targets:Array;

		public function WflCopyObjectRequest() {
		}

		public function get Ticket():String {
			return this._Ticket;
		}
		public function set Ticket(Ticket:String):void {
			this._Ticket = Ticket;
		}

		public function get SourceID():String {
			return this._SourceID;
		}
		public function set SourceID(SourceID:String):void {
			this._SourceID = SourceID;
		}

		public function get MetaData():com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflMetaData {
			return this._MetaData;
		}
		public function set MetaData(MetaData:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflMetaData):void {
			this._MetaData = MetaData;
		}

		public function get Relations():Array {
			return this._Relations;
		}
		public function set Relations(Relations:Array):void {
			this._Relations = Relations;
		}

		public function get Targets():Array {
			return this._Targets;
		}
		public function set Targets(Targets:Array):void {
			this._Targets = Targets;
		}

	}
}