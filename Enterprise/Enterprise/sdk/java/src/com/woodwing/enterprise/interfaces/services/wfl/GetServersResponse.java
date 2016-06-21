/**
 * GetServersResponse.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.wfl;

public class GetServersResponse  implements java.io.Serializable {
    private com.woodwing.enterprise.interfaces.services.wfl.ServerInfo[] servers;

    private java.lang.String companyLanguage;

    public GetServersResponse() {
    }

    public GetServersResponse(
           com.woodwing.enterprise.interfaces.services.wfl.ServerInfo[] servers,
           java.lang.String companyLanguage) {
           this.servers = servers;
           this.companyLanguage = companyLanguage;
    }


    /**
     * Gets the servers value for this GetServersResponse.
     * 
     * @return servers
     */
    public com.woodwing.enterprise.interfaces.services.wfl.ServerInfo[] getServers() {
        return servers;
    }


    /**
     * Sets the servers value for this GetServersResponse.
     * 
     * @param servers
     */
    public void setServers(com.woodwing.enterprise.interfaces.services.wfl.ServerInfo[] servers) {
        this.servers = servers;
    }


    /**
     * Gets the companyLanguage value for this GetServersResponse.
     * 
     * @return companyLanguage
     */
    public java.lang.String getCompanyLanguage() {
        return companyLanguage;
    }


    /**
     * Sets the companyLanguage value for this GetServersResponse.
     * 
     * @param companyLanguage
     */
    public void setCompanyLanguage(java.lang.String companyLanguage) {
        this.companyLanguage = companyLanguage;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof GetServersResponse)) return false;
        GetServersResponse other = (GetServersResponse) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.servers==null && other.getServers()==null) || 
             (this.servers!=null &&
              java.util.Arrays.equals(this.servers, other.getServers()))) &&
            ((this.companyLanguage==null && other.getCompanyLanguage()==null) || 
             (this.companyLanguage!=null &&
              this.companyLanguage.equals(other.getCompanyLanguage())));
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
        if (getServers() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getServers());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getServers(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getCompanyLanguage() != null) {
            _hashCode += getCompanyLanguage().hashCode();
        }
        __hashCodeCalc = false;
        return _hashCode;
    }

    // Type metadata
    private static org.apache.axis.description.TypeDesc typeDesc =
        new org.apache.axis.description.TypeDesc(GetServersResponse.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", ">GetServersResponse"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("servers");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Servers"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "ServerInfo"));
        elemField.setNillable(false);
        elemField.setItemQName(new javax.xml.namespace.QName("", "ServerInfo"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("companyLanguage");
        elemField.setXmlName(new javax.xml.namespace.QName("", "CompanyLanguage"));
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
