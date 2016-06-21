/**
 * CreateLayoutsResponse.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.pln;

public class CreateLayoutsResponse  implements java.io.Serializable {
    private com.woodwing.enterprise.interfaces.services.pln.Layout[] layouts;

    public CreateLayoutsResponse() {
    }

    public CreateLayoutsResponse(
           com.woodwing.enterprise.interfaces.services.pln.Layout[] layouts) {
           this.layouts = layouts;
    }


    /**
     * Gets the layouts value for this CreateLayoutsResponse.
     * 
     * @return layouts
     */
    public com.woodwing.enterprise.interfaces.services.pln.Layout[] getLayouts() {
        return layouts;
    }


    /**
     * Sets the layouts value for this CreateLayoutsResponse.
     * 
     * @param layouts
     */
    public void setLayouts(com.woodwing.enterprise.interfaces.services.pln.Layout[] layouts) {
        this.layouts = layouts;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof CreateLayoutsResponse)) return false;
        CreateLayoutsResponse other = (CreateLayoutsResponse) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.layouts==null && other.getLayouts()==null) || 
             (this.layouts!=null &&
              java.util.Arrays.equals(this.layouts, other.getLayouts())));
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
        if (getLayouts() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getLayouts());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getLayouts(), i);
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
        new org.apache.axis.description.TypeDesc(CreateLayoutsResponse.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartEditorialPlan", ">CreateLayoutsResponse"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("layouts");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Layouts"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartEditorialPlan", "Layout"));
        elemField.setNillable(false);
        elemField.setItemQName(new javax.xml.namespace.QName("", "Layout"));
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
