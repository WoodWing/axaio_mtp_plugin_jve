/**
 * PlutusDatasourceBindingStub.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.dat;

public class PlutusDatasourceBindingStub extends org.apache.axis.client.Stub implements com.woodwing.enterprise.interfaces.services.dat.PlutusDatasourcePort_PortType {
    private java.util.Vector cachedSerClasses = new java.util.Vector();
    private java.util.Vector cachedSerQNames = new java.util.Vector();
    private java.util.Vector cachedSerFactories = new java.util.Vector();
    private java.util.Vector cachedDeserFactories = new java.util.Vector();

    static org.apache.axis.description.OperationDesc [] _operations;

    static {
        _operations = new org.apache.axis.description.OperationDesc[7];
        _initOperationDesc1();
    }

    private static void _initOperationDesc1(){
        org.apache.axis.description.OperationDesc oper;
        org.apache.axis.description.ParameterDesc param;
        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("QueryDatasources");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:PlutusDatasource", "QueryDatasourcesRequest"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:PlutusDatasource", ">QueryDatasourcesRequest"), com.woodwing.enterprise.interfaces.services.dat.QueryDatasourcesRequest.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:PlutusDatasource", ">QueryDatasourcesResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.dat.QueryDatasourcesResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:PlutusDatasource", "QueryDatasourcesResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[0] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("GetDatasource");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:PlutusDatasource", "GetDatasourceRequest"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:PlutusDatasource", ">GetDatasourceRequest"), com.woodwing.enterprise.interfaces.services.dat.GetDatasourceRequest.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:PlutusDatasource", ">GetDatasourceResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.dat.GetDatasourceResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:PlutusDatasource", "GetDatasourceResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[1] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("GetRecords");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:PlutusDatasource", "GetRecordsRequest"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:PlutusDatasource", ">GetRecordsRequest"), com.woodwing.enterprise.interfaces.services.dat.GetRecordsRequest.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:PlutusDatasource", ">GetRecordsResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.dat.GetRecordsResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:PlutusDatasource", "GetRecordsResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[2] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("SetRecords");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:PlutusDatasource", "SetRecordsRequest"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:PlutusDatasource", ">SetRecordsRequest"), com.woodwing.enterprise.interfaces.services.dat.SetRecordsRequest.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "anyType"));
        oper.setReturnClass(java.lang.Object.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:PlutusDatasource", "SetRecordsResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[3] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("HasUpdates");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:PlutusDatasource", "HasUpdatesRequest"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:PlutusDatasource", ">HasUpdatesRequest"), com.woodwing.enterprise.interfaces.services.dat.HasUpdatesRequest.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "anyType"));
        oper.setReturnClass(java.lang.Object.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:PlutusDatasource", "HasUpdatesResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[4] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("OnSave");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:PlutusDatasource", "OnSaveRequest"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:PlutusDatasource", ">OnSaveRequest"), com.woodwing.enterprise.interfaces.services.dat.OnSaveRequest.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "anyType"));
        oper.setReturnClass(java.lang.Object.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:PlutusDatasource", "OnSaveResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[5] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("GetUpdates");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:PlutusDatasource", "GetUpdatesRequest"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:PlutusDatasource", ">GetUpdatesRequest"), com.woodwing.enterprise.interfaces.services.dat.GetUpdatesRequest.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:PlutusDatasource", ">GetUpdatesResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.dat.GetUpdatesResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:PlutusDatasource", "GetUpdatesResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[6] = oper;

    }

    public PlutusDatasourceBindingStub() throws org.apache.axis.AxisFault {
         this(null);
    }

    public PlutusDatasourceBindingStub(java.net.URL endpointURL, javax.xml.rpc.Service service) throws org.apache.axis.AxisFault {
         this(service);
         super.cachedEndpoint = endpointURL;
    }

    public PlutusDatasourceBindingStub(javax.xml.rpc.Service service) throws org.apache.axis.AxisFault {
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
            qName = new javax.xml.namespace.QName("urn:PlutusDatasource", ">GetDatasourceRequest");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.dat.GetDatasourceRequest.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:PlutusDatasource", ">GetDatasourceResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.dat.GetDatasourceResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:PlutusDatasource", ">GetRecordsRequest");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.dat.GetRecordsRequest.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:PlutusDatasource", ">GetRecordsResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.dat.GetRecordsResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:PlutusDatasource", ">GetUpdatesRequest");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.dat.GetUpdatesRequest.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:PlutusDatasource", ">GetUpdatesResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.dat.GetUpdatesResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:PlutusDatasource", ">HasUpdatesRequest");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.dat.HasUpdatesRequest.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:PlutusDatasource", ">OnSaveRequest");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.dat.OnSaveRequest.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:PlutusDatasource", ">QueryDatasourcesRequest");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.dat.QueryDatasourcesRequest.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:PlutusDatasource", ">QueryDatasourcesResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.dat.QueryDatasourcesResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:PlutusDatasource", ">SetRecordsRequest");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.dat.SetRecordsRequest.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:PlutusDatasource", "ArrayOfAttribute");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.dat.Attribute[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:PlutusDatasource", "Attribute");
            qName2 = new javax.xml.namespace.QName("", "Attribute");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:PlutusDatasource", "ArrayOfDatasourceInfo");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.dat.DatasourceInfo[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:PlutusDatasource", "DatasourceInfo");
            qName2 = new javax.xml.namespace.QName("", "DatasourceInfo");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:PlutusDatasource", "ArrayOfFamilyValue");
            cachedSerQNames.add(qName);
            cls = java.lang.String[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:PlutusDatasource", "String");
            qName2 = new javax.xml.namespace.QName("", "FamilyValue");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:PlutusDatasource", "ArrayOfList");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.dat.List[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:PlutusDatasource", "List");
            qName2 = new javax.xml.namespace.QName("", "List");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:PlutusDatasource", "ArrayOfPlacedQuery");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.dat.PlacedQuery[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:PlutusDatasource", "PlacedQuery");
            qName2 = new javax.xml.namespace.QName("", "PlacedQuery");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:PlutusDatasource", "ArrayOfPlacement");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.dat.Placement[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:PlutusDatasource", "Placement");
            qName2 = new javax.xml.namespace.QName("", "Placement");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:PlutusDatasource", "ArrayOfProperty");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.dat.Property[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:PlutusDatasource", "Property");
            qName2 = new javax.xml.namespace.QName("", "Property");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:PlutusDatasource", "ArrayOfQuery");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.dat.Query[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:PlutusDatasource", "Query");
            qName2 = new javax.xml.namespace.QName("", "Query");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:PlutusDatasource", "ArrayOfQueryParam");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.dat.QueryParam[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:PlutusDatasource", "QueryParam");
            qName2 = new javax.xml.namespace.QName("", "QueryParam");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:PlutusDatasource", "ArrayOfRecord");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.dat.Record[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:PlutusDatasource", "Record");
            qName2 = new javax.xml.namespace.QName("", "Record");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:PlutusDatasource", "ArrayOfRecordField");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.dat.RecordField[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:PlutusDatasource", "RecordField");
            qName2 = new javax.xml.namespace.QName("", "RecordField");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:PlutusDatasource", "ArrayOfString");
            cachedSerQNames.add(qName);
            cls = java.lang.String[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:PlutusDatasource", "String");
            qName2 = new javax.xml.namespace.QName("", "String");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:PlutusDatasource", "Attribute");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.dat.Attribute.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:PlutusDatasource", "DatasourceInfo");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.dat.DatasourceInfo.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:PlutusDatasource", "List");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.dat.List.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:PlutusDatasource", "OperationType");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.dat.OperationType.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(enumsf);
            cachedDeserFactories.add(enumdf);

            qName = new javax.xml.namespace.QName("urn:PlutusDatasource", "PlacedQuery");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.dat.PlacedQuery.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:PlutusDatasource", "Placement");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.dat.Placement.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:PlutusDatasource", "Property");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.dat.Property.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:PlutusDatasource", "PropertyType");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.dat.PropertyType.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(enumsf);
            cachedDeserFactories.add(enumdf);

            qName = new javax.xml.namespace.QName("urn:PlutusDatasource", "Query");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.dat.Query.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:PlutusDatasource", "QueryParam");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.dat.QueryParam.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:PlutusDatasource", "Record");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.dat.Record.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:PlutusDatasource", "RecordField");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.dat.RecordField.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:PlutusDatasource", "ResponseType");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.dat.ResponseType.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(enumsf);
            cachedDeserFactories.add(enumdf);

            qName = new javax.xml.namespace.QName("urn:PlutusDatasource", "String");
            cachedSerQNames.add(qName);
            cls = java.lang.String.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(org.apache.axis.encoding.ser.BaseSerializerFactory.createFactory(org.apache.axis.encoding.ser.SimpleSerializerFactory.class, cls, qName));
            cachedDeserFactories.add(org.apache.axis.encoding.ser.BaseDeserializerFactory.createFactory(org.apache.axis.encoding.ser.SimpleDeserializerFactory.class, cls, qName));

            qName = new javax.xml.namespace.QName("urn:PlutusDatasource", "UpdateType");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.dat.UpdateType.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(enumsf);
            cachedDeserFactories.add(enumdf);

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

    public com.woodwing.enterprise.interfaces.services.dat.QueryDatasourcesResponse queryDatasources(com.woodwing.enterprise.interfaces.services.dat.QueryDatasourcesRequest parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[0]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:PlutusDatasource#QueryDatasources");
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
                return (com.woodwing.enterprise.interfaces.services.dat.QueryDatasourcesResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.dat.QueryDatasourcesResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.dat.QueryDatasourcesResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public com.woodwing.enterprise.interfaces.services.dat.GetDatasourceResponse getDatasource(com.woodwing.enterprise.interfaces.services.dat.GetDatasourceRequest parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[1]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:PlutusDatasource#GetDatasource");
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
                return (com.woodwing.enterprise.interfaces.services.dat.GetDatasourceResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.dat.GetDatasourceResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.dat.GetDatasourceResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public com.woodwing.enterprise.interfaces.services.dat.GetRecordsResponse getRecords(com.woodwing.enterprise.interfaces.services.dat.GetRecordsRequest parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[2]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:PlutusDatasource#GetRecords");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "GetRecords"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.dat.GetRecordsResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.dat.GetRecordsResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.dat.GetRecordsResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public java.lang.Object setRecords(com.woodwing.enterprise.interfaces.services.dat.SetRecordsRequest parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[3]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:PlutusDatasource#SetRecords");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "SetRecords"));

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

    public java.lang.Object hasUpdates(com.woodwing.enterprise.interfaces.services.dat.HasUpdatesRequest parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[4]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:PlutusDatasource#HasUpdates");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "HasUpdates"));

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

    public java.lang.Object onSave(com.woodwing.enterprise.interfaces.services.dat.OnSaveRequest parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[5]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:PlutusDatasource#OnSave");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "OnSave"));

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

    public com.woodwing.enterprise.interfaces.services.dat.GetUpdatesResponse getUpdates(com.woodwing.enterprise.interfaces.services.dat.GetUpdatesRequest parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[6]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:PlutusDatasource#GetUpdates");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "GetUpdates"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.dat.GetUpdatesResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.dat.GetUpdatesResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.dat.GetUpdatesResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

}
