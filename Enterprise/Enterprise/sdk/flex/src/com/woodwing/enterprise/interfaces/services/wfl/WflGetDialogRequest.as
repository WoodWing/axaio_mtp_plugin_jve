/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.WflGetDialogRequest")]

	public class WflGetDialogRequest
	{
		private var _Ticket:String;
		private var _ID:String;
		private var _Publication:String;
		private var _Issue:String;
		private var _Section:String;
		private var _State:String;
		private var _Type:String;
		private var _Action:String;
		private var _RequestDialog:String;
		private var _RequestPublication:String;
		private var _RequestMetaData:String;
		private var _RequestStates:String;
		private var _RequestTargets:String;
		private var _DefaultDossier:String;
		private var _Parent:String;
		private var _Template:String;
		private var _Areas:Array;

		public function WflGetDialogRequest() {
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

		public function get Publication():String {
			return this._Publication;
		}
		public function set Publication(Publication:String):void {
			this._Publication = Publication;
		}

		public function get Issue():String {
			return this._Issue;
		}
		public function set Issue(Issue:String):void {
			this._Issue = Issue;
		}

		public function get Section():String {
			return this._Section;
		}
		public function set Section(Section:String):void {
			this._Section = Section;
		}

		public function get State():String {
			return this._State;
		}
		public function set State(State:String):void {
			this._State = State;
		}

		public function get Type():String {
			return this._Type;
		}
		public function set Type(Type:String):void {
			this._Type = Type;
		}

		public function get Action():String {
			return this._Action;
		}
		public function set Action(Action:String):void {
			this._Action = Action;
		}


		// _RequestDialog should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function get RequestDialog():String {
			return this._RequestDialog;
		}

		// _RequestDialog should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function set RequestDialog(RequestDialog:String):void {
			this._RequestDialog = RequestDialog;
		}


		// _RequestPublication should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function get RequestPublication():String {
			return this._RequestPublication;
		}

		// _RequestPublication should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function set RequestPublication(RequestPublication:String):void {
			this._RequestPublication = RequestPublication;
		}


		// _RequestMetaData should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function get RequestMetaData():String {
			return this._RequestMetaData;
		}

		// _RequestMetaData should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function set RequestMetaData(RequestMetaData:String):void {
			this._RequestMetaData = RequestMetaData;
		}


		// _RequestStates should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function get RequestStates():String {
			return this._RequestStates;
		}

		// _RequestStates should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function set RequestStates(RequestStates:String):void {
			this._RequestStates = RequestStates;
		}


		// _RequestTargets should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function get RequestTargets():String {
			return this._RequestTargets;
		}

		// _RequestTargets should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function set RequestTargets(RequestTargets:String):void {
			this._RequestTargets = RequestTargets;
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

	}
}
