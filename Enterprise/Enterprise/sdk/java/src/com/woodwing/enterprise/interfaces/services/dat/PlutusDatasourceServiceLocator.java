/**
 * PlutusDatasourceServiceLocator.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.dat;

public class PlutusDatasourceServiceLocator extends org.apache.axis.client.Service implements com.woodwing.enterprise.interfaces.services.dat.PlutusDatasourceService {

/**
 * Plutus Data source Web Service
 */

    public PlutusDatasourceServiceLocator() {
    }


    public PlutusDatasourceServiceLocator(org.apache.axis.EngineConfiguration config) {
        super(config);
    }

    public PlutusDatasourceServiceLocator(java.lang.String wsdlLoc, javax.xml.namespace.QName sName) throws javax.xml.rpc.ServiceException {
        super(wsdlLoc, sName);
    }

    // Use to get a proxy class for PlutusDatasourcePort
    private java.lang.String PlutusDatasourcePort_address = "http://127.0.0.1/Enterprise/datasourceindex.php";

    public java.lang.String getPlutusDatasourcePortAddress() {
        return PlutusDatasourcePort_address;
    }

    // The WSDD service name defaults to the port name.
    private java.lang.String PlutusDatasourcePortWSDDServiceName = "PlutusDatasourcePort";

    public java.lang.String getPlutusDatasourcePortWSDDServiceName() {
        return PlutusDatasourcePortWSDDServiceName;
    }

    public void setPlutusDatasourcePortWSDDServiceName(java.lang.String name) {
        PlutusDatasourcePortWSDDServiceName = name;
    }

    public com.woodwing.enterprise.interfaces.services.dat.PlutusDatasourcePort_PortType getPlutusDatasourcePort() throws javax.xml.rpc.ServiceException {
       java.net.URL endpoint;
        try {
            endpoint = new java.net.URL(PlutusDatasourcePort_address);
        }
        catch (java.net.MalformedURLException e) {
            throw new javax.xml.rpc.ServiceException(e);
        }
        return getPlutusDatasourcePort(endpoint);
    }

    public com.woodwing.enterprise.interfaces.services.dat.PlutusDatasourcePort_PortType getPlutusDatasourcePort(java.net.URL portAddress) throws javax.xml.rpc.ServiceException {
        try {
            com.woodwing.enterprise.interfaces.services.dat.PlutusDatasourceBindingStub _stub = new com.woodwing.enterprise.interfaces.services.dat.PlutusDatasourceBindingStub(portAddress, this);
            _stub.setPortName(getPlutusDatasourcePortWSDDServiceName());
            return _stub;
        }
        catch (org.apache.axis.AxisFault e) {
            return null;
        }
    }

    public void setPlutusDatasourcePortEndpointAddress(java.lang.String address) {
        PlutusDatasourcePort_address = address;
    }

    /**
     * For the given interface, get the stub implementation.
     * If this service has no port for the given interface,
     * then ServiceException is thrown.
     */
    public java.rmi.Remote getPort(Class serviceEndpointInterface) throws javax.xml.rpc.ServiceException {
        try {
            if (com.woodwing.enterprise.interfaces.services.dat.PlutusDatasourcePort_PortType.class.isAssignableFrom(serviceEndpointInterface)) {
                com.woodwing.enterprise.interfaces.services.dat.PlutusDatasourceBindingStub _stub = new com.woodwing.enterprise.interfaces.services.dat.PlutusDatasourceBindingStub(new java.net.URL(PlutusDatasourcePort_address), this);
                _stub.setPortName(getPlutusDatasourcePortWSDDServiceName());
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
        if ("PlutusDatasourcePort".equals(inputPortName)) {
            return getPlutusDatasourcePort();
        }
        else  {
            java.rmi.Remote _stub = getPort(serviceEndpointInterface);
            ((org.apache.axis.client.Stub) _stub).setPortName(portName);
            return _stub;
        }
    }

    public javax.xml.namespace.QName getServiceName() {
        return new javax.xml.namespace.QName("urn:PlutusDatasource", "PlutusDatasourceService");
    }

    private java.util.HashSet ports = null;

    public java.util.Iterator getPorts() {
        if (ports == null) {
            ports = new java.util.HashSet();
            ports.add(new javax.xml.namespace.QName("urn:PlutusDatasource", "PlutusDatasourcePort"));
        }
        return ports.iterator();
    }

    /**
    * Set the endpoint address for the specified port name.
    */
    public void setEndpointAddress(java.lang.String portName, java.lang.String address) throws javax.xml.rpc.ServiceException {
        
if ("PlutusDatasourcePort".equals(portName)) {
            setPlutusDatasourcePortEndpointAddress(address);
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
