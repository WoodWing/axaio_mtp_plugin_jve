/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl.dataclasses
{
	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflPropertyInfo")]

	public class WflPropertyInfo
	{
		private var _Name:String;
		private var _DisplayName:String;
		private var _Category:String;
		private var _Type:String;
		private var _DefaultValue:String;
		private var _ValueList:Array;
		private var _MinValue:String;
		private var _MaxValue:String;
		private var _MaxLength:Number;
		private var _PropertyValues:Array;
		private var _ParentValue:String;
		private var _DependentProperties:Array;
		private var _MinResolution:String;
		private var _MaxResolution:String;
		private var _Widgets:Array;
		private var _TermEntity:String;
		private var _SuggestionEntity:String;
		private var _AutocompleteProvider:String;
		private var _SuggestionProvider:String;
		private var _PublishSystemId:String;
		private var _Notifications:Array;
		private var _MixedValues:String;

		public function WflPropertyInfo() {
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

		public function get Category():String {
			return this._Category;
		}
		public function set Category(Category:String):void {
			this._Category = Category;
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

		public function get PropertyValues():Array {
			return this._PropertyValues;
		}
		public function set PropertyValues(PropertyValues:Array):void {
			this._PropertyValues = PropertyValues;
		}

		public function get ParentValue():String {
			return this._ParentValue;
		}
		public function set ParentValue(ParentValue:String):void {
			this._ParentValue = ParentValue;
		}

		public function get DependentProperties():Array {
			return this._DependentProperties;
		}
		public function set DependentProperties(DependentProperties:Array):void {
			this._DependentProperties = DependentProperties;
		}

		public function get MinResolution():String {
			return this._MinResolution;
		}
		public function set MinResolution(MinResolution:String):void {
			this._MinResolution = MinResolution;
		}

		public function get MaxResolution():String {
			return this._MaxResolution;
		}
		public function set MaxResolution(MaxResolution:String):void {
			this._MaxResolution = MaxResolution;
		}

		public function get Widgets():Array {
			return this._Widgets;
		}
		public function set Widgets(Widgets:Array):void {
			this._Widgets = Widgets;
		}

		public function get TermEntity():String {
			return this._TermEntity;
		}
		public function set TermEntity(TermEntity:String):void {
			this._TermEntity = TermEntity;
		}

		public function get SuggestionEntity():String {
			return this._SuggestionEntity;
		}
		public function set SuggestionEntity(SuggestionEntity:String):void {
			this._SuggestionEntity = SuggestionEntity;
		}

		public function get AutocompleteProvider():String {
			return this._AutocompleteProvider;
		}
		public function set AutocompleteProvider(AutocompleteProvider:String):void {
			this._AutocompleteProvider = AutocompleteProvider;
		}

		public function get SuggestionProvider():String {
			return this._SuggestionProvider;
		}
		public function set SuggestionProvider(SuggestionProvider:String):void {
			this._SuggestionProvider = SuggestionProvider;
		}

		public function get PublishSystemId():String {
			return this._PublishSystemId;
		}
		public function set PublishSystemId(PublishSystemId:String):void {
			this._PublishSystemId = PublishSystemId;
		}

		public function get Notifications():Array {
			return this._Notifications;
		}
		public function set Notifications(Notifications:Array):void {
			this._Notifications = Notifications;
		}


		// _MixedValues should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function get MixedValues():String {
			return this._MixedValues;
		}

		// _MixedValues should be handled like a Boolean, but since Boolean is not a nillable type
		// we handle it like a String to be able to send it nillable to the server. 
		public function set MixedValues(MixedValues:String):void {
			this._MixedValues = MixedValues;
		}

	}
}
