/**
 * ModifyAdvertsResponse.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.pln;

public class ModifyAdvertsResponse  implements java.io.Serializable {
    private com.woodwing.enterprise.interfaces.services.pln.Advert[] adverts;

    public ModifyAdvertsResponse() {
    }

    public ModifyAdvertsResponse(
           com.woodwing.enterprise.interfaces.services.pln.Advert[] adverts) {
           this.adverts = adverts;
    }


    /**
     * Gets the adverts value for this ModifyAdvertsResponse.
     * 
     * @return adverts
     */
    public com.woodwing.enterprise.interfaces.services.pln.Advert[] getAdverts() {
        return adverts;
    }


    /**
     * Sets the adverts value for this ModifyAdvertsResponse.
     * 
     * @param adverts
     */
    public void setAdverts(com.woodwing.enterprise.interfaces.services.pln.Advert[] adverts) {
        this.adverts = adverts;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof ModifyAdvertsResponse)) return false;
        ModifyAdvertsResponse other = (ModifyAdvertsResponse) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.adverts==null && other.getAdverts()==null) || 
             (this.adverts!=null &&
              java.util.Arrays.equals(this.adverts, other.getAdverts())));
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
        if (getAdverts() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getAdverts());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getAdverts(), i);
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
        new org.apache.axis.description.TypeDesc(ModifyAdvertsResponse.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartEditorialPlan", ">ModifyAdvertsResponse"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("adverts");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Adverts"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartEditorialPlan", "Advert"));
        elemField.setNillable(false);
        elemField.setItemQName(new javax.xml.namespace.QName("", "Advert"));
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
