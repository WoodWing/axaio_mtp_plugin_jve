/**
 * PlutusAdminBindingStub.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.ads;

public class PlutusAdminBindingStub extends org.apache.axis.client.Stub implements com.woodwing.enterprise.interfaces.services.ads.PlutusAdminPort_PortType {
    private java.util.Vector cachedSerClasses = new java.util.Vector();
    private java.util.Vector cachedSerQNames = new java.util.Vector();
    private java.util.Vector cachedSerFactories = new java.util.Vector();
    private java.util.Vector cachedDeserFactories = new java.util.Vector();

    static org.apache.axis.description.OperationDesc [] _operations;

    static {
        _operations = new org.apache.axis.description.OperationDesc[24];
        _initOperationDesc1();
        _initOperationDesc2();
        _initOperationDesc3();
    }

    private static void _initOperationDesc1(){
        org.apache.axis.description.OperationDesc oper;
        org.apache.axis.description.ParameterDesc param;
        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("GetPublications");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:PlutusAdmin", "GetPublicationsRequest"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:PlutusAdmin", ">GetPublicationsRequest"), com.woodwing.enterprise.interfaces.services.ads.GetPublicationsRequest.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:PlutusAdmin", ">GetPublicationsResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.ads.GetPublicationsResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:PlutusAdmin", "GetPublicationsResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[0] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("GetDatasourceInfo");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:PlutusAdmin", "GetDatasourceInfoRequest"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:PlutusAdmin", ">GetDatasourceInfoRequest"), com.woodwing.enterprise.interfaces.services.ads.GetDatasourceInfoRequest.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:PlutusAdmin", ">GetDatasourceInfoResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.ads.GetDatasourceInfoResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:PlutusAdmin", "GetDatasourceInfoResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[1] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("GetDatasource");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:PlutusAdmin", "GetDatasourceRequest"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:PlutusAdmin", ">GetDatasourceRequest"), com.woodwing.enterprise.interfaces.services.ads.GetDatasourceRequest.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:PlutusAdmin", ">GetDatasourceResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.ads.GetDatasourceResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:PlutusAdmin", "GetDatasourceResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[2] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("GetQuery");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:PlutusAdmin", "GetQueryRequest"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:PlutusAdmin", ">GetQueryRequest"), com.woodwing.enterprise.interfaces.services.ads.GetQueryRequest.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:PlutusAdmin", ">GetQueryResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.ads.GetQueryResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:PlutusAdmin", "GetQueryResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[3] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("GetQueries");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:PlutusAdmin", "GetQueriesRequest"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:PlutusAdmin", ">GetQueriesRequest"), com.woodwing.enterprise.interfaces.services.ads.GetQueriesRequest.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:PlutusAdmin", ">GetQueriesResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.ads.GetQueriesResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:PlutusAdmin", "GetQueriesResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[4] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("GetQueryFields");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:PlutusAdmin", "GetQueryFieldsRequest"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:PlutusAdmin", ">GetQueryFieldsRequest"), com.woodwing.enterprise.interfaces.services.ads.GetQueryFieldsRequest.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:PlutusAdmin", ">GetQueryFieldsResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.ads.GetQueryFieldsResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:PlutusAdmin", "GetQueryFieldsResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[5] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("GetDatasourceTypes");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:PlutusAdmin", "GetDatasourceTypesRequest"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:PlutusAdmin", ">GetDatasourceTypesRequest"), com.woodwing.enterprise.interfaces.services.ads.GetDatasourceTypesRequest.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:PlutusAdmin", ">GetDatasourceTypesResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.ads.GetDatasourceTypesResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:PlutusAdmin", "GetDatasourceTypesResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[6] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("GetDatasourceType");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:PlutusAdmin", "GetDatasourceTypeRequest"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:PlutusAdmin", ">GetDatasourceTypeRequest"), com.woodwing.enterprise.interfaces.services.ads.GetDatasourceTypeRequest.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:PlutusAdmin", ">GetDatasourceTypeResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.ads.GetDatasourceTypeResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:PlutusAdmin", "GetDatasourceTypeResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[7] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("GetSettingsDetails");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:PlutusAdmin", "GetSettingsDetailsRequest"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:PlutusAdmin", ">GetSettingsDetailsRequest"), com.woodwing.enterprise.interfaces.services.ads.GetSettingsDetailsRequest.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:PlutusAdmin", ">GetSettingsDetailsResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.ads.GetSettingsDetailsResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:PlutusAdmin", "GetSettingsDetailsResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[8] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("GetSettings");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:PlutusAdmin", "GetSettingsRequest"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:PlutusAdmin", ">GetSettingsRequest"), com.woodwing.enterprise.interfaces.services.ads.GetSettingsRequest.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:PlutusAdmin", ">GetSettingsResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.ads.GetSettingsResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:PlutusAdmin", "GetSettingsResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[9] = oper;

    }

    private static void _initOperationDesc2(){
        org.apache.axis.description.OperationDesc oper;
        org.apache.axis.description.ParameterDesc param;
        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("QueryDatasources");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:PlutusAdmin", "QueryDatasourcesRequest"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:PlutusAdmin", ">QueryDatasourcesRequest"), com.woodwing.enterprise.interfaces.services.ads.QueryDatasourcesRequest.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:PlutusAdmin", ">QueryDatasourcesResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.ads.QueryDatasourcesResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:PlutusAdmin", "QueryDatasourcesResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[10] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("NewQuery");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:PlutusAdmin", "NewQueryRequest"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:PlutusAdmin", ">NewQueryRequest"), com.woodwing.enterprise.interfaces.services.ads.NewQueryRequest.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:PlutusAdmin", ">NewQueryResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.ads.NewQueryResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:PlutusAdmin", "NewQueryResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[11] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("NewDatasource");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:PlutusAdmin", "NewDatasourceRequest"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:PlutusAdmin", ">NewDatasourceRequest"), com.woodwing.enterprise.interfaces.services.ads.NewDatasourceRequest.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:PlutusAdmin", ">NewDatasourceResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.ads.NewDatasourceResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:PlutusAdmin", "NewDatasourceResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[12] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("SavePublication");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:PlutusAdmin", "SavePublicationRequest"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:PlutusAdmin", ">SavePublicationRequest"), com.woodwing.enterprise.interfaces.services.ads.SavePublicationRequest.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "anyType"));
        oper.setReturnClass(java.lang.Object.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:PlutusAdmin", "SavePublicationResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[13] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("SaveQueryField");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:PlutusAdmin", "SaveQueryFieldRequest"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:PlutusAdmin", ">SaveQueryFieldRequest"), com.woodwing.enterprise.interfaces.services.ads.SaveQueryFieldRequest.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "anyType"));
        oper.setReturnClass(java.lang.Object.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:PlutusAdmin", "SaveQueryFieldResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[14] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("SaveQuery");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:PlutusAdmin", "SaveQueryRequest"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:PlutusAdmin", ">SaveQueryRequest"), com.woodwing.enterprise.interfaces.services.ads.SaveQueryRequest.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "anyType"));
        oper.setReturnClass(java.lang.Object.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:PlutusAdmin", "SaveQueryResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[15] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("SaveDatasource");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:PlutusAdmin", "SaveDatasourceRequest"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:PlutusAdmin", ">SaveDatasourceRequest"), com.woodwing.enterprise.interfaces.services.ads.SaveDatasourceRequest.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "anyType"));
        oper.setReturnClass(java.lang.Object.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:PlutusAdmin", "SaveDatasourceResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[16] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("SaveSetting");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:PlutusAdmin", "SaveSettingRequest"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:PlutusAdmin", ">SaveSettingRequest"), com.woodwing.enterprise.interfaces.services.ads.SaveSettingRequest.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "anyType"));
        oper.setReturnClass(java.lang.Object.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:PlutusAdmin", "SaveSettingResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[17] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("DeletePublication");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:PlutusAdmin", "DeletePublicationRequest"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:PlutusAdmin", ">DeletePublicationRequest"), com.woodwing.enterprise.interfaces.services.ads.DeletePublicationRequest.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "anyType"));
        oper.setReturnClass(java.lang.Object.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:PlutusAdmin", "DeletePublicationResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[18] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("DeleteQueryField");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:PlutusAdmin", "DeleteQueryFieldRequest"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:PlutusAdmin", ">DeleteQueryFieldRequest"), com.woodwing.enterprise.interfaces.services.ads.DeleteQueryFieldRequest.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "anyType"));
        oper.setReturnClass(java.lang.Object.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:PlutusAdmin", "DeleteQueryFieldResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[19] = oper;

    }

    private static void _initOperationDesc3(){
        org.apache.axis.description.OperationDesc oper;
        org.apache.axis.description.ParameterDesc param;
        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("DeleteQuery");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:PlutusAdmin", "DeleteQueryRequest"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:PlutusAdmin", ">DeleteQueryRequest"), com.woodwing.enterprise.interfaces.services.ads.DeleteQueryRequest.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "anyType"));
        oper.setReturnClass(java.lang.Object.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:PlutusAdmin", "DeleteQueryResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[20] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("DeleteDatasource");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:PlutusAdmin", "DeleteDatasourceRequest"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:PlutusAdmin", ">DeleteDatasourceRequest"), com.woodwing.enterprise.interfaces.services.ads.DeleteDatasourceRequest.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "anyType"));
        oper.setReturnClass(java.lang.Object.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:PlutusAdmin", "DeleteDatasourceResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[21] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("CopyDatasource");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:PlutusAdmin", "CopyDatasourceRequest"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:PlutusAdmin", ">CopyDatasourceRequest"), com.woodwing.enterprise.interfaces.services.ads.CopyDatasourceRequest.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:PlutusAdmin", ">CopyDatasourceResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.ads.CopyDatasourceResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:PlutusAdmin", "CopyDatasourceResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[22] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("CopyQuery");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:PlutusAdmin", "CopyQueryRequest"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:PlutusAdmin", ">CopyQueryRequest"), com.woodwing.enterprise.interfaces.services.ads.CopyQueryRequest.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:PlutusAdmin", ">CopyQueryResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.ads.CopyQueryResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:PlutusAdmin", "CopyQueryResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[23] = oper;

    }

    public PlutusAdminBindingStub() throws org.apache.axis.AxisFault {
         this(null);
    }

    public PlutusAdminBindingStub(java.net.URL endpointURL, javax.xml.rpc.Service service) throws org.apache.axis.AxisFault {
         this(service);
         super.cachedEndpoint = endpointURL;
    }

    public PlutusAdminBindingStub(javax.xml.rpc.Service service) throws org.apache.axis.AxisFault {
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
            qName = new javax.xml.namespace.QName("urn:PlutusAdmin", ">CopyDatasourceRequest");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.ads.CopyDatasourceRequest.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:PlutusAdmin", ">CopyDatasourceResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.ads.CopyDatasourceResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:PlutusAdmin", ">CopyQueryRequest");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.ads.CopyQueryRequest.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:PlutusAdmin", ">CopyQueryResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.ads.CopyQueryResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:PlutusAdmin", ">DeleteDatasourceRequest");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.ads.DeleteDatasourceRequest.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:PlutusAdmin", ">DeletePublicationRequest");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.ads.DeletePublicationRequest.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:PlutusAdmin", ">DeleteQueryFieldRequest");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.ads.DeleteQueryFieldRequest.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:PlutusAdmin", ">DeleteQueryRequest");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.ads.DeleteQueryRequest.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:PlutusAdmin", ">GetDatasourceInfoRequest");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.ads.GetDatasourceInfoRequest.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:PlutusAdmin", ">GetDatasourceInfoResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.ads.GetDatasourceInfoResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:PlutusAdmin", ">GetDatasourceRequest");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.ads.GetDatasourceRequest.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:PlutusAdmin", ">GetDatasourceResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.ads.GetDatasourceResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:PlutusAdmin", ">GetDatasourceTypeRequest");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.ads.GetDatasourceTypeRequest.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:PlutusAdmin", ">GetDatasourceTypeResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.ads.GetDatasourceTypeResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:PlutusAdmin", ">GetDatasourceTypesRequest");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.ads.GetDatasourceTypesRequest.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:PlutusAdmin", ">GetDatasourceTypesResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.ads.GetDatasourceTypesResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:PlutusAdmin", ">GetPublicationsRequest");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.ads.GetPublicationsRequest.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:PlutusAdmin", ">GetPublicationsResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.ads.GetPublicationsResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:PlutusAdmin", ">GetQueriesRequest");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.ads.GetQueriesRequest.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:PlutusAdmin", ">GetQueriesResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.ads.GetQueriesResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:PlutusAdmin", ">GetQueryFieldsRequest");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.ads.GetQueryFieldsRequest.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:PlutusAdmin", ">GetQueryFieldsResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.ads.GetQueryFieldsResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:PlutusAdmin", ">GetQueryRequest");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.ads.GetQueryRequest.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:PlutusAdmin", ">GetQueryResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.ads.GetQueryResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:PlutusAdmin", ">GetSettingsDetailsRequest");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.ads.GetSettingsDetailsRequest.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:PlutusAdmin", ">GetSettingsDetailsResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.ads.GetSettingsDetailsResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:PlutusAdmin", ">GetSettingsRequest");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.ads.GetSettingsRequest.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:PlutusAdmin", ">GetSettingsResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.ads.GetSettingsResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:PlutusAdmin", ">NewDatasourceRequest");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.ads.NewDatasourceRequest.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:PlutusAdmin", ">NewDatasourceResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.ads.NewDatasourceResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:PlutusAdmin", ">NewQueryRequest");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.ads.NewQueryRequest.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:PlutusAdmin", ">NewQueryResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.ads.NewQueryResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:PlutusAdmin", ">QueryDatasourcesRequest");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.ads.QueryDatasourcesRequest.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:PlutusAdmin", ">QueryDatasourcesResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.ads.QueryDatasourcesResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:PlutusAdmin", ">SaveDatasourceRequest");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.ads.SaveDatasourceRequest.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:PlutusAdmin", ">SavePublicationRequest");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.ads.SavePublicationRequest.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:PlutusAdmin", ">SaveQueryFieldRequest");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.ads.SaveQueryFieldRequest.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:PlutusAdmin", ">SaveQueryRequest");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.ads.SaveQueryRequest.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:PlutusAdmin", ">SaveSettingRequest");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.ads.SaveSettingRequest.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:PlutusAdmin", "ArrayOfDatasourceInfo");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.ads.DatasourceInfo[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:PlutusAdmin", "DatasourceInfo");
            qName2 = new javax.xml.namespace.QName("", "DatasourceInfo");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:PlutusAdmin", "ArrayOfDatasourceType");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.ads.DatasourceType[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:PlutusAdmin", "DatasourceType");
            qName2 = new javax.xml.namespace.QName("", "DatasourceType");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:PlutusAdmin", "ArrayOfPublication");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.ads.Publication[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:PlutusAdmin", "Publication");
            qName2 = new javax.xml.namespace.QName("", "Publication");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:PlutusAdmin", "ArrayOfQuery");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.ads.Query[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:PlutusAdmin", "Query");
            qName2 = new javax.xml.namespace.QName("", "Query");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:PlutusAdmin", "ArrayOfQueryField");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.ads.QueryField[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:PlutusAdmin", "QueryField");
            qName2 = new javax.xml.namespace.QName("", "QueryField");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:PlutusAdmin", "ArrayOfSetting");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.ads.Setting[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:PlutusAdmin", "Setting");
            qName2 = new javax.xml.namespace.QName("", "Setting");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:PlutusAdmin", "ArrayOfSettingsDetail");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.ads.SettingsDetail[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:PlutusAdmin", "SettingsDetail");
            qName2 = new javax.xml.namespace.QName("", "SettingsDetail");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:PlutusAdmin", "DatasourceInfo");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.ads.DatasourceInfo.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:PlutusAdmin", "DatasourceType");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.ads.DatasourceType.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:PlutusAdmin", "Publication");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.ads.Publication.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:PlutusAdmin", "Query");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.ads.Query.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:PlutusAdmin", "QueryField");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.ads.QueryField.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:PlutusAdmin", "Setting");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.ads.Setting.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:PlutusAdmin", "SettingsDetail");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.ads.SettingsDetail.class;
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

    public com.woodwing.enterprise.interfaces.services.ads.GetPublicationsResponse getPublications(com.woodwing.enterprise.interfaces.services.ads.GetPublicationsRequest parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[0]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:PlutusAdmin#GetPublications");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "GetPublications"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.ads.GetPublicationsResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.ads.GetPublicationsResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.ads.GetPublicationsResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public com.woodwing.enterprise.interfaces.services.ads.GetDatasourceInfoResponse getDatasourceInfo(com.woodwing.enterprise.interfaces.services.ads.GetDatasourceInfoRequest parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[1]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:PlutusAdmin#GetDatasourceInfo");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "GetDatasourceInfo"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.ads.GetDatasourceInfoResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.ads.GetDatasourceInfoResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.ads.GetDatasourceInfoResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public com.woodwing.enterprise.interfaces.services.ads.GetDatasourceResponse getDatasource(com.woodwing.enterprise.interfaces.services.ads.GetDatasourceRequest parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[2]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:PlutusAdmin#GetDatasource");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "GetDatasource"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.ads.GetDatasourceResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.ads.GetDatasourceResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.ads.GetDatasourceResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public com.woodwing.enterprise.interfaces.services.ads.GetQueryResponse getQuery(com.woodwing.enterprise.interfaces.services.ads.GetQueryRequest parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[3]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:PlutusAdmin#GetQuery");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "GetQuery"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.ads.GetQueryResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.ads.GetQueryResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.ads.GetQueryResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public com.woodwing.enterprise.interfaces.services.ads.GetQueriesResponse getQueries(com.woodwing.enterprise.interfaces.services.ads.GetQueriesRequest parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[4]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:PlutusAdmin#GetQueries");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "GetQueries"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.ads.GetQueriesResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.ads.GetQueriesResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.ads.GetQueriesResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public com.woodwing.enterprise.interfaces.services.ads.GetQueryFieldsResponse getQueryFields(com.woodwing.enterprise.interfaces.services.ads.GetQueryFieldsRequest parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[5]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:PlutusAdmin#GetQueryFields");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "GetQueryFields"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.ads.GetQueryFieldsResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.ads.GetQueryFieldsResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.ads.GetQueryFieldsResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public com.woodwing.enterprise.interfaces.services.ads.GetDatasourceTypesResponse getDatasourceTypes(com.woodwing.enterprise.interfaces.services.ads.GetDatasourceTypesRequest parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[6]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:PlutusAdmin#GetDatasourceTypes");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "GetDatasourceTypes"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.ads.GetDatasourceTypesResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.ads.GetDatasourceTypesResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.ads.GetDatasourceTypesResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public com.woodwing.enterprise.interfaces.services.ads.GetDatasourceTypeResponse getDatasourceType(com.woodwing.enterprise.interfaces.services.ads.GetDatasourceTypeRequest parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[7]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:PlutusAdmin#GetDatasourceType");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "GetDatasourceType"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.ads.GetDatasourceTypeResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.ads.GetDatasourceTypeResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.ads.GetDatasourceTypeResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public com.woodwing.enterprise.interfaces.services.ads.GetSettingsDetailsResponse getSettingsDetails(com.woodwing.enterprise.interfaces.services.ads.GetSettingsDetailsRequest parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[8]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:PlutusAdmin#GetSettingsDetails");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "GetSettingsDetails"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.ads.GetSettingsDetailsResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.ads.GetSettingsDetailsResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.ads.GetSettingsDetailsResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public com.woodwing.enterprise.interfaces.services.ads.GetSettingsResponse getSettings(com.woodwing.enterprise.interfaces.services.ads.GetSettingsRequest parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[9]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:PlutusAdmin#GetSettings");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "GetSettings"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.ads.GetSettingsResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.ads.GetSettingsResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.ads.GetSettingsResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public com.woodwing.enterprise.interfaces.services.ads.QueryDatasourcesResponse queryDatasources(com.woodwing.enterprise.interfaces.services.ads.QueryDatasourcesRequest parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[10]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:PlutusAdmin#QueryDatasources");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "QueryDatasources"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.ads.QueryDatasourcesResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.ads.QueryDatasourcesResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.ads.QueryDatasourcesResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public com.woodwing.enterprise.interfaces.services.ads.NewQueryResponse newQuery(com.woodwing.enterprise.interfaces.services.ads.NewQueryRequest parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[11]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:PlutusAdmin#NewQuery");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "NewQuery"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.ads.NewQueryResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.ads.NewQueryResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.ads.NewQueryResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public com.woodwing.enterprise.interfaces.services.ads.NewDatasourceResponse newDatasource(com.woodwing.enterprise.interfaces.services.ads.NewDatasourceRequest parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[12]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:PlutusAdmin#NewDatasources");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "NewDatasource"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.ads.NewDatasourceResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.ads.NewDatasourceResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.ads.NewDatasourceResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public java.lang.Object savePublication(com.woodwing.enterprise.interfaces.services.ads.SavePublicationRequest parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[13]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:PlutusAdmin#SavePublication");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "SavePublication"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (java.lang.Object) _resp;
            } catch (java.lang.Exception _exception) {
                return (java.lang.Object) org.apache.axis.utils.JavaUtils.convert(_resp, java.lang.Object.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public java.lang.Object saveQueryField(com.woodwing.enterprise.interfaces.services.ads.SaveQueryFieldRequest parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[14]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:PlutusAdmin#SaveQueryField");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "SaveQueryField"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (java.lang.Object) _resp;
            } catch (java.lang.Exception _exception) {
                return (java.lang.Object) org.apache.axis.utils.JavaUtils.convert(_resp, java.lang.Object.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public java.lang.Object saveQuery(com.woodwing.enterprise.interfaces.services.ads.SaveQueryRequest parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[15]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:PlutusAdmin#SaveQuery");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "SaveQuery"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (java.lang.Object) _resp;
            } catch (java.lang.Exception _exception) {
                return (java.lang.Object) org.apache.axis.utils.JavaUtils.convert(_resp, java.lang.Object.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public java.lang.Object saveDatasource(com.woodwing.enterprise.interfaces.services.ads.SaveDatasourceRequest parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[16]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:PlutusAdmin#SaveDatasource");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "SaveDatasource"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (java.lang.Object) _resp;
            } catch (java.lang.Exception _exception) {
                return (java.lang.Object) org.apache.axis.utils.JavaUtils.convert(_resp, java.lang.Object.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public java.lang.Object saveSetting(com.woodwing.enterprise.interfaces.services.ads.SaveSettingRequest parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[17]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:PlutusAdmin#SaveSetting");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "SaveSetting"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (java.lang.Object) _resp;
            } catch (java.lang.Exception _exception) {
                return (java.lang.Object) org.apache.axis.utils.JavaUtils.convert(_resp, java.lang.Object.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public java.lang.Object deletePublication(com.woodwing.enterprise.interfaces.services.ads.DeletePublicationRequest parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[18]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:PlutusAdmin#DeletePublication");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "DeletePublication"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (java.lang.Object) _resp;
            } catch (java.lang.Exception _exception) {
                return (java.lang.Object) org.apache.axis.utils.JavaUtils.convert(_resp, java.lang.Object.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public java.lang.Object deleteQueryField(com.woodwing.enterprise.interfaces.services.ads.DeleteQueryFieldRequest parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[19]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:PlutusAdmin#DeleteQueryField");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "DeleteQueryField"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (java.lang.Object) _resp;
            } catch (java.lang.Exception _exception) {
                return (java.lang.Object) org.apache.axis.utils.JavaUtils.convert(_resp, java.lang.Object.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public java.lang.Object deleteQuery(com.woodwing.enterprise.interfaces.services.ads.DeleteQueryRequest parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[20]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:PlutusAdmin#DeleteQuery");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "DeleteQuery"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (java.lang.Object) _resp;
            } catch (java.lang.Exception _exception) {
                return (java.lang.Object) org.apache.axis.utils.JavaUtils.convert(_resp, java.lang.Object.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public java.lang.Object deleteDatasource(com.woodwing.enterprise.interfaces.services.ads.DeleteDatasourceRequest parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[21]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:PlutusAdmin#DeleteDatasource");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "DeleteDatasource"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (java.lang.Object) _resp;
            } catch (java.lang.Exception _exception) {
                return (java.lang.Object) org.apache.axis.utils.JavaUtils.convert(_resp, java.lang.Object.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public com.woodwing.enterprise.interfaces.services.ads.CopyDatasourceResponse copyDatasource(com.woodwing.enterprise.interfaces.services.ads.CopyDatasourceRequest parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[22]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:PlutusAdmin#CopyDatasource");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "CopyDatasource"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.ads.CopyDatasourceResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.ads.CopyDatasourceResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.ads.CopyDatasourceResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public com.woodwing.enterprise.interfaces.services.ads.CopyQueryResponse copyQuery(com.woodwing.enterprise.interfaces.services.ads.CopyQueryRequest parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[23]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:PlutusAdmin#CopyQuery");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "CopyQuery"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.ads.CopyQueryResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.ads.CopyQueryResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.ads.CopyQueryResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

}
