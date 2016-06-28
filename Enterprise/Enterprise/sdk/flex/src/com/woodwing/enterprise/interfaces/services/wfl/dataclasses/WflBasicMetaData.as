/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl.dataclasses
{
	import com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflPublication;
	import com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflCategory;

	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflBasicMetaData")]

	public class WflBasicMetaData
	{
		private var _ID:String;
		private var _DocumentID:String;
		private var _Name:String;
		private var _Type:String;
		private var _Publication:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflPublication;
		private var _Category:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflCategory;
		private var _ContentSource:String;

		public function WflBasicMetaData() {
		}

		public function get ID():String {
			return this._ID;
		}
		public function set ID(ID:String):void {
			this._ID = ID;
		}

		public function get DocumentID():String {
			return this._DocumentID;
		}
		public function set DocumentID(DocumentID:String):void {
			this._DocumentID = DocumentID;
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

		public function get Publication():com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflPublication {
			return this._Publication;
		}
		public function set Publication(Publication:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflPublication):void {
			this._Publication = Publication;
		}

		public function get Category():com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflCategory {
			return this._Category;
		}
		public function set Category(Category:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflCategory):void {
			this._Category = Category;
		}

		public function get ContentSource():String {
			return this._ContentSource;
		}
		public function set ContentSource(ContentSource:String):void {
			this._ContentSource = ContentSource;
		}

	}
}
