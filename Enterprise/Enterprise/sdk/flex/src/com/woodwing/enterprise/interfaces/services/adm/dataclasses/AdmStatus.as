/*
	Enterprise Admin Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.adm.dataclasses
{
	import com.woodwing.enterprise.interfaces.services.adm.dataclasses.AdmIdName;

	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.adm.dataclasses.AdmStatus")]

	public class AdmStatus
	{
		private var _Id:Number;
		private var _Name:String;
		private var _SortOrder:Number;
		private var _Type:String;
		private var _Produce:String;
		private var _Color:String;
		private var _DefaultRouteTo:com.woodwing.enterprise.interfaces.services.adm.dataclasses.AdmIdName;
		private var _CreatePermanentVersion:String;
		private var _RemoveIntermediateVersions:String;
		private var _AutomaticallySendToNext:String;
		private var _ReadyForPublishing:String;
		private var _Phase:String;
		private var _SkipIdsa:String;

		public function AdmStatus() {
		}

		public function get Id():Number {
			return this._Id;
		}
		public function set Id(Id:Number):void {
			this._Id = Id;
		}

		public function get Name():String {
			return this._Name;
		}
		public function set Name(Name:String):void {
			this._Name = Name;
		}

		public function get SortOrder():Number {
			return this._SortOrder;
		}
		public function set SortOrder(SortOrder:Number):void {
			this._SortOrder = SortOrder;
		}

		public function get Type():String {
			return this._Type;
		}
		public function set Type(Type:String):void {
			this._Type = Type;
		}


		// _Produce should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function get Produce():String {
			return this._Produce;
		}

		// _Produce should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function set Produce(Produce:String):void {
			this._Produce = Produce;
		}

		public function get Color():String {
			return this._Color;
		}
		public function set Color(Color:String):void {
			this._Color = Color;
		}

		public function get DefaultRouteTo():com.woodwing.enterprise.interfaces.services.adm.dataclasses.AdmIdName {
			return this._DefaultRouteTo;
		}
		public function set DefaultRouteTo(DefaultRouteTo:com.woodwing.enterprise.interfaces.services.adm.dataclasses.AdmIdName):void {
			this._DefaultRouteTo = DefaultRouteTo;
		}


		// _CreatePermanentVersion should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function get CreatePermanentVersion():String {
			return this._CreatePermanentVersion;
		}

		// _CreatePermanentVersion should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function set CreatePermanentVersion(CreatePermanentVersion:String):void {
			this._CreatePermanentVersion = CreatePermanentVersion;
		}


		// _RemoveIntermediateVersions should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function get RemoveIntermediateVersions():String {
			return this._RemoveIntermediateVersions;
		}

		// _RemoveIntermediateVersions should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function set RemoveIntermediateVersions(RemoveIntermediateVersions:String):void {
			this._RemoveIntermediateVersions = RemoveIntermediateVersions;
		}


		// _AutomaticallySendToNext should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function get AutomaticallySendToNext():String {
			return this._AutomaticallySendToNext;
		}

		// _AutomaticallySendToNext should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function set AutomaticallySendToNext(AutomaticallySendToNext:String):void {
			this._AutomaticallySendToNext = AutomaticallySendToNext;
		}


		// _ReadyForPublishing should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function get ReadyForPublishing():String {
			return this._ReadyForPublishing;
		}

		// _ReadyForPublishing should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function set ReadyForPublishing(ReadyForPublishing:String):void {
			this._ReadyForPublishing = ReadyForPublishing;
		}

		public function get Phase():String {
			return this._Phase;
		}
		public function set Phase(Phase:String):void {
			this._Phase = Phase;
		}


		// _SkipIdsa should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function get SkipIdsa():String {
			return this._SkipIdsa;
		}

		// _SkipIdsa should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function set SkipIdsa(SkipIdsa:String):void {
			this._SkipIdsa = SkipIdsa;
		}

	}
}
