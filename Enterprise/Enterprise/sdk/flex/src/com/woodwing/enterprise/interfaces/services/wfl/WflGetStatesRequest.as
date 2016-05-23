/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl
{
	import com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflPublication;
	import com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflIssue;
	import com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflCategory;

	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.WflGetStatesRequest")]

	public class WflGetStatesRequest
	{
		private var _Ticket:String;
		private var _ID:String;
		private var _Publication:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflPublication;
		private var _Issue:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflIssue;
		private var _Section:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflCategory;
		private var _Type:String;

		public function WflGetStatesRequest() {
		}

		public function get Ticket():String {
			return this._Ticket;
		}
		public function set Ticket(Ticket:String):void {
			this._Ticket = Ticket;
		}

		public function get ID():String {
			return this._ID;
		}
		public function set ID(ID:String):void {
			this._ID = ID;
		}

		public function get Publication():com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflPublication {
			return this._Publication;
		}
		public function set Publication(Publication:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflPublication):void {
			this._Publication = Publication;
		}

		public function get Issue():com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflIssue {
			return this._Issue;
		}
		public function set Issue(Issue:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflIssue):void {
			this._Issue = Issue;
		}

		public function get Section():com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflCategory {
			return this._Section;
		}
		public function set Section(Section:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflCategory):void {
			this._Section = Section;
		}

		public function get Type():String {
			return this._Type;
		}
		public function set Type(Type:String):void {
			this._Type = Type;
		}

	}
}
