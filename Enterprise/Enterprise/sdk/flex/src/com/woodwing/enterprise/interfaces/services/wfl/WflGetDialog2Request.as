/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.WflGetDialog2Request")]

	public class WflGetDialog2Request
	{
		private var _Ticket:String;
		private var _Action:String;
		private var _MetaData:Array;
		private var _Targets:Array;
		private var _DefaultDossier:String;
		private var _Parent:String;
		private var _Template:String;
		private var _Areas:Array;
		private var _MultipleObjects:String;

		public function WflGetDialog2Request() {
		}

		public function get Ticket():String {
			return this._Ticket;
		}
		public function set Ticket(Ticket:String):void {
			this._Ticket = Ticket;
		}

		public function get Action():String {
			return this._Action;
		}
		public function set Action(Action:String):void {
			this._Action = Action;
		}

		public function get MetaData():Array {
			return this._MetaData;
		}
		public function set MetaData(MetaData:Array):void {
			this._MetaData = MetaData;
		}

		public function get Targets():Array {
			return this._Targets;
		}
		public function set Targets(Targets:Array):void {
			this._Targets = Targets;
		}

		public function get DefaultDossier():String {
			return this._DefaultDossier;
		}
		public function set DefaultDossier(DefaultDossier:String):void {
			this._DefaultDossier = DefaultDossier;
		}

		public function get Parent():String {
			return this._Parent;
		}
		public function set Parent(Parent:String):void {
			this._Parent = Parent;
		}

		public function get Template():String {
			return this._Template;
		}
		public function set Template(Template:String):void {
			this._Template = Template;
		}

		public function get Areas():Array {
			return this._Areas;
		}
		public function set Areas(Areas:Array):void {
			this._Areas = Areas;
		}


		// _MultipleObjects should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function get MultipleObjects():String {
			return this._MultipleObjects;
		}

		// _MultipleObjects should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function set MultipleObjects(MultipleObjects:String):void {
			this._MultipleObjects = MultipleObjects;
		}

	}
}
