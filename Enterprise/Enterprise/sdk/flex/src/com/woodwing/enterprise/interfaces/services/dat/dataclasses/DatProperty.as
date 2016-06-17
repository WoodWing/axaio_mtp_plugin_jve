/*
	Enterprise DataSource Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.dat.dataclasses
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.dat.dataclasses.DatProperty")]

	public class DatProperty
	{
		private var _Name:String;
		private var _DisplayName:String;
		private var _Type:String;
		private var _DefaultValue:String;
		private var _ValueList:Array;
		private var _MinValue:String;
		private var _MaxValue:String;
		private var _MaxLength:Number;

		public function DatProperty() {
		}

		public function get Name():String {
			return this._Name;
		}
		public function set Name(Name:String):void {
			this._Name = Name;
		}

		public function get DisplayName():String {
			return this._DisplayName;
		}
		public function set DisplayName(DisplayName:String):void {
			this._DisplayName = DisplayName;
		}

		public function get Type():String {
			return this._Type;
		}
		public function set Type(Type:String):void {
			this._Type = Type;
		}

		public function get DefaultValue():String {
			return this._DefaultValue;
		}
		public function set DefaultValue(DefaultValue:String):void {
			this._DefaultValue = DefaultValue;
		}

		public function get ValueList():Array {
			return this._ValueList;
		}
		public function set ValueList(ValueList:Array):void {
			this._ValueList = ValueList;
		}

		public function get MinValue():String {
			return this._MinValue;
		}
		public function set MinValue(MinValue:String):void {
			this._MinValue = MinValue;
		}

		public function get MaxValue():String {
			return this._MaxValue;
		}
		public function set MaxValue(MaxValue:String):void {
			this._MaxValue = MaxValue;
		}

		public function get MaxLength():Number {
			return this._MaxLength;
		}
		public function set MaxLength(MaxLength:Number):void {
			this._MaxLength = MaxLength;
		}

	}
}
