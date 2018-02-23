/**
 * GetRelatedPagesResponse.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.wfl;

public class GetRelatedPagesResponse  implements java.io.Serializable {
    private com.woodwing.enterprise.interfaces.services.wfl.ObjectPageInfo[] objectPageInfos;

    public GetRelatedPagesResponse() {
    }

    public GetRelatedPagesResponse(
           com.woodwing.enterprise.interfaces.services.wfl.ObjectPageInfo[] objectPageInfos) {
           this.objectPageInfos = objectPageInfos;
    }


    /**
     * Gets the objectPageInfos value for this GetRelatedPagesResponse.
     * 
     * @return objectPageInfos
     */
    public com.woodwing.enterprise.interfaces.services.wfl.ObjectPageInfo[] getObjectPageInfos() {
        return objectPageInfos;
    }


    /**
     * Sets the objectPageInfos value for this GetRelatedPagesResponse.
     * 
     * @param objectPageInfos
     */
    public void setObjectPageInfos(com.woodwing.enterprise.interfaces.services.wfl.ObjectPageInfo[] objectPageInfos) {
        this.objectPageInfos = objectPageInfos;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof GetRelatedPagesResponse)) return false;
        GetRelatedPagesResponse other = (GetRelatedPagesResponse) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.objectPageInfos==null && other.getObjectPageInfos()==null) || 
             (this.objectPageInfos!=null &&
              java.util.Arrays.equals(this.objectPageInfos, other.getObjectPageInfos())));
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
        if (getObjectPageInfos() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getObjectPageInfos());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getObjectPageInfos(), i);
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
        new org.apache.axis.description.TypeDesc(GetRelatedPagesResponse.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", ">GetRelatedPagesResponse"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("objectPageInfos");
        elemField.setXmlName(new javax.xml.namespace.QName("", "ObjectPageInfos"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "ObjectPageInfo"));
        elemField.setNillable(false);
        elemField.setItemQName(new javax.xml.namespace.QName("", "ObjectPageInfo"));
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
