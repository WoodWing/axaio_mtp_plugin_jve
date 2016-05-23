/**
 * SmartEditorialPlanBindingStub.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.pln;

public class SmartEditorialPlanBindingStub extends org.apache.axis.client.Stub implements com.woodwing.enterprise.interfaces.services.pln.SmartEditorialPlanPort_PortType {
    private java.util.Vector cachedSerClasses = new java.util.Vector();
    private java.util.Vector cachedSerQNames = new java.util.Vector();
    private java.util.Vector cachedSerFactories = new java.util.Vector();
    private java.util.Vector cachedDeserFactories = new java.util.Vector();

    static org.apache.axis.description.OperationDesc [] _operations;

    static {
        _operations = new org.apache.axis.description.OperationDesc[8];
        _initOperationDesc1();
    }

    private static void _initOperationDesc1(){
        org.apache.axis.description.OperationDesc oper;
        org.apache.axis.description.ParameterDesc param;
        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("LogOn");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartEditorialPlan", "LogOn"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartEditorialPlan", ">LogOn"), com.woodwing.enterprise.interfaces.services.pln.LogOn.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:SmartEditorialPlan", ">LogOnResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.pln.LogOnResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:SmartEditorialPlan", "LogOnResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[0] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("LogOff");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartEditorialPlan", "LogOff"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartEditorialPlan", ">LogOff"), com.woodwing.enterprise.interfaces.services.pln.LogOff.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(org.apache.axis.encoding.XMLType.AXIS_VOID);
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[1] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("CreateLayouts");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartEditorialPlan", "CreateLayouts"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartEditorialPlan", ">CreateLayouts"), com.woodwing.enterprise.interfaces.services.pln.CreateLayouts.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:SmartEditorialPlan", ">CreateLayoutsResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.pln.CreateLayoutsResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:SmartEditorialPlan", "CreateLayoutsResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[2] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("ModifyLayouts");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartEditorialPlan", "ModifyLayouts"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartEditorialPlan", ">ModifyLayouts"), com.woodwing.enterprise.interfaces.services.pln.ModifyLayouts.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:SmartEditorialPlan", ">ModifyLayoutsResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.pln.ModifyLayoutsResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:SmartEditorialPlan", "ModifyLayoutsResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[3] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("DeleteLayouts");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartEditorialPlan", "DeleteLayouts"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartEditorialPlan", ">DeleteLayouts"), com.woodwing.enterprise.interfaces.services.pln.DeleteLayouts.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(org.apache.axis.encoding.XMLType.AXIS_VOID);
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[4] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("CreateAdverts");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartEditorialPlan", "CreateAdverts"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartEditorialPlan", ">CreateAdverts"), com.woodwing.enterprise.interfaces.services.pln.CreateAdverts.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:SmartEditorialPlan", ">CreateAdvertsResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.pln.CreateAdvertsResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:SmartEditorialPlan", "CreateAdvertsResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[5] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("ModifyAdverts");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartEditorialPlan", "ModifyAdverts"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartEditorialPlan", ">ModifyAdverts"), com.woodwing.enterprise.interfaces.services.pln.ModifyAdverts.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(new javax.xml.namespace.QName("urn:SmartEditorialPlan", ">ModifyAdvertsResponse"));
        oper.setReturnClass(com.woodwing.enterprise.interfaces.services.pln.ModifyAdvertsResponse.class);
        oper.setReturnQName(new javax.xml.namespace.QName("urn:SmartEditorialPlan", "ModifyAdvertsResponse"));
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[6] = oper;

        oper = new org.apache.axis.description.OperationDesc();
        oper.setName("DeleteAdverts");
        param = new org.apache.axis.description.ParameterDesc(new javax.xml.namespace.QName("urn:SmartEditorialPlan", "DeleteAdverts"), org.apache.axis.description.ParameterDesc.IN, new javax.xml.namespace.QName("urn:SmartEditorialPlan", ">DeleteAdverts"), com.woodwing.enterprise.interfaces.services.pln.DeleteAdverts.class, false, false);
        oper.addParameter(param);
        oper.setReturnType(org.apache.axis.encoding.XMLType.AXIS_VOID);
        oper.setStyle(org.apache.axis.constants.Style.DOCUMENT);
        oper.setUse(org.apache.axis.constants.Use.LITERAL);
        _operations[7] = oper;

    }

    public SmartEditorialPlanBindingStub() throws org.apache.axis.AxisFault {
         this(null);
    }

    public SmartEditorialPlanBindingStub(java.net.URL endpointURL, javax.xml.rpc.Service service) throws org.apache.axis.AxisFault {
         this(service);
         super.cachedEndpoint = endpointURL;
    }

    public SmartEditorialPlanBindingStub(javax.xml.rpc.Service service) throws org.apache.axis.AxisFault {
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
            qName = new javax.xml.namespace.QName("urn:SmartEditorialPlan", ">CreateAdverts");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.pln.CreateAdverts.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartEditorialPlan", ">CreateAdvertsResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.pln.CreateAdvertsResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartEditorialPlan", ">CreateLayouts");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.pln.CreateLayouts.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartEditorialPlan", ">CreateLayoutsResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.pln.CreateLayoutsResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartEditorialPlan", ">DeleteAdverts");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.pln.DeleteAdverts.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartEditorialPlan", ">DeleteLayouts");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.pln.DeleteLayouts.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartEditorialPlan", ">LogOff");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.pln.LogOff.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartEditorialPlan", ">LogOn");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.pln.LogOn.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartEditorialPlan", ">LogOnResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.pln.LogOnResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartEditorialPlan", ">ModifyAdverts");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.pln.ModifyAdverts.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartEditorialPlan", ">ModifyAdvertsResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.pln.ModifyAdvertsResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartEditorialPlan", ">ModifyLayouts");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.pln.ModifyLayouts.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartEditorialPlan", ">ModifyLayoutsResponse");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.pln.ModifyLayoutsResponse.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartEditorialPlan", "Advert");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.pln.Advert.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartEditorialPlan", "ArrayOfAdvert");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.pln.Advert[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:SmartEditorialPlan", "Advert");
            qName2 = new javax.xml.namespace.QName("", "Advert");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:SmartEditorialPlan", "ArrayOfAttachment");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.pln.Attachment[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:SmartEditorialPlan", "Attachment");
            qName2 = new javax.xml.namespace.QName("", "Attachment");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:SmartEditorialPlan", "ArrayOfEdition");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.pln.Edition[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:SmartEditorialPlan", "Edition");
            qName2 = new javax.xml.namespace.QName("", "Edition");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:SmartEditorialPlan", "ArrayOfLayout");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.pln.Layout[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:SmartEditorialPlan", "Layout");
            qName2 = new javax.xml.namespace.QName("", "Layout");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:SmartEditorialPlan", "ArrayOfLayoutFromTemplate");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.pln.LayoutFromTemplate[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:SmartEditorialPlan", "LayoutFromTemplate");
            qName2 = new javax.xml.namespace.QName("", "LayoutFromTemplate");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:SmartEditorialPlan", "ArrayOfPage");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.pln.Page[].class;
            cachedSerClasses.add(cls);
            qName = new javax.xml.namespace.QName("urn:SmartEditorialPlan", "Page");
            qName2 = new javax.xml.namespace.QName("", "Page");
            cachedSerFactories.add(new org.apache.axis.encoding.ser.ArraySerializerFactory(qName, qName2));
            cachedDeserFactories.add(new org.apache.axis.encoding.ser.ArrayDeserializerFactory());

            qName = new javax.xml.namespace.QName("urn:SmartEditorialPlan", "Attachment");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.pln.Attachment.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartEditorialPlan", "AttachmentContent");
            cachedSerQNames.add(qName);
            cls = byte[].class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(arraysf);
            cachedDeserFactories.add(arraydf);

            qName = new javax.xml.namespace.QName("urn:SmartEditorialPlan", "Edition");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.pln.Edition.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartEditorialPlan", "Layout");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.pln.Layout.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartEditorialPlan", "LayoutFromTemplate");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.pln.LayoutFromTemplate.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartEditorialPlan", "Page");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.pln.Page.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartEditorialPlan", "Placement");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.pln.Placement.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(beansf);
            cachedDeserFactories.add(beandf);

            qName = new javax.xml.namespace.QName("urn:SmartEditorialPlan", "PublishPrioType");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.pln.PublishPrioType.class;
            cachedSerClasses.add(cls);
            cachedSerFactories.add(enumsf);
            cachedDeserFactories.add(enumdf);

            qName = new javax.xml.namespace.QName("urn:SmartEditorialPlan", "RenditionType");
            cachedSerQNames.add(qName);
            cls = com.woodwing.enterprise.interfaces.services.pln.RenditionType.class;
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

    public com.woodwing.enterprise.interfaces.services.pln.LogOnResponse logOn(com.woodwing.enterprise.interfaces.services.pln.LogOn parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[0]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartEditorialPlan#editorialplan#LogOn");
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
                return (com.woodwing.enterprise.interfaces.services.pln.LogOnResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.pln.LogOnResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.pln.LogOnResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public void logOff(com.woodwing.enterprise.interfaces.services.pln.LogOff parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[1]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartEditorialPlan#editorialplan#LogOff");
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

    public com.woodwing.enterprise.interfaces.services.pln.CreateLayoutsResponse createLayouts(com.woodwing.enterprise.interfaces.services.pln.CreateLayouts parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[2]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartEditorialPlan#editorialplan#CreateLayouts");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "CreateLayouts"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.pln.CreateLayoutsResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.pln.CreateLayoutsResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.pln.CreateLayoutsResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public com.woodwing.enterprise.interfaces.services.pln.ModifyLayoutsResponse modifyLayouts(com.woodwing.enterprise.interfaces.services.pln.ModifyLayouts parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[3]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartEditorialPlan#editorialplan#ModifyLayouts");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "ModifyLayouts"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.pln.ModifyLayoutsResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.pln.ModifyLayoutsResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.pln.ModifyLayoutsResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public void deleteLayouts(com.woodwing.enterprise.interfaces.services.pln.DeleteLayouts parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[4]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartEditorialPlan#editorialplan#DeleteLayouts");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "DeleteLayouts"));

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

    public com.woodwing.enterprise.interfaces.services.pln.CreateAdvertsResponse createAdverts(com.woodwing.enterprise.interfaces.services.pln.CreateAdverts parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[5]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartEditorialPlan#editorialplan#CreateAdverts");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "CreateAdverts"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.pln.CreateAdvertsResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.pln.CreateAdvertsResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.pln.CreateAdvertsResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public com.woodwing.enterprise.interfaces.services.pln.ModifyAdvertsResponse modifyAdverts(com.woodwing.enterprise.interfaces.services.pln.ModifyAdverts parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[6]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartEditorialPlan#editorialplan#ModifyAdverts");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "ModifyAdverts"));

        setRequestHeaders(_call);
        setAttachments(_call);
 try {        java.lang.Object _resp = _call.invoke(new java.lang.Object[] {parameters});

        if (_resp instanceof java.rmi.RemoteException) {
            throw (java.rmi.RemoteException)_resp;
        }
        else {
            extractAttachments(_call);
            try {
                return (com.woodwing.enterprise.interfaces.services.pln.ModifyAdvertsResponse) _resp;
            } catch (java.lang.Exception _exception) {
                return (com.woodwing.enterprise.interfaces.services.pln.ModifyAdvertsResponse) org.apache.axis.utils.JavaUtils.convert(_resp, com.woodwing.enterprise.interfaces.services.pln.ModifyAdvertsResponse.class);
            }
        }
  } catch (org.apache.axis.AxisFault axisFaultException) {
  throw axisFaultException;
}
    }

    public void deleteAdverts(com.woodwing.enterprise.interfaces.services.pln.DeleteAdverts parameters) throws java.rmi.RemoteException {
        if (super.cachedEndpoint == null) {
            throw new org.apache.axis.NoEndPointException();
        }
        org.apache.axis.client.Call _call = createCall();
        _call.setOperation(_operations[7]);
        _call.setUseSOAPAction(true);
        _call.setSOAPActionURI("urn:SmartEditorialPlan#editorialplan#DeleteAdverts");
        _call.setEncodingStyle(null);
        _call.setProperty(org.apache.axis.client.Call.SEND_TYPE_ATTR, Boolean.FALSE);
        _call.setProperty(org.apache.axis.AxisEngine.PROP_DOMULTIREFS, Boolean.FALSE);
        _call.setSOAPVersion(org.apache.axis.soap.SOAPConstants.SOAP11_CONSTANTS);
        _call.setOperationName(new javax.xml.namespace.QName("", "DeleteAdverts"));

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
