/**
 * EnterprisePublishingBindingStub.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.pub;

public class EnterprisePublishingBindingStub extends org.apache.axis.client.Stub implements com.woodwing.enterprise.interfaces.services.pub.EnterprisePublishingPort_PortType {
    private java.util.Vector cachedSerClasses = new java.util.Vector();
    private java.util.Vector cachedSerQNames = new java.util.Vector();
    private java.util.Vector cachedSerFactories = new java.util.Vector();
    private java.util.Vector cachedDeserFactories = new java.util.Vector();

    static org.apache.axis.description.OperationDesc [] _operations;

    static {
        _operations = new org.apache.axis.description.OperationDesc[11];
        _initOperationDesc1();
        _initOperationDesc2();
    }

    private static void _initOperationDesc1(){
        org.apache.axis.description.OperationDesc oper;
        org.apache.axis.description.ParameterDesc param;
        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("PublishDossiers");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:EnterprisePublishing", "PublishDossiersRequest"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:EnterprisePublishing", ">PublishDossiersRequest"), com.woodwing.enterprise.interfaces.services.pub.PublishDossiersRequest.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:EnterprisePublishing", ">PublishDossiersResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.pub.PublishDossiersResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:EnterprisePublishing", "PublishDossiersResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[0] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("UpdateDossiers");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:EnterprisePublishing", "UpdateDossiersRequest"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:EnterprisePublishing", ">UpdateDossiersRequest"), com.woodwing.enterprise.interfaces.services.pub.UpdateDossiersRequest.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:EnterprisePublishing", ">UpdateDossiersResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.pub.UpdateDossiersResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:EnterprisePublishing", "UpdateDossiersResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[1] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("UnPublishDossiers");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:EnterprisePublishing", "UnPublishDossiersRequest"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:EnterprisePublishing", ">UnPublishDossiersRequest"), com.woodwing.enterprise.interfaces.services.pub.UnPublishDossiersRequest.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:EnterprisePublishing", ">UnPublishDossiersResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.pub.UnPublishDossiersResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:EnterprisePublishing", "UnPublishDossiersResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[2] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("GetDossierURL");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:EnterprisePublishing", "GetDossierURLRequest"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:EnterprisePublishing", ">GetDossierURLRequest"), com.woodwing.enterprise.interfaces.services.pub.GetDossierURLRequest.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:EnterprisePublishing", ">GetDossierURLResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.pub.GetDossierURLResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:EnterprisePublishing", "GetDossierURLResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[3] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("GetPublishInfo");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:EnterprisePublishing", "GetPublishInfoRequest"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:EnterprisePublishing", ">GetPublishInfoRequest"), com.woodwing.enterprise.interfaces.services.pub.GetPublishInfoRequest.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:EnterprisePublishing", ">GetPublishInfoResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.pub.GetPublishInfoResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:EnterprisePublishing", "GetPublishInfoResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[4] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("SetPublishInfo");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:EnterprisePublishing", "SetPublishInfoRequest"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:EnterprisePublishing", ">SetPublishInfoRequest"), com.woodwing.enterprise.interfaces.services.pub.SetPublishInfoRequest.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:EnterprisePublishing", ">SetPublishInfoResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.pub.SetPublishInfoResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:EnterprisePublishing", "SetPublishInfoResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[5] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("PreviewDossiers");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:EnterprisePublishing", "PreviewDossiersRequest"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:EnterprisePublishing", ">PreviewDossiersRequest"), com.woodwing.enterprise.interfaces.services.pub.PreviewDossiersRequest.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:EnterprisePublishing", ">PreviewDossiersResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.pub.PreviewDossiersResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:EnterprisePublishing", "PreviewDossiersResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[6] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("GetDossierOrder");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:EnterprisePublishing", "GetDossierOrderRequest"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:EnterprisePublishing", ">GetDossierOrderRequest"), com.woodwing.enterprise.interfaces.services.pub.GetDossierOrderRequest.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:EnterprisePublishing", ">GetDossierOrderResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.pub.GetDossierOrderResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:EnterprisePublishing", "GetDossierOrderResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[7] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("UpdateDossierOrder");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:EnterprisePublishing", "UpdateDossierOrderRequest"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:EnterprisePublishing", ">UpdateDossierOrderRequest"), com.woodwing.enterprise.interfaces.services.pub.UpdateDossierOrderRequest.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:EnterprisePublishing", ">UpdateDossierOrderResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.pub.UpdateDossierOrderResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:EnterprisePublishing", "UpdateDossierOrderResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[8] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("AbortOperation");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:EnterprisePublishing", "AbortOperationRequest"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:EnterprisePublishing", ">AbortOperationRequest"), com.woodwing.enterprise.interfaces.services.pub.AbortOperationRequest.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(org.apache.axis.encoding.XMLType.AXIS_VOID);
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[9] = oper;

    }

    private static void _initOperationDesc2(){
        org.apache.axis.description.OperationDesc oper;
        org.apache.axis.description.ParameterDesc param;
        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("OperationProgress");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:EnterprisePublishing", "OperationProgressRequest"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:EnterprisePublishing", ">OperationProgressRequest"), com.woodwing.enterprise.interfaces.services.pub.OperationProgressRequest.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:EnterprisePublishing", ">OperationProgressResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.pub.OperationProgressResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:EnterprisePublishing", "OperationProgressResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[10] = oper;

    }

    public EnterprisePublishingBindingStub() throws org.apache.axis.AxisFault {
         this(null);
    }

    public EnterprisePublishingBindingStub(java.net.URL endpointURL, javax.xml.rpc.Service service) throws org.apache.axis.AxisFault {
         this(service);
         super.cachedEndpoint = endpointURL;
    }

    public EnterprisePublishingBindingStub(javax.xml.rpc.Service service) throws org.apache.axis.AxisFault {
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
            qName = new javax.xml.namespace.QName("urn:EnterprisePublishing", ">AbortOperationRequest");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.pub.AbortOperationRequest.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:EnterprisePublishing", ">GetDossierOrderRequest");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.pub.GetDossierOrderRequest.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:EnterprisePublishing", ">GetDossierOrderResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.pub.GetDossierOrderResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:EnterprisePublishing", ">GetDossierURLRequest");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.pub.GetDossierURLRequest.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:EnterprisePublishing", ">GetDossierURLResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.pub.GetDossierURLResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:EnterprisePublishing", ">GetPublishInfoRequest");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.pub.GetPublishInfoRequest.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:EnterprisePublishing", ">GetPublishInfoResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.pub.GetPublishInfoResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:EnterprisePublishing", ">OperationProgressRequest");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.pub.OperationProgressRequest.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:EnterprisePublishing", ">OperationProgressResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.pub.OperationProgressResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:EnterprisePublishing", ">PreviewDossiersRequest");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.pub.PreviewDossiersRequest.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:EnterprisePublishing", ">PreviewDossiersResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.pub.PreviewDossiersResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:EnterprisePublishing", ">PublishDossiersRequest");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.pub.PublishDossiersRequest.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:EnterprisePublishing", ">PublishDossiersResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.pub.PublishDossiersResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:EnterprisePublishing", ">SetPublishInfoRequest");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.pub.SetPublishInfoRequest.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:EnterprisePublishing", ">SetPublishInfoResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.pub.SetPublishInfoResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:EnterprisePublishing", ">UnPublishDossiersRequest");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.pub.UnPublishDossiersRequest.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:EnterprisePublishing", ">UnPublishDossiersResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.pub.UnPublishDossiersResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:EnterprisePublishing", ">UpdateDossierOrderRequest");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.pub.UpdateDossierOrderRequest.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:EnterprisePublishing", ">UpdateDossierOrderResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.pub.UpdateDossierOrderResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:EnterprisePublishing", ">UpdateDossiersRequest");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.pub.UpdateDossiersRequest.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:EnterprisePublishing", ">UpdateDossiersResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.pub.UpdateDossiersResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:EnterprisePublishing", "ArrayOfField");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.pub.Field[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:EnterprisePublishing", "Field");
            qName2 = new javax.xml.namespace.QName("", "Field");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:EnterprisePublishing", "ArrayOfObjectInfo");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.pub.ObjectInfo[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:EnterprisePublishing", "ObjectInfo");
            qName2 = new javax.xml.namespace.QName("", "ObjectInfo");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:EnterprisePublishing", "ArrayOfProgressPhase");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.pub.ProgressPhase[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:EnterprisePublishing", "ProgressPhase");
            qName2 = new javax.xml.namespace.QName("", "ProgressPhase");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:EnterprisePublishing", "ArrayOfPublishedDossier");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.pub.PublishedDossier[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:EnterprisePublishing", "PublishedDossier");
            qName2 = new javax.xml.namespace.QName("", "PublishedDossier");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:EnterprisePublishing", "ArrayOfPublishedObject");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.pub.PublishedObject[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:EnterprisePublishing", "PublishedObject");
            qName2 = new javax.xml.namespace.QName("", "PublishedObject");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:EnterprisePublishing", "ArrayOfPublishHistory");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.pub.PublishHistory[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:EnterprisePublishing", "PublishHistory");
            qName2 = new javax.xml.namespace.QName("", "PublishHistory");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:EnterprisePublishing", "ArrayOfPublishTarget");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.pub.PublishTarget[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:EnterprisePublishing", "PublishTarget");
            qName2 = new javax.xml.namespace.QName("", "PublishTarget");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:EnterprisePublishing", "ArrayOfReportMessage");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.pub.ReportMessage[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:EnterprisePublishing", "ReportMessage");
            qName2 = new javax.xml.namespace.QName("", "ReportMessage");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:EnterprisePublishing", "ArrayOfString");
            cachedSerQNames.add(qName);
            cls = java.lang.String[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:EnterprisePublishing", "String");
            qName2 = new javax.xml.namespace.QName("", "String");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:EnterprisePublishing", "Field");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.pub.Field.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:EnterprisePublishing", "MessageContext");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.pub.MessageContext.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:EnterprisePublishing", "ObjectInfo");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.pub.ObjectInfo.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:EnterprisePublishing", "ObjectType");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.pub.ObjectType.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(enumsf);
            cachedDeserFactories.add(enumdf);

            qName = new javax.xml.namespace.QName("urn:EnterprisePublishing", "PageInfo");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.pub.PageInfo.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:EnterprisePublishing", "ProgressPhase");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.pub.ProgressPhase.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:EnterprisePublishing", "PropertyType");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.pub.PropertyType.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(enumsf);
            cachedDeserFactories.add(enumdf);

            qName = new javax.xml.namespace.QName("urn:EnterprisePublishing", "PublishedDossier");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.pub.PublishedDossier.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:EnterprisePublishing", "PublishedIssue");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.pub.PublishedIssue.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:EnterprisePublishing", "PublishedObject");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.pub.PublishedObject.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:EnterprisePublishing", "PublishHistory");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.pub.PublishHistory.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:EnterprisePublishing", "PublishTarget");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.pub.PublishTarget.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:EnterprisePublishing", "ReportMessage");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.pub.ReportMessage.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:EnterprisePublishing", "String");
            cachedSerQNames.add(qName);
            cls = java.lang.String.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(org.apache.axis.encoding.ser.BaseSerializerFactory.createFactory(org.apache.axis.encoding.ser.SimpleSerializerFactory.class, cls, qName));
            cachedDeserFactories.add(org.apache.axis.encoding.ser.BaseDeserializerFactory.createFactory(org.apache.axis.encoding.ser.SimpleDeserializerFactory.class, cls, qName));

            qName = new javax.xml.namespace.QName("urn:EnterprisePublishing", "UserMessage");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.pub.UserMessage.class;
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

    public com.woodwing.enterprise.interfaces.services.pub.PublishDossiersResponse publishDossiers(com.woodwing.enterprise.interfaces.services.pub.PublishDossiersRequest parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[0]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:EnterprisePublishing#PublishDossiers");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "PublishDossiers"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.pub.PublishDossiersResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.pub.PublishDossiersResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.pub.PublishDossiersResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public com.woodwing.enterprise.interfaces.services.pub.UpdateDossiersResponse updateDossiers(com.woodwing.enterprise.interfaces.services.pub.UpdateDossiersRequest parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[1]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:EnterprisePublishing#UpdateDossiers");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "UpdateDossiers"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.pub.UpdateDossiersResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.pub.UpdateDossiersResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.pub.UpdateDossiersResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public com.woodwing.enterprise.interfaces.services.pub.UnPublishDossiersResponse unPublishDossiers(com.woodwing.enterprise.interfaces.services.pub.UnPublishDossiersRequest parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[2]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:EnterprisePublishing#UnPublishDossiers");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "UnPublishDossiers"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.pub.UnPublishDossiersResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.pub.UnPublishDossiersResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.pub.UnPublishDossiersResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public com.woodwing.enterprise.interfaces.services.pub.GetDossierURLResponse getDossierURL(com.woodwing.enterprise.interfaces.services.pub.GetDossierURLRequest parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[3]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:EnterprisePublishing#GetDossierURL");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "GetDossierURL"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.pub.GetDossierURLResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.pub.GetDossierURLResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.pub.GetDossierURLResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public com.woodwing.enterprise.interfaces.services.pub.GetPublishInfoResponse getPublishInfo(com.woodwing.enterprise.interfaces.services.pub.GetPublishInfoRequest parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[4]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:EnterprisePublishing#GetPublishInfo");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "GetPublishInfo"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.pub.GetPublishInfoResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.pub.GetPublishInfoResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.pub.GetPublishInfoResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public com.woodwing.enterprise.interfaces.services.pub.SetPublishInfoResponse setPublishInfo(com.woodwing.enterprise.interfaces.services.pub.SetPublishInfoRequest parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[5]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:EnterprisePublishing#SetPublishInfo");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "SetPublishInfo"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.pub.SetPublishInfoResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.pub.SetPublishInfoResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.pub.SetPublishInfoResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public com.woodwing.enterprise.interfaces.services.pub.PreviewDossiersResponse previewDossiers(com.woodwing.enterprise.interfaces.services.pub.PreviewDossiersRequest parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[6]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:EnterprisePublishing#PreviewDossiers");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "PreviewDossiers"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.pub.PreviewDossiersResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.pub.PreviewDossiersResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.pub.PreviewDossiersResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public com.woodwing.enterprise.interfaces.services.pub.GetDossierOrderResponse getDossierOrder(com.woodwing.enterprise.interfaces.services.pub.GetDossierOrderRequest parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[7]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:EnterprisePublishing#GetDossierOrder");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "GetDossierOrder"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.pub.GetDossierOrderResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.pub.GetDossierOrderResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.pub.GetDossierOrderResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public com.woodwing.enterprise.interfaces.services.pub.UpdateDossierOrderResponse updateDossierOrder(com.woodwing.enterprise.interfaces.services.pub.UpdateDossierOrderRequest parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[8]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:EnterprisePublishing#UpdateDossierOrder");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "UpdateDossierOrder"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.pub.UpdateDossierOrderResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.pub.UpdateDossierOrderResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.pub.UpdateDossierOrderResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public void abortOperation(com.woodwing.enterprise.interfaces.services.pub.AbortOperationRequest parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[9]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:EnterprisePublishing#AbortOperation");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "AbortOperation"));

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

    public com.woodwing.enterprise.interfaces.services.pub.OperationProgressResponse operationProgress(com.woodwing.enterprise.interfaces.services.pub.OperationProgressRequest parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[10]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:EnterprisePublishing#OperationProgress");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "OperationProgress"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.pub.OperationProgressResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.pub.OperationProgressResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.pub.OperationProgressResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

}
