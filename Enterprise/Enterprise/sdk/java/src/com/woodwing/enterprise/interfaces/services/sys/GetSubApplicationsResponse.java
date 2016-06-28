/**
 * GetSubApplicationsResponse.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.sys;

public class GetSubApplicationsResponse  implements java.io.Serializable {
    private com.woodwing.enterprise.interfaces.services.sys.SubApplication[] subApplications;

    public GetSubApplicationsResponse() {
    }

    public GetSubApplicationsResponse(
           com.woodwing.enterprise.interfaces.services.sys.SubApplication[] subApplications) {
           this.subApplications = subApplications;
    }


    /**
     * Gets the subApplications value for this GetSubApplicationsResponse.
     * 
     * @return subApplications
     */
    public com.woodwing.enterprise.interfaces.services.sys.SubApplication[] getSubApplications() {
        return subApplications;
    }


    /**
     * Sets the subApplications value for this GetSubApplicationsResponse.
     * 
     * @param subApplications
     */
    public void setSubApplications(com.woodwing.enterprise.interfaces.services.sys.SubApplication[] subApplications) {
        this.subApplications = subApplications;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof GetSubApplicationsResponse)) return false;
        GetSubApplicationsResponse other = (GetSubApplicationsResponse) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.subApplications==null && other.getSubApplications()==null) || 
             (this.subApplications!=null &&
              java.util.Arrays.equals(this.subApplications, other.getSubApplications())));
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
        if (getSubApplications() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getSubApplications());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getSubApplications(), i);
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
        new org.apache.axis.description.TypeDesc(GetSubApplicationsResponse.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnectionSysAdmin", ">GetSubApplicationsResponse"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("subApplications");
        elemField.setXmlName(new javax.xml.namespace.QName("", "SubApplications"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnectionSysAdmin", "SubApplication"));
        elemField.setNillable(false);
        elemField.setItemQName(new javax.xml.namespace.QName("", "SubApplication"));
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
