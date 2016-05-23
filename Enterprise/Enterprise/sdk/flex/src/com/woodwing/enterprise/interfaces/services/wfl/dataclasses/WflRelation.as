/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl.dataclasses
{
	import com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflAttachment;
	import com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflObjectInfo;

	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflRelation")]

	public class WflRelation
	{
		private var _Parent:String;
		private var _Child:String;
		private var _Type:String;
		private var _Placements:Array;
		private var _ParentVersion:String;
		private var _ChildVersion:String;
		private var _Geometry:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflAttachment;
		private var _Rating:Number;
		private var _Targets:Array;
		private var _ParentInfo:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflObjectInfo;
		private var _ChildInfo:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflObjectInfo;
		private var _ObjectLabels:Array;

		public function WflRelation() {
		}

		public function get Parent():String {
			return this._Parent;
		}
		public function set Parent(Parent:String):void {
			this._Parent = Parent;
		}

		public function get Child():String {
			return this._Child;
		}
		public function set Child(Child:String):void {
			this._Child = Child;
		}

		public function get Type():String {
			return this._Type;
		}
		public function set Type(Type:String):void {
			this._Type = Type;
		}

		public function get Placements():Array {
			return this._Placements;
		}
		public function set Placements(Placements:Array):void {
			this._Placements = Placements;
		}

		public function get ParentVersion():String {
			return this._ParentVersion;
		}
		public function set ParentVersion(ParentVersion:String):void {
			this._ParentVersion = ParentVersion;
		}

		public function get ChildVersion():String {
			return this._ChildVersion;
		}
		public function set ChildVersion(ChildVersion:String):void {
			this._ChildVersion = ChildVersion;
		}

		public function get Geometry():com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflAttachment {
			return this._Geometry;
		}
		public function set Geometry(Geometry:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflAttachment):void {
			this._Geometry = Geometry;
		}

		public function get Rating():Number {
			return this._Rating;
		}
		public function set Rating(Rating:Number):void {
			this._Rating = Rating;
		}

		public function get Targets():Array {
			return this._Targets;
		}
		public function set Targets(Targets:Array):void {
			this._Targets = Targets;
		}

		public function get ParentInfo():com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflObjectInfo {
			return this._ParentInfo;
		}
		public function set ParentInfo(ParentInfo:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflObjectInfo):void {
			this._ParentInfo = ParentInfo;
		}

		public function get ChildInfo():com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflObjectInfo {
			return this._ChildInfo;
		}
		public function set ChildInfo(ChildInfo:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflObjectInfo):void {
			this._ChildInfo = ChildInfo;
		}

		public function get ObjectLabels():Array {
			return this._ObjectLabels;
		}
		public function set ObjectLabels(ObjectLabels:Array):void {
			this._ObjectLabels = ObjectLabels;
		}

	}
}
