/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl.dataclasses
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflElement")]

	public class WflElement
	{
		private var _ID:String;
		private var _Name:String;
		private var _LengthWords:Number;
		private var _LengthChars:Number;
		private var _LengthParas:Number;
		private var _LengthLines:Number;
		private var _Snippet:String;
		private var _Version:String;
		private var _Content:String;

		public function WflElement() {
		}

		public function get ID():String {
			return this._ID;
		}
		public function set ID(ID:String):void {
			this._ID = ID;
		}

		public function get Name():String {
			return this._Name;
		}
		public function set Name(Name:String):void {
			this._Name = Name;
		}

		public function get LengthWords():Number {
			return this._LengthWords;
		}
		public function set LengthWords(LengthWords:Number):void {
			this._LengthWords = LengthWords;
		}

		public function get LengthChars():Number {
			return this._LengthChars;
		}
		public function set LengthChars(LengthChars:Number):void {
			this._LengthChars = LengthChars;
		}

		public function get LengthParas():Number {
			return this._LengthParas;
		}
		public function set LengthParas(LengthParas:Number):void {
			this._LengthParas = LengthParas;
		}

		public function get LengthLines():Number {
			return this._LengthLines;
		}
		public function set LengthLines(LengthLines:Number):void {
			this._LengthLines = LengthLines;
		}

		public function get Snippet():String {
			return this._Snippet;
		}
		public function set Snippet(Snippet:String):void {
			this._Snippet = Snippet;
		}

		public function get Version():String {
			return this._Version;
		}
		public function set Version(Version:String):void {
			this._Version = Version;
		}

		public function get Content():String {
			return this._Content;
		}
		public function set Content(Content:String):void {
			this._Content = Content;
		}

	}
}
