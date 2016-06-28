/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl
{
	import com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflDialog;
	import com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflMetaData;

	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.WflGetDialog2Response")]

	public class WflGetDialog2Response
	{
		private var _Dialog:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflDialog;
		private var _PubChannels:Array;
		private var _MetaData:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflMetaData;
		private var _Targets:Array;
		private var _RelatedTargets:Array;
		private var _Relations:Array;

		public function WflGetDialog2Response() {
		}

		public function get Dialog():com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflDialog {
			return this._Dialog;
		}
		public function set Dialog(Dialog:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflDialog):void {
			this._Dialog = Dialog;
		}

		public function get PubChannels():Array {
			return this._PubChannels;
		}
		public function set PubChannels(PubChannels:Array):void {
			this._PubChannels = PubChannels;
		}

		public function get MetaData():com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflMetaData {
			return this._MetaData;
		}
		public function set MetaData(MetaData:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflMetaData):void {
			this._MetaData = MetaData;
		}

		public function get Targets():Array {
			return this._Targets;
		}
		public function set Targets(Targets:Array):void {
			this._Targets = Targets;
		}

		public function get RelatedTargets():Array {
			return this._RelatedTargets;
		}
		public function set RelatedTargets(RelatedTargets:Array):void {
			this._RelatedTargets = RelatedTargets;
		}

		public function get Relations():Array {
			return this._Relations;
		}
		public function set Relations(Relations:Array):void {
			this._Relations = Relations;
		}

	}
}
