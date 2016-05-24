/**
 * EnterprisePublishingServiceLocator.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.pub;

public class EnterprisePublishingServiceLocator extends org.apache.axis.client.Service implements com.woodwing.enterprise.interfaces.services.pub.EnterprisePublishingService {

/**
 * WoodWing Enterprise Channel Publishing Web Service
 */

    public EnterprisePublishingServiceLocator() {
    }


    public EnterprisePublishingServiceLocator(org.apache.axis.EngineConfiguration config) {
        super(config);
    }

    public EnterprisePublishingServiceLocator(java.lang.String wsdlLoc, javax.xml.namespace.QName sName) throws javax.xml.rpc.ServiceException {
        super(wsdlLoc, sName);
    }

    // Use to get a proxy class for EnterprisePublishingPort
    private java.lang.String EnterprisePublishingPort_address = "http://127.0.0.1/Enterprise/publishindex.php";

    public java.lang.String getEnterprisePublishingPortAddress() {
        return EnterprisePublishingPort_address;
    }

    // The WSDD service name defaults to the port name.
    private java.lang.String EnterprisePublishingPortWSDDServiceName = "EnterprisePublishingPort";

    public java.lang.String getEnterprisePublishingPortWSDDServiceName() {
        return EnterprisePublishingPortWSDDServiceName;
    }

    public void setEnterprisePublishingPortWSDDServiceName(java.lang.String name) {
        EnterprisePublishingPortWSDDServiceName = name;
    }

    public com.woodwing.enterprise.interfaces.services.pub.EnterprisePublishingPort_PortType getEnterprisePublishingPort() throws javax.xml.rpc.ServiceException {
       java.net.URL endpoint;
        try {
            endpoint = new java.net.URL(EnterprisePublishingPort_address);
        }
        catch (java.net.MalformedURLException e) {
            throw new javax.xml.rpc.ServiceException(e);
        }
        return getEnterprisePublishingPort(endpoint);
    }

    public com.woodwing.enterprise.interfaces.services.pub.EnterprisePublishingPort_PortType getEnterprisePublishingPort(java.net.URL portAddress) throws javax.xml.rpc.ServiceException {
        try {
            com.woodwing.enterprise.interfaces.services.pub.EnterprisePublishingBindingStub _stub = new com.woodwing.enterprise.interfaces.services.pub.EnterprisePublishingBindingStub(portAddress, this);
            _stub.setPortName(getEnterprisePublishingPortWSDDServiceName());
            return _stub;
        }
        catch (org.apache.axis.AxisFault e) {
            return null;
        }
    }

    public void setEnterprisePublishingPortEndpointAddress(java.lang.String address) {
        EnterprisePublishingPort_address = address;
    }

    /**
     * For the given interface, get the stub implementation.
     * If this service has no port for the given interface,
     * then ServiceException is thrown.
     */
    public java.rmi.Remote getPort(Class serviceEndpointInterface) throws javax.xml.rpc.ServiceException {
        try {
            if (com.woodwing.enterprise.interfaces.services.pub.EnterprisePublishingPort_PortType.class.isAssignableFrom(serviceEndpointInterface)) {
                com.woodwing.enterprise.interfaces.services.pub.EnterprisePublishingBindingStub _stub = new com.woodwing.enterprise.interfaces.services.pub.EnterprisePublishingBindingStub(new java.net.URL(EnterprisePublishingPort_address), this);
                _stub.setPortName(getEnterprisePublishingPortWSDDServiceName());
                return _stub;
            }
        }
        catch (java.lang.Throwable t) {
            throw new javax.xml.rpc.ServiceException(t);
        }
        throw new javax.xml.rpc.ServiceException("There is no stub implementation for the interface:  " + (serviceEndpointInterface == null ? "null" : serviceEndpointInterface.getName()));
    }

    /**
     * For the given interface, get the stub implementation.
     * If this service has no port for the given interface,
     * then ServiceException is thrown.
     */
    public java.rmi.Remote getPort(javax.xml.namespace.QName portName, Class serviceEndpointInterface) throws javax.xml.rpc.ServiceException {
        if (portName == null) {
            return getPort(serviceEndpointInterface);
        }
        java.lang.String inputPortName = portName.getLocalPart();
        if ("EnterprisePublishingPort".equals(inputPortName)) {
            return getEnterprisePublishingPort();
        }
        else  {
            java.rmi.Remote _stub = getPort(serviceEndpointInterface);
            ((org.apache.axis.client.Stub) _stub).setPortName(portName);
            return _stub;
        }
    }

    public javax.xml.namespace.QName getServiceName() {
        return new javax.xml.namespace.QName("urn:EnterprisePublishing", "EnterprisePublishingService");
    }

    private java.util.HashSet ports = null;

    public java.util.Iterator getPorts() {
        if (ports == null) {
            ports = new java.util.HashSet();
            ports.add(new javax.xml.namespace.QName("urn:EnterprisePublishing", "EnterprisePublishingPort"));
        }
        return ports.iterator();
    }

    /**
    * Set the endpoint address for the specified port name.
    */
    public void setEndpointAddress(java.lang.String portName, java.lang.String address) throws javax.xml.rpc.ServiceException {
        
if ("EnterprisePublishingPort".equals(portName)) {
            setEnterprisePublishingPortEndpointAddress(address);
        }
        else 
{ // Unknown Port Name
            throw new javax.xml.rpc.ServiceException(" Cannot set Endpoint Address for Unknown Port" + portName);
        }
    }

    /**
    * Set the endpoint address for the specified port name.
    */
    public void setEndpointAddress(javax.xml.namespace.QName portName, java.lang.String address) throws javax.xml.rpc.ServiceException {
        setEndpointAddress(portName.getLocalPart(), address);
    }

}
