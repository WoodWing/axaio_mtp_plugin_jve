/*
	Enterprise Publishing Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.pub.dataclasses
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.pub.dataclasses.PubPublishedObject")]

	public class PubPublishedObject
	{
		private var _ObjectId:String;
		private var _Version:String;
		private var _Name:String;
		private var _Type:String;
		private var _Format:String;

		public function PubPublishedObject() {
		}

		public function get ObjectId():String {
			return this._ObjectId;
		}
		public function set ObjectId(ObjectId:String):void {
			this._ObjectId = ObjectId;
		}

		public function get Version():String {
			return this._Version;
		}
		public function set Version(Version:String):void {
			this._Version = Version;
		}

		public function get Name():String {
			return this._Name;
		}
		public function set Name(Name:String):void {
			this._Name = Name;
		}

		public function get Type():String {
			return this._Type;
		}
		public function set Type(Type:String):void {
			this._Type = Type;
		}

		public function get Format():String {
			return this._Format;
		}
		public function set Format(Format:String):void {
			this._Format = Format;
		}

	}
}
