/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.WflNamedQueryResponse")]

	public class WflNamedQueryResponse
	{
		private var _Columns:Array;
		private var _Rows:Array;
		private var _ChildColumns:Array;
		private var _ChildRows:Array;
		private var _ComponentColumns:Array;
		private var _ComponentRows:Array;
		private var _FirstEntry:Number;
		private var _ListedEntries:Number;
		private var _TotalEntries:Number;
		private var _UpdateID:String;
		private var _Facets:Array;
		private var _SearchFeatures:Array;

		public function WflNamedQueryResponse() {
		}

		public function get Columns():Array {
			return this._Columns;
		}
		public function set Columns(Columns:Array):void {
			this._Columns = Columns;
		}

		public function get Rows():Array {
			return this._Rows;
		}
		public function set Rows(Rows:Array):void {
			this._Rows = Rows;
		}

		public function get ChildColumns():Array {
			return this._ChildColumns;
		}
		public function set ChildColumns(ChildColumns:Array):void {
			this._ChildColumns = ChildColumns;
		}

		public function get ChildRows():Array {
			return this._ChildRows;
		}
		public function set ChildRows(ChildRows:Array):void {
			this._ChildRows = ChildRows;
		}

		public function get ComponentColumns():Array {
			return this._ComponentColumns;
		}
		public function set ComponentColumns(ComponentColumns:Array):void {
			this._ComponentColumns = ComponentColumns;
		}

		public function get ComponentRows():Array {
			return this._ComponentRows;
		}
		public function set ComponentRows(ComponentRows:Array):void {
			this._ComponentRows = ComponentRows;
		}

		public function get FirstEntry():Number {
			return this._FirstEntry;
		}
		public function set FirstEntry(FirstEntry:Number):void {
			this._FirstEntry = FirstEntry;
		}

		public function get ListedEntries():Number {
			return this._ListedEntries;
		}
		public function set ListedEntries(ListedEntries:Number):void {
			this._ListedEntries = ListedEntries;
		}

		public function get TotalEntries():Number {
			return this._TotalEntries;
		}
		public function set TotalEntries(TotalEntries:Number):void {
			this._TotalEntries = TotalEntries;
		}

		public function get UpdateID():String {
			return this._UpdateID;
		}
		public function set UpdateID(UpdateID:String):void {
			this._UpdateID = UpdateID;
		}

		public function get Facets():Array {
			return this._Facets;
		}
		public function set Facets(Facets:Array):void {
			this._Facets = Facets;
		}

		public function get SearchFeatures():Array {
			return this._SearchFeatures;
		}
		public function set SearchFeatures(SearchFeatures:Array):void {
			this._SearchFeatures = SearchFeatures;
		}

	}
}
