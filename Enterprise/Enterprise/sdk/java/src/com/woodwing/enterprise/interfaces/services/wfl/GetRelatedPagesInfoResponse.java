/**
 * GetRelatedPagesInfoResponse.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.wfl;

public class GetRelatedPagesInfoResponse  implements java.io.Serializable {
    private com.woodwing.enterprise.interfaces.services.wfl.EditionPages[] editionsPages;

    private com.woodwing.enterprise.interfaces.services.wfl.LayoutObject[] layoutObjects;

    public GetRelatedPagesInfoResponse() {
    }

    public GetRelatedPagesInfoResponse(
           com.woodwing.enterprise.interfaces.services.wfl.EditionPages[] editionsPages,
           com.woodwing.enterprise.interfaces.services.wfl.LayoutObject[] layoutObjects) {
           this.editionsPages = editionsPages;
           this.layoutObjects = layoutObjects;
    }


    /**
     * Gets the editionsPages value for this GetRelatedPagesInfoResponse.
     * 
     * @return editionsPages
     */
    public com.woodwing.enterprise.interfaces.services.wfl.EditionPages[] getEditionsPages() {
        return editionsPages;
    }


    /**
     * Sets the editionsPages value for this GetRelatedPagesInfoResponse.
     * 
     * @param editionsPages
     */
    public void setEditionsPages(com.woodwing.enterprise.interfaces.services.wfl.EditionPages[] editionsPages) {
        this.editionsPages = editionsPages;
    }


    /**
     * Gets the layoutObjects value for this GetRelatedPagesInfoResponse.
     * 
     * @return layoutObjects
     */
    public com.woodwing.enterprise.interfaces.services.wfl.LayoutObject[] getLayoutObjects() {
        return layoutObjects;
    }


    /**
     * Sets the layoutObjects value for this GetRelatedPagesInfoResponse.
     * 
     * @param layoutObjects
     */
    public void setLayoutObjects(com.woodwing.enterprise.interfaces.services.wfl.LayoutObject[] layoutObjects) {
        this.layoutObjects = layoutObjects;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof GetRelatedPagesInfoResponse)) return false;
        GetRelatedPagesInfoResponse other = (GetRelatedPagesInfoResponse) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.editionsPages==null && other.getEditionsPages()==null) || 
             (this.editionsPages!=null &&
              java.util.Arrays.equals(this.editionsPages, other.getEditionsPages()))) &&
            ((this.layoutObjects==null && other.getLayoutObjects()==null) || 
             (this.layoutObjects!=null &&
              java.util.Arrays.equals(this.layoutObjects, other.getLayoutObjects())));
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
        if (getEditionsPages() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getEditionsPages());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getEditionsPages(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getLayoutObjects() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getLayoutObjects());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getLayoutObjects(), i);
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
        new org.apache.axis.description.TypeDesc(GetRelatedPagesInfoResponse.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", ">GetRelatedPagesInfoResponse"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("editionsPages");
        elemField.setXmlName(new javax.xml.namespace.QName("", "EditionsPages"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "EditionPages"));
        elemField.setNillable(false);
        elemField.setItemQName(new javax.xml.namespace.QName("", "EditionPages"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("layoutObjects");
        elemField.setXmlName(new javax.xml.namespace.QName("", "LayoutObjects"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "LayoutObject"));
        elemField.setNillable(false);
        elemField.setItemQName(new javax.xml.namespace.QName("", "LayoutObject"));
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
