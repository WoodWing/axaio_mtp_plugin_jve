/**
 * LogOnRequest.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.adm;

public class LogOnRequest  implements java.io.Serializable {
    private java.lang.String adminUser;

    private java.lang.String password;

    private java.lang.String ticket;

    private java.lang.String server;

    private java.lang.String clientName;

    private java.lang.String domain;

    private java.lang.String clientAppName;

    private java.lang.String clientAppVersion;

    private java.lang.String clientAppSerial;

    private java.lang.String clientAppCode;

    public LogOnRequest() {
    }

    public LogOnRequest(
           java.lang.String adminUser,
           java.lang.String password,
           java.lang.String ticket,
           java.lang.String server,
           java.lang.String clientName,
           java.lang.String domain,
           java.lang.String clientAppName,
           java.lang.String clientAppVersion,
           java.lang.String clientAppSerial,
           java.lang.String clientAppCode) {
           this.adminUser = adminUser;
           this.password = password;
           this.ticket = ticket;
           this.server = server;
           this.clientName = clientName;
           this.domain = domain;
           this.clientAppName = clientAppName;
           this.clientAppVersion = clientAppVersion;
           this.clientAppSerial = clientAppSerial;
           this.clientAppCode = clientAppCode;
    }


    /**
     * Gets the adminUser value for this LogOnRequest.
     * 
     * @return adminUser
     */
    public java.lang.String getAdminUser() {
        return adminUser;
    }


    /**
     * Sets the adminUser value for this LogOnRequest.
     * 
     * @param adminUser
     */
    public void setAdminUser(java.lang.String adminUser) {
        this.adminUser = adminUser;
    }


    /**
     * Gets the password value for this LogOnRequest.
     * 
     * @return password
     */
    public java.lang.String getPassword() {
        return password;
    }


    /**
     * Sets the password value for this LogOnRequest.
     * 
     * @param password
     */
    public void setPassword(java.lang.String password) {
        this.password = password;
    }


    /**
     * Gets the ticket value for this LogOnRequest.
     * 
     * @return ticket
     */
    public java.lang.String getTicket() {
        return ticket;
    }


    /**
     * Sets the ticket value for this LogOnRequest.
     * 
     * @param ticket
     */
    public void setTicket(java.lang.String ticket) {
        this.ticket = ticket;
    }


    /**
     * Gets the server value for this LogOnRequest.
     * 
     * @return server
     */
    public java.lang.String getServer() {
        return server;
    }


    /**
     * Sets the server value for this LogOnRequest.
     * 
     * @param server
     */
    public void setServer(java.lang.String server) {
        this.server = server;
    }


    /**
     * Gets the clientName value for this LogOnRequest.
     * 
     * @return clientName
     */
    public java.lang.String getClientName() {
        return clientName;
    }


    /**
     * Sets the clientName value for this LogOnRequest.
     * 
     * @param clientName
     */
    public void setClientName(java.lang.String clientName) {
        this.clientName = clientName;
    }


    /**
     * Gets the domain value for this LogOnRequest.
     * 
     * @return domain
     */
    public java.lang.String getDomain() {
        return domain;
    }


    /**
     * Sets the domain value for this LogOnRequest.
     * 
     * @param domain
     */
    public void setDomain(java.lang.String domain) {
        this.domain = domain;
    }


    /**
     * Gets the clientAppName value for this LogOnRequest.
     * 
     * @return clientAppName
     */
    public java.lang.String getClientAppName() {
        return clientAppName;
    }


    /**
     * Sets the clientAppName value for this LogOnRequest.
     * 
     * @param clientAppName
     */
    public void setClientAppName(java.lang.String clientAppName) {
        this.clientAppName = clientAppName;
    }


    /**
     * Gets the clientAppVersion value for this LogOnRequest.
     * 
     * @return clientAppVersion
     */
    public java.lang.String getClientAppVersion() {
        return clientAppVersion;
    }


    /**
     * Sets the clientAppVersion value for this LogOnRequest.
     * 
     * @param clientAppVersion
     */
    public void setClientAppVersion(java.lang.String clientAppVersion) {
        this.clientAppVersion = clientAppVersion;
    }


    /**
     * Gets the clientAppSerial value for this LogOnRequest.
     * 
     * @return clientAppSerial
     */
    public java.lang.String getClientAppSerial() {
        return clientAppSerial;
    }


    /**
     * Sets the clientAppSerial value for this LogOnRequest.
     * 
     * @param clientAppSerial
     */
    public void setClientAppSerial(java.lang.String clientAppSerial) {
        this.clientAppSerial = clientAppSerial;
    }


    /**
     * Gets the clientAppCode value for this LogOnRequest.
     * 
     * @return clientAppCode
     */
    public java.lang.String getClientAppCode() {
        return clientAppCode;
    }


    /**
     * Sets the clientAppCode value for this LogOnRequest.
     * 
     * @param clientAppCode
     */
    public void setClientAppCode(java.lang.String clientAppCode) {
        this.clientAppCode = clientAppCode;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof LogOnRequest)) return false;
        LogOnRequest other = (LogOnRequest) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.adminUser==null && other.getAdminUser()==null) || 
             (this.adminUser!=null &&
              this.adminUser.equals(other.getAdminUser()))) &&
            ((this.password==null && other.getPassword()==null) || 
             (this.password!=null &&
              this.password.equals(other.getPassword()))) &&
            ((this.ticket==null && other.getTicket()==null) || 
             (this.ticket!=null &&
              this.ticket.equals(other.getTicket()))) &&
            ((this.server==null && other.getServer()==null) || 
             (this.server!=null &&
              this.server.equals(other.getServer()))) &&
            ((this.clientName==null && other.getClientName()==null) || 
             (this.clientName!=null &&
              this.clientName.equals(other.getClientName()))) &&
            ((this.domain==null && other.getDomain()==null) || 
             (this.domain!=null &&
              this.domain.equals(other.getDomain()))) &&
            ((this.clientAppName==null && other.getClientAppName()==null) || 
             (this.clientAppName!=null &&
              this.clientAppName.equals(other.getClientAppName()))) &&
            ((this.clientAppVersion==null && other.getClientAppVersion()==null) || 
             (this.clientAppVersion!=null &&
              this.clientAppVersion.equals(other.getClientAppVersion()))) &&
            ((this.clientAppSerial==null && other.getClientAppSerial()==null) || 
             (this.clientAppSerial!=null &&
              this.clientAppSerial.equals(other.getClientAppSerial()))) &&
            ((this.clientAppCode==null && other.getClientAppCode()==null) || 
             (this.clientAppCode!=null &&
              this.clientAppCode.equals(other.getClientAppCode())));
        __equalsCalc = null;
        return _equals;
    }

    private boolean __hashCodeCalc = false;
    public synchronized int hashCode() {
        if (__hashCodeCalc) {
            return 0;
        }
        __hashCodeCalc = true;
        int _hashCode = 1;
        if (getAdminUser() != null) {
            _hashCode += getAdminUser().hashCode();
        }
        if (getPassword() != null) {
            _hashCode += getPassword().hashCode();
        }
        if (getTicket() != null) {
            _hashCode += getTicket().hashCode();
        }
        if (getServer() != null) {
            _hashCode += getServer().hashCode();
        }
        if (getClientName() != null) {
            _hashCode += getClientName().hashCode();
        }
        if (getDomain() != null) {
            _hashCode += getDomain().hashCode();
        }
        if (getClientAppName() != null) {
            _hashCode += getClientAppName().hashCode();
        }
        if (getClientAppVersion() != null) {
            _hashCode += getClientAppVersion().hashCode();
        }
        if (getClientAppSerial() != null) {
            _hashCode += getClientAppSerial().hashCode();
        }
        if (getClientAppCode() != null) {
            _hashCode += getClientAppCode().hashCode();
        }
        __hashCodeCalc = false;
        return _hashCode;
    }

    // Type metadata
    private static org.apache.axis.description.TypeDesc typeDesc =
        new org.apache.axis.description.TypeDesc(LogOnRequest.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">LogOnRequest"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("adminUser");
        elemField.setXmlName(new javax.xml.namespace.QName("", "AdminUser"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("password");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Password"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("ticket");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Ticket"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("server");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Server"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("clientName");
        elemField.setXmlName(new javax.xml.namespace.QName("", "ClientName"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("domain");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Domain"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("clientAppName");
        elemField.setXmlName(new javax.xml.namespace.QName("", "ClientAppName"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("clientAppVersion");
        elemField.setXmlName(new javax.xml.namespace.QName("", "ClientAppVersion"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("clientAppSerial");
        elemField.setXmlName(new javax.xml.namespace.QName("", "ClientAppSerial"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("clientAppCode");
        elemField.setXmlName(new javax.xml.namespace.QName("", "ClientAppCode"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
    }

    /**
     * Return type metadata object
     */
    public static org.apache.axis.description.TypeDesc getTypeDesc() {
        return typeDesc;
    }

    /**
     * Get Custom Serializer
     */
    public static org.apache.axis.encoding.Serializer getSerializer(
           java.lang.String mechType, 
           java.lang.Class _javaType,  
           javax.xml.namespace.QName _xmlType) {
        return 
          new  org.apache.axis.encoding.ser.BeanSerializer(
            _javaType, _xmlType, typeDesc);
    }

    /**
     * Get Custom Deserializer
     */
    public static org.apache.axis.encoding.Deserializer getDeserializer(
           java.lang.String mechType, 
           java.lang.Class _javaType,  
           javax.xml.namespace.QName _xmlType) {
        return 
          new  org.apache.axis.encoding.ser.BeanDeserializer(
            _javaType, _xmlType, typeDesc);
    }

}
