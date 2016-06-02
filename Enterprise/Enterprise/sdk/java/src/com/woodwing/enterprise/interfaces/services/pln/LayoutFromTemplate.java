/**
 * LayoutFromTemplate.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.pln;

public class LayoutFromTemplate  implements java.io.Serializable {
    private com.woodwing.enterprise.interfaces.services.pln.Layout newLayout;

    private java.lang.String template;

    public LayoutFromTemplate() {
    }

    public LayoutFromTemplate(
           com.woodwing.enterprise.interfaces.services.pln.Layout newLayout,
           java.lang.String template) {
           this.newLayout = newLayout;
           this.template = template;
    }


    /**
     * Gets the newLayout value for this LayoutFromTemplate.
     * 
     * @return newLayout
     */
    public com.woodwing.enterprise.interfaces.services.pln.Layout getNewLayout() {
        return newLayout;
    }


    /**
     * Sets the newLayout value for this LayoutFromTemplate.
     * 
     * @param newLayout
     */
    public void setNewLayout(com.woodwing.enterprise.interfaces.services.pln.Layout newLayout) {
        this.newLayout = newLayout;
    }


    /**
     * Gets the template value for this LayoutFromTemplate.
     * 
     * @return template
     */
    public java.lang.String getTemplate() {
        return template;
    }


    /**
     * Sets the template value for this LayoutFromTemplate.
     * 
     * @param template
     */
    public void setTemplate(java.lang.String template) {
        this.template = template;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof LayoutFromTemplate)) return false;
        LayoutFromTemplate other = (LayoutFromTemplate) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.newLayout==null && other.getNewLayout()==null) || 
             (this.newLayout!=null &&
              this.newLayout.equals(other.getNewLayout()))) &&
            ((this.template==null && other.getTemplate()==null) || 
             (this.template!=null &&
              this.template.equals(other.getTemplate())));
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
        if (getNewLayout() != null) {
            _hashCode += getNewLayout().hashCode();
        }
        if (getTemplate() != null) {
            _hashCode += getTemplate().hashCode();
        }
        __hashCodeCalc = false;
        return _hashCode;
    }

    // Type metadata
    private static org.apache.axis.description.TypeDesc typeDesc =
        new org.apache.axis.description.TypeDesc(LayoutFromTemplate.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartEditorialPlan", "LayoutFromTemplate"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("newLayout");
        elemField.setXmlName(new javax.xml.namespace.QName("", "NewLayout"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartEditorialPlan", "Layout"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("template");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Template"));
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
