/**
 * LogOn.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.wfl;

public class LogOn  implements java.io.Serializable {
    private java.lang.String user;

    private java.lang.String password;

    private java.lang.String ticket;

    private java.lang.String server;

    private java.lang.String clientName;

    private java.lang.String domain;

    private java.lang.String clientAppName;

    private java.lang.String clientAppVersion;

    private java.lang.String clientAppSerial;

    private java.lang.String clientAppProductKey;

    private java.lang.Boolean requestTicket;

    private java.lang.String[] requestInfo;

    public LogOn() {
    }

    public LogOn(
           java.lang.String user,
           java.lang.String password,
           java.lang.String ticket,
           java.lang.String server,
           java.lang.String clientName,
           java.lang.String domain,
           java.lang.String clientAppName,
           java.lang.String clientAppVersion,
           java.lang.String clientAppSerial,
           java.lang.String clientAppProductKey,
           java.lang.Boolean requestTicket,
           java.lang.String[] requestInfo) {
           this.user = user;
           this.password = password;
           this.ticket = ticket;
           this.server = server;
           this.clientName = clientName;
           this.domain = domain;
           this.clientAppName = clientAppName;
           this.clientAppVersion = clientAppVersion;
           this.clientAppSerial = clientAppSerial;
           this.clientAppProductKey = clientAppProductKey;
           this.requestTicket = requestTicket;
           this.requestInfo = requestInfo;
    }


    /**
     * Gets the user value for this LogOn.
     * 
     * @return user
     */
    public java.lang.String getUser() {
        return user;
    }


    /**
     * Sets the user value for this LogOn.
     * 
     * @param user
     */
    public void setUser(java.lang.String user) {
        this.user = user;
    }


    /**
     * Gets the password value for this LogOn.
     * 
     * @return password
     */
    public java.lang.String getPassword() {
        return password;
    }


    /**
     * Sets the password value for this LogOn.
     * 
     * @param password
     */
    public void setPassword(java.lang.String password) {
        this.password = password;
    }


    /**
     * Gets the ticket value for this LogOn.
     * 
     * @return ticket
     */
    public java.lang.String getTicket() {
        return ticket;
    }


    /**
     * Sets the ticket value for this LogOn.
     * 
     * @param ticket
     */
    public void setTicket(java.lang.String ticket) {
        this.ticket = ticket;
    }


    /**
     * Gets the server value for this LogOn.
     * 
     * @return server
     */
    public java.lang.String getServer() {
        return server;
    }


    /**
     * Sets the server value for this LogOn.
     * 
     * @param server
     */
    public void setServer(java.lang.String server) {
        this.server = server;
    }


    /**
     * Gets the clientName value for this LogOn.
     * 
     * @return clientName
     */
    public java.lang.String getClientName() {
        return clientName;
    }


    /**
     * Sets the clientName value for this LogOn.
     * 
     * @param clientName
     */
    public void setClientName(java.lang.String clientName) {
        this.clientName = clientName;
    }


    /**
     * Gets the domain value for this LogOn.
     * 
     * @return domain
     */
    public java.lang.String getDomain() {
        return domain;
    }


    /**
     * Sets the domain value for this LogOn.
     * 
     * @param domain
     */
    public void setDomain(java.lang.String domain) {
        this.domain = domain;
    }


    /**
     * Gets the clientAppName value for this LogOn.
     * 
     * @return clientAppName
     */
    public java.lang.String getClientAppName() {
        return clientAppName;
    }


    /**
     * Sets the clientAppName value for this LogOn.
     * 
     * @param clientAppName
     */
    public void setClientAppName(java.lang.String clientAppName) {
        this.clientAppName = clientAppName;
    }


    /**
     * Gets the clientAppVersion value for this LogOn.
     * 
     * @return clientAppVersion
     */
    public java.lang.String getClientAppVersion() {
        return clientAppVersion;
    }


    /**
     * Sets the clientAppVersion value for this LogOn.
     * 
     * @param clientAppVersion
     */
    public void setClientAppVersion(java.lang.String clientAppVersion) {
        this.clientAppVersion = clientAppVersion;
    }


    /**
     * Gets the clientAppSerial value for this LogOn.
     * 
     * @return clientAppSerial
     */
    public java.lang.String getClientAppSerial() {
        return clientAppSerial;
    }


    /**
     * Sets the clientAppSerial value for this LogOn.
     * 
     * @param clientAppSerial
     */
    public void setClientAppSerial(java.lang.String clientAppSerial) {
        this.clientAppSerial = clientAppSerial;
    }


    /**
     * Gets the clientAppProductKey value for this LogOn.
     * 
     * @return clientAppProductKey
     */
    public java.lang.String getClientAppProductKey() {
        return clientAppProductKey;
    }


    /**
     * Sets the clientAppProductKey value for this LogOn.
     * 
     * @param clientAppProductKey
     */
    public void setClientAppProductKey(java.lang.String clientAppProductKey) {
        this.clientAppProductKey = clientAppProductKey;
    }


    /**
     * Gets the requestTicket value for this LogOn.
     * 
     * @return requestTicket
     */
    public java.lang.Boolean getRequestTicket() {
        return requestTicket;
    }


    /**
     * Sets the requestTicket value for this LogOn.
     * 
     * @param requestTicket
     */
    public void setRequestTicket(java.lang.Boolean requestTicket) {
        this.requestTicket = requestTicket;
    }


    /**
     * Gets the requestInfo value for this LogOn.
     * 
     * @return requestInfo
     */
    public java.lang.String[] getRequestInfo() {
        return requestInfo;
    }


    /**
     * Sets the requestInfo value for this LogOn.
     * 
     * @param requestInfo
     */
    public void setRequestInfo(java.lang.String[] requestInfo) {
        this.requestInfo = requestInfo;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof LogOn)) return false;
        LogOn other = (LogOn) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.user==null && other.getUser()==null) || 
             (this.user!=null &&
              this.user.equals(other.getUser()))) &&
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
            ((this.clientAppProductKey==null && other.getClientAppProductKey()==null) || 
             (this.clientAppProductKey!=null &&
              this.clientAppProductKey.equals(other.getClientAppProductKey()))) &&
            ((this.requestTicket==null && other.getRequestTicket()==null) || 
             (this.requestTicket!=null &&
              this.requestTicket.equals(other.getRequestTicket()))) &&
            ((this.requestInfo==null && other.getRequestInfo()==null) || 
             (this.requestInfo!=null &&
              java.util.Arrays.equals(this.requestInfo, other.getRequestInfo())));
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
        if (getUser() != null) {
            _hashCode += getUser().hashCode();
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
        if (getClientAppProductKey() != null) {
            _hashCode += getClientAppProductKey().hashCode();
        }
        if (getRequestTicket() != null) {
            _hashCode += getRequestTicket().hashCode();
        }
        if (getRequestInfo() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getRequestInfo());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getRequestInfo(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        __hashCodeCalc = false;
        return _hashCode;
    }

    // Type metadata
    private static org.apache.axis.description.TypeDesc typeDesc =
        new org.apache.axis.description.TypeDesc(LogOn.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", ">LogOn"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("user");
        elemField.setXmlName(new javax.xml.namespace.QName("", "User"));
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
        elemField.setFieldName("clientAppProductKey");
        elemField.setXmlName(new javax.xml.namespace.QName("", "ClientAppProductKey"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("requestTicket");
        elemField.setXmlName(new javax.xml.namespace.QName("", "RequestTicket"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "boolean"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("requestInfo");
        elemField.setXmlName(new javax.xml.namespace.QName("", "RequestInfo"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "String"));
        elemField.setMinOccurs(0);
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "String"));
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
