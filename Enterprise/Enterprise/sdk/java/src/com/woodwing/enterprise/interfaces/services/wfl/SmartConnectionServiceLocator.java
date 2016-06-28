/**
 * SmartConnectionServiceLocator.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.wfl;

public class SmartConnectionServiceLocator extends org.apache.axis.client.Service implements com.woodwing.enterprise.interfaces.services.wfl.SmartConnectionService {

/**
 * Enterprise Server workflow service
 */

    public SmartConnectionServiceLocator() {
    }


    public SmartConnectionServiceLocator(org.apache.axis.EngineConfiguration config) {
        super(config);
    }

    public SmartConnectionServiceLocator(java.lang.String wsdlLoc, javax.xml.namespace.QName sName) throws javax.xml.rpc.ServiceException {
        super(wsdlLoc, sName);
    }

    // Use to get a proxy class for SmartConnectionPortName
    private java.lang.String SmartConnectionPortName_address = "http://127.0.0.1/Enterprise/index.php";

    public java.lang.String getSmartConnectionPortNameAddress() {
        return SmartConnectionPortName_address;
    }

    // The WSDD service name defaults to the port name.
    private java.lang.String SmartConnectionPortNameWSDDServiceName = "SmartConnectionPortName";

    public java.lang.String getSmartConnectionPortNameWSDDServiceName() {
        return SmartConnectionPortNameWSDDServiceName;
    }

    public void setSmartConnectionPortNameWSDDServiceName(java.lang.String name) {
        SmartConnectionPortNameWSDDServiceName = name;
    }

    public com.woodwing.enterprise.interfaces.services.wfl.SmartConnectionPort getSmartConnectionPortName() throws javax.xml.rpc.ServiceException {
       java.net.URL endpoint;
        try {
            endpoint = new java.net.URL(SmartConnectionPortName_address);
        }
        catch (java.net.MalformedURLException e) {
            throw new javax.xml.rpc.ServiceException(e);
        }
        return getSmartConnectionPortName(endpoint);
    }

    public com.woodwing.enterprise.interfaces.services.wfl.SmartConnectionPort getSmartConnectionPortName(java.net.URL portAddress) throws javax.xml.rpc.ServiceException {
        try {
            com.woodwing.enterprise.interfaces.services.wfl.SmartConnectionBindingStub _stub = new com.woodwing.enterprise.interfaces.services.wfl.SmartConnectionBindingStub(portAddress, this);
            _stub.setPortName(getSmartConnectionPortNameWSDDServiceName());
            return _stub;
        }
        catch (org.apache.axis.AxisFault e) {
            return null;
        }
    }

    public void setSmartConnectionPortNameEndpointAddress(java.lang.String address) {
        SmartConnectionPortName_address = address;
    }

    /**
     * For the given interface, get the stub implementation.
     * If this service has no port for the given interface,
     * then ServiceException is thrown.
     */
    public java.rmi.Remote getPort(Class serviceEndpointInterface) throws javax.xml.rpc.ServiceException {
        try {
            if (com.woodwing.enterprise.interfaces.services.wfl.SmartConnectionPort.class.isAssignableFrom(serviceEndpointInterface)) {
                com.woodwing.enterprise.interfaces.services.wfl.SmartConnectionBindingStub _stub = new com.woodwing.enterprise.interfaces.services.wfl.SmartConnectionBindingStub(new java.net.URL(SmartConnectionPortName_address), this);
                _stub.setPortName(getSmartConnectionPortNameWSDDServiceName());
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
        if ("SmartConnectionPortName".equals(inputPortName)) {
            return getSmartConnectionPortName();
        }
        else  {
            java.rmi.Remote _stub = getPort(serviceEndpointInterface);
            ((org.apache.axis.client.Stub) _stub).setPortName(portName);
            return _stub;
        }
    }

    public javax.xml.namespace.QName getServiceName() {
        return new javax.xml.namespace.QName("urn:SmartConnection", "SmartConnectionService");
    }

    private java.util.HashSet ports = null;

    public java.util.Iterator getPorts() {
        if (ports == null) {
            ports = new java.util.HashSet();
            ports.add(new javax.xml.namespace.QName("urn:SmartConnection", "SmartConnectionPortName"));
        }
        return ports.iterator();
    }

    /**
    * Set the endpoint address for the specified port name.
    */
    public void setEndpointAddress(java.lang.String portName, java.lang.String address) throws javax.xml.rpc.ServiceException {
        
if ("SmartConnectionPortName".equals(portName)) {
            setSmartConnectionPortNameEndpointAddress(address);
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
