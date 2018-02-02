/**
 * SmartConnectionBindingStub.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.wfl;

public class SmartConnectionBindingStub extends org.apache.axis.client.Stub implements com.woodwing.enterprise.interfaces.services.wfl.SmartConnectionPort {
    private java.util.Vector cachedSerClasses = new java.util.Vector();
    private java.util.Vector cachedSerQNames = new java.util.Vector();
    private java.util.Vector cachedSerFactories = new java.util.Vector();
    private java.util.Vector cachedDeserFactories = new java.util.Vector();

    static org.apache.axis.description.OperationDesc [] _operations;

    static {
        _operations = new org.apache.axis.description.OperationDesc[57];
        _initOperationDesc1();
        _initOperationDesc2();
        _initOperationDesc3();
        _initOperationDesc4();
        _initOperationDesc5();
        _initOperationDesc6();
    }

    private static void _initOperationDesc1(){
        org.apache.axis.description.OperationDesc oper;
        org.apache.axis.description.ParameterDesc param;
        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("GetServers");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnection", "GetServers"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "anyType"), java.lang.Object.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:SmartConnection", ">GetServersResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.wfl.GetServersResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:SmartConnection", "GetServersResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[0] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("LogOn");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnection", "LogOn"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnection", ">LogOn"), com.woodwing.enterprise.interfaces.services.wfl.LogOn.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:SmartConnection", ">LogOnResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.wfl.LogOnResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:SmartConnection", "LogOnResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[1] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("LogOff");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnection", "LogOff"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnection", ">LogOff"), com.woodwing.enterprise.interfaces.services.wfl.LogOff.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(org.apache.axis.encoding.XMLType.AXIS_VOID);
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[2] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("GetUserSettings");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnection", "GetUserSettings"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnection", ">GetUserSettings"), com.woodwing.enterprise.interfaces.services.wfl.GetUserSettings.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:SmartConnection", ">GetUserSettingsResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.wfl.GetUserSettingsResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:SmartConnection", "GetUserSettingsResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[3] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("SaveUserSettings");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnection", "SaveUserSettings"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnection", ">SaveUserSettings"), com.woodwing.enterprise.interfaces.services.wfl.SaveUserSettings.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(org.apache.axis.encoding.XMLType.AXIS_VOID);
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[4] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("DeleteUserSettings");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnection", "DeleteUserSettings"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnection", ">DeleteUserSettings"), com.woodwing.enterprise.interfaces.services.wfl.DeleteUserSettings.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(org.apache.axis.encoding.XMLType.AXIS_VOID);
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[5] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("GetStates");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnection", "GetStates"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnection", ">GetStates"), com.woodwing.enterprise.interfaces.services.wfl.GetStates.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:SmartConnection", "GetStatesResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.wfl.GetStatesResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:SmartConnection", "GetStatesResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[6] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("CreateObjects");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnection", "CreateObjects"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnection", ">CreateObjects"), com.woodwing.enterprise.interfaces.services.wfl.CreateObjects.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:SmartConnection", ">CreateObjectsResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.wfl.CreateObjectsResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:SmartConnection", "CreateObjectsResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[7] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("InstantiateTemplate");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnection", "InstantiateTemplate"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnection", ">InstantiateTemplate"), com.woodwing.enterprise.interfaces.services.wfl.InstantiateTemplate.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:SmartConnection", ">InstantiateTemplateResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.wfl.InstantiateTemplateResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:SmartConnection", "InstantiateTemplateResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[8] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("GetObjects");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnection", "GetObjects"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnection", ">GetObjects"), com.woodwing.enterprise.interfaces.services.wfl.GetObjects.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:SmartConnection", ">GetObjectsResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.wfl.GetObjectsResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:SmartConnection", "GetObjectsResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[9] = oper;

    }

    private static void _initOperationDesc2(){
        org.apache.axis.description.OperationDesc oper;
        org.apache.axis.description.ParameterDesc param;
        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("QueryObjects");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnection", "QueryObjects"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnection", ">QueryObjects"), com.woodwing.enterprise.interfaces.services.wfl.QueryObjects.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:SmartConnection", ">QueryObjectsResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.wfl.QueryObjectsResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:SmartConnection", "QueryObjectsResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[10] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("SaveObjects");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnection", "SaveObjects"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnection", ">SaveObjects"), com.woodwing.enterprise.interfaces.services.wfl.SaveObjects.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:SmartConnection", ">SaveObjectsResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.wfl.SaveObjectsResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:SmartConnection", "SaveObjectsResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[11] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("LockObjects");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnection", "LockObjects"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnection", ">LockObjects"), com.woodwing.enterprise.interfaces.services.wfl.LockObjects.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:SmartConnection", ">LockObjectsResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.wfl.LockObjectsResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:SmartConnection", "LockObjectsResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[12] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("UnlockObjects");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnection", "UnlockObjects"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnection", ">UnlockObjects"), com.woodwing.enterprise.interfaces.services.wfl.UnlockObjects.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:SmartConnection", ">UnlockObjectsResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.wfl.UnlockObjectsResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:SmartConnection", "UnlockObjectsResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[13] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("DeleteObjects");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnection", "DeleteObjects"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnection", ">DeleteObjects"), com.woodwing.enterprise.interfaces.services.wfl.DeleteObjects.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:SmartConnection", ">DeleteObjectsResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.wfl.DeleteObjectsResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:SmartConnection", "DeleteObjectsResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[14] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("RestoreObjects");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnection", "RestoreObjects"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnection", ">RestoreObjects"), com.woodwing.enterprise.interfaces.services.wfl.RestoreObjects.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:SmartConnection", ">RestoreObjectsResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.wfl.RestoreObjectsResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:SmartConnection", "RestoreObjectsResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[15] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("CreateObjectRelations");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnection", "CreateObjectRelations"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnection", ">CreateObjectRelations"), com.woodwing.enterprise.interfaces.services.wfl.CreateObjectRelations.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:SmartConnection", ">CreateObjectRelationsResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.wfl.CreateObjectRelationsResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:SmartConnection", "CreateObjectRelationsResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[16] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("UpdateObjectRelations");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnection", "UpdateObjectRelations"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnection", ">UpdateObjectRelations"), com.woodwing.enterprise.interfaces.services.wfl.UpdateObjectRelations.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:SmartConnection", ">UpdateObjectRelationsResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.wfl.UpdateObjectRelationsResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:SmartConnection", "UpdateObjectRelationsResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[17] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("DeleteObjectRelations");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnection", "DeleteObjectRelations"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnection", ">DeleteObjectRelations"), com.woodwing.enterprise.interfaces.services.wfl.DeleteObjectRelations.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(org.apache.axis.encoding.XMLType.AXIS_VOID);
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[18] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("GetObjectRelations");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnection", "GetObjectRelations"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnection", ">GetObjectRelations"), com.woodwing.enterprise.interfaces.services.wfl.GetObjectRelations.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:SmartConnection", ">GetObjectRelationsResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.wfl.GetObjectRelationsResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:SmartConnection", "GetObjectRelationsResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[19] = oper;

    }

    private static void _initOperationDesc3(){
        org.apache.axis.description.OperationDesc oper;
        org.apache.axis.description.ParameterDesc param;
        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("CreateObjectTargets");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnection", "CreateObjectTargets"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnection", ">CreateObjectTargets"), com.woodwing.enterprise.interfaces.services.wfl.CreateObjectTargets.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:SmartConnection", ">CreateObjectTargetsResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.wfl.CreateObjectTargetsResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:SmartConnection", "CreateObjectTargetsResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[20] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("UpdateObjectTargets");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnection", "UpdateObjectTargets"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnection", ">UpdateObjectTargets"), com.woodwing.enterprise.interfaces.services.wfl.UpdateObjectTargets.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:SmartConnection", ">UpdateObjectTargetsResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.wfl.UpdateObjectTargetsResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:SmartConnection", "UpdateObjectTargetsResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[21] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("DeleteObjectTargets");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnection", "DeleteObjectTargets"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnection", ">DeleteObjectTargets"), com.woodwing.enterprise.interfaces.services.wfl.DeleteObjectTargets.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(org.apache.axis.encoding.XMLType.AXIS_VOID);
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[22] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("GetVersion");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnection", "GetVersion"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnection", ">GetVersion"), com.woodwing.enterprise.interfaces.services.wfl.GetVersion.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:SmartConnection", ">GetVersionResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.wfl.GetVersionResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:SmartConnection", "GetVersionResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[23] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("ListVersions");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnection", "ListVersions"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnection", ">ListVersions"), com.woodwing.enterprise.interfaces.services.wfl.ListVersions.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:SmartConnection", ">ListVersionsResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.wfl.ListVersionsResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:SmartConnection", "ListVersionsResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[24] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("RestoreVersion");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnection", "RestoreVersion"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnection", ">RestoreVersion"), com.woodwing.enterprise.interfaces.services.wfl.RestoreVersion.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(org.apache.axis.encoding.XMLType.AXIS_VOID);
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[25] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("CreateArticleWorkspace");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnection", "CreateArticleWorkspace"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnection", ">CreateArticleWorkspace"), com.woodwing.enterprise.interfaces.services.wfl.CreateArticleWorkspace.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:SmartConnection", ">CreateArticleWorkspaceResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.wfl.CreateArticleWorkspaceResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:SmartConnection", "CreateArticleWorkspaceResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[26] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("ListArticleWorkspaces");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnection", "ListArticleWorkspaces"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnection", ">ListArticleWorkspaces"), com.woodwing.enterprise.interfaces.services.wfl.ListArticleWorkspaces.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:SmartConnection", ">ListArticleWorkspacesResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.wfl.ListArticleWorkspacesResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:SmartConnection", "ListArticleWorkspacesResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[27] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("GetArticleFromWorkspace");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnection", "GetArticleFromWorkspace"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnection", ">GetArticleFromWorkspace"), com.woodwing.enterprise.interfaces.services.wfl.GetArticleFromWorkspace.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:SmartConnection", ">GetArticleFromWorkspaceResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.wfl.GetArticleFromWorkspaceResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:SmartConnection", "GetArticleFromWorkspaceResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[28] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("SaveArticleInWorkspace");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnection", "SaveArticleInWorkspace"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnection", ">SaveArticleInWorkspace"), com.woodwing.enterprise.interfaces.services.wfl.SaveArticleInWorkspace.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(org.apache.axis.encoding.XMLType.AXIS_VOID);
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[29] = oper;

    }

    private static void _initOperationDesc4(){
        org.apache.axis.description.OperationDesc oper;
        org.apache.axis.description.ParameterDesc param;
        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("PreviewArticleAtWorkspace");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnection", "PreviewArticleAtWorkspace"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnection", ">PreviewArticleAtWorkspace"), com.woodwing.enterprise.interfaces.services.wfl.PreviewArticleAtWorkspace.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:SmartConnection", ">PreviewArticleAtWorkspaceResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.wfl.PreviewArticleAtWorkspaceResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:SmartConnection", "PreviewArticleAtWorkspaceResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[30] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("PreviewArticlesAtWorkspace");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnection", "PreviewArticlesAtWorkspace"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnection", ">PreviewArticlesAtWorkspace"), com.woodwing.enterprise.interfaces.services.wfl.PreviewArticlesAtWorkspace.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:SmartConnection", ">PreviewArticlesAtWorkspaceResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.wfl.PreviewArticlesAtWorkspaceResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:SmartConnection", "PreviewArticlesAtWorkspaceResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[31] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("DeleteArticleWorkspace");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnection", "DeleteArticleWorkspace"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnection", ">DeleteArticleWorkspace"), com.woodwing.enterprise.interfaces.services.wfl.DeleteArticleWorkspace.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(org.apache.axis.encoding.XMLType.AXIS_VOID);
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[32] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("CheckSpelling");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnection", "CheckSpelling"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnection", ">CheckSpelling"), com.woodwing.enterprise.interfaces.services.wfl.CheckSpelling.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:SmartConnection", ">CheckSpellingResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.wfl.CheckSpellingResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:SmartConnection", "CheckSpellingResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[33] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("GetSuggestions");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnection", "GetSuggestions"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnection", ">GetSuggestions"), com.woodwing.enterprise.interfaces.services.wfl.GetSuggestions.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:SmartConnection", ">GetSuggestionsResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.wfl.GetSuggestionsResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:SmartConnection", "GetSuggestionsResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[34] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("CheckSpellingAndSuggest");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnection", "CheckSpellingAndSuggest"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnection", ">CheckSpellingAndSuggest"), com.woodwing.enterprise.interfaces.services.wfl.CheckSpellingAndSuggest.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:SmartConnection", ">CheckSpellingAndSuggestResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.wfl.CheckSpellingAndSuggestResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:SmartConnection", "CheckSpellingAndSuggestResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[35] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("NamedQuery");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnection", "NamedQuery"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnection", ">NamedQuery"), com.woodwing.enterprise.interfaces.services.wfl.NamedQueryType0.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:SmartConnection", ">NamedQueryResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.wfl.NamedQueryResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:SmartConnection", "NamedQueryResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[36] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("ChangePassword");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnection", "ChangePassword"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnection", ">ChangePassword"), com.woodwing.enterprise.interfaces.services.wfl.ChangePassword.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(org.apache.axis.encoding.XMLType.AXIS_VOID);
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[37] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("SendMessages");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnection", "SendMessages"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnection", ">SendMessages"), com.woodwing.enterprise.interfaces.services.wfl.SendMessages.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:SmartConnection", ">SendMessagesResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.wfl.SendMessagesResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:SmartConnection", "SendMessagesResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[38] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("CreateObjectOperations");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnection", "CreateObjectOperations"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnection", ">CreateObjectOperations"), com.woodwing.enterprise.interfaces.services.wfl.CreateObjectOperations.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:SmartConnection", ">CreateObjectOperationsResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.wfl.CreateObjectOperationsResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:SmartConnection", "CreateObjectOperationsResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[39] = oper;

    }

    private static void _initOperationDesc5(){
        org.apache.axis.description.OperationDesc oper;
        org.apache.axis.description.ParameterDesc param;
        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("CopyObject");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnection", "CopyObject"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnection", ">CopyObject"), com.woodwing.enterprise.interfaces.services.wfl.CopyObject.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:SmartConnection", ">CopyObjectResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.wfl.CopyObjectResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:SmartConnection", "CopyObjectResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[40] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("SetObjectProperties");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnection", "SetObjectProperties"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnection", ">SetObjectProperties"), com.woodwing.enterprise.interfaces.services.wfl.SetObjectProperties.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:SmartConnection", ">SetObjectPropertiesResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.wfl.SetObjectPropertiesResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:SmartConnection", "SetObjectPropertiesResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[41] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("MultiSetObjectProperties");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnection", "MultiSetObjectProperties"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnection", ">MultiSetObjectProperties"), com.woodwing.enterprise.interfaces.services.wfl.MultiSetObjectProperties.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:SmartConnection", ">MultiSetObjectPropertiesResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.wfl.MultiSetObjectPropertiesResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:SmartConnection", "MultiSetObjectPropertiesResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[42] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("SendTo");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnection", "SendTo"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnection", ">SendTo"), com.woodwing.enterprise.interfaces.services.wfl.SendTo.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:SmartConnection", ">SendToResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.wfl.SendToResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:SmartConnection", "SendToResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[43] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("SendToNext");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnection", "SendToNext"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnection", ">SendToNext"), com.woodwing.enterprise.interfaces.services.wfl.SendToNext.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:SmartConnection", ">SendToNextResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.wfl.SendToNextResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:SmartConnection", "SendToNextResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[44] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("ChangeOnlineStatus");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnection", "ChangeOnlineStatus"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnection", ">ChangeOnlineStatus"), com.woodwing.enterprise.interfaces.services.wfl.ChangeOnlineStatus.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(org.apache.axis.encoding.XMLType.AXIS_VOID);
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[45] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("GetDialog");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnection", "GetDialog"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnection", ">GetDialog"), com.woodwing.enterprise.interfaces.services.wfl.GetDialog.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:SmartConnection", ">GetDialogResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.wfl.GetDialogResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:SmartConnection", "GetDialogResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[46] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("GetDialog2");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnection", "GetDialog2"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnection", ">GetDialog2"), com.woodwing.enterprise.interfaces.services.wfl.GetDialog2.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:SmartConnection", ">GetDialog2Response"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.wfl.GetDialog2Response.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:SmartConnection", "GetDialog2Response"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[47] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("GetPages");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnection", "GetPages"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnection", ">GetPages"), com.woodwing.enterprise.interfaces.services.wfl.GetPages.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:SmartConnection", ">GetPagesResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.wfl.GetPagesResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:SmartConnection", "GetPagesResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[48] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("GetPagesInfo");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnection", "GetPagesInfo"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnection", ">GetPagesInfo"), com.woodwing.enterprise.interfaces.services.wfl.GetPagesInfo.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:SmartConnection", ">GetPagesInfoResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.wfl.GetPagesInfoResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:SmartConnection", "GetPagesInfoResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[49] = oper;

    }

    private static void _initOperationDesc6(){
        org.apache.axis.description.OperationDesc oper;
        org.apache.axis.description.ParameterDesc param;
        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("Autocomplete");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnection", "Autocomplete"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnection", ">Autocomplete"), com.woodwing.enterprise.interfaces.services.wfl.Autocomplete.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:SmartConnection", ">AutocompleteResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.wfl.AutocompleteResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:SmartConnection", "AutocompleteResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[50] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("Suggestions");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnection", "Suggestions"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnection", ">Suggestions"), com.woodwing.enterprise.interfaces.services.wfl.Suggestions.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:SmartConnection", ">SuggestionsResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.wfl.SuggestionsResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:SmartConnection", "SuggestionsResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[51] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("CreateObjectLabels");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnection", "CreateObjectLabels"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnection", ">CreateObjectLabels"), com.woodwing.enterprise.interfaces.services.wfl.CreateObjectLabels.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:SmartConnection", ">CreateObjectLabelsResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.wfl.CreateObjectLabelsResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:SmartConnection", "CreateObjectLabelsResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[52] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("UpdateObjectLabels");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnection", "UpdateObjectLabels"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnection", ">UpdateObjectLabels"), com.woodwing.enterprise.interfaces.services.wfl.UpdateObjectLabels.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:SmartConnection", ">UpdateObjectLabelsResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.wfl.UpdateObjectLabelsResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:SmartConnection", "UpdateObjectLabelsResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[53] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("DeleteObjectLabels");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnection", "DeleteObjectLabels"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnection", ">DeleteObjectLabels"), com.woodwing.enterprise.interfaces.services.wfl.DeleteObjectLabels.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(org.apache.axis.encoding.XMLType.AXIS_VOID);
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[54] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("AddObjectLabels");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnection", "AddObjectLabels"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnection", ">AddObjectLabels"), com.woodwing.enterprise.interfaces.services.wfl.AddObjectLabels.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(org.apache.axis.encoding.XMLType.AXIS_VOID);
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[55] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("RemoveObjectLabels");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartConnection", "RemoveObjectLabels"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartConnection", ">RemoveObjectLabels"), com.woodwing.enterprise.interfaces.services.wfl.RemoveObjectLabels.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(org.apache.axis.encoding.XMLType.AXIS_VOID);
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[56] = oper;

    }

    public SmartConnectionBindingStub() throws org.apache.axis.AxisFault {
         this(null);
    }

    public SmartConnectionBindingStub(java.net.URL endpointURL, javax.xml.rpc.Service service) throws org.apache.axis.AxisFault {
         this(service);
         super.cachedEndpoint = endpointURL;
    }

    public SmartConnectionBindingStub(javax.xml.rpc.Service service) throws org.apache.axis.AxisFault {
        if (service == null) {
            super.service = new org.apache.axis.client.Service();
        } else {
            super.service = service;
        }
        ((org.apache.axis.client.Service)super.service).setTypeMappingVersion("1.2");
            java.lang.Class cls;
            javax.xml.namespace.QName qName;
            javax.xml.namespace.QName qName2;
            java.lang.Class beansf = org.apache.axis.encoding.ser.BeanSerializerFactory.class;
            java.lang.Class beandf = org.apache.axis.encoding.ser.BeanDeserializerFactory.class;
            java.lang.Class enumsf = org.apache.axis.encoding.ser.EnumSerializerFactory.class;
            java.lang.Class enumdf = org.apache.axis.encoding.ser.EnumDeserializerFactory.class;
            java.lang.Class arraysf = org.apache.axis.encoding.ser.ArraySerializerFactory.class;
            java.lang.Class arraydf = org.apache.axis.encoding.ser.ArrayDeserializerFactory.class;
            java.lang.Class simplesf = org.apache.axis.encoding.ser.SimpleSerializerFactory.class;
            java.lang.Class simpledf = org.apache.axis.encoding.ser.SimpleDeserializerFactory.class;
            java.lang.Class simplelistsf = org.apache.axis.encoding.ser.SimpleListSerializerFactory.class;
            java.lang.Class simplelistdf = org.apache.axis.encoding.ser.SimpleListDeserializerFactory.class;
        addBindings0();
        addBindings1();
        addBindings2();
    }

    private void addBindings0() {
            java.lang.Class cls;
            javax.xml.namespace.QName qName;
            javax.xml.namespace.QName qName2;
            java.lang.Class beansf = org.apache.axis.encoding.ser.BeanSerializerFactory.class;
            java.lang.Class beandf = org.apache.axis.encoding.ser.BeanDeserializerFactory.class;
            java.lang.Class enumsf = org.apache.axis.encoding.ser.EnumSerializerFactory.class;
            java.lang.Class enumdf = org.apache.axis.encoding.ser.EnumDeserializerFactory.class;
            java.lang.Class arraysf = org.apache.axis.encoding.ser.ArraySerializerFactory.class;
            java.lang.Class arraydf = org.apache.axis.encoding.ser.ArrayDeserializerFactory.class;
            java.lang.Class simplesf = org.apache.axis.encoding.ser.SimpleSerializerFactory.class;
            java.lang.Class simpledf = org.apache.axis.encoding.ser.SimpleDeserializerFactory.class;
            java.lang.Class simplelistsf = org.apache.axis.encoding.ser.SimpleListSerializerFactory.class;
            java.lang.Class simplelistdf = org.apache.axis.encoding.ser.SimpleListDeserializerFactory.class;
            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">AddObjectLabels");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.AddObjectLabels.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">Autocomplete");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.Autocomplete.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">AutocompleteResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.AutocompleteResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">ChangeOnlineStatus");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.ChangeOnlineStatus.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">ChangePassword");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.ChangePassword.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">CheckSpelling");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.CheckSpelling.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">CheckSpellingAndSuggest");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.CheckSpellingAndSuggest.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">CheckSpellingAndSuggestResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.CheckSpellingAndSuggestResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">CheckSpellingResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.CheckSpellingResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">CopyObject");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.CopyObject.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">CopyObjectResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.CopyObjectResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">CreateArticleWorkspace");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.CreateArticleWorkspace.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">CreateArticleWorkspaceResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.CreateArticleWorkspaceResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">CreateObjectLabels");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.CreateObjectLabels.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">CreateObjectLabelsResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.CreateObjectLabelsResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">CreateObjectOperations");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.CreateObjectOperations.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">CreateObjectOperationsResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.CreateObjectOperationsResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">CreateObjectRelations");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.CreateObjectRelations.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">CreateObjectRelationsResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.CreateObjectRelationsResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">CreateObjects");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.CreateObjects.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">CreateObjectsResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.CreateObjectsResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">CreateObjectTargets");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.CreateObjectTargets.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">CreateObjectTargetsResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.CreateObjectTargetsResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">DeleteArticleWorkspace");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.DeleteArticleWorkspace.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">DeleteObjectLabels");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.DeleteObjectLabels.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">DeleteObjectRelations");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.DeleteObjectRelations.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">DeleteObjects");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.DeleteObjects.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">DeleteObjectsResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.DeleteObjectsResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">DeleteObjectTargets");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.DeleteObjectTargets.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">DeleteUserSettings");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.DeleteUserSettings.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">GetArticleFromWorkspace");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.GetArticleFromWorkspace.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">GetArticleFromWorkspaceResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.GetArticleFromWorkspaceResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">GetDialog");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.GetDialog.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">GetDialog2");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.GetDialog2.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">GetDialog2Response");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.GetDialog2Response.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">GetDialogResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.GetDialogResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">GetObjectRelations");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.GetObjectRelations.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">GetObjectRelationsResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.GetObjectRelationsResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">GetObjects");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.GetObjects.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">GetObjectsResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.GetObjectsResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">GetPages");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.GetPages.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">GetPagesInfo");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.GetPagesInfo.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">GetPagesInfoResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.GetPagesInfoResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">GetPagesResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.GetPagesResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">GetServersResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.GetServersResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">GetStates");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.GetStates.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">GetSuggestions");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.GetSuggestions.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">GetSuggestionsResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.GetSuggestionsResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">GetUserSettings");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.GetUserSettings.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">GetUserSettingsResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.GetUserSettingsResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">GetVersion");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.GetVersion.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">GetVersionResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.GetVersionResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">InstantiateTemplate");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.InstantiateTemplate.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">InstantiateTemplateResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.InstantiateTemplateResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">ListArticleWorkspaces");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.ListArticleWorkspaces.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">ListArticleWorkspacesResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.ListArticleWorkspacesResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">ListVersions");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.ListVersions.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">ListVersionsResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.ListVersionsResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">LockObjects");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.LockObjects.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">LockObjectsResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.LockObjectsResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">LogOff");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.LogOff.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">LogOn");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.LogOn.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">LogOnResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.LogOnResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">MultiSetObjectProperties");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.MultiSetObjectProperties.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">MultiSetObjectPropertiesResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.MultiSetObjectPropertiesResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">NamedQuery");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.NamedQueryType0.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">NamedQueryResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.NamedQueryResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">PreviewArticleAtWorkspace");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.PreviewArticleAtWorkspace.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">PreviewArticleAtWorkspaceResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.PreviewArticleAtWorkspaceResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">PreviewArticlesAtWorkspace");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.PreviewArticlesAtWorkspace.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">PreviewArticlesAtWorkspaceResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.PreviewArticlesAtWorkspaceResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">QueryObjects");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.QueryObjects.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">QueryObjectsResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.QueryObjectsResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">RemoveObjectLabels");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.RemoveObjectLabels.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">RestoreObjects");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.RestoreObjects.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">RestoreObjectsResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.RestoreObjectsResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">RestoreVersion");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.RestoreVersion.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">SaveArticleInWorkspace");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.SaveArticleInWorkspace.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">SaveObjects");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.SaveObjects.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">SaveObjectsResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.SaveObjectsResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">SaveUserSettings");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.SaveUserSettings.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">SendMessages");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.SendMessages.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">SendMessagesResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.SendMessagesResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">SendTo");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.SendTo.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">SendToNext");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.SendToNext.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">SendToNextResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.SendToNextResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">SendToResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.SendToResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">SetObjectProperties");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.SetObjectProperties.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">SetObjectPropertiesResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.SetObjectPropertiesResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">Suggestions");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.Suggestions.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">SuggestionsResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.SuggestionsResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">UnlockObjects");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.UnlockObjects.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">UnlockObjectsResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.UnlockObjectsResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">UpdateObjectLabels");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.UpdateObjectLabels.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">UpdateObjectLabelsResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.UpdateObjectLabelsResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">UpdateObjectRelations");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.UpdateObjectRelations.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">UpdateObjectRelationsResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.UpdateObjectRelationsResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">UpdateObjectTargets");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.UpdateObjectTargets.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", ">UpdateObjectTargetsResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.UpdateObjectTargetsResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "Action");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.Action.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(enumsf);
            cachedDeserFactories.add(enumdf);

    }
    private void addBindings1() {
            java.lang.Class cls;
            javax.xml.namespace.QName qName;
            javax.xml.namespace.QName qName2;
            java.lang.Class beansf = org.apache.axis.encoding.ser.BeanSerializerFactory.class;
            java.lang.Class beandf = org.apache.axis.encoding.ser.BeanDeserializerFactory.class;
            java.lang.Class enumsf = org.apache.axis.encoding.ser.EnumSerializerFactory.class;
            java.lang.Class enumdf = org.apache.axis.encoding.ser.EnumDeserializerFactory.class;
            java.lang.Class arraysf = org.apache.axis.encoding.ser.ArraySerializerFactory.class;
            java.lang.Class arraydf = org.apache.axis.encoding.ser.ArrayDeserializerFactory.class;
            java.lang.Class simplesf = org.apache.axis.encoding.ser.SimpleSerializerFactory.class;
            java.lang.Class simpledf = org.apache.axis.encoding.ser.SimpleDeserializerFactory.class;
            java.lang.Class simplelistsf = org.apache.axis.encoding.ser.SimpleListSerializerFactory.class;
            java.lang.Class simplelistdf = org.apache.axis.encoding.ser.SimpleListDeserializerFactory.class;
            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ActionProperty");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.ActionProperty.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "AppFeature");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.AppFeature.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "AreaType");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.AreaType.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(enumsf);
            cachedDeserFactories.add(enumdf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ArrayOfActionProperty");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.ActionProperty[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ActionProperty");
            qName2 = new javax.xml.namespace.QName("", "ActionProperty");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ArrayOfAppFeature");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.AppFeature[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:SmartConnection", "AppFeature");
            qName2 = new javax.xml.namespace.QName("", "AppFeature");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ArrayOfAreaType");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.AreaType[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:SmartConnection", "AreaType");
            qName2 = new javax.xml.namespace.QName("", "AreaType");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ArrayOfArticleAtWorkspace");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.ArticleAtWorkspace[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ArticleAtWorkspace");
            qName2 = new javax.xml.namespace.QName("", "ArticleAtWorkspace");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ArrayOfAttachment");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.Attachment[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:SmartConnection", "Attachment");
            qName2 = new javax.xml.namespace.QName("", "Attachment");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ArrayOfAutoSuggestProperty");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.AutoSuggestProperty[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:SmartConnection", "AutoSuggestProperty");
            qName2 = new javax.xml.namespace.QName("", "AutoSuggestProperty");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ArrayOfAutoSuggestTag");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.AutoSuggestTag[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:SmartConnection", "AutoSuggestTag");
            qName2 = new javax.xml.namespace.QName("", "AutoSuggestTag");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ArrayOfCategoryInfo");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.CategoryInfo[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:SmartConnection", "CategoryInfo");
            qName2 = new javax.xml.namespace.QName("", "CategoryInfo");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ArrayOfChildRow");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.ChildRow[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ChildRow");
            qName2 = new javax.xml.namespace.QName("", "ChildRow");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ArrayOfDialogButton");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.DialogButton[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:SmartConnection", "DialogButton");
            qName2 = new javax.xml.namespace.QName("", "DialogButton");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ArrayOfDialogTab");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.DialogTab[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:SmartConnection", "DialogTab");
            qName2 = new javax.xml.namespace.QName("", "DialogTab");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ArrayOfDialogWidget");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.DialogWidget[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:SmartConnection", "DialogWidget");
            qName2 = new javax.xml.namespace.QName("", "DialogWidget");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ArrayOfDictionary");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.Dictionary[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:SmartConnection", "Dictionary");
            qName2 = new javax.xml.namespace.QName("", "Dictionary");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ArrayOfEdition");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.Edition[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:SmartConnection", "Edition");
            qName2 = new javax.xml.namespace.QName("", "Edition");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ArrayOfEditionPages");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.EditionPages[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:SmartConnection", "EditionPages");
            qName2 = new javax.xml.namespace.QName("", "EditionPages");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ArrayOfEditionRenditionsInfo");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.EditionRenditionsInfo[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:SmartConnection", "EditionRenditionsInfo");
            qName2 = new javax.xml.namespace.QName("", "EditionRenditionsInfo");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ArrayOfElement");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.Element[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:SmartConnection", "Element");
            qName2 = new javax.xml.namespace.QName("", "Element");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ArrayOfEntityTags");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.EntityTags[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:SmartConnection", "EntityTags");
            qName2 = new javax.xml.namespace.QName("", "EntityTags");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ArrayOfErrorReport");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.ErrorReport[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ErrorReport");
            qName2 = new javax.xml.namespace.QName("", "ErrorReport");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ArrayOfErrorReportEntity");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.ErrorReportEntity[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ErrorReportEntity");
            qName2 = new javax.xml.namespace.QName("", "ErrorReportEntity");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ArrayOfErrorReportEntry");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.ErrorReportEntry[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ErrorReportEntry");
            qName2 = new javax.xml.namespace.QName("", "ErrorReportEntry");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ArrayOfExtraMetaData");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.ExtraMetaData[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ExtraMetaData");
            qName2 = new javax.xml.namespace.QName("", "ExtraMetaData");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ArrayOfFacet");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.Facet[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:SmartConnection", "Facet");
            qName2 = new javax.xml.namespace.QName("", "Facet");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ArrayOfFacetItem");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.FacetItem[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:SmartConnection", "FacetItem");
            qName2 = new javax.xml.namespace.QName("", "FacetItem");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ArrayOfFeature");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.Feature[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:SmartConnection", "Feature");
            qName2 = new javax.xml.namespace.QName("", "Feature");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ArrayOfFeatureAccess");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.FeatureAccess[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:SmartConnection", "FeatureAccess");
            qName2 = new javax.xml.namespace.QName("", "FeatureAccess");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ArrayOfFeatureProfile");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.FeatureProfile[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:SmartConnection", "FeatureProfile");
            qName2 = new javax.xml.namespace.QName("", "FeatureProfile");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ArrayOfInDesignArticle");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.InDesignArticle[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:SmartConnection", "InDesignArticle");
            qName2 = new javax.xml.namespace.QName("", "InDesignArticle");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ArrayOfIssueInfo");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.IssueInfo[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:SmartConnection", "IssueInfo");
            qName2 = new javax.xml.namespace.QName("", "IssueInfo");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ArrayOfLayoutObject");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.LayoutObject[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:SmartConnection", "LayoutObject");
            qName2 = new javax.xml.namespace.QName("", "LayoutObject");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ArrayOfMessage");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.Message[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:SmartConnection", "Message");
            qName2 = new javax.xml.namespace.QName("", "Message");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ArrayOfMessageQueueConnection");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.MessageQueueConnection[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:SmartConnection", "MessageQueueConnection");
            qName2 = new javax.xml.namespace.QName("", "MessageQueueConnection");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ArrayOfMetaDataValue");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.MetaDataValue[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:SmartConnection", "MetaDataValue");
            qName2 = new javax.xml.namespace.QName("", "MetaDataValue");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ArrayOfNamedQuery");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.NamedQuery[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:SmartConnection", "NamedQuery");
            qName2 = new javax.xml.namespace.QName("", "NamedQuery");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ArrayOfObject");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.Object[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:SmartConnection", "Object");
            qName2 = new javax.xml.namespace.QName("", "Object");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ArrayOfObjectInfo");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.ObjectInfo[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ObjectInfo");
            qName2 = new javax.xml.namespace.QName("", "ObjectInfo");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ArrayOfObjectLabel");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.ObjectLabel[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ObjectLabel");
            qName2 = new javax.xml.namespace.QName("", "ObjectLabel");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ArrayOfObjectOperation");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.ObjectOperation[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ObjectOperation");
            qName2 = new javax.xml.namespace.QName("", "ObjectOperation");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ArrayOfObjectPageInfo");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.ObjectPageInfo[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ObjectPageInfo");
            qName2 = new javax.xml.namespace.QName("", "ObjectPageInfo");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ArrayOfObjectTargetsInfo");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.ObjectTargetsInfo[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ObjectTargetsInfo");
            qName2 = new javax.xml.namespace.QName("", "ObjectTargetsInfo");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ArrayOfObjectTypeProperty");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.ObjectTypeProperty[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ObjectTypeProperty");
            qName2 = new javax.xml.namespace.QName("", "ObjectTypeProperty");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ArrayOfObjectVersion");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.ObjectVersion[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ObjectVersion");
            qName2 = new javax.xml.namespace.QName("", "ObjectVersion");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ArrayOfPage");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.Page[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:SmartConnection", "Page");
            qName2 = new javax.xml.namespace.QName("", "Page");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ArrayOfPageObject");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.PageObject[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:SmartConnection", "PageObject");
            qName2 = new javax.xml.namespace.QName("", "PageObject");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ArrayOfParam");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.Param[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:SmartConnection", "Param");
            qName2 = new javax.xml.namespace.QName("", "Param");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ArrayOfPlacedObject");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.PlacedObject[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:SmartConnection", "PlacedObject");
            qName2 = new javax.xml.namespace.QName("", "PlacedObject");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ArrayOfPlacement");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.Placement[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:SmartConnection", "Placement");
            qName2 = new javax.xml.namespace.QName("", "Placement");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ArrayOfPlacementInfo");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.PlacementInfo[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:SmartConnection", "PlacementInfo");
            qName2 = new javax.xml.namespace.QName("", "PlacementInfo");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ArrayOfPlacementTile");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.PlacementTile[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:SmartConnection", "PlacementTile");
            qName2 = new javax.xml.namespace.QName("", "PlacementTile");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ArrayOfProperty");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.Property[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:SmartConnection", "Property");
            qName2 = new javax.xml.namespace.QName("", "Property");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ArrayOfPropertyInfo");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.PropertyInfo[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:SmartConnection", "PropertyInfo");
            qName2 = new javax.xml.namespace.QName("", "PropertyInfo");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ArrayOfPropertyNotification");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.PropertyNotification[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:SmartConnection", "PropertyNotification");
            qName2 = new javax.xml.namespace.QName("", "PropertyNotification");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ArrayOfPropertyUsage");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.PropertyUsage[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:SmartConnection", "PropertyUsage");
            qName2 = new javax.xml.namespace.QName("", "PropertyUsage");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ArrayOfPropertyValue");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.PropertyValue[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:SmartConnection", "PropertyValue");
            qName2 = new javax.xml.namespace.QName("", "PropertyValue");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ArrayOfPubChannelInfo");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.PubChannelInfo[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:SmartConnection", "PubChannelInfo");
            qName2 = new javax.xml.namespace.QName("", "PubChannelInfo");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ArrayOfPublication");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.Publication[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:SmartConnection", "Publication");
            qName2 = new javax.xml.namespace.QName("", "Publication");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ArrayOfPublicationInfo");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.PublicationInfo[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:SmartConnection", "PublicationInfo");
            qName2 = new javax.xml.namespace.QName("", "PublicationInfo");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ArrayOfQueryOrder");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.QueryOrder[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:SmartConnection", "QueryOrder");
            qName2 = new javax.xml.namespace.QName("", "QueryOrder");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ArrayOfQueryParam");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.QueryParam[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:SmartConnection", "QueryParam");
            qName2 = new javax.xml.namespace.QName("", "QueryParam");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ArrayOfRelation");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.Relation[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:SmartConnection", "Relation");
            qName2 = new javax.xml.namespace.QName("", "Relation");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ArrayOfRenditionType");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.RenditionType[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:SmartConnection", "RenditionType");
            qName2 = new javax.xml.namespace.QName("", "RenditionType");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ArrayOfRenditionTypeInfo");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.RenditionTypeInfo[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:SmartConnection", "RenditionTypeInfo");
            qName2 = new javax.xml.namespace.QName("", "RenditionTypeInfo");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ArrayOfRoutingMetaData");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.RoutingMetaData[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:SmartConnection", "RoutingMetaData");
            qName2 = new javax.xml.namespace.QName("", "RoutingMetaData");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ArrayOfRow");
            cachedSerQNames.add(qName);
            cls = java.lang.String[][].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:SmartConnection", "Row");
            qName2 = new javax.xml.namespace.QName("", "Row");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ArrayOfServerInfo");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.ServerInfo[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ServerInfo");
            qName2 = new javax.xml.namespace.QName("", "ServerInfo");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ArrayOfSetting");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.Setting[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:SmartConnection", "Setting");
            qName2 = new javax.xml.namespace.QName("", "Setting");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ArrayOfState");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.State[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:SmartConnection", "State");
            qName2 = new javax.xml.namespace.QName("", "State");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ArrayOfString");
            cachedSerQNames.add(qName);
            cls = java.lang.String[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:SmartConnection", "String");
            qName2 = new javax.xml.namespace.QName("", "String");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ArrayOfSuggestion");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.Suggestion[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:SmartConnection", "Suggestion");
            qName2 = new javax.xml.namespace.QName("", "Suggestion");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ArrayOfTarget");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.Target[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:SmartConnection", "Target");
            qName2 = new javax.xml.namespace.QName("", "Target");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ArrayOfTerm");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.Term[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:SmartConnection", "Term");
            qName2 = new javax.xml.namespace.QName("", "Term");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ArrayOfUser");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.User[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:SmartConnection", "User");
            qName2 = new javax.xml.namespace.QName("", "User");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ArrayOfUserGroup");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.UserGroup[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:SmartConnection", "UserGroup");
            qName2 = new javax.xml.namespace.QName("", "UserGroup");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ArrayOfVersionInfo");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.VersionInfo[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:SmartConnection", "VersionInfo");
            qName2 = new javax.xml.namespace.QName("", "VersionInfo");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ArticleAtWorkspace");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.ArticleAtWorkspace.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "Attachment");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.Attachment.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "AttachmentContent");
            cachedSerQNames.add(qName);
            cls = byte[].class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(arraysf);
            cachedDeserFactories.add(arraydf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "AutoSuggestProperty");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.AutoSuggestProperty.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "AutoSuggestTag");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.AutoSuggestTag.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "BasicMetaData");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.BasicMetaData.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "Category");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.Category.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "CategoryInfo");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.CategoryInfo.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ChildRow");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.ChildRow.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "Color");
            cachedSerQNames.add(qName);
            cls = java.lang.String.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(org.apache.axis.encoding.ser.BaseSerializerFactory.createFactory(org.apache.axis.encoding.ser.SimpleSerializerFactory.class, cls, qName));
            cachedDeserFactories.add(org.apache.axis.encoding.ser.BaseDeserializerFactory.createFactory(org.apache.axis.encoding.ser.SimpleDeserializerFactory.class, cls, qName));

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ContentMetaData");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.ContentMetaData.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "dateTimeOrEmpty");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.DateTimeOrEmpty.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(org.apache.axis.encoding.ser.BaseSerializerFactory.createFactory(org.apache.axis.encoding.ser.SimpleSerializerFactory.class, cls, qName));
            cachedDeserFactories.add(org.apache.axis.encoding.ser.BaseDeserializerFactory.createFactory(org.apache.axis.encoding.ser.SimpleDeserializerFactory.class, cls, qName));

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "Dialog");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.Dialog.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "DialogButton");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.DialogButton.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "DialogTab");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.DialogTab.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "DialogWidget");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.DialogWidget.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "Dictionary");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.Dictionary.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "Edition");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.Edition.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "EditionPages");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.EditionPages.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "EditionRenditionsInfo");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.EditionRenditionsInfo.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "Element");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.Element.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "emptyString");
            cachedSerQNames.add(qName);
            cls = java.lang.String.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(org.apache.axis.encoding.ser.BaseSerializerFactory.createFactory(org.apache.axis.encoding.ser.SimpleSerializerFactory.class, cls, qName));
            cachedDeserFactories.add(org.apache.axis.encoding.ser.BaseDeserializerFactory.createFactory(org.apache.axis.encoding.ser.SimpleDeserializerFactory.class, cls, qName));

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "EntityTags");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.EntityTags.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

    }
    private void addBindings2() {
            java.lang.Class cls;
            javax.xml.namespace.QName qName;
            javax.xml.namespace.QName qName2;
            java.lang.Class beansf = org.apache.axis.encoding.ser.BeanSerializerFactory.class;
            java.lang.Class beandf = org.apache.axis.encoding.ser.BeanDeserializerFactory.class;
            java.lang.Class enumsf = org.apache.axis.encoding.ser.EnumSerializerFactory.class;
            java.lang.Class enumdf = org.apache.axis.encoding.ser.EnumDeserializerFactory.class;
            java.lang.Class arraysf = org.apache.axis.encoding.ser.ArraySerializerFactory.class;
            java.lang.Class arraydf = org.apache.axis.encoding.ser.ArrayDeserializerFactory.class;
            java.lang.Class simplesf = org.apache.axis.encoding.ser.SimpleSerializerFactory.class;
            java.lang.Class simpledf = org.apache.axis.encoding.ser.SimpleDeserializerFactory.class;
            java.lang.Class simplelistsf = org.apache.axis.encoding.ser.SimpleListSerializerFactory.class;
            java.lang.Class simplelistdf = org.apache.axis.encoding.ser.SimpleListDeserializerFactory.class;
            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ErrorReport");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.ErrorReport.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ErrorReportEntity");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.ErrorReportEntity.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ErrorReportEntry");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.ErrorReportEntry.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ExtraMetaData");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.ExtraMetaData.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "Facet");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.Facet.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "FacetItem");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.FacetItem.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "Feature");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.Feature.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "FeatureAccess");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.FeatureAccess.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "FeatureProfile");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.FeatureProfile.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "FrameType");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.FrameType.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(enumsf);
            cachedDeserFactories.add(enumdf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "GetStatesResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.GetStatesResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "InDesignArticle");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.InDesignArticle.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "InstanceType");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.InstanceType.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(enumsf);
            cachedDeserFactories.add(enumdf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "Issue");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.Issue.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "IssueInfo");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.IssueInfo.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "LayoutObject");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.LayoutObject.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "Message");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.Message.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "MessageLevel");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.MessageLevel.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(enumsf);
            cachedDeserFactories.add(enumdf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "MessageList");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.MessageList.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "MessageQueueConnection");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.MessageQueueConnection.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "MessageStatus");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.MessageStatus.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(enumsf);
            cachedDeserFactories.add(enumdf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "MessageType");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.MessageType.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(enumsf);
            cachedDeserFactories.add(enumdf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "MetaData");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.MetaData.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "MetaDataValue");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.MetaDataValue.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "NamedQuery");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.NamedQuery.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "Object");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.Object.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ObjectInfo");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.ObjectInfo.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ObjectLabel");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.ObjectLabel.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ObjectOperation");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.ObjectOperation.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ObjectPageInfo");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.ObjectPageInfo.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ObjectTargetsInfo");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.ObjectTargetsInfo.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ObjectType");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.ObjectType.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(enumsf);
            cachedDeserFactories.add(enumdf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ObjectTypeProperty");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.ObjectTypeProperty.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ObjectVersion");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.ObjectVersion.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "OnlineStatusType");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.OnlineStatusType.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(enumsf);
            cachedDeserFactories.add(enumdf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "OperationType");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.OperationType.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(enumsf);
            cachedDeserFactories.add(enumdf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "Page");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.Page.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "PageObject");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.PageObject.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "Param");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.Param.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "PlacedObject");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.PlacedObject.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "Placement");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.Placement.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "PlacementInfo");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.PlacementInfo.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "PlacementTile");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.PlacementTile.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "PreviewType");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.PreviewType.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(enumsf);
            cachedDeserFactories.add(enumdf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "Property");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.Property.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "PropertyInfo");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.PropertyInfo.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "PropertyNotification");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.PropertyNotification.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "PropertyType");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.PropertyType.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(enumsf);
            cachedDeserFactories.add(enumdf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "PropertyUsage");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.PropertyUsage.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "PropertyValue");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.PropertyValue.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "PubChannel");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.PubChannel.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "PubChannelInfo");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.PubChannelInfo.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "PubChannelType");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.PubChannelType.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(enumsf);
            cachedDeserFactories.add(enumdf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "Publication");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.Publication.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "PublicationInfo");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.PublicationInfo.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "QueryOrder");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.QueryOrder.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "QueryParam");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.QueryParam.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "Relation");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.Relation.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "RelationType");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.RelationType.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(enumsf);
            cachedDeserFactories.add(enumdf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "RenditionType");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.RenditionType.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(enumsf);
            cachedDeserFactories.add(enumdf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "RenditionTypeInfo");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.RenditionTypeInfo.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "RightsMetaData");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.RightsMetaData.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "RoutingMetaData");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.RoutingMetaData.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "Row");
            cachedSerQNames.add(qName);
            cls = java.lang.String[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:SmartConnection", "String");
            qName2 = null;
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "ServerInfo");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.ServerInfo.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "Setting");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.Setting.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "SourceMetaData");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.SourceMetaData.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "State");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.State.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "StickyInfo");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.StickyInfo.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "String");
            cachedSerQNames.add(qName);
            cls = java.lang.String.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(org.apache.axis.encoding.ser.BaseSerializerFactory.createFactory(org.apache.axis.encoding.ser.SimpleSerializerFactory.class, cls, qName));
            cachedDeserFactories.add(org.apache.axis.encoding.ser.BaseDeserializerFactory.createFactory(org.apache.axis.encoding.ser.SimpleDeserializerFactory.class, cls, qName));

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "Suggestion");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.Suggestion.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "Target");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.Target.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "Term");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.Term.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "User");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.User.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "UserGroup");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.UserGroup.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "VersionInfo");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.VersionInfo.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartConnection", "WorkflowMetaData");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.wfl.WorkflowMetaData.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

    }

    protected org.apache.axis.client.Call createCall() throws java.rmi.RemoteException {
        try {
            org.apache.axis.client.Call _call = super._createCall();
            if (super.maintainSessionSet) {
                _call.setMaintainSession(super.maintainSession);
            }
            if (super.cachedUsername != null) {
                _call.setUsername(super.cachedUsername);
            }
            if (super.cachedPassword != null) {
                _call.setPassword(super.cachedPassword);
            }
            if (super.cachedEndpoint != null) {
                _call.setTargetEndpointAddress(super.cachedEndpoint);
            }
            if (super.cachedTimeout != null) {
                _call.setTimeout(super.cachedTimeout);
            }
            if (super.cachedPortName != null) {
                _call.setPortName(super.cachedPortName);
            }
            java.util.Enumeration keys = super.cachedProperties.keys();
            while (keys.hasMoreElements()) {
                java.lang.String key = (java.lang.String) keys.nextElement();
                _call.setProperty(key, super.cachedProperties.get(key));
            }
            // All the type mapping information is registered
            // when the first call is made.
            // The type mapping information is actually registered in
            // the TypeMappingRegistry of the service, which
            // is the reason why registration is only needed for the first call.
            synchronized (this) {
                if (firstCall()) {
                    // must set encoding style before registering serializers
                    _call.setEncodingStyle(null);
                    for (int i = 0; i < cachedSerFactories.size(); ++i) {
                        java.lang.Class cls = (java.lang.Class) cachedSerClasses.get(i);
                        javax.xml.namespace.QName qName =
                                (javax.xml.namespace.QName) cachedSerQNames.get(i);
                        java.lang.Object x = cachedSerFactories.get(i);
                        if (x instanceof Class) {
                            java.lang.Class sf = (java.lang.Class)
                                 cachedSerFactories.get(i);
                            java.lang.Class df = (java.lang.Class)
                                 cachedDeserFactories.get(i);
                            _call.registerTypeMapping(cls, qName, sf, df, false);
                        }
                        else if (x instanceof javax.xml.rpc.encoding.SerializerFactory) {
                            org.apache.axis.encoding.SerializerFactory sf = (org.apache.axis.encoding.SerializerFactory)
                                 cachedSerFactories.get(i);
                            org.apache.axis.encoding.DeserializerFactory df = (org.apache.axis.encoding.DeserializerFactory)
                                 cachedDeserFactories.get(i);
                            _call.registerTypeMapping(cls, qName, sf, df, false);
                        }
                    }
                }
            }
            return _call;
        }
        catch (java.lang.Throwable _t) {
            throw new org.apache.axis.AxisFault("Failure trying to get the Call object", _t);
        }
    }

    public com.woodwing.enterprise.interfaces.services.wfl.GetServersResponse getServers(java.lang.Object parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[0]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnection#GetServers");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "GetServers"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.wfl.GetServersResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.wfl.GetServersResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.wfl.GetServersResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public com.woodwing.enterprise.interfaces.services.wfl.LogOnResponse logOn(com.woodwing.enterprise.interfaces.services.wfl.LogOn parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[1]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnection#LogOn");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "LogOn"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.wfl.LogOnResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.wfl.LogOnResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.wfl.LogOnResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public void logOff(com.woodwing.enterprise.interfaces.services.wfl.LogOff parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[2]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnection#LogOff");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "LogOff"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        extractAttachments(_call);
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public com.woodwing.enterprise.interfaces.services.wfl.GetUserSettingsResponse getUserSettings(com.woodwing.enterprise.interfaces.services.wfl.GetUserSettings parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[3]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnection#GetUserSettings");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "GetUserSettings"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.wfl.GetUserSettingsResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.wfl.GetUserSettingsResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.wfl.GetUserSettingsResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public void saveUserSettings(com.woodwing.enterprise.interfaces.services.wfl.SaveUserSettings parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[4]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnection#SaveUserSettings");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "SaveUserSettings"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        extractAttachments(_call);
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public void deleteUserSettings(com.woodwing.enterprise.interfaces.services.wfl.DeleteUserSettings parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[5]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnection#DeleteUserSettings");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "DeleteUserSettings"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        extractAttachments(_call);
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public com.woodwing.enterprise.interfaces.services.wfl.GetStatesResponse getStates(com.woodwing.enterprise.interfaces.services.wfl.GetStates parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[6]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnection#GetStates");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "GetStates"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.wfl.GetStatesResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.wfl.GetStatesResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.wfl.GetStatesResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public com.woodwing.enterprise.interfaces.services.wfl.CreateObjectsResponse createObjects(com.woodwing.enterprise.interfaces.services.wfl.CreateObjects parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[7]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnection#CreateObjects");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "CreateObjects"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.wfl.CreateObjectsResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.wfl.CreateObjectsResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.wfl.CreateObjectsResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public com.woodwing.enterprise.interfaces.services.wfl.InstantiateTemplateResponse instantiateTemplate(com.woodwing.enterprise.interfaces.services.wfl.InstantiateTemplate parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[8]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnection#InstantiateTemplate");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "InstantiateTemplate"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.wfl.InstantiateTemplateResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.wfl.InstantiateTemplateResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.wfl.InstantiateTemplateResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public com.woodwing.enterprise.interfaces.services.wfl.GetObjectsResponse getObjects(com.woodwing.enterprise.interfaces.services.wfl.GetObjects parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[9]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnection#GetObjects");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "GetObjects"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.wfl.GetObjectsResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.wfl.GetObjectsResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.wfl.GetObjectsResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public com.woodwing.enterprise.interfaces.services.wfl.QueryObjectsResponse queryObjects(com.woodwing.enterprise.interfaces.services.wfl.QueryObjects parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[10]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnection#QueryObjects");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "QueryObjects"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.wfl.QueryObjectsResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.wfl.QueryObjectsResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.wfl.QueryObjectsResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public com.woodwing.enterprise.interfaces.services.wfl.SaveObjectsResponse saveObjects(com.woodwing.enterprise.interfaces.services.wfl.SaveObjects parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[11]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnection#SaveObjects");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "SaveObjects"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.wfl.SaveObjectsResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.wfl.SaveObjectsResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.wfl.SaveObjectsResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public com.woodwing.enterprise.interfaces.services.wfl.LockObjectsResponse lockObjects(com.woodwing.enterprise.interfaces.services.wfl.LockObjects parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[12]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnection#LockObjects");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "LockObjects"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.wfl.LockObjectsResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.wfl.LockObjectsResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.wfl.LockObjectsResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public com.woodwing.enterprise.interfaces.services.wfl.UnlockObjectsResponse unlockObjects(com.woodwing.enterprise.interfaces.services.wfl.UnlockObjects parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[13]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnection#UnlockObjects");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "UnlockObjects"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.wfl.UnlockObjectsResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.wfl.UnlockObjectsResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.wfl.UnlockObjectsResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public com.woodwing.enterprise.interfaces.services.wfl.DeleteObjectsResponse deleteObjects(com.woodwing.enterprise.interfaces.services.wfl.DeleteObjects parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[14]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnection#DeleteObjects");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "DeleteObjects"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.wfl.DeleteObjectsResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.wfl.DeleteObjectsResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.wfl.DeleteObjectsResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public com.woodwing.enterprise.interfaces.services.wfl.RestoreObjectsResponse restoreObjects(com.woodwing.enterprise.interfaces.services.wfl.RestoreObjects parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[15]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnection#RestoreObjects");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "RestoreObjects"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.wfl.RestoreObjectsResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.wfl.RestoreObjectsResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.wfl.RestoreObjectsResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public com.woodwing.enterprise.interfaces.services.wfl.CreateObjectRelationsResponse createObjectRelations(com.woodwing.enterprise.interfaces.services.wfl.CreateObjectRelations parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[16]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnection#CreateObjectRelations");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "CreateObjectRelations"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.wfl.CreateObjectRelationsResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.wfl.CreateObjectRelationsResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.wfl.CreateObjectRelationsResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public com.woodwing.enterprise.interfaces.services.wfl.UpdateObjectRelationsResponse updateObjectRelations(com.woodwing.enterprise.interfaces.services.wfl.UpdateObjectRelations parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[17]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnection#UpdateObjectRelations");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "UpdateObjectRelations"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.wfl.UpdateObjectRelationsResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.wfl.UpdateObjectRelationsResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.wfl.UpdateObjectRelationsResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public void deleteObjectRelations(com.woodwing.enterprise.interfaces.services.wfl.DeleteObjectRelations parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[18]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnection#DeleteObjectRelations");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "DeleteObjectRelations"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        extractAttachments(_call);
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public com.woodwing.enterprise.interfaces.services.wfl.GetObjectRelationsResponse getObjectRelations(com.woodwing.enterprise.interfaces.services.wfl.GetObjectRelations parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[19]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnection#GetObjectRelations");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "GetObjectRelations"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.wfl.GetObjectRelationsResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.wfl.GetObjectRelationsResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.wfl.GetObjectRelationsResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public com.woodwing.enterprise.interfaces.services.wfl.CreateObjectTargetsResponse createObjectTargets(com.woodwing.enterprise.interfaces.services.wfl.CreateObjectTargets parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[20]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnection#CreateObjectTargets");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "CreateObjectTargets"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.wfl.CreateObjectTargetsResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.wfl.CreateObjectTargetsResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.wfl.CreateObjectTargetsResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public com.woodwing.enterprise.interfaces.services.wfl.UpdateObjectTargetsResponse updateObjectTargets(com.woodwing.enterprise.interfaces.services.wfl.UpdateObjectTargets parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[21]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnection#UpdateObjectTargets");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "UpdateObjectTargets"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.wfl.UpdateObjectTargetsResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.wfl.UpdateObjectTargetsResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.wfl.UpdateObjectTargetsResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public void deleteObjectTargets(com.woodwing.enterprise.interfaces.services.wfl.DeleteObjectTargets parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[22]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnection#DeleteObjectTargets");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "DeleteObjectTargets"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        extractAttachments(_call);
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public com.woodwing.enterprise.interfaces.services.wfl.GetVersionResponse getVersion(com.woodwing.enterprise.interfaces.services.wfl.GetVersion parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[23]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnection#GetVersion");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "GetVersion"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.wfl.GetVersionResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.wfl.GetVersionResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.wfl.GetVersionResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public com.woodwing.enterprise.interfaces.services.wfl.ListVersionsResponse listVersions(com.woodwing.enterprise.interfaces.services.wfl.ListVersions parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[24]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnection#ListVersions");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "ListVersions"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.wfl.ListVersionsResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.wfl.ListVersionsResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.wfl.ListVersionsResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public void restoreVersion(com.woodwing.enterprise.interfaces.services.wfl.RestoreVersion parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[25]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnection#RestoreVersion");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "RestoreVersion"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        extractAttachments(_call);
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public com.woodwing.enterprise.interfaces.services.wfl.CreateArticleWorkspaceResponse createArticleWorkspace(com.woodwing.enterprise.interfaces.services.wfl.CreateArticleWorkspace parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[26]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnection#CreateArticleWorkspace");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "CreateArticleWorkspace"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.wfl.CreateArticleWorkspaceResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.wfl.CreateArticleWorkspaceResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.wfl.CreateArticleWorkspaceResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public com.woodwing.enterprise.interfaces.services.wfl.ListArticleWorkspacesResponse listArticleWorkspaces(com.woodwing.enterprise.interfaces.services.wfl.ListArticleWorkspaces parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[27]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnection#ListArticleWorkspaces");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "ListArticleWorkspaces"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.wfl.ListArticleWorkspacesResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.wfl.ListArticleWorkspacesResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.wfl.ListArticleWorkspacesResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public com.woodwing.enterprise.interfaces.services.wfl.GetArticleFromWorkspaceResponse getArticleFromWorkspace(com.woodwing.enterprise.interfaces.services.wfl.GetArticleFromWorkspace parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[28]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnection#GetArticleFromWorkspace");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "GetArticleFromWorkspace"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.wfl.GetArticleFromWorkspaceResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.wfl.GetArticleFromWorkspaceResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.wfl.GetArticleFromWorkspaceResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public void saveArticleInWorkspace(com.woodwing.enterprise.interfaces.services.wfl.SaveArticleInWorkspace parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[29]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnection#SaveArticleInWorkspace");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "SaveArticleInWorkspace"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        extractAttachments(_call);
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public com.woodwing.enterprise.interfaces.services.wfl.PreviewArticleAtWorkspaceResponse previewArticleAtWorkspace(com.woodwing.enterprise.interfaces.services.wfl.PreviewArticleAtWorkspace parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[30]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnection#PreviewArticleAtWorkspace");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "PreviewArticleAtWorkspace"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.wfl.PreviewArticleAtWorkspaceResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.wfl.PreviewArticleAtWorkspaceResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.wfl.PreviewArticleAtWorkspaceResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public com.woodwing.enterprise.interfaces.services.wfl.PreviewArticlesAtWorkspaceResponse previewArticlesAtWorkspace(com.woodwing.enterprise.interfaces.services.wfl.PreviewArticlesAtWorkspace parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[31]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnection#PreviewArticlesAtWorkspace");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "PreviewArticlesAtWorkspace"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.wfl.PreviewArticlesAtWorkspaceResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.wfl.PreviewArticlesAtWorkspaceResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.wfl.PreviewArticlesAtWorkspaceResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public void deleteArticleWorkspace(com.woodwing.enterprise.interfaces.services.wfl.DeleteArticleWorkspace parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[32]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnection#DeleteArticleWorkspace");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "DeleteArticleWorkspace"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        extractAttachments(_call);
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public com.woodwing.enterprise.interfaces.services.wfl.CheckSpellingResponse checkSpelling(com.woodwing.enterprise.interfaces.services.wfl.CheckSpelling parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[33]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnection#CheckSpelling");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "CheckSpelling"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.wfl.CheckSpellingResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.wfl.CheckSpellingResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.wfl.CheckSpellingResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public com.woodwing.enterprise.interfaces.services.wfl.GetSuggestionsResponse getSuggestions(com.woodwing.enterprise.interfaces.services.wfl.GetSuggestions parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[34]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnection#GetSuggestions");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "GetSuggestions"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.wfl.GetSuggestionsResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.wfl.GetSuggestionsResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.wfl.GetSuggestionsResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public com.woodwing.enterprise.interfaces.services.wfl.CheckSpellingAndSuggestResponse checkSpellingAndSuggest(com.woodwing.enterprise.interfaces.services.wfl.CheckSpellingAndSuggest parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[35]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnection#CheckSpellingAndSuggest");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "CheckSpellingAndSuggest"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.wfl.CheckSpellingAndSuggestResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.wfl.CheckSpellingAndSuggestResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.wfl.CheckSpellingAndSuggestResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public com.woodwing.enterprise.interfaces.services.wfl.NamedQueryResponse namedQuery(com.woodwing.enterprise.interfaces.services.wfl.NamedQueryType0 parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[36]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnection#NamedQuery");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "NamedQuery"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.wfl.NamedQueryResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.wfl.NamedQueryResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.wfl.NamedQueryResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public void changePassword(com.woodwing.enterprise.interfaces.services.wfl.ChangePassword parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[37]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnection#ChangePassword");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "ChangePassword"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        extractAttachments(_call);
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public com.woodwing.enterprise.interfaces.services.wfl.SendMessagesResponse sendMessages(com.woodwing.enterprise.interfaces.services.wfl.SendMessages parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[38]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnection#SendMessages");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "SendMessages"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.wfl.SendMessagesResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.wfl.SendMessagesResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.wfl.SendMessagesResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public com.woodwing.enterprise.interfaces.services.wfl.CreateObjectOperationsResponse createObjectOperations(com.woodwing.enterprise.interfaces.services.wfl.CreateObjectOperations parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[39]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnection#CreateObjectOperations");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "CreateObjectOperations"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.wfl.CreateObjectOperationsResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.wfl.CreateObjectOperationsResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.wfl.CreateObjectOperationsResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public com.woodwing.enterprise.interfaces.services.wfl.CopyObjectResponse copyObject(com.woodwing.enterprise.interfaces.services.wfl.CopyObject parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[40]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnection#CopyObject");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "CopyObject"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.wfl.CopyObjectResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.wfl.CopyObjectResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.wfl.CopyObjectResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public com.woodwing.enterprise.interfaces.services.wfl.SetObjectPropertiesResponse setObjectProperties(com.woodwing.enterprise.interfaces.services.wfl.SetObjectProperties parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[41]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnection#SetObjectProperties");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "SetObjectProperties"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.wfl.SetObjectPropertiesResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.wfl.SetObjectPropertiesResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.wfl.SetObjectPropertiesResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public com.woodwing.enterprise.interfaces.services.wfl.MultiSetObjectPropertiesResponse multiSetObjectProperties(com.woodwing.enterprise.interfaces.services.wfl.MultiSetObjectProperties parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[42]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnection#MultiSetObjectProperties");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "MultiSetObjectProperties"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.wfl.MultiSetObjectPropertiesResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.wfl.MultiSetObjectPropertiesResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.wfl.MultiSetObjectPropertiesResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public com.woodwing.enterprise.interfaces.services.wfl.SendToResponse sendTo(com.woodwing.enterprise.interfaces.services.wfl.SendTo parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[43]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnection#SendTo");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "SendTo"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.wfl.SendToResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.wfl.SendToResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.wfl.SendToResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public com.woodwing.enterprise.interfaces.services.wfl.SendToNextResponse sendToNext(com.woodwing.enterprise.interfaces.services.wfl.SendToNext parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[44]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnection#SendToNext");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "SendToNext"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.wfl.SendToNextResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.wfl.SendToNextResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.wfl.SendToNextResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public void changeOnlineStatus(com.woodwing.enterprise.interfaces.services.wfl.ChangeOnlineStatus parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[45]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnection#ChangeOnlineStatus");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "ChangeOnlineStatus"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        extractAttachments(_call);
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public com.woodwing.enterprise.interfaces.services.wfl.GetDialogResponse getDialog(com.woodwing.enterprise.interfaces.services.wfl.GetDialog parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[46]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnection#GetDialog");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "GetDialog"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.wfl.GetDialogResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.wfl.GetDialogResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.wfl.GetDialogResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public com.woodwing.enterprise.interfaces.services.wfl.GetDialog2Response getDialog2(com.woodwing.enterprise.interfaces.services.wfl.GetDialog2 parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[47]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnection#GetDialog2");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "GetDialog2"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.wfl.GetDialog2Response) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.wfl.GetDialog2Response) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.wfl.GetDialog2Response.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public com.woodwing.enterprise.interfaces.services.wfl.GetPagesResponse getPages(com.woodwing.enterprise.interfaces.services.wfl.GetPages parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[48]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnection#GetPages");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "GetPages"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.wfl.GetPagesResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.wfl.GetPagesResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.wfl.GetPagesResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public com.woodwing.enterprise.interfaces.services.wfl.GetPagesInfoResponse getPagesInfo(com.woodwing.enterprise.interfaces.services.wfl.GetPagesInfo parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[49]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnection#GetPagesInfo");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "GetPagesInfo"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.wfl.GetPagesInfoResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.wfl.GetPagesInfoResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.wfl.GetPagesInfoResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public com.woodwing.enterprise.interfaces.services.wfl.AutocompleteResponse autocomplete(com.woodwing.enterprise.interfaces.services.wfl.Autocomplete parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[50]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnection#Autocomplete");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "Autocomplete"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.wfl.AutocompleteResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.wfl.AutocompleteResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.wfl.AutocompleteResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public com.woodwing.enterprise.interfaces.services.wfl.SuggestionsResponse suggestions(com.woodwing.enterprise.interfaces.services.wfl.Suggestions parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[51]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnection#Suggestions");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "Suggestions"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.wfl.SuggestionsResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.wfl.SuggestionsResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.wfl.SuggestionsResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public com.woodwing.enterprise.interfaces.services.wfl.CreateObjectLabelsResponse createObjectLabels(com.woodwing.enterprise.interfaces.services.wfl.CreateObjectLabels parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[52]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnection#CreateObjectLabels");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "CreateObjectLabels"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.wfl.CreateObjectLabelsResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.wfl.CreateObjectLabelsResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.wfl.CreateObjectLabelsResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public com.woodwing.enterprise.interfaces.services.wfl.UpdateObjectLabelsResponse updateObjectLabels(com.woodwing.enterprise.interfaces.services.wfl.UpdateObjectLabels parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[53]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnection#UpdateObjectLabels");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "UpdateObjectLabels"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.wfl.UpdateObjectLabelsResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.wfl.UpdateObjectLabelsResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.wfl.UpdateObjectLabelsResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public void deleteObjectLabels(com.woodwing.enterprise.interfaces.services.wfl.DeleteObjectLabels parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[54]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnection#DeleteObjectLabels");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "DeleteObjectLabels"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        extractAttachments(_call);
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public void addObjectLabels(com.woodwing.enterprise.interfaces.services.wfl.AddObjectLabels parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[55]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnection#AddObjectLabels");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "AddObjectLabels"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        extractAttachments(_call);
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public void removeObjectLabels(com.woodwing.enterprise.interfaces.services.wfl.RemoveObjectLabels parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[56]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartConnection#RemoveObjectLabels");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "RemoveObjectLabels"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        extractAttachments(_call);
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

}
