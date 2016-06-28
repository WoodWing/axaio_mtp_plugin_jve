/**
 * EditionRenditionsInfo.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.wfl;

public class EditionRenditionsInfo  implements java.io.Serializable {
    private com.woodwing.enterprise.interfaces.services.wfl.Edition edition;

    private com.woodwing.enterprise.interfaces.services.wfl.RenditionTypeInfo[] renditions;

    public EditionRenditionsInfo() {
    }

    public EditionRenditionsInfo(
           com.woodwing.enterprise.interfaces.services.wfl.Edition edition,
           com.woodwing.enterprise.interfaces.services.wfl.RenditionTypeInfo[] renditions) {
           this.edition = edition;
           this.renditions = renditions;
    }


    /**
     * Gets the edition value for this EditionRenditionsInfo.
     * 
     * @return edition
     */
    public com.woodwing.enterprise.interfaces.services.wfl.Edition getEdition() {
        return edition;
    }


    /**
     * Sets the edition value for this EditionRenditionsInfo.
     * 
     * @param edition
     */
    public void setEdition(com.woodwing.enterprise.interfaces.services.wfl.Edition edition) {
        this.edition = edition;
    }


    /**
     * Gets the renditions value for this EditionRenditionsInfo.
     * 
     * @return renditions
     */
    public com.woodwing.enterprise.interfaces.services.wfl.RenditionTypeInfo[] getRenditions() {
        return renditions;
    }


    /**
     * Sets the renditions value for this EditionRenditionsInfo.
     * 
     * @param renditions
     */
    public void setRenditions(com.woodwing.enterprise.interfaces.services.wfl.RenditionTypeInfo[] renditions) {
        this.renditions = renditions;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof EditionRenditionsInfo)) return false;
        EditionRenditionsInfo other = (EditionRenditionsInfo) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.edition==null && other.getEdition()==null) || 
             (this.edition!=null &&
              this.edition.equals(other.getEdition()))) &&
            ((this.renditions==null && other.getRenditions()==null) || 
             (this.renditions!=null &&
              java.util.Arrays.equals(this.renditions, other.getRenditions())));
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
        if (getEdition() != null) {
            _hashCode += getEdition().hashCode();
        }
        if (getRenditions() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getRenditions());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getRenditions(), i);
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
        new org.apache.axis.description.TypeDesc(EditionRenditionsInfo.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "EditionRenditionsInfo"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("edition");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Edition"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "Edition"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("renditions");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Renditions"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "RenditionTypeInfo"));
        elemField.setNillable(false);
        elemField.setItemQName(new javax.xml.namespace.QName("", "RenditionTypeInfo"));
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
