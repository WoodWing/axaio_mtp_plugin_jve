/**
 * RenditionTypeInfo.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.wfl;

public class RenditionTypeInfo  implements java.io.Serializable {
    private com.woodwing.enterprise.interfaces.services.wfl.RenditionType rendition;

    private java.lang.String type;

    public RenditionTypeInfo() {
    }

    public RenditionTypeInfo(
           com.woodwing.enterprise.interfaces.services.wfl.RenditionType rendition,
           java.lang.String type) {
           this.rendition = rendition;
           this.type = type;
    }


    /**
     * Gets the rendition value for this RenditionTypeInfo.
     * 
     * @return rendition
     */
    public com.woodwing.enterprise.interfaces.services.wfl.RenditionType getRendition() {
        return rendition;
    }


    /**
     * Sets the rendition value for this RenditionTypeInfo.
     * 
     * @param rendition
     */
    public void setRendition(com.woodwing.enterprise.interfaces.services.wfl.RenditionType rendition) {
        this.rendition = rendition;
    }


    /**
     * Gets the type value for this RenditionTypeInfo.
     * 
     * @return type
     */
    public java.lang.String getType() {
        return type;
    }


    /**
     * Sets the type value for this RenditionTypeInfo.
     * 
     * @param type
     */
    public void setType(java.lang.String type) {
        this.type = type;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof RenditionTypeInfo)) return false;
        RenditionTypeInfo other = (RenditionTypeInfo) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.rendition==null && other.getRendition()==null) || 
             (this.rendition!=null &&
              this.rendition.equals(other.getRendition()))) &&
            ((this.type==null && other.getType()==null) || 
             (this.type!=null &&
              this.type.equals(other.getType())));
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
        if (getRendition() != null) {
            _hashCode += getRendition().hashCode();
        }
        if (getType() != null) {
            _hashCode += getType().hashCode();
        }
        __hashCodeCalc = false;
        return _hashCode;
    }

    // Type metadata
    private static org.apache.axis.description.TypeDesc typeDesc =
        new org.apache.axis.description.TypeDesc(RenditionTypeInfo.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "RenditionTypeInfo"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("rendition");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Rendition"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "RenditionType"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("type");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Type"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
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
