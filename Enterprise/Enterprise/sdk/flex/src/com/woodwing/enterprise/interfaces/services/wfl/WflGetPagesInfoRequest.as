/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl
{
	import com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflIssue;
	import com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflEdition;
	import com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflCategory;
	import com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflState;

	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.WflGetPagesInfoRequest")]

	public class WflGetPagesInfoRequest
	{
		private var _Ticket:String;
		private var _Issue:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflIssue;
		private var _IDs:Array;
		private var _Edition:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflEdition;
		private var _Category:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflCategory;
		private var _State:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflState;

		public function WflGetPagesInfoRequest() {
		}

		public function get Ticket():String {
			return this._Ticket;
		}
		public function set Ticket(Ticket:String):void {
			this._Ticket = Ticket;
		}

		public function get Issue():com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflIssue {
			return this._Issue;
		}
		public function set Issue(Issue:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflIssue):void {
			this._Issue = Issue;
		}

		public function get IDs():Array {
			return this._IDs;
		}
		public function set IDs(IDs:Array):void {
			this._IDs = IDs;
		}

		public function get Edition():com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflEdition {
			return this._Edition;
		}
		public function set Edition(Edition:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflEdition):void {
			this._Edition = Edition;
		}

		public function get Category():com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflCategory {
			return this._Category;
		}
		public function set Category(Category:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflCategory):void {
			this._Category = Category;
		}

		public function get State():com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflState {
			return this._State;
		}
		public function set State(State:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflState):void {
			this._State = State;
		}

	}
}
