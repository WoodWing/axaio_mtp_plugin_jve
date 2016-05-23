/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.WflRemoveObjectLabelsRequest")]

	public class WflRemoveObjectLabelsRequest
	{
		private var _Ticket:String;
		private var _ParentId:String;
		private var _ChildIds:Array;
		private var _ObjectLabels:Array;

		public function WflRemoveObjectLabelsRequest() {
		}

		public function get Ticket():String {
			return this._Ticket;
		}
		public function set Ticket(Ticket:String):void {
			this._Ticket = Ticket;
		}

		public function get ParentId():String {
			return this._ParentId;
		}
		public function set ParentId(ParentId:String):void {
			this._ParentId = ParentId;
		}

		public function get ChildIds():Array {
			return this._ChildIds;
		}
		public function set ChildIds(ChildIds:Array):void {
			this._ChildIds = ChildIds;
		}

		public function get ObjectLabels():Array {
			return this._ObjectLabels;
		}
		public function set ObjectLabels(ObjectLabels:Array):void {
			this._ObjectLabels = ObjectLabels;
		}

	}
}
