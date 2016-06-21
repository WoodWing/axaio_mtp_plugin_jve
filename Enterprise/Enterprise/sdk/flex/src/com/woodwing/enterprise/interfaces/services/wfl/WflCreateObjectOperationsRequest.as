/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl
{
	import com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflObjectVersion;

	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.WflCreateObjectOperationsRequest")]

	public class WflCreateObjectOperationsRequest
	{
		private var _Ticket:String;
		private var _HaveVersion:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflObjectVersion;
		private var _Operations:Array;

		public function WflCreateObjectOperationsRequest() {
		}

		public function get Ticket():String {
			return this._Ticket;
		}
		public function set Ticket(Ticket:String):void {
			this._Ticket = Ticket;
		}

		public function get HaveVersion():com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflObjectVersion {
			return this._HaveVersion;
		}
		public function set HaveVersion(HaveVersion:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflObjectVersion):void {
			this._HaveVersion = HaveVersion;
		}

		public function get Operations():Array {
			return this._Operations;
		}
		public function set Operations(Operations:Array):void {
			this._Operations = Operations;
		}

	}
}
