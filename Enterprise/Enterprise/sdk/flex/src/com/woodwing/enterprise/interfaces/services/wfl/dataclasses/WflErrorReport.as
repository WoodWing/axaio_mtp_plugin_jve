/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl.dataclasses
{
	import com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflErrorReportEntity;

	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflErrorReport")]

	public class WflErrorReport
	{
		private var _BelongsTo:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflErrorReportEntity;
		private var _Entries:Array;

		public function WflErrorReport() {
		}

		public function get BelongsTo():com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflErrorReportEntity {
			return this._BelongsTo;
		}
		public function set BelongsTo(BelongsTo:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflErrorReportEntity):void {
			this._BelongsTo = BelongsTo;
		}

		public function get Entries():Array {
			return this._Entries;
		}
		public function set Entries(Entries:Array):void {
			this._Entries = Entries;
		}

	}
}
