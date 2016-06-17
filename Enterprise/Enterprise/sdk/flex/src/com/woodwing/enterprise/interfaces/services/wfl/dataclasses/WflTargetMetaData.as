/*
	Enterprise Workflow Services
	Copyright (c) WoodWing Software bv. All Rights Reserved.

	IMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!
*/

package com.woodwing.enterprise.interfaces.services.wfl.dataclasses
{
	import com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflPublication;
	import com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflIssue;
	import com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflCategory;

	[Bindable]
	[RemoteClass(alias="com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflTargetMetaData")]

	public class WflTargetMetaData
	{
		public function WflTargetMetaData() {}

		public var Publication:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflPublication;
		public var Issue:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflIssue;
		public var Section:com.woodwing.enterprise.interfaces.services.wfl.dataclasses.WflCategory;
		public var Editions:Array;
	}
}
