/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.WflCreateObjectLabelsRequest")]

	public class WflCreateObjectLabelsRequest
	{
		private var _Ticket:String;
		private var _ObjectId:String;
		private var _ObjectLabels:Array;

		public function WflCreateObjectLabelsRequest() {
		}

		public function get Ticket():String {
			return this._Ticket;
		}
		public function set Ticket(Ticket:String):void {
			this._Ticket = Ticket;
		}

		public function get ObjectId():String {
			return this._ObjectId;
		}
		public function set ObjectId(ObjectId:String):void {
			this._ObjectId = ObjectId;
		}

		public function get ObjectLabels():Array {
			return this._ObjectLabels;
		}
		public function set ObjectLabels(ObjectLabels:Array):void {
			this._ObjectLabels = ObjectLabels;
		}

	}
}
